<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\DI\Container;
use ILIAS\HTTP\Wrapper\WrapperFactory;
use ILIAS\Plugin\CrsGrpImport\Utils\UiUtil;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

require_once __DIR__ . '/../vendor/autoload.php';

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
    public ilCrsGrpImportPlugin $plugin_obj;
    private readonly ilCtrlInterface $ctrl;
    private readonly ilLanguage $lng;
    private readonly ilGlobalTemplateInterface $main_tpl;
    private readonly Renderer $ui_renderer;
    private readonly Factory $ui_factory;
    private readonly UiUtil $ui_util;
    private readonly ILIAS\Refinery\Factory $refinery;
    private readonly WrapperFactory $http_wrapper;

    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->ui_util = new UiUtil();
        $this->http_wrapper = $DIC->http()->wrapper();
        $this->refinery = $DIC->refinery();
    }

    private function saveConfigurationForm(): void
    {
        try {
            $local_role_ids_post = $this->http_wrapper->post()->retrieve(
                'default_local_role_ids',
                $this->refinery->byTrying([
                    $this->refinery->kindlyTo()->string(),
                    $this->refinery->always('')
                ])
            );
            $local_role_ids_post = str_replace(' ', '', $local_role_ids_post);
            if ($local_role_ids_post === '') {
                $this->dic->settings()->delete('crs_grp_import_default_local_role_ids');
            } else {
                $this->dic->settings()->set('crs_grp_import_default_local_role_ids', $local_role_ids_post);
            }

            $this->ui_util->sendSuccess($this->dic->language()->txt('saved_successfully'), true);
            $this->dic->ctrl()->redirect($this, 'configure');
        } catch (ilException) {
            $this->ui_util->sendFailure($this->dic->language()->txt('form_input_not_valid'));
        }
    }

    protected function configure(): void
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->dic->language()->txt('settings'));
        $form->setFormAction($this->dic->ctrl()->getFormAction($this, 'saveConfigurationForm'));

        $role = new ilTextInputGUI($this->getPluginObject()->txt('role_select'), 'default_local_role_ids');
        $selected_role = $this->dic->settings()->get('crs_grp_import_default_local_role_ids', '');
        $role->setValue($selected_role);
        $role->setInfo($this->getPluginObject()->txt('role_select_info'));
        $role->setRequired(false);
        $form->addItem($role);
        $form->addCommandButton('saveConfigurationForm', $this->dic->language()->txt('save'));
        $this->main_tpl->setContent($form->getHTML());
    }

    public function performCommand(string $cmd): void
    {
        global $DIC;

        $this->dic = $DIC;
        $this->plugin_obj = ilCrsGrpImportPlugin::getInstance();

        $this->$cmd();
    }
}
