<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpImport\Utils\UiUtil;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

require_once __DIR__ . '/../vendor/autoload.php';

require_once 'Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * @ilCtrl_Calls      ilCrsGrpImportConfigGUI: ilPropertyFormGUI
 * @ilCtrl_Calls      ilCrsGrpImportConfigGUI: ilExplorerSelectInputGUI
 * @ilCtrl_Calls      ilCrsGrpImportConfigGUI: ilFileSystemGUI
 * @ilCtrl_Calls      ilCrsGrpImportConfigGUI: ilAdministrationGUI
 * @ilCtrl_IsCalledBy ilCrsGrpImportConfigGUI: ilObjComponentSettingsGUI
 */
class ilCrsGrpImportConfigGUI extends ilPluginConfigGUI
{
    protected Container $dic;
    public ilCrsGrpImportPlugin $pluginObj;
    private ilCtrl $ctrl;
    private ilLanguage $lng;
    private ilGlobalTemplateInterface $mainTpl;
    private Renderer $uiRenderer;
    private Factory $uiFactory;
    private UiUtil $uiUtil;

    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->mainTpl = $DIC->ui()->mainTemplate();
        $this->uiFactory = $DIC->ui()->factory();
        $this->uiRenderer = $DIC->ui()->renderer();
        $this->uiUtil = new UiUtil();
    }

    private function saveConfigurationForm(): void
    {
        try {
            global $DIC;

            $local_role_ids_post = $DIC->http()->request()->getParsedBody()['default_local_role_ids'];
            $local_role_ids_post = str_replace(' ', '', $local_role_ids_post);
            if ($local_role_ids_post === '') {
                $this->dic->settings()->delete('crs_grp_import_default_local_role_ids');
            } else {
                $this->dic->settings()->set('crs_grp_import_default_local_role_ids', $local_role_ids_post);
            }

            $this->uiUtil->sendSuccess($this->dic->language()->txt('saved_successfully'), true);
            $this->dic->ctrl()->redirect($this, 'configure');
        } catch (ilException $e) {
            $this->uiUtil->sendFailure($this->dic->language()->txt('form_input_not_valid'));
        }
    }

    protected function configure(): void
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
        $this->mainTpl->setContent($form->getHTML());
    }

    public function performCommand(string $cmd): void
    {
        global $DIC;

        $this->dic = $DIC;
        $this->pluginObj = ilCrsGrpImportPlugin::getInstance();

        $this->$cmd();
    }
}
