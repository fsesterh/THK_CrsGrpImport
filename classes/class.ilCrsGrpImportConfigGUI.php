<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Plugin\CrsGrpImport\Lock\Locker;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

require_once __DIR__ . '/../vendor/autoload.php';

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
    /**
     * @var Locker
     */
    private $lock;
    /**
     * @var ilCtrl
     */
    private $ctrl;
    /**
     * @var ilLanguage
     */
    private $lng;
    /**
     * @var ilGlobalPageTemplate
     */
    private $mainTpl;
    /**
     * @var Renderer
     */
    private $uiRenderer;
    /**
     * @var Factory
     */
    private $uiFactory;

    public function __construct()
    {
        global $DIC;
        $this->lock = $DIC['plugin.crsgrpimport.cronjob.locker'];
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->mainTpl = $DIC->ui()->mainTemplate();
        $this->uiFactory = $DIC->ui()->factory();
        $this->uiRenderer = $DIC->ui()->renderer();
    }

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

        $content = "";

        if ($this->lock->isLocked()) {
            $releaseLockButton = $this->uiFactory->button()->standard(
                $this->getPluginObject()->txt('lock.release'),
                $this->ctrl->getLinkTarget($this, 'confirmReleaseLock')
            );
            ilUtil::sendInfo($this->getPluginObject()->txt('lock.locked'));
            $content = $this->uiRenderer->render($releaseLockButton);
        }

        $content .= $form->getHTML();
        $this->mainTpl->setContent($content);
    }

    protected function performReleaseLock() : void
    {
        if ($this->lock->isLocked()) {
            $this->lock->releaseLock();
            ilUtil::sendSuccess($this->getPluginObject()->txt('lock.released'), true);
        }

        $this->ctrl->redirect($this, 'configure');
    }

    public function confirmReleaseLock() : void
    {
        $confirmation = new ilConfirmationGUI();
        $confirmation->setFormAction($this->ctrl->getFormAction($this, 'configure'));
        $confirmation->setConfirm($this->lng->txt('confirm'), 'performReleaseLock');
        $confirmation->setCancel($this->lng->txt('cancel'), 'configure');
        $confirmation->setHeaderText($this->getPluginObject()->txt('lock.release.sure'));

        $this->mainTpl->setContent($confirmation->getHTML());
    }

    /**
     * @param $cmd
     */
    public function performCommand($cmd) : void
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
