<?php

function job_in_array($id, $jobs) {
    foreach ($jobs as $job) {
        if ($job['jobID'] == $id) return true;
    }

    return false;
}

function load_config_categorized() {
	global $db;

	$allConfig = $db->prepare("SELECT * FROM config ORDER BY category ASC")->execute()->fetchAllAssociative();

	// Separate lines into categories
	$configCategorized = array();
	$count = 0;
	foreach($allConfig as $key=>$value) {
        if ($value['type'] != "hidden") {
            $configCategorized[$value['category']][$count]['conf'] = $value['conf'];
            $configCategorized[$value['category']][$count]['value'] = $value['value'];
            $configCategorized[$value['category']][$count]['label'] = $value['label'];
            $configCategorized[$value['category']][$count]['description'] = $value['description'];
            $configCategorized[$value['category']][$count]['type'] = parse_config_type($value['type']);
        }
	    $count++;
	}

	// into a easy twig array
	$catcount = 0;
	foreach ($configCategorized as $key => $value) {
		$twigarray[$catcount]['name'] = $key;
		$twigarray[$catcount]['conf'] = $value;
		$catcount++;
	}

	return $twigarray;
}

function get_configvalue($conf) {
	global $db;

	$config = $db->prepare("SELECT value FROM config WHERE conf = :conf")->execute([':conf' => $conf])->fetchAssociative();

	return $config['value'];

}

function parse_config_type($type) {
    $splittype = explode('(', explode(')', $type)[0]);

    $r_var['type'] = $splittype[0];
    if (isset($splittype[1])) {
    	$splitargs = explode(',', $splittype[1]);

	    switch($r_var['type'])
	    {
	        case 'number':
	            $r_var['args'][] = isset($splitargs[0]) ? 'min="' . $splitargs[0] . '"' : '';
	            $r_var['args'][] = isset($splitargs[1]) ? 'max="' . $splitargs[1] . '"' : '';
	            break;
	    }
	}
    return $r_var;
}

function clean_database() {
	global $db;

	$oldestrun = time() - (get_configvalue('dbclean.expireruns') * 60 * 60 * 24);

    $db->prepare("DELETE FROM runs WHERE timestamp < :oldestrun")->execute([':oldestrun' => $oldestrun]);

    $db->prepare("UPDATE config SET value = :value WHERE conf = :conf")->execute([':value' => time(), ':conf' => 'dbclean.lastrun']);
}