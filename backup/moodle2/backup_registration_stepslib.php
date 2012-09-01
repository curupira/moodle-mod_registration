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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Marc-Robin Wendt {@link YOUR_URL_GOES_HERE}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
class backup_registration_activity_structure_step extends backup_activity_structure_step {
 
  protected function define_structure() {
 
    // To know if we are including userinfo
    $userinfo = $this->get_setting_value('userinfo');
 
    // Define each element separated
    $registration = new backup_nested_element('registration', array('id'), 
					      array(
						    'name', 'intro', 'number', 'room',
						    'timedue', 'timeavailable', 'grade', 'timemodified', 'allowqueue')
					      );
 
    $submissions = new backup_nested_element('submissions');
 
    $submission = new backup_nested_element('submission', array('id'), 
					    array(
						  'userid', 'timecreated', 'timemodified', 'grade',
						  'teacher', 'timemarked', 'mailed', 'comment')
					    ); 

    // Build the tree
    $registration->add_child($submissions);
    $submissions->add_child($submission);
 
    // Define sources
    $registration->set_source_table('registration', array('id' => backup::VAR_ACTIVITYID));
 
    // All the rest of elements only happen if we are including user info
    if ($userinfo) {
      $submission->set_source_table('registration_submissions', array('registration' => backup::VAR_PARENTID));
    }

    // Define id annotations
    $submission->annotate_ids('user', 'userid');

    // Define file annotations
    $registration->annotate_files('mod_registration', 'intro', null); // This file area hasn't itemid

    // Return the root element, wrapped into standard activity structure
    return $this->prepare_activity_structure($registration);

 
  }
}