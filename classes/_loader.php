<?php

set_error_handler(function($num, $msg, $file, $line) {
	echo
		"<script>
			console.log('%cPHP error [$num]:%c $msg\\n\\t\\t\\t%c└─►%c on line %c$line%c in %c" . addslashes($file) . "%c.',
				'background-color: #8892be; padding: 2px 24px; border: 1px solid #4f5b93; font-weight: bold; color: black;',
				'font-weight: normal; font-style: italic;',
				'font-weight: bold; color: #4f5b93;', 'font-weight: normal; color: black;',
				'font-weight: bold; color: red;', 'font-weight: normal; color: black;',
				'font-weight: bold; color: red;', 'font-weight: normal; color: black;');
		</script>";
});

spl_autoload_register(function($class) {
	require_once dirname(__FILE__) . "/$class.php";
});