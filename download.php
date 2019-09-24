<?php

require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/csv_form.php");

$id = required_param("id", PARAM_INT);
$state = optional_param("state", -1, PARAM_INT);

if (! $registration = $DB->get_record("registration", array('id' => $id)))
	throw new moodle_exception("courseincorrect", "mod_registration");
if (! $course = $DB->get_record("course",  array('id'=>$registration->course)))
	throw new moodle_exception("coursemisconfigured", "mod_registration");
if (! $cm = get_coursemodule_from_instance("registration", $registration->id, $course->id))
	throw new moodle_exception("courseidincorrect", "mod_registration");

require_login($course, false, $cm);
$cmcontext = context_module::instance($cm->id);
//require_capability("registration/download", $cmcontext);

$PAGE->set_url(new moodle_url("/mod/registration/download.php", array("id" => $id)));
$PAGE->set_title($cm->name . ": " . get_string("download_results", "mod_registration"));
$PAGE->set_heading(get_string("download_results", "mod_registration"));
$PAGE->navbar->add(get_string("download_results", "mod_registration"));

$form = new mod_registration_csv_form(new moodle_url("#", array("id" => $id)));

if ($form->is_submitted() && $form->is_validated()) {
	if (!$data = $form->get_data()) {
		// Something went wrong, but use default settings instead.
		$data = new stdClass();
		$data->separator = null;
		$data->encoding = null;
	}
	switch ($data->separator) {
		case "semicolon":
			$sep = ";";
			break;
		case "tab":
			$sep = "\t";
			break;
		default:
			$sep = ",";
	}
	$submissions = $DB->get_records("registration_submissions", array("registration" => $id), 'timecreated');
	$csv = get_string("order", "mod_registration") . $sep
	     . get_string("firstname") . $sep
	     . get_string("lastname") . $sep
	     . get_string("note", "mod_registration") . "\n";
	$i = 0;
	foreach ($submissions as $sub) {
		if ($i == $registration->number) {
			$csv .= "-,-,-," . get_string("in_queue", "mod_registration") . ":\n";
		}
		$user = get_complete_user_data("id", $sub->userid);
		$csv .= ++$i . $sep . $user->firstname . $sep . $user->lastname . $sep . $sub->comment . "\n";
	}
	if ($data->encoding === "iso") {
		$csv = utf8_decode($csv);
	}

	\mod_registration\event\download_csv::create(array("context" => $cmcontext, "objectid" => $id))->trigger();
	header("Cache-Control: no-cache, must-revalidate");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=" . $cm->name . ".csv");
	header("Content-Length: " . strlen($csv));
	echo $csv;
} else {
	echo $OUTPUT->header();
	$options = array(0 => get_string("showcategory", "", get_string("modulename", "mod_registration")),
	                 1 => get_string("viewsubmissions", "mod_registration"),
	                 3 => get_string("printversionname", "mod_registration"));
	if (empty($CFG->registration_hide_idnumber)) {
		$options[4] = get_string("printversionid", "mod_registration");
		$options[5] = get_string("printversionidname", "mod_registration");
	}
	echo $OUTPUT->single_select(new moodle_url("/mod/registration/view.php", array("a" => $registration->id)),
	                            "redirect",
	                            $options);
	echo $OUTPUT->heading($registration->name);
	$form->display();
	echo $OUTPUT->footer();
}