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

/**
 * English strings for mod_mediaspeler.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['autherror'] = 'Nextcloud refused the login. Check the username and app password of the service account.';
$string['completiondetail:listenall'] = 'Listen to every item (at least 75% of each)';
$string['completionlistenall'] = 'Students must listen to every item (at least 75% of each)';
$string['completionlistenall_help'] = 'An item counts as listened to once the student has heard at least 75% of it. Only stretches that really played count: skipping ahead does not. The order is free.';
$string['connectionerror'] = 'Could not reach Nextcloud: {$a}';
$string['foldernotfound'] = 'The folder was not found in Nextcloud. Check that it is shared with the service account.';
$string['freeorder'] = 'Choose for yourself which item you play. The order of the list is a suggestion.';
$string['httperror'] = 'Nextcloud answered with HTTP status {$a}.';
$string['invalidncurl'] = 'The Nextcloud address "{$a}" is not a valid URL.';
$string['invalidresponse'] = 'Nextcloud sent an answer that could not be read.';
$string['itemdescription'] = 'Explanation';
$string['itemposition'] = 'Position';
$string['itemssaved'] = 'The items have been saved.';
$string['itemtitle'] = 'Title';
$string['listened'] = 'Listened';
$string['listenedshare'] = 'Share of this item you have heard';
$string['manageitems'] = 'Manage items';
$string['manageitems_intro'] = 'Set the title, explanation and position of every audio file. New files in the Nextcloud folder are added at the end automatically.';
$string['mediaspeler:addinstance'] = 'Add a new media player';
$string['mediaspeler:manageitems'] = 'Manage the items of a media player';
$string['mediaspeler:view'] = 'View and play a media player';
$string['modulename'] = 'Media player';
$string['modulename_help'] = 'The media player plays audio from a Nextcloud folder as a practice series, without copying the files into Moodle.';
$string['modulenameplural'] = 'Media players';
$string['ncapppassword'] = 'App password';
$string['ncapppassword_desc'] = 'App password of the service account, created in Nextcloud under Personal settings → Security.';
$string['ncfolder'] = 'Nextcloud folder';
$string['ncfolder_help'] = 'The folder in Nextcloud, as the service account sees it. For example: Muzieklab-test';
$string['ncurl'] = 'Nextcloud address';
$string['ncurl_desc'] = 'Address Moodle uses to reach Nextcloud. When both run on the same server: http://localhost:8080';
$string['ncusername'] = 'Service account';
$string['ncusername_desc'] = 'Nextcloud user that reads the shared folders on behalf of Moodle.';
$string['nextcloudsettings'] = 'Nextcloud';
$string['nextcloudsettings_desc'] = 'All activities read from Nextcloud through this one service account. Share the folders with this account, read-only.';
$string['noitems'] = 'There are no audio files in this activity yet.';
$string['notconfigured'] = 'The connection with Nextcloud is not configured yet. An administrator can do this under Site administration → Plugins → Activity modules → Media player.';
$string['nowplaying'] = 'Now loaded';
$string['pluginadministration'] = 'Media player administration';
$string['pluginname'] = 'Media player';
$string['privacy:metadata:mediaspeler_listened'] = 'Which items a user has listened to, for activity completion.';
$string['privacy:metadata:mediaspeler_listened:itemid'] = 'The item that was listened to.';
$string['privacy:metadata:mediaspeler_listened:timecreated'] = 'When the user had heard enough of the item.';
$string['privacy:metadata:mediaspeler_listened:userid'] = 'The user who listened.';
$string['privacy:metadata:nextcloud'] = 'The audio is fetched from Nextcloud with a shared service account. No data about the user is sent to Nextcloud.';
$string['sourcefolder'] = 'Nextcloud folder: {$a}';
$string['syncdone'] = 'Updated from Nextcloud: {$a->added} added, {$a->updated} changed, {$a->removed} removed.';
$string['syncfailed'] = 'Could not update from Nextcloud. {$a}';
