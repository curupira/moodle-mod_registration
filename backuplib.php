<?PHP 
    //This php script contains all the stuff to backup/restore
    //registration mods

    //This is the "graphical" structure of the registration mod:
    //
    //                     registration
    //                    (CL,pk->id)             
    //                        |
    //                        |
    //                        |
    //                 registration_submissions 
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

    //This function executes all the backup procedure about this mod
    function registration_backup_mods($bf,$preferences) {

      global $CFG, $DB;

        $status = true;

        //Iterate over registration table
        $registrations = $DB->get_records("registration", array("course" => $preferences->backup_course), "id");
        if ($registrations) {
            foreach ($registrations as $registration) {
                if (backup_mod_selected($preferences,'registration',$registration->id)) {
                    $status = registration_backup_one_mod($bf,$preferences,$registration);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        return $status;  
    }

    function registration_backup_one_mod($bf,$preferences,$registration) {
        
      global $CFG, $DB;
    
        if (is_numeric($registration)) {
	  $registration = $DB->get_record('registration',array('id'=>$registration));
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print registration data
        fwrite ($bf,full_tag("ID",4,false,$registration->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"registration"));
        fwrite ($bf,full_tag("NAME",4,false,$registration->name));
        fwrite ($bf,full_tag("INTRO",4,false,$registration->intro));
        fwrite ($bf,full_tag("NUMBER",4,false,$registration->number));
        fwrite ($bf,full_tag("ROOM",4,false,$registration->room));
        fwrite ($bf,full_tag("TIMEDUE",4,false,$registration->timedue));
        fwrite ($bf,full_tag("TIMEAVAILABLE",4,false,$registration->timeavailable));
        fwrite ($bf,full_tag("GRADE",4,false,$registration->grade));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$registration->timemodified));
        fwrite ($bf,full_tag("ALLOWQUEUE",4,false,$registration->allowqueue));












        //if we've selected to backup users info, then execute backup_registration_submisions and
        //backup_registration_files_instance
        if (backup_userdata_selected($preferences,'registration',$registration->id)) {
            $status = backup_registration_submissions($bf,$preferences,$registration->id);
            if ($status) {
                $status = backup_registration_files_instance($bf,$preferences,$registration->id);
            }
        }
        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;  
    }

    //Backup registration_submissions contents (executed from registration_backup_mods)
    function backup_registration_submissions ($bf,$preferences,$registration) {

      global $CFG, $DB;

        $status = true;

        $registration_submissions = $DB->get_records("registration_submissions",array('registration'=>$registration),"id");
        //If there is submissions
        if ($registration_submissions) {
            //Write start tag
            $status =fwrite ($bf,start_tag("SUBMISSIONS",4,true));
            //Iterate over each submission
            foreach ($registration_submissions as $ass_sub) {
                //Start submission
                $status =fwrite ($bf,start_tag("SUBMISSION",5,true));
                //Print submission contents
                fwrite ($bf,full_tag("ID",6,false,$ass_sub->id));       
                fwrite ($bf,full_tag("USERID",6,false,$ass_sub->userid));       
                fwrite ($bf,full_tag("TIMECREATED",6,false,$ass_sub->timecreated));       
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$ass_sub->timemodified));       
                fwrite ($bf,full_tag("GRADE",6,false,$ass_sub->grade));       
                fwrite ($bf,full_tag("COMMENT",6,false,$ass_sub->comment));       
                fwrite ($bf,full_tag("TEACHER",6,false,$ass_sub->teacher));       
                fwrite ($bf,full_tag("TIMEMARKED",6,false,$ass_sub->timemarked));       
                fwrite ($bf,full_tag("MAILED",6,false,$ass_sub->mailed));       
                //End submission
                $status =fwrite ($bf,end_tag("SUBMISSION",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("SUBMISSIONS",4,true));
        }
        return $status;
    }

    //Backup registration files because we've selected to backup user info
    //and files are user info's level
    function backup_registration_files($bf,$preferences) {

        global $CFG;
       
        $status = true;

        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        //Now copy the registration dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/registration")) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/registration",
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/registration");
            }
        }

        return $status;

    }

    function backup_registration_files_instance($bf,$preferences,$instanceid) {

        global $CFG;
       
        $status = true;

        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/registration/",true);
        //Now copy the registration dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/registration/".$instanceid)) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/registration/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/registration/".$instanceid);
            }
        }

        return $status;

    } 

    //Return an array of info (name,value)
    function registration_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += registration_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","registration");
        if ($ids = registration_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("submissions","registration");
            if ($ids = registration_submission_ids_by_course ($course)) { 
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

    //Return an array of info (name,value)
    function registration_check_backup_mods_instances($instance,$backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("submissions","registration");
            if ($ids = registration_submission_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function registration_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of registrations
        $buscar="/(".$base."\/mod\/registration\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@REGISTRATIONINDEX*$2@$',$content);

        //Link to registration view by moduleid
        $buscar="/(".$base."\/mod\/registration\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@REGISTRATIONVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of registrations id 
    function registration_ids ($course) {

      global $CFG, $DB;

        return $DB->get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}registration a
                                 WHERE a.course = '$course'");
    }
    
    //Returns an array of registration_submissions id
    function registration_submission_ids_by_course ($course) {

      global $CFG, $DB;

        return $DB->get_records_sql ("SELECT s.id , s.registration
                                 FROM {$CFG->prefix}registration_submissions s,
                                      {$CFG->prefix}registration a
                                 WHERE a.course = '$course' AND
                                       s.registration = a.id");
    }

    //Returns an array of registration_submissions id
    function registration_submission_ids_by_instance ($instanceid) {

      global $CFG, $DB;

        return $DB->get_records_sql ("SELECT s.id , s.registration
                                 FROM {$CFG->prefix}registration_submissions s
                                 WHERE s.registration = $instanceid");
    }
?>
