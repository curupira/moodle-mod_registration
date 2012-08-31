<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/registration/lib.php');

    $settings->add(new admin_setting_configcheckbox('registration_hide_idnumber', get_string('hideidnumber', 'registration'),
                       get_string('confighideidnumber', 'registration'), 0));

    $settings->add(new admin_setting_configtext('registration_maxstudents', get_string('maxstudents', 'registration'),
						get_string('configmaxstudents', 'registration'), 100, PARAM_INT));

}

