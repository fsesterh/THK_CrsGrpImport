<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\UI\Table\Data;

/**
 * Interface Provider
 * @package ILIAS\Plugin\CrsGrpImport\UI\Table\Data
 * @author Michael Jansen <mjansen@databay.de>
 */
interface Provider
{
	/**
	 * @param array $params Table parameters like limit or order
	 * @param array $filter Filter settings
	 * @return array ['cnt' => array(), 'items' => array()]
	 */
	public function getList(array $params, array $filter);
}