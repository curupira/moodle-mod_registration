<?PHP

require_once("../../config.php");
require_once("lib.php");

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
//optional_variable($id);// Course Module ID
$a = optional_param('a', 0, PARAM_INT); // registration ID
//optional_variable($a);// registration ID
$redirect = optional_param("redirect", -1, PARAM_INT);

if ($id)
{
  if (! $cm = $DB->get_record("course_modules", array('id' => $id)))
    print_error("courseidincorrect","registration");
  if (! $course = $DB->get_record("course",  array('id' => $cm->course)))
    print_error("coursemisconfigured","registration");
  if (! $registration = $DB->get_record("registration",  array('id' => $cm->instance)))
    print_error("courseincorrect","registration");
}
else
{
  if (! $registration = $DB->get_record("registration", array('id' => $a)))
    print_error("courseincorrect","registration");
  if (! $course = $DB->get_record("course",  array('id' => $registration->course)))
    print_error("coursemisconfigured","registration");
  if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id))
    print_error("courseidincorrect","registration");
}
require_login($course, false, $cm);
$context = context_course::instance($course->id);
// $data for every event in this script.
$eventdata = array("context" => context_module::instance($cm->id), "objectid" => $registration->id);

require_capability('mod/registration:view', $context);
$ismyteacher = has_capability('mod/registration:grade', $context);
$ismystudent = has_capability('mod/registration:view', $context);

if ($redirect > -1) {
	switch ($redirect) {
		case 1:
			redirect(new moodle_url("/mod/registration/submissions.php", array("id" => $registration->id)));
			break;
		case 2:
			redirect(new moodle_url("/mod/registration/download.php", array("id" => $registration->id)));
			break;
		case 3:
			redirect(new moodle_url("/mod/registration/print.php", array("version" => 2, "id" => $registration->id)));
			break;
		case 4:
			redirect(new moodle_url("/mod/registration/print.php", array("version" => 0, "id" => $registration->id)));
			break;
		case 5:
			redirect(new moodle_url("/mod/registration/print.php", array("version" => 1, "id" => $registration->id)));
			break;
	}
}

//add_to_log($course->id, "registration", "view", "view.php?id=$cm->id", $registration->id, $cm->id);
\mod_registration\event\viewed_single::create($eventdata)->trigger();

$strregistrations = get_string("modulenameplural", "registration");
$strregistration = get_string("modulename", "registration");
$strorder = get_string("order", "registration");
$strfirstname = get_string("firstname");
$strlastname = get_string("lastname");
$strdatetext = get_string("datetext", "registration");
$strclosed = get_string("closed", "registration");
$strfull = get_string("full", "registration");
$stranswer = get_string("answer", "registration");
$stranswercancel = get_string("answercancel", "registration");
$strpoints = get_string("points", "registration");
$strnote = get_string("note", "registration");
$stridnumber = get_string("idnumber");
$strregistrations = get_string("modulenameplural", "registration");
$strregistration = get_string("modulename", "registration");
$stror = get_string("or", "registration");
$strlookfor = get_string("lookfor", "registration");
$strnotefull = get_string("notefull", "registration");

$url = new moodle_url('/mod/registration/view.php', array('id'=>$registration->id));
$PAGE->set_url($url);
$PAGE->set_title($strregistrations);
$PAGE->navbar->add($strregistrations, new moodle_url("/mod/registration/index.php?id=".$course->id));
$PAGE->navbar->add($registration->name);

