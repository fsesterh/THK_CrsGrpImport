<?php
spl_autoload_register(function($class) {
	$path = str_replace("\\", '/', str_replace("ILIAS\\Plugin\\CrsGrpImport\\", '', $class)) . '.php';

	if(file_exists(\ilCrsGrpImportPlugin::getInstance()->getDirectory() . '/classes/' . $path))
	{
		\ilCrsGrpImportPlugin::getInstance()->includeClass($path);
	}
}, true, true);