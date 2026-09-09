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
use ILIAS\Plugin\CrsGrpImport\Frontend;
use ILIAS\Refinery\Factory;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 *
 * @ilCtrl_Calls      ilCrsGrpImportUIHookGUI: ilPropertyFormGUI
 * @ilCtrl_isCalledBy ilCrsGrpImportUIHookGUI: ilObjCourseGUI, ilObjGroupGUI
 * @ilCtrl_isCalledBy ilCrsGrpImportUIHookGUI: ilUIPluginRouterGUI
 */
class ilCrsGrpImportUIHookGUI extends ilUIHookPluginGUI
{
    protected static bool $stop_recursion = false;
    protected static bool $handled = false;

    protected Container $dic;
    private readonly WrapperFactory $http_wrapper;
    private readonly Factory $refinery;

    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
        $this->refinery = $this->dic->refinery();
        $this->http_wrapper = $this->dic->http()->wrapper();
    }

    public function executeCommand(): void
    {
        $this->setPluginObject(ilCrsGrpImportPlugin::getInstance());

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

    public function getHTML(string $a_comp, string $a_part, array $a_par = []): array
    {
        if (!isset($a_par['tpl_id'], $a_par['html']) || !$a_par['tpl_id'] || !$a_par['html']) {
            return $this->uiHookResponse();
        }

        if (self::$stop_recursion === true) {
            return $this->uiHookResponse();
        }

        $get_from_query = fn(string $key, string $type) => $this->http_wrapper->query()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->$type(),
                $this->refinery->always(null)
            ])
        );


        $ref_id = $get_from_query('ref_id', 'int');
        $cmd = $get_from_query('cmd', 'string');
        $new_type = $get_from_query('new_type', 'string');

        if ($cmd !== 'create' || !in_array($new_type, ['grp', 'crs'])) {
            return $this->uiHookResponse();
        }

        if (
            self::$handled === false
            && $a_part === 'template_get'
            && $a_par['tpl_id'] === 'components/ILIAS/UI/src/templates/default/Panel/tpl.standard.html'
            && $this->isAllowedUser()
        ) {
            self::$handled = true;
            self::$stop_recursion = true;

            $ui_factory = $this->dic->ui()->factory();
            $ui_renderer = $this->dic->ui()->renderer();

            $form = $this->getImportForm($ref_id);

            $panel = $ui_factory->panel()->standard(
                $this->plugin_object->txt('creation_accordion_header'),
                $ui_factory->legacy()->content($form->getHTML())
            );

            $processed_html = $ui_renderer->render($panel);
            self::$stop_recursion = false;

            return $this->uiHookResponse(self::APPEND, $processed_html);
        }

        return $this->uiHookResponse();
    }

    private function isAllowedUser(): bool
    {
        $role_ids = $this->dic->settings()->get('crs_grp_import_default_local_role_ids');

        $selected_role = $role_ids ? explode(',', $role_ids) : [];
        $user_roles = $this->dic->rbac()->review()->assignedRoles($this->dic->user()->getId());

        if (count(array_intersect($user_roles, $selected_role)) > 0) {
            return true;
        }
        return $this->dic->rbac()->review()->isAssigned($this->dic->user()->getId(), SYSTEM_ROLE_ID);
    }

    /**
     * @throws ilCtrlException
     */
    private function getImportForm(int $ref_id): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $url = $this->dic->ctrl()->getFormActionByClass(
            [ilUIPluginRouterGUI::class, self::class],
            'Import.showCmd'
        );
        $form->setFormAction($url);
        $file = new ilFileInputGUI($this->plugin_object->txt('select_file'), 'csv_file');
        $file->setRequired(true);
        $form->addItem($file);
        $parent_ref_id = new ilHiddenInputGUI('parent_ref_id');
        $parent_ref_id->setValue((string) $ref_id);
        $form->addItem($parent_ref_id);
        $form->addCommandButton('Import.import', $this->plugin_object->txt('grp' . '_add'));
        $form->addCommandButton('Import.cancel', $this->dic->language()->txt('cancel'));
        return $form;
    }

    protected function uiHookResponse(string $mode = ilUIHookPluginGUI::KEEP, string $html = ''): array
    {
        return ['mode' => $mode, 'html' => $html];
    }
}
