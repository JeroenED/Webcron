<?php

/* 
 * The MIT License
 *
 * Copyright 2017 Jeroen De Meerleer.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

function job_in_array($id, $jobs) {
    foreach ($jobs as $job) {
        if ($job['jobID'] == $id) return true;
    }

    return false;
}

function load_config_categorized() {
	global $db;

	$allConfig = $db->prepare("SELECT * FROM config ORDER BY category ASC");
	$allConfig->execute();
	$allConfigResult = $allConfig->fetchAll(PDO::FETCH_ASSOC);

	// Separate lines into categories
	$configCategorized = array();
	$count = 0;
	foreach($allConfigResult as $key=>$value) {
	    $configCategorized[$value['category']][$count]['conf'] = $value['conf'];
	    $configCategorized[$value['category']][$count]['value'] = $value['value'];
	    $configCategorized[$value['category']][$count]['label'] = $value['label'];
	    $configCategorized[$value['category']][$count]['description'] = $value['description'];
	    $configCategorized[$value['category']][$count]['type'] = parse_config_type($value['type']);
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

	$config = $db->prepare("SELECT value FROM config WHERE conf = ?");
	$config->execute(array($conf));
	$configResult = $config->fetch(PDO::FETCH_ASSOC);

	return $configResult['value'];

}

function parse_config_type($type) {
    $splittype = explode('(', substr($type, 0, -1));

    $r_var['type'] = $splittype[0];
    $splitargs = explode(',', $splittype[1]);

    switch($r_var['type'])
    {
        case 'number':
            $r_var['args'][] = $splitargs[0] != '-1' ? 'min="' . $splitargs[0] . '"' : '';
            $r_var['args'][] = $splitargs[1] != '-1' ? 'max="' . $splitargs[1] . '"' : '';
            break;
    }
    return $r_var;
}

function clean_database() {
	global $db;

	$oldestrun = time() - (60 * 60 * 24 * get_configvalue('dbclean.expireruns'));

	$stmt = $db->prepare("DELETE FROM runs WHERE timestamp < ?");
	$stmt->execute(array($oldestrun));
}