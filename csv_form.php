<?php

defined("MOODLE_INTERNAL") || die();

require_once($CFG->libdir . "/formslib.php");

class mod_registration_csv_form extends moodleform {

	public function definition() {
		$form = &$this->_form;

		$form->addElement("header", "head1", get_string("settings"));
		$radio = array();
		$radio[] = $form->createElement("radio", "separator", null, get_string("sepcomma", "grades"), "comma");
		$radio[] = $form->createElement("radio", "separator", null, get_string("sepsemicolon", "grades"), "semicolon");
		$radio[] = $form->createElement("radio", "separator", null, get_string("septab", "grades"), "tab");
		$form->addGroup($radio, "separator", get_string("separator", "grades"), "&nbsp;&nbsp;&nbsp;", false);
		$radio = array();
		$radio[] = $form->createElement("radio", "encoding", null, "UTF-8", "utf8");
		$radio[] = $form->createElement("radio", "encoding", null, "ISO-8859-1 (Windows)", "iso");
		$form->addGroup($radio, "encoding", get_string("encoding", "grades"), "&nbsp;&nbsp;&nbsp;", false);

		/* TYPES */
		$types = array("seperator" => PARAM_TEXT,
		               "format" => PARAM_ALPHAEXT);
		$form->setTypes($types);

		/* DEFAULTS */
		$form->setDefault("separator", "comma");
		$form->setDefault("encoding", "utf8");


		$form->closeHeaderBefore("submit");
		$form->addElement("submit", "submit", get_string("download"));
	}
}

?>