if ($form = data_submitted())
{

        if ($form->answer == addslashes($stranswer))
        {
                $timenow = time();
		$newanswer = new stdClass();
                $newanswer->timecreated = $timenow;
                $newanswer->timemodified = $timenow;
                $newanswer->registration = $registration->id;
		$newanswer->comment = "";
                $newanswer->userid = (isset($_POST['action']) && isset($_POST['idstudent'])) ? $_POST['idstudent'] : $USER->id;
                $num_students = $DB->get_records("registration_submissions",array("registration"=>$cm->instance));
                $sql = "SELECT userid FROM {registration_submissions} rs, {registration} r
                        WHERE registration = r.id
                        AND course = ?
                        AND userid = ?
                        AND registration = ?";
                $result = $DB->get_record_sql($sql, array($cm->course, $USER->id, $cm->instance));
                // zrusene obmedzenie
                if (empty($num_students)) $num_students = array() ;
                if (!$result && ((count($num_students) < $registration->number) || $registration->allowqueue))
                {
                  if (! $DB->insert_record("registration_submissions", $newanswer))
		    print_error("errorchoice","registration");
                  //add_to_log($course->id, "registration", "subscribe", "view.php?id=$cm->id", $USER->id, $cm->id, $USER->id);
                  \mod_registration\event\user_subscribed::create($eventdata)->trigger();
                  redirect("view.php?id=$cm->id","",0);
                  exit;
                }
        }
        elseif ($form->answer == $stranswercancel)
        {
                $select = "SELECT s.userid, r.number
                           FROM {registration_submissions} s, {registration} r
                           WHERE s.registration = ?
                           AND s.registration = r.id
                           ORDER BY s.id";
                $result = $DB->get_records_sql($select, array("registration" => $registration->id));
                $user_delete = (isset($_POST["action"]) && $_POST["action"]) ? $_POST['idstudent_del'] : $USER->id;
                unset($user_id); // Ensure, that isset($user_id) returns false.
                if($result)
                {
                        $pole = array();
                        $i = 0;
                        foreach($result as $value)
                        {
                            $pole[++$i] = $value->userid;
                            if($value->userid == $user_delete)
                                $order = $i;
                        }
                        if($value->number >= $order && $i > $value->number)
                            $user_id = $pole[$value->number+1];
                }
                if (! $DB->delete_records("registration_submissions",array("userid"=>$user_delete, "registration"=>$cm->instance)))
		  print_error("errorchoice","registration");
                else
                {
                        if(isset($user_id))
                        {
                            $message = new stdClass();
                            $message->coursename = $course->fullname;
                            $message->duedate = userdate($registration->timedue);
                            $message->name = $registration->name;
                            $message->url = "$CFG->wwwroot/mod/registration/view.php?id=$cm->id";
                            $student = get_complete_user_data("id", $user_id);
                            $site = get_site();
                            $strsubject = strip_tags(get_string('subject', 'registration', $message));
                            email_to_user($student, $site->shortname, $strsubject, get_string("message", "registration", $message));
                        }
                }
                //add_to_log($course->id, "registration", "unsubscribe", "view.php?id=$cm->id", $user_delete, $cm->id, $USER->id);
                \mod_registration\event\user_unsubscribed::create($eventdata)->trigger();
                redirect("view.php?id=$cm->id","",0);
                exit;
        }
}

// Start output.
echo $OUTPUT->header();
if ($ismyteacher)
{
	$options = array(1 => get_string("viewsubmissions", "mod_registration"),
	                 2 => get_string("download_results", "mod_registration"),
	                 3 => get_string("printversionname", "mod_registration"));
	if (empty($CFG->registration_hide_idnumber)) {
		$options[4] = get_string("printversionid", "mod_registration");
	    $options[5] = get_string("printversionidname", "mod_registration");
	}
	echo $OUTPUT->single_select(new moodle_url("/mod/registration/view.php", array("a" => $registration->id)), "redirect", $options);
}
elseif (!$cm->visible)
notice(get_string("activityiscurrentlyhidden"));
echo $OUTPUT->heading($registration->name);
echo $OUTPUT->box_start();

