<?php

if (file_exists('vendor/autoload.php')) {
	$loader = include 'vendor/autoload.php';
}
if (class_exists('Zend\Loader\AutoloaderFactory')) {
	return;
}else {
	throw new RuntimeException('Unable to load ZF2. Run `php composer.phar install` or define a ZF2_PATH environment variable.');
}