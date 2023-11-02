<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */


use ILIAS\Plugin\CrsGrpImport\Job\CrsGrpImportJob;
use ILIAS\Plugin\CrsGrpImport\Lock\PidBasedLocker;

require_once __DIR__ . '/../vendor/autoload.php';

require_once 'Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpImport/vendor/autoload.php';
/**
 * Class ilCrsGrpImportPlugin
 */
class ilCrsGrpImportPlugin extends \ilUserInterfaceHookPlugin
{
    /**
     * @var string
     */
    public const PLUGIN_CMD_DETECTION_PARAMETER = 'isCrsGrpImport';

    /**
     * @var string
     */
    public const CTYPE = 'Services';

    /**
     * @var string
     */
    public const CNAME = 'UIComponent';

    /**
     * @var string
     */
    public const SLOT_ID = 'uihk';

    /**
     * @var string
     */
    public const PNAME = 'CrsGrpImport';

    /**
     * @var self|\ilPlugin|\ilUserInterfaceHookPlugin
     */
    private static $instance;

    protected static $initialized = false;

    /**
     * @return self|\ilPlugin|\ilUserInterfaceHookPlugin
     */
    public static function getInstance()
    {
        if (null !== self::$instance) {
            return self::$instance;
        }

        return (self::$instance = \ilPluginAdmin::getPluginObject(
            self::CTYPE,
            self::CNAME,
            self::SLOT_ID,
            self::PNAME
        ));
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        if (!self::$initialized) {
            self::$initialized = true;

            $GLOBALS['DIC']['plugin.crsgrpimport.cronjob.locker'] = function () {
                return new PidBasedLocker(
                    new ilSetting($this->getPluginName())
                );
            };
        }
    }

    /**
     * @return string
     */
    final public function getPluginName()
    {
        return self::PNAME;
    }

    public function run() : ilCronJobResult
    {
        $job = new CrsGrpImportJob();
        return $job->run();
    }

    public function getLinkTarget($cmd, $parameters = [], $prevent_xhtml_style = false)
    {
        /** @var $ilCtrl ilCtrl */
        global $ilCtrl;

        foreach ($parameters as $key => $val) {
            $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', $key, $val);
        }
        $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', self::PLUGIN_CMD_DETECTION_PARAMETER, 1);

        $url = $ilCtrl->getLinkTargetByClass(
            ['ilUIPluginRouterGUI', 'ilCrsGrpImportUIHookGUI'],
            $cmd,
            '',
            false,
            $prevent_xhtml_style
        );

        foreach ($parameters as $key => $val) {
            $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', $key, '');
        }
        $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', self::PLUGIN_CMD_DETECTION_PARAMETER, '');

        return $url;
    }
}
