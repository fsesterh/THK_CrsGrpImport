<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php';

/**
 * Class ilCrsGrpImportPlugin
 */
class ilCrsGrpImportPlugin extends \ilUserInterfaceHookPlugin
{

    /**
     * @var string
     */
    const PLUGIN_CMD_DETECTION_PARAMETER = 'isCrsGrpImport';

    /**
     * @var string
     */
    const CTYPE = 'Services';

    /**
     * @var string
     */
    const CNAME = 'UIComponent';

    /**
     * @var string
     */
    const SLOT_ID = 'uihk';

    /**
     * @var string
     */
    const PNAME = 'CrsGrpImport';

    /**
     * @var self|\ilPlugin|\ilUserInterfaceHookPlugin
     */
    private static $instance;

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
        $this->registerAutoloader();
    }

    /**
     * Register the plugin autoloader
     */
    public function registerAutoloader()
    {
        require_once realpath(dirname(__FILE__) . '/../autoload.php');
    }

    /**
     * @return string
     */
    final public function getPluginName()
    {
        return self::PNAME;
    }

    public function getLinkTarget($cmd, $parameters = array(), $prevent_xhtml_style = false)
    {
        /** @var $ilCtrl ilCtrl */
        global $ilCtrl;

        foreach ($parameters as $key => $val) {
            $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', $key, $val);
        }
        $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', self::PLUGIN_CMD_DETECTION_PARAMETER, 1);

        $url = $ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCrsGrpImportUIHookGUI'), $cmd, '', false,
            $prevent_xhtml_style);

        foreach ($parameters as $key => $val) {
            $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', $key, '');
        }
        $ilCtrl->setParameterByClass('ilCrsGrpImportUIHookGUI', self::PLUGIN_CMD_DETECTION_PARAMETER, '');

        return $url;
    }

} 