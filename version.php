<?PHP // $Id: version.php,v 1.4.2 2012/07/11 20:43:00

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
 * Registration version information
 *
 * @package    mod
 * @subpackage registration
 * @author     Miroslav Fikar, Marc-Robin Wendt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugin->version  = 2015021201; //was 2013112802
$plugin->release  = 'v2.1.1';    // human-friendly version name
$plugin->requires = 2013051402;  // Requires this Moodle version
$plugin->cron     = 60;
$plugin->component     = 'mod_registration';

?>