$timedifference_due = $registration->timedue - time();
$timedifference_avail = $registration->timeavailable - time();
if ($timedifference_due < 31536000)
{
        // Don't bother showing dates over a year in the future
        $strdifference_due = format_time($timedifference_due);
        $strdifference_avail = format_time($timedifference_avail);
        if ($timedifference_due < 0)
                $strdifference_due = '<span style="color: red;">'.$strdifference_due.'</span>';
        if ($timedifference_avail < 0)
                $strdifference_avail = '<span style="color: red;">'.$strdifference_avail.'</span>';
        $strduedate = userdate($registration->timedue)." ($strdifference_due)";
        $stravailabledate = userdate($registration->timeavailable)." ($strdifference_avail)";

	echo "<table>";
        echo "<tr><td><strong>".get_string("duedate", "registration")."</strong>:</td><td>$strduedate</td></tr>";
        echo "<tr><td><strong>".get_string("availabledate", "registration")."</strong>:</td><td>$stravailabledate</td></tr>";
        echo "<tr><td><strong>".get_string("place", "registration")."</strong>:</td><td>".$registration->room."</td></tr>";
        echo "<tr><td><strong>".get_string("maximumsize", "registration")."</strong>:</td><td>".$registration->number."</td></tr>";
        if($registration->grade >0)
                echo "<tr><td><strong>".get_string("maximumpoints", "registration")."</strong>:</td><td>".$registration->grade."</td></tr>";
	echo "</table>";
}

$table = new html_table();
$table->attributes['style']="margin-left:auto; margin-right:auto;";


$table->head[] = $strorder;
$table->align[] = "center";

if (empty($CFG->registration_hide_idnumber))
  {
    $table->head[] = $stridnumber;
    $table->align[] = "center";
  }

$table->head[] = $strfirstname;
$table->align[] = "center";
$table->head[] = $strlastname;
$table->align[] = "center";

if($ismyteacher)
  {
    if ($registration->grade)
      {
	$table->head[] = $strpoints;
	$table->align[] = "center";
      }
    $table->head[] = $stranswercancel;
    $table->align[] = "center";
    $table->head[] =$strnote;
    $table->align[] = "left";
  }

$scale = make_grades_menu($registration->grade);

$students = $DB->get_records("registration_submissions",array("registration"=>$cm->instance),"id");
$i = 0;
if (!$students) $students = array();
$text_assessment='';
foreach ($students as $data)
{
  $person = $DB->get_record("user",array("id"=>$data->userid));
        $points[$USER->id] = $data->grade;
        if($i >= $registration->number)
        {
                $start = "<span style='color: red'>";
                $stop = "</span>";
        }
        else
        {
                $start = "";
                $stop = "";
        }

	$line = array();
	$line[] = $start.(++$i).$stop;

	if (empty($CFG->registration_hide_idnumber))
	  {
	    $line[] = $start.$person->idnumber.$stop;
	  }

	$line[] = $start.$person->firstname.$stop;
	$line[] = $start.$person->lastname.$stop;

	if($ismyteacher)
	  {
	    if ($registration->grade)
	      {
		$line[] = $start.$scale[$data->grade].$stop;
	      }
	    $line[] = "<input type='radio' name='idstudent_del' value='".$person->id."' />";
	    $line[] = $start.$data->comment.$stop;
	    if ($person->deleted)
	      $line[] = $start.get_string("userdeleted","registration").$stop;
	    $text_assessment = '';
	  }
	else
	  {
	    if ($registration->timedue < time() && $USER->id == $data->userid && $registration->grade)
	      $text_assessment = '<div style="color: #800000; font-weight: bold;">'.get_string("feedback","registration").": ".$scale[$data->grade].'<br />'.get_string("note", "registration").": ".$data->comment."</div>";
	  }
	$table->data[] = $line;

}
if($ismyteacher && isset($table->data)){
  if ($registration->grade) {
    $table->data[] = array ("", "", "", "", "", '<INPUT type="submit" name="answer" value="'.$stranswercancel.'" />', "");
  }else{
    $table->data[] = array ("", "", "", "", '<INPUT type="submit" name="answer" value="'.$stranswercancel.'" />', "");
  }
 }

