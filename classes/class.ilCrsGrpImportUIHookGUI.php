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
require_once 'Services/UIComponent/classes/class.ilUIHookPluginGUI.php';

/**
 *
 * @ilCtrl_Calls      ilCrsGrpImportUIHookGUI: ilPropertyFormGUI
 * @ilCtrl_isCalledBy ilCrsGrpImportUIHookGUI: ilObjCourseGUI, ilObjGroupGUI
 * @ilCtrl_isCalledBy ilCrsGrpImportUIHookGUI: ilUIPluginRouterGUI
 */
class ilCrsGrpImportUIHookGUI extends \ilUIHookPluginGUI
{
    protected static bool $stop_recursion = false;
    protected static bool $has_accordion = false;
    protected static bool $handled = false;

    protected Container $dic;
    private WrapperFactory $httpWrapper;
    private Factory $refinery;

    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
        $this->refinery = $this->dic->refinery();
        $this->httpWrapper = $this->dic->http()->wrapper();
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
        if (!isset($a_par["tpl_id"], $a_par["html"]) || !$a_par["tpl_id"] || !$a_par["html"]) {
            return $this->uiHookResponse();
        }

        if (self::$stop_recursion === true) {
            return $this->uiHookResponse();
        }

        $getFromQuery = fn(string $key, string $type) => $this->httpWrapper->query()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->$type(),
                $this->refinery->always(null)
            ])
        );


        $refId = $getFromQuery('ref_id', 'int');
        $cmd = $getFromQuery('cmd', 'string');
        $newType = $getFromQuery('new_type', 'string');

        if ($cmd !== 'create' || !in_array($newType, ['grp', 'crs'])) {
            return $this->uiHookResponse();
        }

        if (
            self::$handled === false
            && $a_part === 'template_get'
            && $a_par['tpl_id'] === 'Services/Accordion/tpl.accordion.html'
            && $this->isAllowedUser()
        ) {
            self::$has_accordion = true;
            self::$handled = true;
            self::$stop_recursion = true;

            $core_doc = new DOMDocument("1.0", "utf-8");
            if (!@$core_doc->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $a_par['html'] . '</body></html>')) {
                return $this->uiHookResponse();
            }
            $core_doc->encoding = 'UTF-8';

            $xp = new DOMXPath($core_doc);
            $accordion_sections = $xp->query(
                "//div[contains(concat(' ', normalize-space(@class), ' '), ' il_VAccordionInnerContainer ')]"
            );

            $form = $this->getImportForm($refId);

            $counter = $accordion_sections->count() + 1;
            /** @var DOMNode|null $previous_sibling */
            $previous_sibling = null;
            foreach ($accordion_sections as $accordion_section) {
                $import_sections = [
                    $this->dic->language()->txt('crs_import'),
                    $this->dic->language()->txt('grp_import')
                ];

                foreach ($import_sections as $import_section) {
                    $header = preg_quote(
                            $this->dic->language()->txt('option'),
                            '/'
                        ) . ' (\d+): ' . preg_quote($import_section, '/');
                    if (preg_match('/' . $header . '/', $accordion_section->textContent, $matches)) {
                        $counter = (int) $matches[1] + 1;
                        $previous_sibling = $accordion_section;
                        break;
                    }
                }
            }

            if (null === $previous_sibling && $accordion_sections->count() > 1) {
                // If no node could be found, append it as penultimate node
                $counter = $accordion_sections->count();
                $previous_sibling = $accordion_sections->item($accordion_sections->count() - 2);
            }

            $acc = new ilAccordionGUI();

            $htpl = new ilTemplate('tpl.creation_acc_head.html', true, true, 'Services/Object');
            $htpl->setVariable(
                'TITLE',
                $this->dic->language()->txt('option') . ' ' . $counter . ': ' . $this->plugin_object->txt('creation_accordion_header')
            );
            $acc->addItem(
                $htpl->get(),
                $form->getHTML()
            );

            $additional_accordion_doc = new DOMDocument("1.0", "utf-8");
            if (!@$additional_accordion_doc->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $acc->getHTML() . '</body></html>')) {
                return $this->uiHookResponse();
            }
            $additional_accordion_doc_xpath = new DOMXPath($additional_accordion_doc);
            $additional_accordion_doc->encoding = 'UTF-8';

            foreach ($additional_accordion_doc->getElementsByTagName('body')->item(0)->childNodes as $child) {
                $accordion_sections = $additional_accordion_doc_xpath->query(
                    "./div[contains(concat(' ', normalize-space(@class), ' '), ' il_VAccordionInnerContainer ')]",
                    $child
                );

                if ($accordion_sections->count() > 0) {
                    foreach ($accordion_sections as $accordion_section) {
                        $imported_accordion_section = $core_doc->importNode($accordion_section, true);

                        if ($previous_sibling->nextSibling) {
                            $previous_sibling->parentNode->insertBefore(
                                $imported_accordion_section,
                                $previous_sibling->nextSibling
                            );
                            $previous_sibling = $imported_accordion_section;
                        } elseif ($previous_sibling->parentNode) {
                            $previous_sibling->parentNode->apppendChild($imported_accordion_section);
                        } else {
                            $core_doc->getElementById('accordion__1')->appendChild($imported_accordion_section);
                        }
                    }
                }
            }

            $accordion_headers = $xp->query(
                "//div[contains(concat(' ', normalize-space(@class), ' '), ' il_VAccordionHead ')]/*[contains(concat(' ', normalize-space(@class), ' '), ' ilBlockHeader ')]/text()",
            );
            $i = 1;
            $begin_manipulations = false;
            foreach ($accordion_headers as $accordion_header) {
                if ($begin_manipulations) {
                    $header = '(' . preg_quote($this->dic->language()->txt('option'), '/') . ' )(\d+):';
                    $accordion_header->nodeValue = preg_replace_callback(
                        '/' . $header . '/',
                        static function (array $matches): string {
                            return $matches[1] . ' ' . ((string) (((int) $matches[2]) + 1)) . ': ';
                        },
                        $accordion_header->nodeValue
                    );
                }

                if ($i === $counter) {
                    $begin_manipulations = true;
                }
                ++$i;
            }

            $processed_html = $core_doc->saveHTML($core_doc->getElementsByTagName('body')->item(0));

            self::$stop_recursion = false;

            return $this->uiHookResponse(self::REPLACE, $processed_html);
        }

        if (
            self::$handled === false
            && self::$has_accordion === false
            && $a_part === 'template_load'
            && $a_par['tpl_id'] === 'Services/Object/tpl.creation_acc_head.html'
            && $this->isAllowedUser()
        ) {
            self::$has_accordion = true;
        }

        if (
            self::$handled === false
            && self::$has_accordion === false
            && $a_part === 'template_get'
            && $a_par['tpl_id'] === 'Services/Form/tpl.form.html'
            && $this->isAllowedUser()
        ) {
            self::$handled = true;

            $core_doc = new DOMDocument("1.0", "utf-8");
            if (!@$core_doc->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $a_par['html'] . '</body></html>')) {
                return $this->uiHookResponse();
            }
            $core_doc->encoding = 'UTF-8';

            $xp = new DOMXPath($core_doc);
            $form_header = $xp->query(
                "//div[contains(concat(' ', normalize-space(@class), ' '), ' ilFormHeader ')]"
            );
            $header_text = trim($form_header->item(0)->nodeValue);
            $form_header->item(0)->parentNode->removeChild($form_header->item(0));

            $acc = new ilAccordionGUI();
            $acc->setBehaviour(ilAccordionGUI::FIRST_OPEN);

            $htpl = new ilTemplate('tpl.creation_acc_head.html', true, true, 'Services/Object');
            $htpl->setVariable(
                'TITLE',
                $this->dic->language()->txt('option') . ' 1: ' . $header_text
            );

            $acc->addItem($htpl->get(), $core_doc->saveHTML($core_doc->getElementsByTagName('body')->item(0)));

            $htpl = new ilTemplate('tpl.creation_acc_head.html', true, true, 'Services/Object');
            $htpl->setVariable(
                'TITLE',
                $this->dic->language()->txt('option') . ' 2: ' . $this->plugin_object->txt('creation_accordion_header')
            );
            $acc->addItem(
                $htpl->get(),
                $this->getImportForm($refId)->getHTML()
            );

            return $this->uiHookResponse(self::REPLACE, $acc->getHTML());
        }

        return $this->uiHookResponse();
    }

    private function isAllowedUser(): bool
    {
        $roleIds = $this->dic->settings()->get('crs_grp_import_default_local_role_ids');

        $selected_role = $roleIds ? explode(',', $roleIds) : [];
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
