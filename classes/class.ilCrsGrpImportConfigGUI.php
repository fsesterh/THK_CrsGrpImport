<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Plugin\CrsGrpImport\UI\Table;

require_once \dirname(__FILE__) . '/class.ilCrsGrpImportPlugin.php';
\ilCrsGrpImportPlugin::getInstance()->registerAutoloader();

require_once 'Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * Class ilCrsGrpImportConfigGUI
 */
class ilCrsGrpImportConfigGUI extends \ilPluginConfigGUI
{
	/**
	 * @var \ilCrsGrpImportPlugin
	 */
	public $pluginObj = null;

	/**
	 * @var \ILIAS\DI\Container
	 */
	protected $dic;

	/**
	 * @param string $cmd
	 */
	public function performCommand($cmd)
	{
		global $DIC;

		$this->dic       = $DIC;
		$this->pluginObj = \ilCrsGrpImportPlugin::getInstance();

		switch($cmd)
		{
			default:
				$this->$cmd();
				break;
		}
	}

	/**
	 * @param $command string
	 * @return Table\Example
	 */
	protected function getTable($command)
	{
		$table = new Table\Example($this, $command);
		$table->setProvider(new Table\ExampleProvider($this->dic->database()));

		return $table;
	}
	

	/**
	 *
	 */
	protected function configure()
	{
		$table = $this->getTable(__FUNCTION__);
		$table->populate();

		$this->dic->ui()->mainTemplate()->setContent(
			$table->getHTML()
		);
	}
} 