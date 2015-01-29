<?PHP

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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage registration
 * @copyright  2012 Marc-Robin Wendt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);           // Course Module ID
//    require_variable($id);   // course

if (! $course = $DB->get_record("course", array("id"=>$id))) {
  print_error("courseidincorrect","registration");
}

require_course_login($course);
$context = context_course::instance($course->id);
require_capability('mod/registration:view', $context);
$ismyteacher = has_capability('mod/registration:grade', $context);
$ismystudent = has_capability('mod/registration:view', $context);

//add_to_log($course->id, "registration", "view all", "index.php?id=$course->id", "");
\mod_registration\event\viewed_all::create(array("context" => $context))->trigger();

$strregistrations = get_string("modulenameplural", "registration");
$strregistration = get_string("modulename", "registration");
$strweek = get_string("week");
$strtopic = get_string("topic");
$strname = get_string("name");
$strduedate = get_string("duedate", "registration");
$stravailabledate = get_string("availabledate", "registration");
$strsubmitted = get_string("submitted", "registration");

$url = new moodle_url('/mod/registration/index.php', array('id'=>$course->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($strregistrations);
$PAGE->navbar->add($strregistrations, new moodle_url("/mod/registration/index.php?id=".$course->id));
echo $OUTPUT->header();

if (! $registrations = get_all_instances_in_course("registration", $course)) {
  notice(get_string('noregistrations', 'registration'), "../../course/view.php?id=$course->id");
  die;
}

$timenow = time();
$table = new html_table();
$table->attributes['style']="margin-left:auto; margin-right:auto;";

if ($course->format == "weeks" && $ismyteacher)
  {
    $table->head  = array ($strweek, $strduedate, $stravailabledate, get_string("registrations", "registration"));
    $table->align = array ("center", "left", "left", "center");
  }
elseif ($course->format == "weeks")
  {
    $table->head  = array ($strweek, $strduedate, $stravailabledate, get_string("registrations", "registration"),get_string("booked", "registration"));
    $table->align = array ("center", "left", "left", "center", "center");
  }
elseif ($course->format == "topics" && $ismyteacher)
  {
    $table->head  = array ($strtopic, $strduedate, $stravailabledate, get_string("registrations", "registration"));
    $table->align = array ("center", "left", "left", "center");
  }
elseif ($course->format == "topics")
  {
    $table->head  = array ($strtopic, $strduedate, $stravailabledate, get_string("registrations", "registration"),get_string("booked", "registration"));
    $table->align = array ("center", "left", "left", "center", "center");
  }
else
  {
    $table->head  = array ($strduedate);
    $table->align = array ("left", "left");
  }

$currentsection = "";

foreach ($registrations as $registration) {
  $submitted = get_string("no");
  if ($ismyteacher) {
    $count = registration_count_submissions($registration);
    $submitted = "<a href=\"submissions.php?id=$registration->id\">" .
      get_string("viewsubmissions", "registration", $count) . "</a>";
  } else {
    $count = registration_count_submissions($registration);
    if (isset($USER->id)) {
      if ($submission = registration_get_submission($registration, $USER)) {
	if ($submission->timemodified <= $registration->timedue) {
	  $submitted = userdate($submission->timemodified);
	} else {
	  $submitted = "<font color=red>".userdate($submission->timemodified)."</font>";
	}
      }
    }
  }
  $room = get_string("place", "registration");
  $due = $registration->name.", ".userdate($registration->timedue)." (".$room.": ".$registration->room.")";
  if (!$registration->visible) {
    //Show dimmed if the mod is hidden
    $link = "<a class=\"dimmed\" href=\"view.php?id=$registration->coursemodule\">$due</a>";
  } else {
    //Show normal if the mod is visible
    $link = "<a href=\"view.php?id=$registration->coursemodule\">$due</a>";
  }

  if ($registration->timeavailable < time())
    {
      $timeavailable="<span class=\"dimmed_text\">".userdate($registration->timeavailable)."</span>";
    } else {
    $timeavailable=userdate($registration->timeavailable);
  }

  $printsection = "";
  if ($registration->section !== $currentsection) {
    if ($registration->section) {
      $printsection = $registration->section;
    }
    if ($currentsection !== "") {
      $table->data[] = 'hr';
    }
    $currentsection = $registration->section;
  }

  $position = registration_get_position_in_list($registration->id,$USER->id);
  if ($position == 0) {
    $booked = "";
  } elseif ($position > $registration->number) {
    $booked = get_string("in_queue","registration");
  } else {
    $booked = get_string("yes", "moodle");
  }

  if ($course->format == "weeks" or $course->format == "topics")
    {
      if($ismyteacher)
	$table->data[] = array ($printsection, $link, $timeavailable, $count."/".$registration->number);
      else
	$table->data[] = array ($printsection, $link, $timeavailable, $count."/".$registration->number,$booked);
    }
  else
    {
      $table->data[] = array ($link, $submitted);
    }
}

echo "<br />";

echo html_writer::table($table);

echo "<br />";
$legend = new html_table();
$legend->attributes['style']="margin-left:auto; margin-right:auto;";
$legend->head = array( '<div style="color: red; font-weight: bold;">'.get_string("legend","registration").'</div>');

echo html_writer::table($legend);
echo $OUTPUT->footer();
?>
