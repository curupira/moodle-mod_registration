<?PHP // $Id: mysql.php,v 1.10.10.2 2004/11/18 18:58:00 stronk7 Exp $

function registration_upgrade($oldversion) {
// This function does anything necessary to upgrade
// older versions to match current functionality
	$success = true;

    global $CFG;

    if ($oldversion < 2008012900){
		$success = $success && execute_sql("  ALTER TABLE `{$CFG->prefix}registration` CHANGE `number` `number` MEDIUMINT( 5 ) UNSIGNED NOT NULL DEFAULT '0'");
	}

    return $success;
}


?>
