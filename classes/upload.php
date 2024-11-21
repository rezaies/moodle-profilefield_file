<?php

namespace profilefield_file;

use context_user;
use stdClass;

class upload {

    const FILE_UPDATED = 0;
    const FILE_ERROR   = 1;
    const FILE_SKIPPED = 2;

    /**
     * Create a unique temporary directory with a given prefix name,
     * inside a given directory, with given permissions. Return the
     * full path to the newly created temp directory.
     *
     * @param string $dir where to create the temp directory.
     * @param string $prefix prefix for the temp directory name (default '')
     *
     * @return string The full path to the temp directory.
     */
    public static function mktempdir($dir, $prefix='') {
        global $CFG;

        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }

        do {
            $path = $dir.$prefix.mt_rand(0, 9999999);
        } while (file_exists($path));

        check_dir_exists($path);

        return $path;
    }

    public static function process_directory($dir, $userfield, $filefield, $overwrite, &$results) {
        global $OUTPUT;
        if(!($handle = opendir($dir))) {
            echo $OUTPUT->notification(get_string('uploadfile_cannotprocessdir', 'profilefield_file'));
            return;
        }

        while (false !== ($item = readdir($handle))) {
            if ($item != '.' && $item != '..') {
                if (is_dir($dir.'/'.$item)) {
                    self::process_directory($dir.'/'.$item, $userfield, $filefield, $overwrite, $results);
                } else if (is_file($dir.'/'.$item))  {
                    $result = self::process_file($dir.'/'.$item, $userfield, $filefield, $overwrite);
                    switch ($result) {
                        case self::FILE_ERROR:
                            $results['errors']++;
                            break;
                        case self::FILE_UPDATED:
                            $results['updated']++;
                            break;
                    }
                }
                // Ignore anything else that is not a directory or a file (e.g.,
                // symbolic links, sockets, pipes, etc.)
            }
        }
        closedir($handle);
    }

    /**
     * Given the full path of a file, try to find the user the file
     * corresponds to and assign him/her this file as his/her file.
     * Make extensive checks to make sure we don't open any security holes
     * and report back any success/error.
     *
     * @param string $file the full path of the file to process
     * @param string $userfield the prefix_user table field to use to
     *               match files names to users.
     * @param string $filefield the custom profile field id
     * @param bool $overwrite overwrite existing file or not.
     *
     * @return integer either self::FILE_UPDATED, self::FILE_ERROR or
     *                  self::FILE_SKIPPED
     */
    private static function process_file($file, $userfield, $filefield, $overwrite) {
        global $DB, $OUTPUT;

        // Add additional checks on the filenames, as they are user
        // controlled and we don't want to open any security holes.
        $path_parts = pathinfo(cleardoubleslashes($file));
        $basename  = $path_parts['basename'];
        $extension = $path_parts['extension'];

        // The file name (without extension) must match the
        // userfield attribute.
        $uservalue = substr($basename, 0,
                            strlen($basename) -
                            strlen($extension) - 1);

        // userfield names are safe, so don't quote them.
        if (!($user = $DB->get_record('user', array ($userfield => $uservalue, 'deleted' => 0)))) {
            $a = new stdClass();
            $a->userfield = clean_param($userfield, PARAM_CLEANHTML);
            $a->uservalue = clean_param($uservalue, PARAM_CLEANHTML);
            echo $OUTPUT->notification(get_string('uploadfile_usernotfound', 'profilefield_file', $a));
            return self::FILE_ERROR;
        }

        $fs = get_file_storage();
        $usercontext = context_user::instance($user->id);

        $fileinfo = [
            'contextid' => $usercontext->id,     // ID of the context.
            'component' => 'profilefield_file',  // Your component name.
            'filearea'  => "files_{$filefield}", // Id of the user_info_field.
            'itemid'    => 0,                    // Usually = ID of row in table.
            'filepath'  => '/',                  // Any path beginning and ending in /.
            'filename'  => $basename,            // Any filename.
        ];

        $areafiles = $fs->get_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea']);

        if (!empty($areafiles) && !$overwrite) {
            echo $OUTPUT->notification(get_string('uploadfile_userskipped', 'profilefield_file', $user->username));
            return self::FILE_SKIPPED;
        }

        if (!empty($areafiles)) {
            foreach ($areafiles as $af) {
                $af->delete();
            }
        }

        $infodata = ['fieldid' => $filefield, 'userid' => $user->id];
        if ($inforecord = $DB->get_record('user_info_data', $infodata)) {
            $inforecord->data = 1;
            $DB->update_record('user_info_data', $inforecord);
        } else {
            $infodata['data'] = 1;
            $DB->insert_record('user_info_data', $infodata);
        }
        // Create a new file containing the text 'hello world'.
        if ($fs->create_file_from_pathname($fileinfo, $file)) {
            echo $OUTPUT->notification(get_string('uploadfile_userupdated', 'profilefield_file', $user->username), 'notifysuccess');
            return self::FILE_UPDATED;
        } else {
            echo $OUTPUT->notification(get_string('uploadfile_cannotsave', 'profilefield_file', $user->username));
            return self::FILE_ERROR;
        }
    }
}
