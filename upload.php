<?PHP  // $Id: upload.php,v 1.14 2004/08/21 20:20:53 gustav_delius Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);           // Course Module ID 
//require_variable($id);          // registration ID

    if (!empty($_FILES['newfile'])) {
        $newfile = $_FILES['newfile'];
    }

    if (! $registration = get_record("registration", "id", $id)) {
      print_error("invalidregid","registration");
    }

    if (! $course = get_record("course", "id", $registration->course)) {
      print_error("coursemisconfigured","registration");
    }

    if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id)) {
      print_error("courseidincorrect","registration");
    }

    require_login($course->id);

    add_to_log($course->id, "registration", "upload", "view.php?a=$registration->id", "$registration->id", $cm->id);

    $strregistrations = get_string("modulenameplural", "registration");
    $strregistration  = get_string("modulename", "registration");
    $strupload      = get_string("upload");

    print_header_simple("$registration->name : $strupload", "",
                 "<A HREF=index.php?id=$course->id>$strregistrations</A> -> 
                  <A HREF=\"view.php?a=$registration->id\">$registration->name</A> -> $strupload", 
                  "", "", true);

    if ($submission = get_record("registration_submissions", "registration", $registration->id, "userid", $USER->id)) {
        if ($submission->grade and !$registration->resubmit) {
	  print_error("alreadygraded","registration");
        }
    }

    if (! $dir = registration_file_area($registration, $USER)) {
      print_error("systemuploadfail","registration");
    }

    if (empty($newfile)) {
        notify(get_string("uploadnofilefound", "registration") );

    } else if (is_uploaded_file($newfile['tmp_name']) and $newfile['size'] > 0) {
        if ($newfile['size'] > $registration->maxbytes) {
            notify(get_string("uploadfiletoobig", "registration", $registration->maxbytes));
        } else {
            $newfile_name = clean_filename($newfile['name']);
            if ($newfile_name) {
                if (move_uploaded_file($newfile['tmp_name'], "$dir/$newfile_name")) {
                    chmod("$dir/$newfile_name", $CFG->directorypermissions);
                    registration_delete_user_files($registration, $USER, $newfile_name);
                    if ($submission) {
                        $submission->timemodified = time();
                        $submission->numfiles     = 1;
                        $submission->comment = addslashes($submission->comment);
                        if (update_record("registration_submissions", $submission)) {
                            print_heading(get_string("uploadsuccess", "registration", $newfile_name) );
                        } else {
                            notify(get_string("uploadfailnoupdate", "registration"));
                        }
                    } else {
                        $newsubmission->registration   = $registration->id;
                        $newsubmission->userid       = $USER->id;
                        $newsubmission->timecreated  = time();
                        $newsubmission->timemodified = time();
                        $newsubmission->numfiles     = 1;
                        if (insert_record("registration_submissions", $newsubmission)) {
                            print_heading(get_string("uploadsuccess", "registration", $newfile_name) );
                        } else {
                            notify(get_string("uploadnotregistered", "registration", $newfile_name) );
                        }
                    }
                } else {
                    notify(get_string("uploaderror", "registration") );
                }
            } else {
                notify(get_string("uploadbadname", "registration") );
            }
        }
    } else {
        notify(get_string("uploadnofilefound", "registration") );
    }
    
    print_continue("view.php?a=$registration->id");

    print_footer($course);

?>
