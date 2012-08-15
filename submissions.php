<?PHP  // $Id: submissions.php,v 1.31 2004/08/21 20:20:53 gustav_delius Exp $

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);           // Course Module ID 
$sort = optional_param('sort', "timemodified", PARAM_ALPHA); 
$dir = optional_param('dir', "DESC", PARAM_ALPHA); 
$timenow = optional_param('timenow', 0, PARAM_INT); 

$timewas = $timenow;
$timenow = time();

if (! $registration = get_record("registration", "id", $id))
	error("Course module is incorrect");
if (! $course = get_record("course", "id", $registration->course))
	error("Course is misconfigured");
if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id))
	error("Course Module ID was incorrect");

require_login($course->id);
$context=get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('mod/registration:grade', $context);
$ismyteacher = has_capability('mod/registration:grade', $context);

$strregistrations = get_string("modulenameplural", "registration");
$strregistration  = get_string("modulename", "registration");
$strsubmissions = get_string("submissions", "registration");
$strsaveallfeedback = get_string("saveallfeedback", "registration");

print_header_simple($registration->name, "","<a href=\"index.php?id=$course->id\">$strregistrations</a> -> <a href=\"view.php?a=$registration->id\">$registration->name</a> -> $strsubmissions", "", "", true, update_module_button($cm->id, $course->id, $strregistration), navmenu($course, $cm));

if($submissions = registration_get_all_submissions($registration, $sort, $dir)) {

	/// If data is being submitted, then process it
        if ($data = data_submitted()) 
       	{
               	$feedback = array();
                // Peel out all the data from variable names.
       	        foreach ($data as $key => $val) 
               	{
                       	if (!in_array($key, array("id", "timenow"))) 
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
                                        if (! update_record("registration_submissions", $newsubmission)) 
       	                                        notify(get_string("failedupdatefeedback", "registration", $submission->userid));
               	                        else
                       	                        $count++;
                               	}
                        }
       	        }
               	$submissions = registration_get_all_submissions($registration,$sort, $dir);
                add_to_log($course->id, "registration", "update grades", "submissions.php?id=$registration->id", "$count users", $cm->id);
       	        notify(get_string("feedbackupdated", "registration", $count));
        }
       	else
               	add_to_log($course->id, "registration", "view submission", "submissions.php?id=$registration->id", "$registration->id", $cm->id);

        // Submission sorting
       	$sorttypes = array('firstname', 'lastname', 'timemodified', 'grade');

        print_simple_box_start("center", "80%");
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
                echo "<a href=\"submissions.php?id=$registration->id&sort=$sorttype&dir=$newdir\">$label</a>";
       	        if ($sort == $sorttype) 
               	{
                        // Current sort
       	                $diricon = $dir == 'ASC' ? 'down' : 'up';
               	        echo " <img src=\"$CFG->pixpath/t/$diricon.gif\" />";
                }
       	        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        }
       	echo "</p>";
        print_simple_box_end();
       	print_spacer(8,1);

        echo '<form action="submissions.php" method="post">';
       	echo "<center>";
        echo "<input type=submit value=\"$strsaveallfeedback\">";
       	echo "</center><br />";

        $grades = make_grades_menu($registration->grade);

       	foreach ($submissions as $submission)
        {
       	        $user = get_complete_user_data('id', $submission->userid);
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

print_footer($course);
?>
