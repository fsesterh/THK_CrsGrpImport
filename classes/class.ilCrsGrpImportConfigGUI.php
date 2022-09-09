<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * Class ilCrsGrpImportConfigGUI
 */
class ilCrsGrpImportConfigGUI extends \ilPluginConfigGUI
{
    /**
     * @var \ILIAS\DI\Container
     */
    protected $dic;
    /**
     * @var \ilCrsGrpImportPlugin
     */
    public $pluginObj = null;

    private function saveConfigurationForm()
    {
        try {
            global $DIC;

            $local_role_ids_post = $DIC->http()->request()->getParsedBody()['default_local_role_ids'];
            $local_role_ids_post = str_replace(' ', '', $local_role_ids_post);
            if (strlen($local_role_ids_post) === 0) {
                $this->dic->settings()->delete('crs_grp_import_default_local_role_ids');
            } else {
                $this->dic->settings()->set('crs_grp_import_default_local_role_ids', $local_role_ids_post);
            }

            ilUtil::sendSuccess($this->dic->language()->txt('saved_successfully'), true);
            $this->dic->ctrl()->redirect($this, 'configure');
        } catch (ilException $e) {
            ilUtil::sendFailure($this->dic->language()->txt('form_input_not_valid'));
        }
    }

    protected function configure() : void
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->dic->language()->txt('settings'));
        $form->setFormAction($this->dic->ctrl()->getFormAction($this, 'saveConfigurationForm'));

        $role = new ilTextInputGUI($this->getPluginObject()->txt('role_select'), 'default_local_role_ids');
        $selected_role = $this->dic->settings()->get('crs_grp_import_default_local_role_ids');
        $role->setValue($selected_role);
        $role->setInfo($this->getPluginObject()->txt('role_select_info'));
        $role->setRequired(false);
        $form->addItem($role);
        $form->addCommandButton('saveConfigurationForm', $this->dic->language()->txt('save'));
        $this->dic->ui()->mainTemplate()->setContent($form->getHTML());
    }

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
}
