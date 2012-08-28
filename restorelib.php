<?PHP //$Id: restorelib.php,v 1.6 2004/02/15 21:18:52 stronk7 Exp $
    //This php script contains all the stuff to backup/restore
    //registration mods

    //This is the "graphical" structure of the registration mod:
    //
    //                     registration
    //                    (CL,pk->id)             
    //                        |
    //                        |
    //                        |
    //                 registration_submisions 
    //           (UL,pk->id, fk->registration,files)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function registration_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object   
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the registration record structure
            $registration->course = $restore->course_id;
            $registration->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $registration->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
            $registration->format = backup_todb($info['MOD']['#']['FORMAT']['0']['#']);
            $registration->resubmit = backup_todb($info['MOD']['#']['RESUBMIT']['0']['#']);
            $registration->type = backup_todb($info['MOD']['#']['TYPE']['0']['#']);
            $registration->maxbytes = backup_todb($info['MOD']['#']['MAXBYTES']['0']['#']);
            $registration->number = backup_todb($info['MOD']['#']['NUMBER']['0']['#']);
            $registration->room = backup_todb($info['MOD']['#']['ROOM']['0']['#']);
            $registration->timedue = backup_todb($info['MOD']['#']['TIMEDUE']['0']['#']);
            $registration->grade = backup_todb($info['MOD']['#']['GRADE']['0']['#']);
            $registration->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);
            $registration->timeavailable = backup_todb($info['MOD']['#']['TIMEAVAILABLE']['0']['#']);
            $registration->allowqueue = backup_todb($info['MOD']['#']['ALLOWQUEUE']['0']['#']);

            //We have to recode the grade field if it is <0 (scale)
            if ($registration->grade < 0) {
                $scale = backup_getid($restore->backup_unique_code,"scale",abs($registration->grade));        
                if ($scale) {
                    $registration->grade = -($scale->new_id);       
                }
            }








            
            //The structure is equal to the db, so insert the registration
            $newid = insert_record ("registration",$registration);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","registration")." \"".format_string(stripslashes($registration->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'registration',$mod->id)) { 
                    //Restore assignmet_submissions
                    $status = registration_submissions_restore_mods ($mod->id, $newid,$info,$restore);
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    //This function restores the registration_submissions
    function registration_submissions_restore_mods($old_registration_id, $new_registration_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the submissions array 
        $submissions = $info['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION'];

        //Iterate over submissions
        for($i = 0; $i < sizeof($submissions); $i++) {
            $sub_info = $submissions[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);

            //Now, build the registration_SUBMISSIONS record structure
            $submission->registration = $new_registration_id;
            $submission->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
            $submission->timecreated = backup_todb($sub_info['#']['TIMECREATED']['0']['#']);
            $submission->timemodified = backup_todb($sub_info['#']['TIMEMODIFIED']['0']['#']);
            $submission->numfiles = backup_todb($sub_info['#']['NUMFILES']['0']['#']);
            $submission->grade = backup_todb($sub_info['#']['GRADE']['0']['#']);
            $submission->comment = backup_todb($sub_info['#']['COMMENT']['0']['#']);
            $submission->teacher = backup_todb($sub_info['#']['TEACHER']['0']['#']);
            $submission->timemarked = backup_todb($sub_info['#']['TIMEMARKED']['0']['#']);
            $submission->mailed = backup_todb($sub_info['#']['MAILED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$submission->userid);
            if ($user) {
                $submission->userid = $user->new_id;
            }

            //We have to recode the teacher field
            $user = backup_getid($restore->backup_unique_code,"user",$submission->teacher);
            if ($user) {
                $submission->teacher = $user->new_id;
            } 

            //The structure is equal to the db, so insert the registration_submission
            $newid = insert_record ("registration_submissions",$submission);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"registration_submission",$oldid,
                             $newid);

                //Now copy moddata associated files
                $status = registration_restore_files ($old_registration_id, $new_registration_id, 
                                                    $olduserid, $submission->userid, $restore);

            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function copies the registration related info from backup temp dir to course moddata folder,
    //creating it if needed and recoding everything (registration id and user id) 
    function registration_restore_files ($oldassid, $newassid, $olduserid, $newuserid, $restore) {

        global $CFG;

        $status = true;
        $todo = false;
        $moddata_path = "";
        $registration_path = "";
        $temp_path = "";

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir,true);

        //Now, locate course's moddata directory
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata;
   
        //Check it exists and create it
        $status = check_dir_exists($moddata_path,true);

        //Now, locate registration directory
        if ($status) {
            $registration_path = $moddata_path."/registration";
            //Check it exists and create it
            $status = check_dir_exists($registration_path,true);
        }

        //Now locate the temp dir we are gong to restore
        if ($status) {
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/registration/".$oldassid."/".$olduserid;
            //Check it exists
            if (is_dir($temp_path)) {
                $todo = true;
            }
        }

        //If todo, we create the neccesary dirs in course moddata/registration
        if ($status and $todo) {
            //First this registration id
            $this_registration_path = $registration_path."/".$newassid;
            $status = check_dir_exists($this_registration_path,true);
            //Now this user id
            $user_registration_path = $this_registration_path."/".$newuserid;
            //And now, copy temp_path to user_registration_path
            $status = backup_copy_file($temp_path, $user_registration_path); 
        }
       
        return $status;
    }
    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //registration_decode_content_links_caller() function in each module
    //in the restore process
    function registration_decode_content_links ($content,$restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of registrations
                
        $searchstring='/\$@(REGISTRATIONINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(REGISTRATIONINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/registration/index.php?id='.$rec->new_id,$result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/registration/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to registration view by moduleid

        $searchstring='/\$@(REGISTRATIONVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(REGISTRATIONVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/registration/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/registration/view.php?id='.$old_id,$result);
                }
            }
        }

        return $result;
    }

    //This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function registration_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        if ($registrations = get_records_sql ("SELECT a.id, a.intro
                                   FROM {$CFG->prefix}registration a
                                   WHERE a.course = $restore->course_id")) {
            //Iterate over each registration->intro
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($registrations as $registration) {
                //Increment counter
                $i++;
                $content = $registration->intro;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $registration->intro = addslashes($result);
                    $status = update_record("registration",$registration);
                    if ($CFG->debug>7) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }
        return $status;
    }

//This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function registration_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "upload":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?a=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view submission":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update grades":
            if ($log->cmid) {
                //Extract the registration id from the url field                             
                $assid = substr(strrchr($log->url,"="),1);
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$assid);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
