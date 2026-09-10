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

use ILIAS\Cron\CronJob;
use ILIAS\Cron\Job\JobProvider;
use ILIAS\Cron\Job\JobResult;
use ILIAS\Plugin\CrsGrpImport\Job\CrsGrpImportJob;
use ILIAS\Plugin\CrsGrpImport\Setup\DBUpdateSteps;

require_once __DIR__ . '/../vendor/autoload.php';

class ilCrsGrpImportPlugin extends ilUserInterfaceHookPlugin implements JobProvider
{
    public const string ID = 'crsgrpimport';
    public const string PLUGIN_CMD_DETECTION_PARAMETER = 'isCrsGrpImport';

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        global $DIC;

        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC['component.factory'];
        self::$instance = $component_factory->getPlugin(self::ID);
        return self::$instance;
    }

    public function run(): JobResult
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
    public function getCronJobInstance(string $job_id): CronJob
    {
        foreach ($this->getCronJobInstances() as $cron_job_instance) {
            if ($cron_job_instance->getId() === $job_id) {
                return $cron_job_instance;
            }
        }
        throw new Exception("No cron job found with the id '$job_id'.");
    }

    public function install(): void
    {
        (new DBUpdateSteps())->install($this->db);
        parent::install();
    }

    public function update(): bool
    {
        //DbUpdateSteps should be executed before the parent update so potential exceptions result in no il_plugin db changes
        (new DBUpdateSteps())->install($this->db);

        return parent::update();
    }

    protected function uninstallCustom(): void
    {
        (new DBUpdateSteps())->uninstall($this->db);
    }

}
