<?php
spl_autoload_register(function ($class) {
	$file = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $class) . '.php';
	
	if(file_exists($file))
		include $file;
});
