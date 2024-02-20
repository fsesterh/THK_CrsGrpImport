<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */


use ILIAS\Plugin\CrsGrpImport\Job\CrsGrpImportJob;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class ilCrsGrpImportPlugin
 */
class ilCrsGrpImportPlugin extends ilUserInterfaceHookPlugin implements ilCronJobProvider
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

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        global $DIC;

        /** @var ilComponentFactory $componentFactory */
        $componentFactory = $DIC['component.factory'];
        self::$instance = $componentFactory->getPlugin('crsgrpimport');
        return self::$instance;
    }

    final public function getPluginName(): string
    {
        return self::PNAME;
    }

    public function run(): ilCronJobResult
    {
        return (new CrsGrpImportJob())->run();
    }

    public function getLinkTarget($cmd, $parameters = [], $prevent_xhtml_style = false): string
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

    public function getCronJobInstances(): array
    {
        return [
            new CrsGrpImportJob()
        ];
    }

    /**
     * @throws Exception
     */
    public function getCronJobInstance(string $jobId): ilCronJob
    {
        foreach ($this->getCronJobInstances() as $cronJobInstance) {
            if ($cronJobInstance->getId() === $jobId) {
                return $cronJobInstance;
            }
        }
        throw new Exception("No cron job found with the id '$jobId'.");
    }
}
