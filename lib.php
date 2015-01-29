<?PHP

//require_once($CFG->libdir.'/filelib.php');
//require_once("$CFG->dirroot/files/mimetypes.php");

//define("OFFLINE",      "0");
//define("UPLOADSINGLE", "1");
define ('registration_RESETFORM_RESET', 'registration_reset_data_');
define ('registration_RESETFORM_DROP', 'registration_drop_registration_');

//$registration_TYPE = array (OFFLINE       => get_string("typeoffline",      "registration"),
//                          UPLOADSINGLE  => get_string("typeuploadsingle", "registration") );

if (!isset($CFG->registration_maxstudents)) {
    set_config("registration_maxstudents", 100);  // Default maximum number of students
}


function registration_add_instance($registration) {
// Given an object containing all the necessary data,
// (defined by the form in mod.html) this function
// will create a new instance and return the id number
// of the new instance.
// ver2.0

  global $CFG, $DB;

    $registration->timemodified = time();

    $registration->name = strip_tags($registration->name);
    $registration->room = strip_tags($registration->room);

    if ($registration->timeavailable > $registration->timedue)
	print_error("dateerror", "registration",$CFG->wwwroot."/course/mod.php?id=".$registration->course."&section=".$registration->section."&sesskey=".$registration->sesskey."&add=registration");

    if ($returnid = $DB->insert_record("registration", $registration)) {

        $event = new stdClass();
        $event->name          = $registration->name;
        $event->description   = $registration->intro;
        $event->format        = null;
        $event->courseid      = $registration->course;
        $event->groupid       = 0;
        $event->userid        = 0;
        $event->modulename    = 'registration';
        $event->instance      = $returnid;
        $event->eventtype     = 'due';
        $event->allowqueue    = !empty($registration->allowqueue);
        $event->timestart     = $registration->timedue;
        $event->timeavailable = $registration->timeavailable;
        $event->timeduration  = 0;

        //add_event($event);
        calendar_event::create($event);
    }

    return $returnid;
}


function registration_update_instance($registration) {
// Given an object containing all the necessary data,
// (defined by the form in mod.html) this function
// will update an existing instance with new data.
// ver2.0

  global $CFG, $DB;

    $registration->timemodified = time();

    $registration->name = strip_tags($registration->name);
    $registration->room = strip_tags($registration->room);

    if ($registration->timeavailable > $registration->timedue)
	print_error("dateerror", "registration",$CFG->wwwroot."/course/mod.php?update=".$registration->coursemodule."&return=true&sesskey=".$registration->sesskey);
    $registration->id = $registration->instance;
    $registration->allowqueue = !empty($registration->allowqueue);

    if ($returnid = $DB->update_record("registration", $registration)) {

        $event = new stdClass();

        if ($event->id = $DB->get_field('event', 'id', array('modulename'=>'registration', 'instance'=>$registration->id))) {

            $event->name          = $registration->name;
            $event->description   = $registration->intro;
            $event->timestart     = $registration->timedue;
            $event->timeavailable = $registration->allowqueue;

            //update_event($event);
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        }
    }

    return $returnid;
}


