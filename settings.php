<?php

/**
 * Link to bulk custom field file upload
 *
 * @package    profilefield_file
 * @copyright  2024 Daniel Neis Araujo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
$ADMIN->add('accounts',
    new admin_externalpage(
        'profielfieldfileupload',
        get_string('upload','profilefield_file'),
        new moodle_url('/user/profile/field/file/upload.php'),
        'moodle/user:update'
    )
);
