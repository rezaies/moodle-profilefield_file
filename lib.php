<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * File profile field lib.
 *
 * @package    profilefield_file
 * @copyright  2014 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serve the files from the profilefield_file file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param context $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @return false|void
 */
function profilefield_file_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_USER) {
        return false;
    }
    if (strpos($filearea, 'files_') !== 0) {
        return false;
    }

    require_login($course, false, $cm);

    $fieldid = substr($filearea, strlen('files_'));
    $field = $DB->get_record('user_info_field', ['id' => $fieldid]);

        // Allow the file owner (user) to access their own file without requiring viewalldetails capability.
        if ($context->instanceid == $USER->id) {
            // The user is the file owner, allow access.
        } else {
            // Apply regular visibility checks for other users.
            if ($field->visible != PROFILE_VISIBLE_ALL) {
                if ($field->visible == PROFILE_VISIBLE_PRIVATE) {
                    if (!has_capability('moodle/user:viewalldetails', $context)) {
                        return false;
                    }
                } else if (!has_capability('moodle/user:viewalldetails', $context)) {
                    return false;
                }
            }
        }

    array_shift($args); // Ignore revision - designed to prevent caching problems only.

    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/profilefield_file/$filearea/0/$relativepath";
    $fs = get_file_storage();
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload);
}
