<<<?php

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
 * @copyright 2012 onwards Marc-Robin Wendt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_registration_activity_task
 */

/**
 * Structure step to restore one registration activity
 */
class restore_registration_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('registration', '/activity/registration');
        $paths[] = new restore_path_element('registration_submission', '/activity/registration/submissions/submission');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_registration($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

	//        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the registration record
        $newitemid = $DB->insert_record('registration', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_registration_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->registration = $this->get_new_parentid('registration');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('registration_submissions', $data);
        $this->set_mapping('registration_submission', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add registration related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_registration', 'intro', null);
    }
}
