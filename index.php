<?PHP // $Id: index.php,v 1.15 2004/08/22 14:38:37 gustav_delius Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);           // Course Module ID 
//    require_variable($id);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_course_login($course);
    $context=get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('mod/registration:view', $context);
    $ismyteacher = has_capability('mod/registration:grade', $context);
    $ismystudent = has_capability('mod/registration:view', $context);

    add_to_log($course->id, "registration", "view all", "index.php?id=$course->id", "");

    $strregistrations = get_string("modulenameplural", "registration");
    $strregistration = get_string("modulename", "registration");
    $strweek = get_string("week");
    $strtopic = get_string("topic");
    $strname = get_string("name");
    $strduedate = get_string("duedate", "registration");
    $stravailabledate = get_string("availabledate", "registration");
    $strsubmitted = get_string("submitted", "registration");


    print_header_simple($strregistrations, "", $strregistrations, "", "", true, "", navmenu($course));

    if (! $registrations = get_all_instances_in_course("registration", $course)) {
        notice(get_string('noregistrations', 'registration'), "../../course/view.php?id=$course->id");
        die;
    }

    $timenow = time();

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

    print_table($table);

    echo "<br />";
    $legend->head = array( '<div style="color: red; font-weight: bold;">'.get_string("legend","registration").'</div>');
    
    print_table($legend);
    print_footer($course);
?>
