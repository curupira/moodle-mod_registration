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
 * @package    mod
 * @subpackage registration
 * @copyright  2012 Marc-Robin Wendt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//It must be included from a Moodle page
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_registration_mod_form extends moodleform_mod {

  /**
   * Defines forms elements
   */
  public function definition() {
    global $CFG, $DB;

    $mform = $this->_form;

    //-------------------------------------------------------------------------------
    // Adding the "general" fieldset, where all the common settings are showed
    $mform->addElement('header', 'general', get_string('general', 'form'));

    // Adding the standard "name" field
    $mform->addElement('text', 'name', get_string('registrationname', 'registration'), array('size'=>'64'));
    if (!empty($CFG->formatstringstriptags)) {
      $mform->setType('name', PARAM_TEXT);
    } else {
      $mform->setType('name', PARAM_CLEAN);
    }
    $mform->addRule('name', null, 'required', null, 'client');
    $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
    //    $mform->addHelpButton('name', 'newmodulename', 'newmodule');

    // Adding the standard "intro" and "introformat" fields
    $this->add_intro_editor();

    for($i=$CFG->registration_maxstudents; $i>=1; $i--) $choices[$i] = $i;
    $mform->addElement('select', 'number', get_string('maximumsize', 'registration'), $choices);
    $mform->addElement('modgrade', 'grade', get_string('maximumpoints', 'registration'), false);
    $mform->addElement('text', 'room', get_string('place', 'registration'), 'size="10" maxlength="30"');
    $mform->addElement('checkbox', 'allowqueue', get_string('allowqueue', 'registration'));
    $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'registration'));
    $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'registration'));


    //-------------------------------------------------------------------------------
    // add standard elements, common to all modules
    $this->standard_coursemodule_elements();
    //-------------------------------------------------------------------------------
    // add standard buttons, common to all modules
    $this->add_action_buttons();
  }
}