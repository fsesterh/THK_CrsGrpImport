<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\UI;

use ILIAS\Plugin\CrsGrpImport\UI\Table\Data\Provider;

require_once 'Services/Table/classes/class.ilTable2GUI.php';

/**
 * Class Table
 * @package ILIAS\Plugin\CrsGrpImport\UI
 * @author  Michael Jansen <mjansen@databay.de>
 */
abstract class Table extends \ilTable2GUI
{
	/**
	 * @var Provider
	 */
	protected $provider;

	/**
	 * @var array
	 */
	protected $visibleOptionalColumns = [];

	/**
	 * @var array
	 */
	protected $optionalColumns = [];

	/**
	 * @var array
	 */
	protected $optional_filter = [];

	/**
	 * @inheritdoc
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = "")
	{
		$this->setId($this->getTableId());
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

		$this->setRowTemplate($this->getParentObject()->getPluginObject()->getDirectory(). '/templates/tpl.tbl_row.html');

		$columns                      = $this->getColumnDefinition();
		$this->optionalColumns        = (array)$this->getSelectableColumns();
		$this->visibleOptionalColumns = (array)$this->getSelectedColumns();
		foreach($columns as $index => $column)
		{
			if($this->isColumnVisible($index))
			{
				$this->addColumn(
					$column['txt'],
					isset($column['sortable']) && $column['sortable'] ? $column['field'] : ''
				);
			}
		}
	}

	/**
	 * Set the provider to be used for data retrieval.
	 * @param Provider $provider
	 */
	public function setProvider(Provider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * Get the registered provider instance
	 * @return Provider
	 */
	public function getProvider()
	{
		return $this->provider;
	}

	/**
	 * Returns an unique table id used for storage (filter, sorting) purposes
	 * @return string
	 */
	abstract protected function getTableId();

	/**
	 * @throws \ilException
	 */
	final public function populate()
	{
		if($this->getExternalSegmentation() && $this->getExternalSorting())
		{
			$this->determineOffsetAndOrder();
		}
		else if(!$this->getExternalSegmentation() && $this->getExternalSorting())
		{
			$this->determineOffsetAndOrder(true);
		}

		$params = [];
		if($this->getExternalSegmentation())
		{
			$params['limit']  = $this->getLimit();
			$params['offset'] = $this->getOffset();
		}
		if($this->getExternalSorting())
		{
			$params['order_field']     = $this->getOrderField();
			$params['order_direction'] = $this->getOrderDirection();
		}

		$this->determineSelectedFilters();
		$filter = (array)$this->filter;

		foreach($this->optional_filter as $key => $value)
		{
			if($this->isFilterSelected($key))
			{
				$filter[$key] = $value;
			}
		}

		$this->onBeforeDataFetched($params, $filter);
		$data = $this->getProvider()->getList($params, $filter);

		if(!count($data['items']) && $this->getOffset() > 0 && $this->getExternalSegmentation())
		{
			$this->resetOffset();
			if($this->getExternalSegmentation())
			{
				$params['limit']  = $this->getLimit();
				$params['offset'] = $this->getOffset();
			}
			$data = $this->getProvider()->getList($params, $filter);
		}

		$data = $this->preProcessData($data);

		$this->setData($data['items']);
		if($this->getExternalSegmentation())
		{
			$this->setMaxCount($data['cnt']);
		}
	}

	/**
	 * @param array $params
	 * @param array $filter
	 */
	protected function onBeforeDataFetched(&$params, &$filter)
	{
	}

	/**
	 * @param array $row
	 */
	protected function prepareRow(array &$row)
	{
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function preProcessData(array $data)
	{
		return $data;
	}

	/**
	 * Define a final formatting for a cell value
	 * @param mixed $column
	 * @param array $row
	 * @return mixed
	 */
	protected function formatCellValue($column, array $row)
	{
		return trim($row[$column]);
	}

	/**
	 * @return array
	 */
	public function getSelectableColumns()
	{
		$optionalColumns = array_filter($this->getColumnDefinition(), function ($column) {
			return isset($column['optional']) && $column['optional'];
		});

		$columns = array();
		foreach($optionalColumns as $index => $column)
		{
			$columns[$column['field']] = $column;
		}

		return $columns;
	}

	/**
	 * @return array
	 */
	abstract protected function getColumnDefinition();

	/**
	 * @param int $index
	 * @return bool
	 */
	protected function isColumnVisible($index)
	{
		$columnDefinition = $this->getColumnDefinition();
		if(array_key_exists($index, $columnDefinition))
		{
			$column = $columnDefinition[$index];
			if(isset($column['optional']) && !$column['optional'])
			{
				return true;
			}

			if(is_array($this->visibleOptionalColumns) && array_key_exists($column['field'], $this->visibleOptionalColumns))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $row
	 */
	final protected function fillRow($row)
	{
		$this->prepareRow($row);

		foreach($this->getColumnDefinition() as $index => $column)
		{
			if(!$this->isColumnVisible($index))
			{
				continue;
			}

			$this->tpl->setCurrentBlock('column');
			$value = $this->formatCellValue($column['field'], $row);
			if((string)$value === '')
			{
				$this->tpl->touchBlock('column');
			}
			else
			{
				$this->tpl->setVariable('COLUMN_VALUE', $value);
			}

			$this->tpl->parseCurrentBlock();
		}
	}
}