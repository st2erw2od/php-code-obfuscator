<?php
	/**
	 * Obfuscate the code of your PHP project
	 */
    require_once 'Obfuscator.php';
    $obfuscator = new Obfuscator();
    $obfuscator->run();
	
    //options: overwrite default settings
    /*
    $obfuscator = new Obfuscator();
    $obfuscator->setSetting('source_dir','./');
    $obfuscator->setSetting('destination_dir','C:/Temp/_obfuscated/');
    $obfuscator->run();
    */
?>