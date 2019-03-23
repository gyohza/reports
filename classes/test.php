<?php
	require "_report.php";
	
	$test = new Report("provs");
	
	echo $test->buildParamTable();
?>