function registration_delete_instance($id) {
// Given an ID of an instance of this module,
// this function will permanently delete the instance
// and any data that depends on it.
// ver2.0

  global $DB;

  if (! $registration = $DB->get_record("registration", array('id'=>$id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("registration_submissions", array('registration'=> $registration->id))) {
        $result = false;
    }

    if (! $DB->delete_records("registration", array('id'=>$registration->id))) {
        $result = false;
    }

    if ($eventid = $DB->get_field('event', 'id', array('modulename' => 'registration', 'instance' => $registration->id))) {
        $event = calendar_event::load($eventid);
        $event->delete();
    }

    return $result;
}

function registration_delete_submission_instance($id) {
// Given an ID of an instance of this module,
// this function will permanently clear the data of the instance

  global $DB;

  if (! $registration = $DB->get_record("registration", array('id'=>$id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("registration_submissions", array('registration' => $registration->id))) {
        $result = false;
    }

    return $result;
}

function registration_refresh_events($courseid = 0) {
// This standard function will check all instances of this module
// and make sure there are up-to-date events created for each of them.
// If courseid = 0, then every registration event in the site is checked, else
// only registration events belonging to the course specified are checked.
// This function is used, in its new format, by restore_refresh_events()

  global $DB;

  if ($courseid == 0) {
    if (! $registrations = $DB->get_records("registration")) {
      return true;
    }
  } else {
    if (! $registrations = $DB->get_records("registration",array("course"=>$courseid))) {
      return true;
    }
  }
  $moduleid = $DB->get_field('modules', 'id',array('name'=>'registration'));

  foreach ($registrations as $registration) {
    $event = new stdClass();
    $event->name          = addslashes($registration->name);
    $event->description   = addslashes($registration->intro);
    $event->allowqueue    = $registration->allowqueue;
    $event->timestart     = $registration->timedue;
    $event->timeavailable = $registration->timeavailable;

    if ($event->id = $DB->get_field('event', 'id', array('modulename'=>'registration', 'instance'=>$registration->id))) {
        //update_event($event);
        $calendarevent = calendar_event::load($event->id);
        $calendarevent->update($event);

    } else {
      $event->courseid    = $registration->course;
      $event->groupid     = 0;
      $event->userid      = 0;
      $event->modulename  = 'registration';
      $event->instance    = $registration->id;
      $event->eventtype   = 'due';
      $event->timeduration = 0;
      $event->visible     = $DB->get_field('course_modules', 'visible', array('module'=>$moduleid, 'instance'=>$registration->id));
      //add_event($event);
      calendar_event::create($event);
    }

  }
  return true;
}


function registration_user_outline($course, $user, $mod, $registration) {
    if ($submission = registration_get_submission($registration, $user)) {

        if ($submission->grade) {
            $result->info = get_string("grade").": $submission->grade";
        }
        $result->time = $submission->timemodified;
        return $result;
    }
    return NULL;
}

function registration_user_complete($course, $user, $mod, $registration) {
    if ($submission = registration_get_submission($registration, $user)) {
        if ($basedir = registration_file_area($registration, $user)) {
            if ($files = get_directory_list($basedir)) {
                $countfiles = count($files)." ".get_string("uploadedfiles", "registration");
                foreach ($files as $file) {
                    $countfiles .= "; $file";
                }
            }
        }

        print_simple_box_start();
        echo "<p><font size=1>";
        echo get_string("lastmodified").": ";
        echo userdate($submission->timemodified);
        echo registration_print_difference($registration->timedue - $submission->timemodified);
        echo "</font></p>";

        registration_print_user_files($registration, $user);

        echo "<br />";

        if (empty($submission->timemarked)) {
            print_string("notgradedyet", "registration");
        } else {
            registration_print_feedback($course, $submission, $registration);
        }

        print_simple_box_end();

    } else {
        print_string("notsubmittedyet", "registration");
    }
}


function registration_cron () {
// Function to be run periodically according to the moodle cron
// Finds all registration notifications that have yet to be mailed out, and mails them

    global $CFG, $DB, $USER;

    /// Notices older than 1 day will not be mailed.  This is to avoid the problem where
    /// cron has not been running for a long time, and then suddenly people are flooded
    /// with mail from the past few weeks or months

    $timenow   = time();
    $endtime   = $timenow - $CFG->maxeditingtime;
    $starttime = $endtime - 24 * 3600;   /// One day earlier

    if ($submissions = registration_get_unmailed_submissions($starttime, $endtime)) {

        foreach ($submissions as $key => $submission) {
            if (! $DB->set_field("registration_submissions", "mailed", "1", array("id" => $submission->id))) {
                echo "Could not update the mailed field for id $submission->id.  Not mailed.\n";
                unset($submissions[$key]);
            }
        }

        $timenow = time();

        foreach ($submissions as $submission) {

            echo "Processing registration submission $submission->id\n";

            if (! $user = $DB->get_record("user", array('id'=>$submission->userid))) {
                echo "Could not find user $post->userid\n";
                continue;
            }

            $USER->lang = $user->lang;

            if (! $course = $DB->get_record("course", array('id'=>$submission->course))) {
                echo "Could not find course $submission->course\n";
                continue;
            }

            if (!has_capability('moodle/course:view', get_context_instance(CONTEXT_COURSE, $$submission->course))) {
                echo fullname($user)." not an active participant in $course->shortname\n";
                continue;
            }

            if (! $teacher = $DB->get_record("user", array('id'=>$submission->teacher))) {
                echo "Could not find teacher $submission->teacher\n";
                continue;
            }

            if (! $mod = get_coursemodule_from_instance("registration", $submission->registration, $course->id)) {
                echo "Could not find course module for registration id $submission->registration\n";
                continue;
            }

            if (! $mod->visible) {    /// Hold mail notification for hidden registrations until later
                continue;
            }

            $strregistrations = get_string("modulenameplural", "registration");
            $strregistration  = get_string("modulename", "registration");

            unset($registrationinfo);
            $registrationinfo->teacher = fullname($teacher);
            $registrationinfo->registration = "$submission->name";
            $registrationinfo->url = "$CFG->wwwroot/mod/registration/view.php?id=$mod->id";

            $postsubject = "$course->shortname: $strregistrations: $submission->name";
            $posttext  = "$course->shortname -> $strregistrations -> $submission->name\n";
            $posttext .= "---------------------------------------------------------------------\n";
            $posttext .= get_string("registrationmail", "registration", $registrationinfo);
            $posttext .= "---------------------------------------------------------------------\n";

            if ($user->mailformat == 1) {  // HTML
                $posthtml = "<p><font face=\"sans-serif\">".
                "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->".
                "<a href=\"$CFG->wwwroot/mod/registration/index.php?id=$course->id\">$strregistrations</a> ->".
                "<a href=\"$CFG->wwwroot/mod/registration/view.php?id=$mod->id\">$submission->name</a></font></p>";
                $posthtml .= "<hr><font face=\"sans-serif\">";
                $posthtml .= "<p>".get_string("registrationmailhtml", "registration", $registrationinfo)."</p>";
                $posthtml .= "</font><hr>";
            } else {
                $posthtml = "";
            }

            if (! email_to_user($user, $teacher, $postsubject, $posttext, $posthtml)) {
                echo "Error: registration cron: Could not send out mail for id $submission->id to user $user->id ($user->email)\n";
            }
        }
    }

    return true;
}

function registration_print_recent_activity($course, $viewfullnames, $timestart) {
  global $CFG, $DB;

    $content = false;
    $registrations = NULL;

    if (!$logs = $DB->get_records_select("log", "time > '$timestart' AND ".
                                           "course = '$course->id' AND ".
                                           "module = 'registration' AND ".
					 "action = 'upload' ", NULL, "time ASC")) {
        return false;
    }

    foreach ($logs as $log) {
        //Create a temp valid module structure (course,id)
        $tempmod->course = $log->course;
        $tempmod->id = $log->info;
        //Obtain the visible property from the instance
        $modvisible = instance_is_visible($log->module,$tempmod);

        //Only if the mod is visible
        if ($modvisible) {
            $registrations[$log->info] = registration_log_info($log);
            $registrations[$log->info]->time = $log->time;
            $registrations[$log->info]->url  = $log->url;
        }
    }

    if ($registrations) {
        $strftimerecent = get_string("strftimerecent");
        $content = true;
        print_headline(get_string("newsubmissions", "registration").":");
        foreach ($registrations as $registration) {
            $date = userdate($registration->time, $strftimerecent);
            echo "<p><font size=1>$date - ".fullname($registration)."<br />";
            echo "\"<a href=\"$CFG->wwwroot/mod/registration/$registration->url\">";
            echo "$registration->name";
            echo "</a>\"</font></p>";
        }
    }

    return $content;
}

function registration_grades($registrationid) {
/// Must return an array of grades, indexed by user, and a max grade.

  global $DB;

  if (!$registration = $DB->get_record("registration", array('id'=>$registrationid))) {
        return NULL;
    }

    $grades = $DB->get_records_menu("registration_submissions", "registration",
                               $registration->id, "", "userid,grade");

    if ($registration->grade >= 0) {
        $return->grades = $grades;
        $return->maxgrade = $registration->grade;

    } else {
        $scaleid = - ($registration->grade);
        if ($scale = $DB->get_record("scale", array('id'=>$scaleid))) {
            $scalegrades = make_menu_from_list($scale->scale);
            if ($grades) {
                foreach ($grades as $key => $grade) {
                    $grades[$key] = $scalegrades[$grade];
                }
            }
        }
        $return->grades = $grades;
        $return->maxgrade = "";
    }

    return $return;
}

function registration_get_participants($registrationid) {
//Returns the users with data in one registration
//(users with records in registration_submissions, students)
////(users with records in registration_submissions, students and teachers)

    global $CFG, $DB;

    //Get students
    $students = $DB->get_records_sql("SELECT DISTINCT u.*
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}registration_submissions a
                                 WHERE a.registration = '$registrationid' and
                                       u.id = a.userid");
    //Get teachers
    //    $teachers = $DB->get_records_sql("SELECT DISTINCT u.*
    //                                 FROM {$CFG->prefix}user u,
    //                                      {$CFG->prefix}registration_submissions a
    //                                 WHERE a.registration = '$registrationid' and
    //                                       u.id = a.teacher");

    //Add teachers to students
    //    if ($teachers) {
    //        foreach ($teachers as $teacher) {
    //            $students[$teacher->id] = $teacher;
    //        }
    //    }
    //Return students array (it contains an array of unique users)
    return ($students);
}

function registration_scale_used($registrationid,$scaleid) {
//This function returns if a scale is being used by one registration

    global $DB;

    $return = false;

    $rec = $DB->get_record("registration",array('id'=>$registrationid,'grade'=> -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Returns true if the scale is in use in the system.
 *
 * @param $scaleid int The scale to be counted.
 * @return boolean
 */
function registration_scale_used_anywhere($scaleid) {
  global $DB;
  return $DB->record_exists("registration",array('grade'=> -$scaleid ));
}

/// SQL STATEMENTS //////////////////////////////////////////////////////////////////

function registration_log_info($log) {
  global $CFG, $DB;
    return $DB->get_record_sql("SELECT a.name, u.firstname, u.lastname
                             FROM {$CFG->prefix}registration a,
                                  {$CFG->prefix}user u
                            WHERE a.id = '$log->info'
                              AND u.id = '$log->userid'");
}

function registration_count_submissions($registration) {
/// Return all registration submissions
  global $CFG, $DB;

    return $DB->count_records_sql("SELECT COUNT(*)
                                  FROM {$CFG->prefix}registration_submissions a
                                 WHERE a.registration = '$registration->id'
                                   AND a.timemodified > 0");
}

function registration_get_all_submissions($registration, $sort="", $dir="DESC") {
/// Return all registration submissions by ENROLLED students (even empty)
  global $CFG, $DB;

    if ($sort == "lastname" or $sort == "firstname") {
        $sort = "u.$sort $dir";
    } else if (empty($sort)) {
        $sort = "a.timemodified DESC";
    } else {
        $sort = "a.$sort $dir";
    }

    $select = "s.course = '$registration->course' AND";
    $site = get_site();
    if ($registration->course == $site->id) {
        $select = '';
    }
    return $DB->get_records_sql("SELECT a.*
                              FROM {$CFG->prefix}registration_submissions a
                              LEFT JOIN {$CFG->prefix}registration s ON s.id=a.registration
                              LEFT JOIN {$CFG->prefix}user u ON u.id = a.userid
                              WHERE $select a.registration = '$registration->id'
                          ORDER BY $sort");
}

function registration_get_users_done($registration) {
/// Return list of users who have done an registration
  global $CFG, $DB;

    $select = "s.course = '$registration->course' AND";
    $site = get_site();
    if ($registration->course == $site->id) {
        $select = '';
    }
    return $DB->get_records_sql("SELECT u.*
                              FROM {$CFG->prefix}user u,
                                   {$CFG->prefix}registration s,
                                   {$CFG->prefix}registration_submissions a
                             WHERE $select u.id = a.userid
                               AND a.registration = '$registration->id'
                          ORDER BY a.timemodified DESC");
    /*    return $DB->get_records_sql("SELECT u.*
                              FROM {$CFG->prefix}user u,
                                   {$CFG->prefix}user_students s,
                                   {$CFG->prefix}registration_submissions a
                             WHERE $select s.userid = u.id
                               AND u.id = a.userid
                               AND a.registration = '$registration->id'
                          ORDER BY a.timemodified DESC");
    */
}

function registration_get_unmailed_submissions($starttime, $endtime) {
/// Return list of marked submissions that have not been mailed out for currently enrolled students
  global $CFG, $DB;
    return $DB->get_records_sql("SELECT s.*, a.course, a.name
                              FROM {$CFG->prefix}registration_submissions s,
                                   {$CFG->prefix}registration a
                             WHERE s.mailed = 0
                               AND s.timemarked <= $endtime
                               AND s.timemarked >= $starttime
                               AND s.registration = a.id");
    /*    return $DB->get_records_sql("SELECT s.*, a.course, a.name
                              FROM {$CFG->prefix}registration_submissions s,
                                   {$CFG->prefix}registration a,
                                   {$CFG->prefix}user_students us
                             WHERE s.mailed = 0
                               AND s.timemarked <= $endtime
                               AND s.timemarked >= $starttime
                               AND s.registration = a.id
                               AND s.userid = us.userid
                               AND a.course = us.course");
    */
}


//////////////////////////////////////////////////////////////////////////////////////

function registration_file_area_name($registration, $user) {
//  Creates a directory file name, suitable for make_upload_directory()
    global $CFG;

    return "$registration->course/$CFG->moddata/registration/$registration->id/$user->id";
}

function registration_file_area($registration, $user) {
    return make_upload_directory( registration_file_area_name($registration, $user) );
}

function registration_get_submission($registration, $user) {
  global $DB;
  $submission = $DB->get_record("registration_submissions", array('registration'=>$registration->id, 'userid'=>$user->id));
    if (!empty($submission->timemodified)) {
        return $submission;
    }
    return NULL;
}

function registration_print_difference($time) {
    if ($time < 0) {
        $timetext = get_string("late", "registration", format_time($time));
        return " (<FONT COLOR=RED>$timetext</FONT>)";
    } else {
        $timetext = get_string("early", "registration", format_time($time));
        return " ($timetext)";
    }
}

function registration_print_submission($registration, $user, $submission, $grades) {
  global $THEME, $OUTPUT, $USER;

    echo "\n<TABLE BORDER=1 CELLSPACING=0 valign=top cellpadding=10 align=center>";

    echo "\n<TR>";
    echo "\n<TD ROWSPAN=2 WIDTH=35 VALIGN=TOP>";
    echo $OUTPUT->user_picture($user, array('popup'=>true));
    echo "</TD>";
    echo "<TD NOWRAP>".fullname($user, true);
    if ($submission->timemodified) {
        echo "&nbsp;&nbsp;<FONT SIZE=1>".get_string("lastmodified").": ";
        echo userdate($submission->timemodified);
        echo registration_print_difference($registration->timedue - $submission->timemodified);
        echo "</FONT>";
    }
    echo "</TR>";

    echo "\n<TR><TD>";
    if ($submission->timemodified) {
        registration_print_user_files($registration, $user);
    } else {
        print_string("notsubmittedyet", "registration");
    }
    echo "</TD></TR>";

    echo "\n<TR>";
    echo "<TD WIDTH=35 VALIGN=TOP>";
    if (!$submission->teacher) {
        $submission->teacher = $USER->id;
    }
    if ($submission->timemodified > $submission->timemarked) {
        echo "<TD>";
    } else {
        echo "<TD>";
    }
    if (!$submission->grade and !$submission->timemarked) {
        $submission->grade = -1;   /// Hack to stop zero being selected on the menu below (so it shows 'no grade')
    }
    echo get_string("feedback", "registration").":";
    echo html_writer::select($grades, "g$submission->id", $submission->grade, get_string("nograde"));
    //    choose_from_menu($grades, "g$submission->id", $submission->grade, get_string("nograde"));
    if ($submission->timemarked) {
        echo "&nbsp;&nbsp;<FONT SIZE=1>".userdate($submission->timemarked)."</FONT>";
    }
    echo "<BR><TEXTAREA NAME=\"c$submission->id\" ROWS=6 COLS=60 WRAP=virtual>";
    p($submission->comment);
    echo "</TEXTAREA><BR>";
    echo "</TD></TR>";

    echo "</TABLE><BR CLEAR=ALL>\n";
}

function registration_print_feedback($course, $submission, $registration) {
    global $CFG, $DB, $THEME, $RATING;

    if (! $teacher = $DB->get_record("user", array('id'=>$submission->teacher))) {
      print_error("weirderror","registration");
    }

    echo "\n<TABLE BORDER=0 CELLPADDING=1 CELLSPACING=1 ALIGN=CENTER><TR><TD BGCOLOR=#888888>";
    echo "\n<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 VALIGN=TOP>";

    echo "\n<TR>";
    echo "\n<TD ROWSPAN=3 WIDTH=35 VALIGN=TOP>";
    print_user_picture($teacher->id, $course->id, $teacher->picture);
    echo "</TD>";
    echo "<TD NOWRAP WIDTH=100%>".fullname($teacher);
    echo "&nbsp;&nbsp;<FONT SIZE=2><I>".userdate($submission->timemarked)."</I>";
    echo "</TR>";

    echo "\n<TR><TD WIDTH=100%>";

    echo "<P ALIGN=RIGHT><FONT SIZE=-1><I>";
    if ($registration->grade) {
        if ($submission->grade or $submission->timemarked) {
            echo get_string("grade").": $submission->grade";
        } else {
            echo get_string("nograde");
        }
    }
    echo "</I></FONT></P>";

    echo text_to_html($submission->comment);
    echo "</TD></TR></TABLE>";
    echo "</TD></TR></TABLE>";
}


function registration_print_user_files($registration, $user) {
// Arguments are objects

    global $CFG;

    $filearea = registration_file_area_name($registration, $user);

    if ($basedir = registration_file_area($registration, $user)) {
        if ($files = get_directory_list($basedir)) {
            foreach ($files as $file) {
                $icon = mimeinfo("icon", $file);
                if ($CFG->slasharguments) {
                    $ffurl = "file.php/$filearea/$file";
                } else {
                    $ffurl = "file.php?file=/$filearea/$file";
                }

                echo "<img src=\"$CFG->pixpath/f/$icon\" height=16 width=16 border=0 alt=\"file\">";
                echo "&nbsp;<a target=\"uploadedfile\" href=\"$CFG->wwwroot/$ffurl\">$file</a>";
                echo "<br />";
            }
        }
    }
}

function registration_delete_user_files($registration, $user, $exception) {
// Deletes all the user files in the registration area for a user
// EXCEPT for any file named $exception

    if ($basedir = registration_file_area($registration, $user)) {
        if ($files = get_directory_list($basedir)) {
            foreach ($files as $file) {
                if ($file != $exception) {
                    unlink("$basedir/$file");
                    notify(get_string("existingfiledeleted", "registration", $file));
                }
            }
        }
    }
}

function registration_get_recent_mod_activity(&$activities, &$index, $sincetime, $courseid, $registration="0", $user="", $groupid="")  {
// Returns all registrations since a given time.  If registration is specified then
// this restricts the results

  global $CFG, $DB;

    if ($registration) {
        $registrationselect = " AND cm.id = '$registration'";
    } else {
        $registrationselect = "";
    }
    if ($user) {
        $userselect = " AND u.id = '$user'";
    } else {
        $userselect = "";
    }

    $registrations = $DB->get_records_sql("SELECT asub.*, u.firstname, u.lastname, u.picture, u.id as userid,
                                           a.grade as maxgrade, name, cm.instance, cm.section
                                  FROM {$CFG->prefix}registration_submissions asub,
                                       {$CFG->prefix}user u,
                                       {$CFG->prefix}registration a,
                                       {$CFG->prefix}course_modules cm
                                 WHERE asub.timemodified > '$sincetime'
                                   AND asub.userid = u.id $userselect
                                   AND a.id = asub.registration $registrationselect
                                   AND cm.course = '$courseid'
                                   AND cm.instance = a.id
                                 ORDER BY asub.timemodified ASC");

    if (empty($registrations))
      return;

    foreach ($registrations as $registration) {
        if (empty($groupid) || ismember($groupid, $registration->userid)) {

          $tmpactivity = new Object;

          $tmpactivity->type = "registration";
          $tmpactivity->defaultindex = $index;
          $tmpactivity->instance = $registration->instance;
          $tmpactivity->name = $registration->name;
          $tmpactivity->section = $registration->section;

          $tmpactivity->content->grade = $registration->grade;
          $tmpactivity->content->maxgrade = $registration->maxgrade;
          // $tmpactivity->content->type = $registration->type;

          $tmpactivity->user->userid = $registration->userid;
          $tmpactivity->user->fullname = fullname($registration);
          $tmpactivity->user->picture = $registration->picture;

          $tmpactivity->timestamp = $registration->timemodified;

          $activities[] = $tmpactivity;

          $index++;
        }
    }

    return;
}

function registration_print_recent_mod_activity($activity, $course, $detail=false)  {
    global $CFG, $THEME;

    echo '<table border="0" cellpadding="3" cellspacing="0">';

    echo "<tr><td class=\"forumpostpicture\" width=\"35\" valign=\"top\">";
    print_user_picture($activity->user->userid, $course, $activity->user->picture);
    echo "</td><td width=\"100%\"><font size=2>";


    if ($detail) {
        echo "<img src=\"$CFG->modpixpath/$activity->type/icon.gif\" ".
             "height=16 width=16 alt=\"$activity->type\">  ";
        echo "<a href=\"$CFG->wwwroot/mod/registration/view.php?id=" . $activity->instance . "\">"
             . $activity->name . "</a> - ";

    }

    if (has_capability('moodle/course:viewrecent', get_context_instance(CONTEXT_COURSE, $course))) {
        $grades = "(" .  $activity->content->grade . " / " . $activity->content->maxgrade . ") ";

        $registration->id = $activity->instance;
        $registration->course = $course;
        $user->id = $activity->user->userid;

        echo $grades;
        //        if ($activity->content->type == UPLOADSINGLE) {
        //            $file = registration_get_user_file($registration, $user);
        //            echo "<img src=\"$CFG->pixpath/f/$file->icon\" height=16 width=16 border=0 alt=\"file\">";
        //            echo "&nbsp;<a target=\"uploadedfile\" HREF=\"$CFG->wwwroot/$file->url\">$file->name</A>";
        //        }
        echo "<br>";
    }
    echo "<a href=\"$CFG->wwwroot/user/view.php?id="
         . $activity->user->userid . "&course=$course\">"
         . $activity->user->fullname . "</a> ";

    echo " - " . userdate($activity->timestamp);

    echo "</font></td></tr>";
    echo "</table>";

    return;
}

function registration_get_user_file($registration, $user) {
    global $CFG;

    $tmpfile = "";

    $filearea = registration_file_area_name($registration, $user);

    if ($basedir = registration_file_area($registration, $user)) {
        if ($files = get_directory_list($basedir)) {
            foreach ($files as $file) {
                $icon = mimeinfo("icon", $file);
                if ($CFG->slasharguments) {
                    $ffurl = "file.php/$filearea/$file";
                } else {
                    $ffurl = "file.php?file=/$filearea/$file";
                }
                $tmpfile->url  = $ffurl;
                $tmpfile->name = $file;
                $tmpfile->icon = $icon;
            }
        }
    }
    return $tmpfile;
}

// Function registration_reset_userdata for Moodle 1.9 (I guess) [PASCAL Univ. Paris Ouest]
function registration_reset_userdata($data) {
        return registration_delete_userdata($data);
}

// Updated for Moodle 1.9 (I guess) by [PASCAL Univ. Paris Ouest]
//This function is used by the remove_course_userdata function in moodlelib.
//If this function exists, remove_course_userdata will execute it.
//This function will remove all completeds from the specified registration.
function registration_delete_userdata($data, $showregistration=true) {
  global $CFG, $DB;

   $resetregistrations = array();
   $dropregistrations = array();
   $status = array();

   // Get the course's registrations
   $cr = $DB->get_records('registration', array('course'=>$data->courseid));

        // If you need to clean something
        if (!empty($data->reset_registration_all) || !empty($data->drop_registration_all)) {
                foreach($cr as $key => $value) {
                        if (!empty($data->drop_registration_all)) {
                                $dropregistrations[] = intval($cr[$key]->id);
                        }
                        elseif (!empty($data->reset_registration_all)) {
                                $resetregistrations[] = intval($cr[$key]->id);
                        }
                }

                //reset the selected registrations
                foreach($resetregistrations as $id) {
                        registration_delete_submission_instance($id);
                        $status[] = array('component'=>get_string('modulenameplural', 'registration'), 'item'=>get_string('reset_data', 'registration').' ('.$cr[$id]->name.')', 'error'=>false);
                }

                //drop the selected registrations
                foreach($dropregistrations as $id) {
                        $cm = get_coursemodule_from_instance('registration', $id);
                        registration_delete_instance($id);
                        registration_delete_course_module($cm->id);
                        $status[] = array('component'=>get_string('modulenameplural', 'registration'), 'item'=>get_string('drop_registration', 'registration').' ('.$cr[$id]->name.')', 'error'=>false);
                }
        }

        // Must return a status;
        return $status;
}

// Function registration_reset_course_form_definition for Moodle 1.9 (I guess) [PASCAL Univ. Paris Ouest]
function registration_reset_course_form_definition(&$mform) {

    $mform->addElement('header', 'registrationheader', get_string('modulenameplural', 'registration'));
    $mform->addElement('checkbox', 'reset_registration_all', get_string('reset_data','registration'));
    $mform->addElement('checkbox', 'drop_registration_all', get_string('drop_registration','registration'));

}


// Function registration_reset_course_form_defaults [PASCAL Univ. Paris Ouest]
function registration_reset_course_form_defaults($course) {
    return array('reset_registration_all'=>1, 'drop_registration_all'=>0);
}


// Called by course/reset.php and shows the formdata by coursereset
function registration_reset_course_form($course) {

   global $DB;

   echo get_string('registrationsreset', 'registration'); echo ':<br />';
   if(!$registrations = $DB->get_records('registration', array('course'=>$course->id), 'name')) return;

   foreach($registrations as $registration) {
      echo '<p>';
      echo $registration->name.'<br />';
      print_checkbox(registration_RESETFORM_RESET.$registration->id, 1, true, get_string('reset_data','registration'), '', '');  echo '<br />';
      print_checkbox(registration_RESETFORM_DROP.$registration->id, 1, false, get_string('drop_registration','registration'), '', '');
      echo '</p>';
   }
}

function registration_delete_course_module($id) {
  global $DB;
  if (!$cm = $DB->get_record('course_modules', 'id', $id)) {
    return true;
  }
  return $DB->delete_records('course_modules', array('id'=>$cm->id));
}

function registration_get_position_in_list($registration_id,$userid) {
  global $CFG, $DB;

  $students = $DB->get_records("registration_submissions",array("registration"=>$registration_id),"id","userid");
  if (!$students) { return 0; }
  $i = 0;
  foreach ($students as $data)
    {
      $i++;
      if ($data->userid == $userid) { return $i; }
    }
  return 0;

}

function registration_supports($feature) {
  switch($feature) {
  case FEATURE_GROUPS:                  return false;
  case FEATURE_GROUPINGS:               return false;
  case FEATURE_GROUPMEMBERSONLY:        return false;
  case FEATURE_MOD_INTRO:               return true;
  case FEATURE_GRADE_HAS_GRADE:         return true;
  case FEATURE_GRADE_OUTCOMES:          return false;
  case FEATURE_BACKUP_MOODLE2:          return true;
  case FEATURE_SHOW_DESCRIPTION:        return true;
  case FEATURE_COMPLETION_HAS_RULES:    return false;

  default: return null;
  }
}


?>
