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

if (! $registration = $DB->get_record("registration", array('id'=>$id)))
  print_error("courseincorrect","registration");
if (! $course = $DB->get_record("course", array('id'=>$registration->course)))
  print_error("coursemisconfigured","registration");
if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id))
  print_error("courseidincorrect","registration");

require_course_login($course);
$context=get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('mod/registration:viewlist', $context);
$ismyteacher = has_capability('mod/registration:grade', $context);
$ismystudent = has_capability('mod/registration:view', $context);

if (!empty($CFG->registration_hide_idnumber))
  $version = 2;

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

$table = new html_table();
$table->attributes['style']="margin-left:auto; margin-right:auto;";

$table->head[] = $strorder;
$table->align[] = "center";

if (empty($CFG->registration_hide_idnumber) && $version<2)
  {
    $table->head[] = $stridnumber;
    $table->align[] = "center";
  }

if ($version>0)
  {
    $table->head[] = $strfirstname;
    $table->head[] = $strlastname;
  }

if($ismyteacher && $registration->grade)
  {
    $table->head[] = $strpoints;
    $table->align[] = "center";
  }
$table->head[] =$strnote;
$table->align[] = "left";

$grades = make_grades_menu($registration->grade);

$students = $DB->get_records("registration_submissions",array('registration'=>$cm->instance));
$i = 0;
if (!$students) $students = array();
foreach ($students as $data)
{
  $person = $DB->get_record("user",array('id'=>$data->userid));

  $line = array();
  $line[] = ++$i;

  if (empty($CFG->registration_hide_idnumber) && $version<2)
    {
      $line[] = $person->idnumber;
    }

  if ($version>0)
    {
      $line[] = $person->firstname;
      $line[] = $person->lastname;
    }

  if($ismyteacher && $registration->grade)
    {
      $line[] = $grades[$data->grade];
    }
  $line[] = $data->comment;

  $table->data[] =$line;

}

echo html_writer::table($table);

echo "
</body>
</html>
";
?>
