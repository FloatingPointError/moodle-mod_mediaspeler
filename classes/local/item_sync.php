<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_mediaspeler\local;

/**
 * Brings the items of an activity in line with the files in its Nextcloud folder (UC9).
 *
 * Only references are stored. New files are added at the end of the series, changed files get their
 * new ETag, and items whose file is gone are removed. Titles, descriptions and order set by the
 * teacher are kept.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_sync {

    /** @var string[] File extensions treated as audio when Nextcloud reports no audio content type. */
    const AUDIO_EXTENSIONS = ['mp3', 'wav', 'flac', 'm4a', 'ogg', 'oga', 'opus'];

    /**
     * Synchronises the items of one activity.
     *
     * @param \stdClass $mediaspeler The activity record.
     * @param nextcloud_client|null $client Client to use; defaults to the site settings.
     * @return array Counts with keys added, updated, removed.
     */
    public static function sync(\stdClass $mediaspeler, ?nextcloud_client $client = null): array {
        global $DB;

        $client = $client ?? nextcloud_client::from_config();
        $files = array_values(array_filter($client->list_folder($mediaspeler->ncfolder), [self::class, 'is_audio']));
        usort($files, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        $existing = $DB->get_records('mediaspeler_item', ['mediaspelerid' => $mediaspeler->id], 'sortorder ASC');
        $bypath = [];
        foreach ($existing as $item) {
            $bypath[$item->ncpath] = $item;
        }

        $counts = ['added' => 0, 'updated' => 0, 'removed' => 0];
        $now = time();
        $nextorder = $existing ? max(array_column($existing, 'sortorder')) + 1 : 1;

        $transaction = $DB->start_delegated_transaction();
        foreach ($files as $file) {
            if (isset($bypath[$file['path']])) {
                $item = $bypath[$file['path']];
                unset($bypath[$file['path']]);
                if ($item->etag !== $file['etag']) {
                    $DB->update_record('mediaspeler_item', (object) [
                        'id' => $item->id,
                        'etag' => $file['etag'],
                        'timemodified' => $now,
                    ]);
                    $counts['updated']++;
                }
                continue;
            }
            $DB->insert_record('mediaspeler_item', (object) [
                'mediaspelerid' => $mediaspeler->id,
                'ncpath' => $file['path'],
                'etag' => $file['etag'],
                'title' => self::title_from_name($file['name']),
                'sortorder' => $nextorder++,
                'timemodified' => $now,
            ]);
            $counts['added']++;
        }

        // Whatever is left in $bypath no longer exists in Nextcloud.
        if ($bypath) {
            $ids = array_column($bypath, 'id');
            $DB->delete_records_list('mediaspeler_item_group', 'itemid', $ids);
            $DB->delete_records_list('mediaspeler_listened', 'itemid', $ids);
            $DB->delete_records_list('mediaspeler_item', 'id', $ids);
            $counts['removed'] = count($ids);
        }
        $transaction->allow_commit();

        if ($counts['removed']) {
            self::update_completion($mediaspeler);
        }

        return $counts;
    }

    /**
     * Re-checks completion after items were removed.
     *
     * A student who had heard every item except the one that is now gone, has now heard everything.
     * Removing items can only complete students, never undo a completion, so only students who have
     * listened to something in this activity need checking.
     *
     * Also used after groups change (fase 6): fewer items for a student can complete him as well.
     *
     * @param \stdClass $mediaspeler The activity record.
     * @param int|null $userid Only this user; null for everyone who has listened to something.
     */
    public static function update_completion(\stdClass $mediaspeler, ?int $userid = null): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');

        if (empty($mediaspeler->completionlistenall)) {
            $mediaspeler = $DB->get_record('mediaspeler', ['id' => $mediaspeler->id]);
            if (!$mediaspeler || empty($mediaspeler->completionlistenall)) {
                return;
            }
        }
        $cm = get_coursemodule_from_instance('mediaspeler', $mediaspeler->id, $mediaspeler->course);
        if (!$cm || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
            return;
        }
        $course = get_course($cm->course);
        $completion = new \completion_info($course);
        if (!$completion->is_enabled($cm)) {
            return;
        }
        $userids = $userid !== null ? [$userid] : $DB->get_fieldset_select('mediaspeler_listened', 'DISTINCT userid',
            'mediaspelerid = ?', [$mediaspeler->id]);
        foreach ($userids as $userid) {
            $completion->update_state($cm, COMPLETION_COMPLETE, (int) $userid);
        }
    }

    /**
     * Whether a listed file is audio.
     *
     * @param array $file A file as returned by nextcloud_client::list_folder().
     * @return bool
     */
    public static function is_audio(array $file): bool {
        if (str_starts_with($file['contenttype'], 'audio/')) {
            return true;
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        return in_array($extension, self::AUDIO_EXTENSIONS, true);
    }

    /**
     * MIME type to send to the browser, based on the file extension.
     *
     * Nextcloud sometimes reports flac or opus as application/octet-stream, which browsers will not play.
     *
     * @param string $path File path or name.
     * @return string
     */
    public static function audio_mimetype(string $path): string {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'm4a' => 'audio/mp4',
            'ogg', 'oga', 'opus' => 'audio/ogg',
            default => 'application/octet-stream',
        };
    }

    /**
     * Default title for a new item: the file name without extension, underscores as spaces.
     *
     * @param string $name File name.
     * @return string
     */
    public static function title_from_name(string $name): string {
        $title = trim(str_replace('_', ' ', pathinfo($name, PATHINFO_FILENAME)));
        return \core_text::substr($title === '' ? $name : $title, 0, 255);
    }
}
