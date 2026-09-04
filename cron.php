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

if (PHP_SAPI !== 'cli') {
    die("Cronjob can only be run from the command line.\n");
}

if ($_SERVER['argc'] < 3) {
    die("Usage:  " . basename(__FILE__) . " username client \n");
}

chdir(dirname(__FILE__));
$iliasRootDir = './';
while (!file_exists($iliasRootDir . 'ilias.ini.php')) {
    $iliasRootDir .= '../';
}
chdir($iliasRootDir);

require_once 'vendor/composer/vendor/autoload.php';

use ILIAS\Cron\CLI\StartUp;
use ILIAS\Cron\Job\JobResult;

$cron = new StartUp($_SERVER['argv'][2], $_SERVER['argv'][1]);

try {
    $cron->authenticate();
    $cronResult = ilCrsGrpImportPlugin::getInstance()->run();
    $cron->logout();
    switch ($cronResult->getStatus()) {
        case JobResult::STATUS_INVALID_CONFIGURATION:
            $status = "INVALID CONFIGURATION";
            break;
        case JobResult::STATUS_NO_ACTION:
            $status = "NO ACTION";
            break;
        case JobResult::STATUS_OK:
            $status = "OK";
            break;
        case JobResult::STATUS_CRASHED:
            $status = "CRASHED";
            break;
        case JobResult::STATUS_RESET:
            $status = "RESET";
            break;
        case JobResult::STATUS_FAIL:
        default:
            $status = "FAILED";
            break;
    }

    echo "[$status]: {$cronResult->getMessage()}\n";
    exit;
} catch (Exception $e) {
    $cron->logout();

    echo $e->getMessage() . "\n";
    exit(1);
}
