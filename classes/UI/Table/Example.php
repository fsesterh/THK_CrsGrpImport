<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\UI\Table;

use ILIAS\Plugin\CrsGrpImport\UI as Base;

require_once 'Services/Tree/classes/class.ilPathGUI.php';
require_once 'Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';

/**
 * Class Example
 * @package ILIAS\Plugin\CrsGrpImport\Company
 * @author Michael Jansen <mjansen@databay.de>
 */
class Example extends Base\Table
{
	/**
	 * @var \ilCtrl
	 */
	protected $ctrl;

	/**
	 * Table constructor.
	 * @param        $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param string $a_template_context
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = "")
	{
		global $DIC;

		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

		$this->ctrl = $DIC->ctrl(); 

		$this->setFormName('dummy');
		$this->setFormAction($this->ctrl->getFormAction($this->getParentObject(), $a_parent_cmd));
		$this->setDefaultOrderDirection('DESC');
		$this->setDefaultOrderField('title');
		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);

		$this->setTitle($this->getParentObject()->getPluginObject()->txt('table-title'));
		$this->setDescription($this->getParentObject()->getPluginObject()->txt('table-caption'));
	}

	/**
	 * @inheritdoc
	 */
	protected function getTableId()
	{
		return 'dummy_id_4711';
	}

	/**
	 * @inheritdoc
	 */
	protected function prepareRow(array &$row)
	{
		$actions = new \ilAdvancedSelectionListGUI();
		$actions->setId('item_' . $row['id']);
		$actions->setListTitle($this->lng->txt('actions'));

		$actions->addItem(
			$this->getParentObject()->getPluginObject()->txt('edit'),
			'',
			$this->ctrl->getLinkTarget($this->getParentObject(), 'edit')
		);
		$row['actions'] = $actions->getHtml();

		parent::prepareRow($row);
	}

	/**
	 * @inheritdoc
	 */
	protected function getColumnDefinition()
	{
		return array_filter(array(
			1  => array('field' => 'id',  'txt' => $this->lng->txt('id'), 'default' => false, 'optional' => true, 'sortable' => true),
			2  => array('field' => 'title', 'txt' => $this->lng->txt('title'), 'default' => true, 'optional' => false, 'sortable' => true),
			3  => array('field' => 'actions', 'txt' => $this->lng->txt('actions'), 'default' => true, 'optional' => false, 'sortable' => false),
		));
	}
}