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

use ILIAS\Filesystem\Stream\Streams;

chdir(dirname(__FILE__));
$iliasRootDir = './';
while (!file_exists($iliasRootDir . 'ilias.ini.php')) {
    $iliasRootDir .= '../';
}
chdir($iliasRootDir);

require_once './Services/Cron/classes/class.ilCronStartUp.php';

if ($_SERVER['argc'] < 3) {
    die("Usage:  " . basename(__FILE__) . " username client \n");
}

include_once './Services/Cron/classes/class.ilCronStartUp.php';
require_once __DIR__ . '/vendor/autoload.php';

$cron = new ilCronStartUp($_SERVER['argv'][2], $_SERVER['argv'][1]);

try {
    $cron->authenticate();
    $cronResult = ilCrsGrpImportPlugin::getInstance()->run();
    $cron->logout();
    switch ($cronResult->getStatus()) {
        case ilCronJobResult::STATUS_INVALID_CONFIGURATION:
            $status = "INVALID CONFIGURATION";
            break;
        case ilCronJobResult::STATUS_NO_ACTION:
            $status = "NO ACTION";
            break;
        case ilCronJobResult::STATUS_OK:
            $status = "OK";
            break;
        case ilCronJobResult::STATUS_CRASHED:
            $status = "CRASHED";
            break;
        case ilCronJobResult::STATUS_RESET:
            $status = "RESET";
            break;
        case ilCronJobResult::STATUS_FAIL:
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
