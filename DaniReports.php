<?php

	$dir = scandir("queries/");
	$exts = array('json');
	$jsons = array();
	
	foreach ($dir as $v) {
	
		if (preg_match("/^\.{1,2}$/", $v)) continue;
		
		$ext = array_reverse(explode( '.', $v))[0];

		if (in_array($ext, $exts)) array_push($jsons, substr($v, 0, strlen($v) - strlen($ext) - 1));
	}
	
	var_dump($jsons);
	