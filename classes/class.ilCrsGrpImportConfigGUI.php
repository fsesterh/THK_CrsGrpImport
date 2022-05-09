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
     * @param $cmd
     */
    public function performCommand($cmd)
    {
        global $DIC;

        $this->dic = $DIC;
        $this->pluginObj = ilCrsGrpImportPlugin::getInstance();

        switch ($cmd) {
            default:
                $this->$cmd();
                break;
        }
    }

    protected function configure() : void
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->dic->language()->txt('settings'));
        $form->setFormAction($this->dic->ctrl()->getFormAction($this, 'saveConfigurationForm'));
        $role = new ilMultiSelectInputGUI($this->getPluginObject()->txt('role_select'), 'default_role_ids');
        $role->setOptions($this->prepareRoleSelection());
        $selected_role = explode(';', $this->dic->settings()->get('crs_grp_import_default_role_ids'));
        if ($selected_role !== null && $selected_role !== false) {
            $role->setValue($selected_role);
        }
        $role->setRequired(false);
        $form->addItem($role);

        $form->addCommandButton('saveConfigurationForm', $this->dic->language()->txt('save'));
        $this->dic->ui()->mainTemplate()->setContent($form->getHTML());
    }

    protected function prepareRoleSelection(): array
    {
        global $DIC;

        $select = [];
        $global_roles = ilUtil::_sortIds(
            $DIC->rbac()->review()->getGlobalRoles(),
            'object_data',
            'title',
            'obj_id'
        );

        foreach ($global_roles as $role_id) {
            $select[$role_id] = ilObject::_lookupTitle($role_id);
        }

        return $select;
    }

    private function saveConfigurationForm()
    {
        try {
            global $DIC;

            $role_ids_post = $DIC->http()->request()->getParsedBody()['default_role_ids'];
            if ($role_ids_post === null) {
                $this->dic->settings()->delete('crs_grp_import_default_role_ids');
            } else {
                $role_ids = implode(';', $role_ids_post);
                $this->dic->settings()->set('crs_grp_import_default_role_ids', $role_ids);
            }

            ilUtil::sendSuccess($this->dic->language()->txt('saved_successfully'), true);
            $this->dic->ctrl()->redirect($this, 'configure');
        } catch (ilException $e) {
            ilUtil::sendFailure($this->dic->language()->txt('form_input_not_valid'));
        }
    }
} 