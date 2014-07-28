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
 * File profile field definition.
 *
 * @package    profilefield_file
 * @copyright  2014 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('PROFILEFIELD_FILE_MAXFILES', 20);

/**
 * Class profile_define_text
 *
 * @copyright  2014 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_file extends profile_define_base {

    /**
     * Add elements for creating/editing a file profile field.
     * @param moodleform $form
     */
    public function define_form_specific($form) {
        global $CFG;

        // Default data.
        $form->addElement('hidden', 'defaultdata', '');
        $form->setType('defaultdata', PARAM_TEXT);

        // Param 1 for file type is the maxfiles of the field.
        // Let's prepare the maxfiles popup.
        $options = array();
        for ($i = 1; $i <= PROFILEFIELD_FILE_MAXFILES; $i++) {
            $options[$i] = $i;
        }
        $form->addElement('select', 'param1', get_string('maximumupload'), $options);
        $form->setDefault('param1', $CFG->maxbytes);
        $form->setType('param1', PARAM_INT);

        // Param 2 for file type is the maxbytes of the field.
        // Let's prepare the maxbytes popup.
        $choices = get_max_upload_sizes($CFG->maxbytes);
        $form->addElement('select', 'param2', get_string('maximumupload'), $choices);
        $form->setDefault('param2', $CFG->maxbytes);
        $form->setType('param2', PARAM_INT);
    }

    /**
     * Alter form based on submitted or existing data
     * @param moodleform $mform
     */
    public function define_after_data(&$mform) {
        $mform->addHelpButton('forceunique', 'forceunique', 'profilefield_file');
        $mform->getElement('forceunique')->setValue('0');
        $mform->hardFreeze('forceunique');

        $mform->addHelpButton('signup', 'signup', 'profilefield_file');
        $mform->getElement('signup')->setValue('0');
        $mform->hardFreeze('signup');
    }
}