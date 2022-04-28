<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\Frontend\Controller;

/**
 * Class Error
 * @package ILIAS\Plugin\CrsGrpImport\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>          
 */
class Error extends Base
{
	/**
	 * @inheritdoc
	 */
	public function getDefaultCommand()
	{
		return 'showCmd';
	}

	/**
	 * @return string
	 */
	public function showCmd()
	{
		\ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt('controller_not_found'));

		return '';
	}
}