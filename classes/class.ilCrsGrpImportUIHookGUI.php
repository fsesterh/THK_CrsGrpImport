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
    protected static $stop_recursion = false;
    protected static $has_accordion = false;
    protected static $handled = false;

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

    public function getHTML($a_comp, $a_part, $a_par = [])
    {
        if (self::$stop_recursion === true) {
            return ['mode' => ilUIHookPluginGUI::KEEP];
        }

        $queryParams = $this->dic->http()->request()->getQueryParams();

        if ($this->isAllowedUser() && array_key_exists('cmd', $queryParams) && $queryParams['cmd'] === 'create' &&
            (
                (array_key_exists('new_type', $queryParams) && $queryParams['new_type'] === 'grp') ||
                (array_key_exists('new_type', $queryParams) && $queryParams['new_type'] === 'crs')
            )
        ) {
            if (self::$handled === false && is_array($a_par) && $a_par['tpl_id'] === 'Services/Accordion/tpl.accordion.html' && $a_part === 'template_get') {
                self::$has_accordion = true;
                self::$handled = true;
                self::$stop_recursion = true;

                $core_doc = new DOMDocument("1.0", "utf-8");
                if (!@$core_doc->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $a_par['html'] . '</body></html>')) {
                    return ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];
                }
                $core_doc->encoding = 'UTF-8';

                $xp = new DOMXPath($core_doc);
                $accordion_sections = $xp->query(
                    "//div[contains(concat(' ', normalize-space(@class), ' '), ' il_VAccordionInnerContainer ')]"
                );

                $form = $this->getImportForm($queryParams['ref_id']);

                $counter = $accordion_sections->count() + 1;
                /** @var DOMNode|null $previous_sibling */
                $previous_sibling = null;
                foreach ($accordion_sections as $accordion_section) {
                    $import_sections = [
                        $this->dic->language()->txt('crs_import'),
                        $this->dic->language()->txt('grp_import')
                    ];

                    foreach ($import_sections as $import_section) {
                        $header = preg_quote($this->dic->language()->txt('option'), '/') . ' (\d+): ' . preg_quote($import_section, '/');
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
                    return ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];
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
                            static function (array $matches) : string {
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

                return ['mode' => ilUIHookPluginGUI::REPLACE, 'html' => $processed_html];
            }

            if (self::$handled === false && self::$has_accordion === false && is_array($a_par) && $a_par['tpl_id'] === 'Services/Object/tpl.creation_acc_head.html' && $a_part === 'template_load') {
                self::$has_accordion = true;
            }

            if (self::$handled === false && self::$has_accordion === false && is_array($a_par) && $a_par['tpl_id'] === 'Services/Form/tpl.form.html' && $a_part === 'template_get') {
                self::$handled = true;

                $core_doc = new DOMDocument("1.0", "utf-8");
                if (!@$core_doc->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $a_par['html'] . '</body></html>')) {
                    return ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];
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
                    $this->getImportForm($queryParams['ref_id'])->getHTML()
                );

                return ['mode' => ilUIHookPluginGUI::REPLACE, 'html' => $acc->getHTML()];
            }
        }

        return ['mode' => ilUIHookPluginGUI::KEEP];
    }

    /**
     * @return bool
     */
    private function isAllowedUser() : bool
    {
        $selected_role = explode(';', $this->dic->settings()->get('crs_grp_import_default_role_ids'));
        $user_roles = $this->dic->rbac()->review()->assignedGlobalRoles($this->dic->user()->id);

        if (count(array_intersect($user_roles, $selected_role)) > 0) {
            return true;
        }
        return $this->dic->rbac()->review()->isAssigned($this->dic->user()->getId(), SYSTEM_ROLE_ID);
    }

    /**
     * @param $ref_id
     * @return ilPropertyFormGUI
     */
    private function getImportForm($ref_id) : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $url = $this->dic->ctrl()->getLinkTargetByClass(
            [ilUIPluginRouterGUI::class, self::class],
            'Import.showCmd'
        );
        $form->setFormAction($url);
        $file = new ilFileInputGUI($this->plugin_object->txt('select_file'), 'csv_file');
        $file->setRequired(true);
        $form->addItem($file);
        $parent_ref_id = new ilHiddenInputGUI('parent_ref_id');
        $parent_ref_id->setValue($ref_id);
        $form->addItem($parent_ref_id);
        $form->addCommandButton('#', $this->plugin_object->txt('grp' . '_add'));
        $form->addCommandButton('cancel', $this->dic->language()->txt('cancel'));
        return $form;
    }
}
