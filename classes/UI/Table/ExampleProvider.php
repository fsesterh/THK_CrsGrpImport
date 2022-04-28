<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\UI\Table;

use ILIAS\Plugin\CrsGrpImport\UI as Base;

/**
 * Class ExampleProvider
 * @package ILIAS\Plugin\CrsGrpImport\Company
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExampleProvider extends Base\Table\Data\DatabaseProvider
{
	/**
	 * @inheritdoc
	 */
	protected function getSelectPart(array $params, array $filter)
	{
		return implode(', ', array(
			'objr.ref_id id',
			'od.*'
		));
	}

	/**
	 * @inheritdoc
	 */
	protected function getFromPart(array $params, array $filter)
	{
		return implode(' ', array(
			'object_data od',
			'INNER JOIN object_reference objr ON objr.obj_id = od.obj_id AND objr.deleted IS NULL',
			'INNER JOIN tree t ON t.child = objr.ref_id AND t.tree = ' . $this->db->quote(1, 'integer')
		));
	}

	/**
	 * @inheritdoc
	 */
	protected function getWherePart(array $params, array $filter)
	{
		return 'od.type = ' . $this->db->quote('cat', 'text');
	}

	/**
	 * @inheritdoc
	 */
	protected function getGroupByPart(array $params, array $filter)
	{
		return '';
	}

	/**
	 * @inheritdoc
	 */
	protected function getHavingPart(array $params, array $filter)
	{
		return '';
	}

	/**
	 * @inheritdoc
	 */
	protected function getOrderByPart(array $params, array $filter)
	{
		if(isset($params['order_field']))
		{
			if(!is_string($params['order_field']))
			{
				throw new \InvalidArgumentException('Please provide a valid order field.');
			}

			if(!in_array($params['order_field'], array('title', 'id')))
			{
				throw new \InvalidArgumentException('Please provide a valid order field.');
			}

			if(!isset($params['order_direction']))
			{
				$params['order_direction'] = 'ASC';
			}
			else if(!in_array(strtolower($params['order_direction']), array('asc', 'desc')))
			{
				throw new \InvalidArgumentException('Please provide a valid order direction.');
			}

			return $params['order_field'] . ' ' . $params['order_direction'];
		}

		return '';
	}
}