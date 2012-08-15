<?PHP  // $Id: view.php,v 1.25 2004/08/22 14:38:38 gustav_delius Exp $

require_once("../../config.php");
require_once("lib.php");

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

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
//optional_variable($id);// Course Module ID
$version = optional_param('version', 0, PARAM_INT); // print names?
//optional_variable($a);// registration ID

if (! $registration = get_record("registration", "id", $id))
        error("Course module is incorrect");
if (! $course = get_record("course", "id", $registration->course))
        error("Course is misconfigured");
if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id))
        error("Course Module ID was incorrect");

require_course_login($course);
$context=get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('mod/registration:view', $context);
$ismyteacher = has_capability('mod/registration:grade', $context);
$ismystudent = has_capability('mod/registration:view', $context);

echo '<html> 
<head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <style type="text/css" media="all">
        * {font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 10pt;}
        h1 {font-size: 12pt; text-align: center;};
        .generalbox {border: 1px solid;}
        .generaltableheader {border: 1px solid;}
        .generaltablecell {border: 1px solid;}
        </style>
</head>
<body>
';

$strduedate = userdate($registration->timedue);
echo '<h1>'.$course->fullname.": ".$strduedate.'</h1>
<div style="text-align: center; margin: 10px;">'.$registration->name.'</div>
';

if($ismyteacher && $version)
{
    if ($registration->grade) {
        $table->head  = array ($strorder, $stridnumber, $strfirstname, $strlastname, $strpoints, $strnote);
        $table->align = array ("center", "center", "center", "center", "center", "left");
    }else{
        $table->head  = array ($strorder, $stridnumber, $strfirstname, $strlastname, $strnote);
        $table->align = array ("center", "center", "center", "center", "left");
    }
}
elseif($ismyteacher && !$version)
{
    if ($registration->grade) {
        $table->head  = array ($strorder, $stridnumber." / ".$strfirstname." ".$strlastname,  $strpoints, $strnote);
        $table->align = array ("center", "center", "center", "left");
    }else{
        $table->head  = array ($strorder, $stridnumber." / ".$strfirstname." ".$strlastname,  $strnote);
        $table->align = array ("center", "center", "left");
    }
}
else
{
        $table->head  = array ($strorder, $stridnumber." / ".$strfirstname." ".$strlastname, $strnote);
        $table->align = array ("center", "center", "left");
}

$grades = make_grades_menu($registration->grade);

$students = get_records("registration_submissions","registration",$cm->instance);
$i = 0;
if (!$students) $students = array();
foreach ($students as $data)
{
        $person = get_record("user","id",$data->userid);
        $idnumber_name = ($person->idnumber) ? $person->idnumber : $person->firstname." ".$person->lastname;
        if ($ismyteacher && $version)
          if ($registration->grade) {
                $table->data[] = array (++$i, $person->idnumber, $person->firstname, $person->lastname, $grades[$data->grade], $data->comment);
          }else{
                $table->data[] = array (++$i, $person->idnumber, $person->firstname, $person->lastname, $data->comment);
          }
        elseif ($ismyteacher && !$version)
          if ($registration->grade) {
                $table->data[] = array (++$i, $idnumber_name, $grades[$data->grade], $data->comment);
          }else{
                $table->data[] = array (++$i, $idnumber_name, $data->comment);
          }
        else
                $table->data[] = array (++$i, $idnumber_name, $data->comment);
}

print_table($table);

echo "
</body>
</html>
";
?>
