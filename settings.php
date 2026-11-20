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
 * Site settings for mod_mediaspeler: the connection with Nextcloud.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'mod_mediaspeler/ncheading',
        get_string('nextcloudsettings', 'mediaspeler'),
        get_string('nextcloudsettings_desc', 'mediaspeler')
    ));

    $settings->add(new admin_setting_configtext(
        'mod_mediaspeler/ncurl',
        get_string('ncurl', 'mediaspeler'),
        get_string('ncurl_desc', 'mediaspeler'),
        'http://localhost:8080',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'mod_mediaspeler/ncusername',
        get_string('ncusername', 'mediaspeler'),
        get_string('ncusername_desc', 'mediaspeler'),
        'moodle-mediaspeler',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'mod_mediaspeler/ncapppassword',
        get_string('ncapppassword', 'mediaspeler'),
        get_string('ncapppassword_desc', 'mediaspeler'),
        ''
    ));
}
