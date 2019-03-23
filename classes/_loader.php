<?php

spl_autoload_register(function($class) {
	require_once dirname(__FILE__) . "/$class.php";
});

?>