$submit_button = '<center>
<FORM name="form" method="post" action="view.php">
<INPUT type="hidden" name="id" value="'.$cm->id.'" />
<INPUT type="submit" name="answer" value="'.$stranswer.'" />
</FORM>
</center>';
$cancel_button = '<center>
<FORM name="form" method="post" action="view.php">
<INPUT type="hidden" name="id" value="'.$cm->id.'" />
<INPUT type="submit" name="answer" value="'.$stranswercancel.'" />
</FORM>
</center>';

$registrable = true;
if ($registration->timeavailable < time())
{
        $registrable = false;
        echo '<div style="color: red; font-weight: bold;">'.$strclosed.'</div>';
}

$sql = "SELECT * FROM ".$CFG->prefix."modules WHERE name LIKE 'registration'";
$moduleID = $DB->get_record_sql($sql);

$sql = "SELECT * FROM ".$CFG->prefix."course_modules WHERE course='".$cm->course."' AND module = '".$moduleID->id."' AND instance = '".$cm->instance."'";
$sectionID = $DB->get_record_sql($sql);

$position = registration_get_position_in_list($cm->instance,$USER->id);

$sql = "SELECT * FROM ".$CFG->prefix."course_modules c
LEFT JOIN ".$CFG->prefix."registration r ON r.id = c.instance
LEFT JOIN ".$CFG->prefix."registration_submissions s ON r.id = s.registration
WHERE s.registration = r.id
AND c.course = '".$cm->course."'
AND s.userid = '".$USER->id."'
AND r.timedue > '".time()."'
ORDER BY r.timedue";

//add last line - if date is closed - student can sign in to another date (in the same week or topics)
$moduleID_new = $DB->get_records_sql($sql);
if($moduleID_new != "")
{
	//booked on a future exam - show only if date is not closed
	if($registration->timedue > time())
	{
		foreach($moduleID_new as $value)
		{
		  $result = $DB->get_record("registration", array("id"=>$value->instance));
		  $sql = "SELECT * FROM ".$CFG->prefix."course_modules WHERE course='".$cm->course."' AND module = '".$moduleID->id."' AND instance = '".$value->instance."'";
		  $sectionID_all = $DB->get_record_sql($sql);
		  if($sectionID->section == $sectionID_all->section)
		    {
		      if($position > $registration->number) {
			$queue = " (".get_string("in_queue","registration").")";
		      } else {
			$queue = "";
		      }
		      echo '<a href="view.php?a='.$value->instance.'"><div style="color: red; font-weight: bold; margin-bottom: 5px;">'.get_string("datetext","registration").$queue.': &quot;'.$result->name.'&quot; , '.userdate($result->timedue).'</div></a>';
		      $registrable = false;
		    }
		}
	}
}

if ($i >= $registration->number)
{
	if ($registration->allowqueue)
	{
	        echo '<span style="color: red; font-weight: bold;">'.$strfull.'</span>';
	        if(!$ismyteacher && !$position)
	       	        echo '<p>'.$strnotefull.'</p>';
	}
	else
	{
	        echo '<span style="color: red; font-weight: bold;">'.$strfull.'</span>';
	       	$registrable = false;
	}
}

//if booked on this future registration
if ($position > 0)
{
       	// if date is closed - do not show button sign out
        if($registration->timeavailable > time())
       	        echo $cancel_button;
        $registrable = false;
}

if ($registrable == true && $ismystudent)
//if ($registrable == true && isstudent($course->id, $user->id))
       	echo $submit_button;

echo '<br>'.$text_assessment;

echo "<form action='view.php' method='post'>\n";
echo "<input type='hidden' name='id' value='".$cm->id."'>\n";
echo "<input type='hidden' name='action' value='1'>\n";
if (has_capability('mod/registration:viewlist', $context))
{
  echo html_writer::table($table);
  echo "</form>\n";

  echo format_text($registration->intro);
}
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
