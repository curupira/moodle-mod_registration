<?PHP

require_once("../../config.php");
require_once("lib.php");

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
//optional_variable($id);// Course Module ID
$a = optional_param('a', 0, PARAM_INT); // registration ID
//optional_variable($a);// registration ID


if ($id) 
{
        if (! $cm = get_record("course_modules", "id", $id))
                error("Course Module ID was incorrect");
        if (! $course = get_record("course", "id", $cm->course))
                error("Course is misconfigured");
        if (! $registration = get_record("registration", "id", $cm->instance))
                error("Course module is incorrect");
} 
else 
{
        if (! $registration = get_record("registration", "id", $a))
                error("Course module is incorrect");
        if (! $course = get_record("course", "id", $registration->course))
                error("Course is misconfigured");
        if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id))
                error("Course Module ID was incorrect");
}
require_course_login($course);
$context=get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('mod/registration:view', $context);
$ismyteacher = has_capability('mod/registration:grade', $context);
$ismystudent = has_capability('mod/registration:view', $context);

add_to_log($course->id, "registration", "view", "view.php?id=$cm->id", $registration->id, $cm->id);

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

$navigation = "<a href=\"index.php?id=$course->id\">$strregistrations</a> ";
print_header_simple($strregistrations, "", "$navigation -> $registration->name", "", "", true, update_module_button($cm->id, $course->id, $strregistration), navmenu($course, $cm));
//print_header_simple(format_string($forum->name), "", "$navigation -> $registration->name", "", "", true, update_module_button($cm->id, $course->id, $strregistration), navmenu($course, $cm));


if ($ismyteacher)
{
        // if number of registered students == 0 then do not show menu
        echo '<p align="right">';
	if(registration_count_submissions($registration))
        {
                echo '<a href="submissions.php?id='.$registration->id.'">'.get_string("viewsubmissions", "registration").'</a><br>';
                echo '<a href="print.php?version=0&id='.$registration->id.'">'.get_string("printversionid", "registration").'</a><br>';
                echo '<a href="print.php?version=1&id='.$registration->id.'">'.get_string("printversionname", "registration").'</a><br>';
        }
}
elseif (!$cm->visible) 
        notice(get_string("activityiscurrentlyhidden"));

if ($form = data_submitted()) 
{

        if ($form->answer == addslashes($stranswer))
        {
                $timenow = time();
                $newanswer->timecreated = $timenow;
                $newanswer->timemodified = $timenow;
                $newanswer->registration = $registration->id;
                $newanswer->userid = ($_POST['action']) ? $_POST['idstudent'] : $USER->id;
                $num_students = get_records("registration_submissions","registration",$cm->instance);
                $sql = "SELECT userid FROM ".$CFG->prefix."registration_submissions, ".$CFG->prefix."registration 
                WHERE registration = ".$CFG->prefix."registration.id 
                AND course = '".$cm->course."'
                AND userid = '".$USER->id."'
                AND registration = '".$cm->instance."'";
                $result = get_record_sql($sql);
                // zrusene obmedzenie
                if (empty($num_students)) $num_students = array() ;
                if (!$result->userid && ((count($num_students) < $registration->number) || $registration->allowqueue))
                {
                  if (! insert_record("registration_submissions", $newanswer)) 
                        error("Could not save your choice");
                  add_to_log($course->id, "registration", "subscribe", "view.php?id=$cm->id", $USER->id, $cm->id, $USER->id);
                  redirect("view.php?id=$cm->id","",0);
                  exit;
                }
        }
        elseif ($form->answer == $stranswercancel)
        {
                $select = "SELECT s.userid, r.number FROM ".$CFG->prefix."registration_submissions s, ".$CFG->prefix."registration r WHERE s.registration = '".$registration->id."' AND s.registration = r.id ORDER BY s.id";
                $result = get_records_sql($select);
                $user_delete = ($_POST['action']) ? $_POST['idstudent_del'] : $USER->id;
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
                if (! delete_records("registration_submissions", "userid", $user_delete, "registration", $cm->instance))
                        error("Could not save your choice");
                else
                {
                        if($user_id)
                        {
                                $message->coursename = $course->fullname;
                                $message->duedate = userdate($registration->timedue);
                                $message->name = $registration->name;
                                $message->url = "$CFG->wwwroot/mod/registration/view.php?id=$cm->id";
                                $select = "SELECT * FROM ".$CFG->prefix."user u WHERE u.id = '".$user_id."'";
                                $student = get_record_sql($select);
                                $site = get_site();
                                $strsubject = strip_tags(get_string('subject', 'registration', $message));
                                email_to_user($student, $site->shortname, $strsubject, get_string("message", "registration", $message));
                        }
                }
                add_to_log($course->id, "registration", "unsubscribe", "view.php?id=$cm->id", $user_delete, $cm->id, $USER->id);
                redirect("view.php?id=$cm->id","",0);
                exit;
        }
}

print_simple_box_start("center");
print_heading($registration->name, "center");

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

if($ismyteacher)
{
  if ($registration->grade) {
    $table->head = array ($strorder, $stridnumber, $strfirstname, $strlastname, $strpoints, $stranswercancel, $strnote);
    $table->align = array ("center", "center"    , "center"     , "center"    , "center"  , "center"        , "left");
  }else{
    $table->head = array ($strorder, $stridnumber, $strfirstname, $strlastname, $stranswercancel);
    $table->align = array ("center", "center"    , "center"     , "center"    , "center");
  }
}
else
{
  $table->head = array ($strorder, $stridnumber." / ".$strfirstname." ".$strlastname);
  $table->align = array ("center", "center", "left");
}

