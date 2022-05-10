<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Plugin\CrsGrpImport\Frontend;

require_once 'Services/UIComponent/classes/class.ilUIHookPluginGUI.php';

/**
 * Class ilCrsGrpImportUIHookGUI
 * @ilCtrl_Calls      ilCrsGrpImportUIHookGUI: ilPropertyFormGUI
 * @ilCtrl_isCalledBy ilCrsGrpImportUIHookGUI: ilObjCourseGUI, ilObjGroupGUI
 * @ilCtrl_isCalledBy ilCrsGrpImportUIHookGUI: ilUIPluginRouterGUI
 */
class ilCrsGrpImportUIHookGUI extends \ilUIHookPluginGUI
{
    /**
     * @var \ILIAS\DI\Container
     */
    protected $dic;

    /**
     * ilCrsGrpImportUIHookGUI constructor.
     */
    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
    }

    /**
     *
     */
    public function executeCommand()
    {
        $this->setPluginObject(ilCrsGrpImportPlugin::getInstance());

        #$this->dic->ui()->mainTemplate()->getStandardTemplate();

        $next_class = $this->dic->ctrl()->getNextClass();
        switch (strtolower($next_class)) {
            default:
                $dispatcher = Frontend\Dispatcher::getInstance($this);
                $dispatcher->setDic($this->dic);

                $response = $dispatcher->dispatch($this->dic->ctrl()->getCmd());
                break;
        }

        $this->dic->ui()->mainTemplate()->setContent($response);
        $this->dic->ui()->mainTemplate()->printToStdOut();

    }

    public function getHTML($a_comp, $a_part, $a_par = array())
    {
        $queryParams = $this->dic->http()->request()->getQueryParams();

        if ($this->isAllowedUser() && array_key_exists('cmd', $queryParams) && $queryParams['cmd'] === 'create' &&
            (
                array_key_exists('new_type', $queryParams) && $queryParams['new_type'] === 'grp' ||
                array_key_exists('new_type', $queryParams) && $queryParams['new_type'] === 'crs'
            )
        ) {
            if (is_array($a_par) && $a_par['tpl_id'] === 'Services/Accordion/tpl.accordion.html' && $a_part === 'template_get') {
                $form = new ilPropertyFormGUI();

                $url = $this->dic->ctrl()->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilCrsGrpImportUIHookGUI'],
                    'Import.showCmd');
                $form->setFormAction($url);
                $file = new ilFileInputGUI( $this->plugin_object->txt('select_file'), 'csv_file');
                $file->setRequired(true);
                $form->addItem($file);
                $parent_ref_id = new ilHiddenInputGUI('parent_ref_id');
                $parent_ref_id->setValue($queryParams['ref_id']);
                $form->addItem($parent_ref_id);
                $form->addCommandButton('#', $this->plugin_object->txt('grp' . "_add"));
                $form->addCommandButton("cancel", $this->dic->language()->txt("cancel"));
                #$acc = new ilAccordionGUI();
                #$acc->addItem('Option 4: Import Group from CSV', $form->getHTML());
                return array('mode' => ilUIHookPluginGUI::APPEND, 'html' => $form->getHTML());
            }
        }
        return array('mode' => ilUIHookPluginGUI::KEEP);
    }

    /**
     * @return bool
     */
    private function isAllowedUser(): bool
    {
        $selected_role = explode(';', $this->dic->settings()->get('crs_grp_import_default_role_ids'));
        $user_roles = $this->dic->rbac()->review()->assignedGlobalRoles($this->dic->user()->id);

        if (count(array_intersect($user_roles, $selected_role)) > 0) {
            return true;
        }
        return $this->dic->rbac()->review()->isAssigned($this->dic->user()->getId(), SYSTEM_ROLE_ID);
    }

} 