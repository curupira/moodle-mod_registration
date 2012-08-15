<?php
function migrate2utf8_registration_name($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if (!$att = get_record('registration', 'id', $recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($att->course);  //Non existing!
        $userlang   = get_main_teacher_lang($att->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($att->name, $fromenc);

        $newatt = new object;
        $newatt->id = $recordid;
        $newatt->name = $result;
        migrate2utf8_update_record('registration',$newatt);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_registration_description($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if (!$att = get_record('registration', 'id', $recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($att->course);  //Non existing!
        $userlang   = get_main_teacher_lang($att->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($att->name, $fromenc);

        $newatt = new object;
        $newatt->id = $recordid;
        $newatt->name = $result;
        migrate2utf8_update_record('registration',$newatt);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_registration_room($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if (!$att = get_record('registration', 'id', $recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($att->course);  //Non existing!
        $userlang   = get_main_teacher_lang($att->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($att->name, $fromenc);

        $newatt = new object;
        $newatt->id = $recordid;
        $newatt->name = $result;
        migrate2utf8_update_record('registration',$newatt);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_registration_submissions_comment($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if (!$att = get_record('registration_submissions ', 'id', $recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($att->course);  //Non existing!
        $userlang   = get_main_teacher_lang($att->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($att->name, $fromenc);

        $newatt = new object;
        $newatt->id = $recordid;
        $newatt->name = $result;
        migrate2utf8_update_record('registration_submissions ',$newatt);
    }
/// And finally, just return the converted field
    return $result;
}
?>
