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

namespace profilefield_file\output;

require_once $CFG->libdir.'/formslib.php';

/**
 * Bulk user file upload form
 *
 * @package    profilefield_file
 * @copyright  (C) 2007 Inaki Arenaza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_form extends \moodleform {
    function definition (){
        global $CFG, $USER, $DB;

        $mform =& $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $options = array();
        $options['accepted_types'] = array('.zip');
        $mform->addElement('filepicker', 'userfilesfile', get_string('file'), 'size="40"', $options);
        $mform->addRule('userfilesfile', null, 'required');

        $choices =& $this->_customdata;
        $mform->addElement('select', 'userfield', get_string('uploadfile_userfield', 'profilefield_file'), $choices);
        $mform->setType('userfield', PARAM_INT);

        $choices = array( 0 => get_string('no'), 1 => get_string('yes') );
        $mform->addElement('select', 'overwritefile', get_string('uploadfile_overwrite', 'profilefield_file'), $choices);
        $mform->setType('overwritefile', PARAM_INT);

        $choices = $DB->get_records_menu('user_info_field', ['datatype' => 'file'], 'name', 'id,name');
        $mform->addElement('select', 'filefield', get_string('filefield', 'profilefield_file'), $choices);
        $mform->setType('filefield', PARAM_INT);

        $this->add_action_buttons(false, get_string('uploadfiles', 'profilefield_file'));
    }
}
