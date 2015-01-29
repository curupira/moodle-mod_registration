<?PHP

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);           // Course Module ID
$sort = optional_param('sort', "timemodified", PARAM_ALPHA);
$dir = optional_param('dir', "DESC", PARAM_ALPHA);
$timenow = optional_param('timenow', 0, PARAM_INT);

$timewas = $timenow;
$timenow = time();

if (! $registration = $DB->get_record("registration", array('id'=>$id)))
  print_error("courseincorrect","registration");
if (! $course = $DB->get_record("course", array('id'=>$registration->course)))
  print_error("coursemisconfigured","registration");
if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id))
  print_error("courseidincorrect","registration");

require_login($course, false, $cm);
$context = context_course::instance($course->id);
require_capability('mod/registration:grade', $context);
$ismyteacher = has_capability('mod/registration:grade', $context);

$strregistrations = get_string("modulenameplural", "registration");
$strregistration  = get_string("modulename", "registration");
$strsubmissions = get_string("submissions", "registration");
$strsaveallfeedback = get_string("saveallfeedback", "registration");

$url = new moodle_url('/mod/registration/submissions.php', array('id'=>$registration->id));
$PAGE->set_url($url);
$PAGE->set_title($strregistrations);
$PAGE->navbar->add(get_string("viewsubmissions", "mod_registration"));
echo $OUTPUT->header();
$options = array(0 => get_string("showcategory", "", get_string("modulename", "mod_registration")),
                 2 => get_string("download_results", "mod_registration"),
                 3 => get_string("printversionname", "mod_registration"));
if (empty($CFG->registration_hide_idnumber)) {
	$options[4] = get_string("printversionid", "mod_registration");
	$options[5] = get_string("printversionidname", "mod_registration");
}
echo $OUTPUT->single_select(new moodle_url("/mod/registration/view.php", array("a" => $registration->id)),
                            "redirect",
                            $options);
echo $OUTPUT->heading($registration->name);
if($submissions = registration_get_all_submissions($registration, $sort, $dir)) {

	/// If data is being submitted, then process it
        if ($data = data_submitted())
       	{
               	$feedback = array();
                // Peel out all the data from variable names.
       	        foreach ($data as $key => $val)
               	{
		  if (!in_array($key, array("id", "timenow", "sort")))
                        {
       	                        $type = substr($key,0,1);
               	                $num  = substr($key,1);
                       	        $feedback[$num][$type] = $val;
                        }
       	        }
               	$count = 0;
                foreach ($feedback as $num => $vals)
       	        {
               	        $submission = $submissions[$num];
                       	// Only update entries where feedback has actually changed.
                        if (($vals['g'] <> $submission->grade) || ($vals['c'] <> addslashes($submission->comment)))
       	                {
               	                unset($newsubmission);
				$newsubmission = new stdClass();
                       	        $newsubmission->grade = $vals['g'];
                               	$newsubmission->comment = $vals['c'];
                                $newsubmission->teacher = $USER->id;
       	                        $newsubmission->timemarked = $timenow;
               	                $newsubmission->mailed = 0;              // Make sure mail goes out (again, even)
                       	        $newsubmission->id = $num;

                                // Make sure that we aren't overwriting any recent feedback from other teachers. (see bug #324)
       	                        if ($timewas < $submission->timemarked && (!empty($submission->grade)) && (!empty($submission->comment)))
               	                {
                       	                notify(get_string("failedupdatefeedback", "registration", fullname(get_complete_user_data('id', $submission->userid)))
                               	        . "<br>" . get_string("grade") . ": $newsubmission->grade"
                                       	. "<br>" . get_string("feedback", "registration") . ": $newsubmission->comment\n");
                                }
       	                        else
               	                {
                                        //print out old feedback and grade
       	                                if (empty($submission->timemodified))
               	                        {
                       	                        // eg for offline registrations
                               	                $newsubmission->timemodified = $timenow;
                                       	}
                                        if (! $DB->update_record("registration_submissions", $newsubmission))
       	                                        notify(get_string("failedupdatefeedback", "registration", $submission->userid));
               	                        else
                       	                        $count++;
                               	}
                        }
       	        }
               	$submissions = registration_get_all_submissions($registration,$sort, $dir);
                //add_to_log($course->id, "registration", "update grades", "submissions.php?id=$registration->id", "$count users", $cm->id);
               	$eventdata = array("context" => context_module::instance($cm->id), "objectid" => $registration->id);
                \mod_registration\event\grades_updated::create($eventdata)->trigger();
       	        notify(get_string("feedbackupdated", "registration", $count));
        }
       	else {
               	//add_to_log($course->id, "registration", "view submission", "submissions.php?id=$registration->id", "$registration->id", $cm->id);
       	        $eventdata = array("context" => context_module::instance($cm->id), "objectid" => $registration->id);
       	        \mod_registration\event\submission_viewed::create($eventdata)->trigger();
       	}
        // Submission sorting
       	$sorttypes = array('firstname', 'lastname', 'timemodified', 'grade');

	echo $OUTPUT->box_start();
       	echo '<p align="center">'.get_string('order').':&nbsp;&nbsp;';

        foreach ($sorttypes as $sorttype)
       	{
               	if ($sorttype == 'timemodified')
                       	$label = get_string("lastmodified");
                else
       	                $label = get_string($sorttype);
               	if ($sort == $sorttype)
                {
       	                // Current sort
               	        $newdir = $dir == 'ASC' ? 'DESC' : 'ASC';
                }
       	        else
               	        $newdir = 'ASC';
                echo html_writer::link(new moodle_url("/mod/registration/submissions.php", array("id" => $registration->id,
                                                                                                 "sort" => $sorttype,
                                                                                                 "dir" => $newdir)),
                                       $label);
       	        if ($sort == $sorttype)
               	{
                        // Current sort
       	                $diricon = ($dir == 'ASC') ? "i/down" : "i/up";
               	        echo $OUTPUT->pix_icon($diricon, get_string("sortby"));
                }
       	        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        }
       	echo "</p>";
	echo $OUTPUT->box_end();
	echo $OUTPUT->spacer(array('height'=>8, 'width'=>1));

        echo '<form action="submissions.php" method="post">';
       	echo "<center>";
        echo "<input type=submit value=\"$strsaveallfeedback\">";
       	echo "</center><br />";

        $grades = make_grades_menu($registration->grade);

       	foreach ($submissions as $submission)
        {
	  if ($user = get_complete_user_data('id', $submission->userid))
		registration_print_submission($registration, $user, $submission, $grades);
        }

       	echo "<center>";
        echo "<input type=hidden name=sort value=\"$sort\">";
        echo "<input type=hidden name=timenow value=\"$timenow\">";
       	echo "<input type=hidden name=id value=\"$registration->id\">";
        echo "<input type=submit value=\"$strsaveallfeedback\">";
       	echo "</center>";
        echo "</form>";

} else {
	echo notify(get_string("nostudentsyet","registration"));
}

echo $OUTPUT->footer();
?>
