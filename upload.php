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
 * Bulk upload of user files
 *
 * Based on .../admin/uploaduser.php and .../lib/gdlib.php
 *
 * @package    profilefield_file
 * @copyright  (C) 2007 Inaki Arenaza
 * @copyright  2024 Daniel Neis Araujo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

//admin_externalpage_setup('profielfieldfileupload');

require_capability('moodle/user:update', context_system::instance());

$userfield = optional_param('userfield', 0, PARAM_INT);
$filefield = optional_param('filefield', 0, PARAM_INT);
$overwritefile = optional_param('overwritefile', 0, PARAM_BOOL);

$userfields = [0 => 'username', 1 => 'idnumber', 2 => 'id'];

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/user/profile/field/file/upload.php'));

echo $OUTPUT->header();
$mform = new \profilefield_file\output\upload_form(null, $userfields);
if ($formdata = $mform->get_data()) {
    if (!array_key_exists($userfield, $userfields)) {
        echo $OUTPUT->notification(get_string('uploadfile_baduserfield', 'profilefield_file'));
    } else {
        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        // Create a unique temporary directory, to process the zip file
        // contents.
        $zipdir = \profilefield_file\upload::mktempdir($CFG->tempdir.'/', 'usrprofilefieldfile');
        $dstfile = $zipdir.'/images.zip';

        if (!$mform->save_file('userfilesfile', $dstfile, true)) {
            echo $OUTPUT->notification(get_string('uploadfile_cannotmovezip', 'profilefield_file'));
            @remove_dir($zipdir);
        } else {
            $fp = get_file_packer('application/zip');
            $unzipresult = $fp->extract_to_pathname($dstfile, $zipdir);
            if (!$unzipresult) {
                echo $OUTPUT->notification(get_string('uploadfile_cannotunzip', 'profilefield_file'));
                @remove_dir($zipdir);
            } else {
                // We don't need the zip file any longer, so delete it to make
                // it easier to process the rest of the files inside the directory.
                @unlink($dstfile);

                $results = array ('errors' => 0,'updated' => 0);

                \profilefield_file\upload::process_directory($zipdir, $userfields[$userfield], $filefield, $overwritefile, $results);

                // Finally remove the temporary directory with all the user images and print some stats.
                remove_dir($zipdir);
                echo $OUTPUT->notification(get_string('usersupdated', 'profilefield_file') . ": " . $results['updated'], 'notifysuccess');
                echo $OUTPUT->notification(get_string('errors', 'profilefield_file') . ": " . $results['errors'], ($results['errors'] ? 'notifyproblem' : 'notifysuccess'));
                echo '<hr />';
            }
        }
    }
}
$mform->display();
echo $OUTPUT->footer();
