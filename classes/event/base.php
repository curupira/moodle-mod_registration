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

namespace mod_registration\event;

defined("MOODLE_INTERNAL") || die();

/**
 * Registration base event for module context
 *
 * @package mod_registration
 * @copyright 2015 Jan Eberhardt <eberhardt@math.tu-berlin.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base extends \core\event\base {

	/**
	 * (non-PHPdoc)
	 * @see \core\event\base::get_url()
	 */
	public function get_url() {
		return new \moodle_url("/mod/registration/view.php",  array("id" => $this->contextinstanceid));
	}

	/**
	 * @param string $crud
	 */
	protected function base_init($crud) {
		$this->data["crud"] = $crud;
		$this->data["edulevel"] = self::LEVEL_PARTICIPATING;
		$this->data["objecttable"] = "registration";
	}

	/**
	 * Custom validation.
	 *
	 * @throws \coding_exception
	 * @return void
	 */
	protected function validate_data() {
		parent::validate_data();

		if ($this->contextlevel != CONTEXT_MODULE) {
			throw new \coding_exception('Context level must be CONTEXT_MODULE.');
		}
	}
}
