<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\UI\Table\Data;

use ILIAS\Plugin\CrsGrpImport\UI\Table\Data;

/**
 * Class DatabaseProvider
 * @package ILIAS\Plugin\CrsGrpImport\UI\Table\Data
 * @author Michael Jansen <mjansen@databay.de>
 */
abstract class DatabaseProvider implements Data\Provider
{
	/**
	 * @var \ilDBInterface
	 */
	protected $db;

	/**
	 * @param \ilDBInterface $db
	 */
	public function __construct(\ilDBInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * @inheritdoc
	 */
	public function getList(array $params, array $filter)
	{
		$data = [
			'items' => [],
			'cnt'   => 0
		];

		$select = $this->getSelectPart($params, $filter);
		$where  = $this->getWherePart($params, $filter);
		$from   = $this->getFromPart($params, $filter);
		$order  = $this->getOrderByPart($params, $filter);
		$group  = $this->getGroupByPart($params, $filter);
		$having = $this->getHavingPart($params, $filter);

		if(isset($params['limit']))
		{
			if(!is_numeric($params['limit']))
			{
				throw new \InvalidArgumentException('Please provide a valid numerical limit.');
			}

			if(!isset($params['offset']))
			{
				$params['offset'] = 0;
			}
			else if(!is_numeric($params['offset']))
			{
				throw new \InvalidArgumentException('Please provide a valid numerical offset.');
			}

			$this->db->setLimit($params['limit'], $params['offset']);
		}

		$where = strlen($where) ? 'WHERE ' . $where : '';
		$query = "SELECT {$select} FROM {$from} {$where}";

		if(strlen($group))
		{
			$query .= " GROUP BY {$group}";
		}

		if(strlen($having))
		{
			$query .= " HAVING {$having}";
		}

		if(strlen($order))
		{
			$query .= " ORDER BY {$order}";
		}

		$res = $this->db->query($query);
		while($row = $this->db->fetchAssoc($res))
		{
			$data['items'][] = $row;
		}

		if(isset($params['limit']))
		{
			$cnt_sql     = "SELECT COUNT(*) cnt FROM ({$query}) subquery";
			$row_cnt     = $this->db->fetchAssoc($this->db->query($cnt_sql));
			$data['cnt'] = $row_cnt['cnt'];
		}

		return $data;
	}

	/**
	 * Retrieve the fields list for the SELECT clause.
	 * The getSelectPart() method is called internally to build the SELECT fields list.
	 * @param array $params
	 * @param array $filter
	 * @return string
	 */
	abstract protected function getSelectPart(array $params, array $filter);

	/**
	 * Retrieve the FROM clause rows.
	 * The getFromPart() method is called internally
	 * to build the FROM rows list, including the JOIN(s).
	 * @param array $params
	 * @param array $filter
	 * @return string
	 */
	abstract protected function getFromPart(array $params, array $filter);

	/**
	 * Retrieve the WHERE clause conditions.
	 * The getWherePart() method is called internally
	 * to build the WHERE clause. All conditions should
	 * be concatenated and returned by this method.
	 * @param array $params
	 * @param array $filter
	 * @return string
	 */
	abstract protected function getWherePart(array $params, array $filter);

	/**
	 * @param array $params
	 * @param array $filter
	 * @return string
	 */
	abstract protected function getGroupByPart(array $params, array $filter);

	/**
	 * @param array $params
	 * @param array $filter
	 * @return string
	 * @abstract
	 */
	abstract protected function getHavingPart(array $params, array $filter);

	/**
	 * @param array $params
	 * @param array $filter
	 * @return string
	 */
	abstract protected function getOrderByPart(array $params, array $filter);
}