$scale = make_grades_menu($registration->grade);

$students = get_records("registration_submissions","registration",$cm->instance,"id");
$i = 0;
if (!$students) $students = array();
foreach ($students as $data)
{
        $person = get_record("user","id",$data->userid);
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
        if ($ismyteacher)
        {
                $cancel_radio = "<input type='radio' name='idstudent_del' value='".$person->id."' />";
                if($registration->grade)
                        $table->data[] = array ($start.(++$i).$stop, $start.$person->idnumber.$stop, $start.$person->firstname.$stop, $start.$person->lastname.$stop, $start.$scale[$data->grade], $cancel_radio, $start.$data->comment.$stop);
                else
                        $table->data[] = array ($start.(++$i).$stop, $start.$person->idnumber.$stop, $start.$person->firstname.$stop, $start.$person->lastname.$stop, $cancel_radio);
                $text_assessment = '';
        }
        else
        {
                $idnumber_name = ($person->idnumber) ? $person->idnumber : $person->firstname." ".$person->lastname;
                $table->data[] = array ($start.(++$i).$stop, $start.$idnumber_name.$stop);
                if ($registration->timedue < time() && $USER->id == $data->userid && $registration->grade)
                        $text_assessment = '<div style="color: #800000; font-weight: bold;">'.get_string("feedback","registration").": ".$scale[$data->grade].'<br />'.get_string("note", "registration").": ".$data->comment."</div>";
        }
}
if($ismyteacher && $table->data){
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
$moduleID = get_record_sql($sql);

$sql = "SELECT * FROM ".$CFG->prefix."course_modules WHERE course='".$cm->course."' AND module = '".$moduleID->id."' AND instance = '".$cm->instance."'";
$sectionID = get_record_sql($sql);

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
$moduleID_new = get_records_sql($sql);
if($moduleID_new != "")
{
	//booked on a future exam - show only if date is not closed
	if($registration->timedue > time())
	{
		foreach($moduleID_new as $value)
		{
			$result = get_record("registration", "id", $value->instance);
			$sql = "SELECT * FROM ".$CFG->prefix."course_modules WHERE course='".$cm->course."' AND module = '".$moduleID->id."' AND instance = '".$value->instance."'";
			$sectionID_all = get_record_sql($sql);
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
echo "<input type='hidden' name='id' value='".$id."'>\n";
echo "<input type='hidden' name='action' value='1'>\n";
if (has_capability('mod/registration:viewlist', $context))
{
	print_table($table);
	echo "</form>\n";

	echo format_text($registration->description);
}
print_simple_box_end();

if ($ismyteacher)
{
        $result='';
        if($_POST['search'])
        {

          $teachers = get_users_by_capability($context, 'mod/registration:grade');
          if($teachers)
          {
            $condition = array();
            foreach($teachers as $teacher){
              $condition[] = $teacher->id;
            }
          }

                // choose all students except registered for the date and except teachers

                $select = 'SELECT u.id, u.firstname, u.lastname ';
                $from = 'FROM '.$CFG->prefix.'user u
                JOIN '.$CFG->prefix.'role_assignments ra ON ra.userid = u.id 
                LEFT JOIN '.$CFG->prefix.'registration_submissions rs ON rs.userid = u.id
                AND rs.registration = '.$registration->id;
                $where = ' WHERE ra.contextid = ' . $context->id . '
                AND u.deleted = 0 
                AND (u.lastname like \'%'.$_POST['search'].'%\' OR u.firstname like \'%'.$_POST['search'].'%\')
                AND rs.userid IS NULL';
                if($condition) 
                        $where .= " AND u.id <> ".implode(" AND u.id <> ", $condition);
                $sort = ' ORDER BY u.lastname, u.firstname';

                $result = get_records_sql($select.$from.$where.$sort);
                $table1->head = array ("", $strlastname, $strfirstname);
                $table1->align = array ("right", "left", "left");
                if($result)
                {
                        $checked = ' checked="checked"';
                        $i = 0;
                        foreach($result as $value)
                        {
                                $table1->data[] = array ("<input type='radio' name='idstudent' value='".$value->id."'".$checked." />", $value->lastname, $value->firstname);
                                $checked = '';
                                if(!(++$i%10))
                                        $table1->data[] = array ("", "", "<input type='submit' name='answer' value='".$stranswer."'>");
                        }
                        if(($i%10))
                                $table1->data[] = array ("", "", "<input type='submit' name='answer' value='".$stranswer."'>");
                }
        }
        print_simple_box_start("center");
        $table2->head = array ($strfirstname." <span style='font-weight: normal;'>".$stror."</span> ".$strlastname ." <span style='font-weight: normal;'>".$stror."</span> %");
        $table2->align = array ("left");
        $table2->data[] = array ("<input type='text' name='search'>&nbsp;<input type='submit' name='submit' value='".$strlookfor."'>");
        echo "<form action='view.php' method='post'>\n";
        echo "<input type='hidden' name='id' value='".$id."'>\n";
        echo "<input type='hidden' name='action' value='1'>\n";
        print_table($table2);
        if($result) 
          print_table($table1);
        echo "</form>\n";
        print_simple_box_end();
}
print_footer($course);
?>
