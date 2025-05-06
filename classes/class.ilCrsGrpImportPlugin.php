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

use ILIAS\Plugin\CrsGrpImport\Job\CrsGrpImportJob;

require_once __DIR__ . '/../vendor/autoload.php';

class ilCrsGrpImportPlugin extends ilUserInterfaceHookPlugin implements ilCronJobProvider
{
    public const ID = 'crsgrpimport';
    public const PLUGIN_CMD_DETECTION_PARAMETER = 'isCrsGrpImport';

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        global $DIC;

        /** @var ilComponentFactory $componentFactory */
        $componentFactory = $DIC['component.factory'];
        self::$instance = $componentFactory->getPlugin(self::ID);
        return self::$instance;
    }

    public function run(): ilCronJobResult
    {
        return (new CrsGrpImportJob())->run();
    }

    public function getLinkTarget($cmd, $parameters = [], $prevent_xhtml_style = false): string
    {
        global $DIC;
        $ilCtrl = $DIC->ctrl();

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
