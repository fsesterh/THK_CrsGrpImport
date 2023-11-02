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

namespace ILIAS\Plugin\CrsGrpImport\Job;

use ilCronJob;
use ilCronJobResult;
use ilCrsGrpEnrollmentPlugin;
use ilDateTimeException;
use ilFileDataMail;
use ILIAS\Plugin\CrsGrpImport\Creator\BaseObject;
use ILIAS\Plugin\CrsGrpImport\Creator\Course;
use ILIAS\Plugin\CrsGrpImport\Creator\Group;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Lock\Locker;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;
use ILIAS\Plugin\CrsGrpImport\Repository\QueuedRepository;
use ilLogger;
use ilMail;
use ilObjUser;
use ilPluginAdmin;
use ilPluginException;
use ilUtil;
use ReflectionClass;

/**
 * Class CrsGrpImportJob
 * @package Job
 * @author Marvin Beym <mbeym@databay.de>
 */
class CrsGrpImportJob extends ilCronJob
{
    public const COURSE = 'crs';
    public const GROUP = 'grp';
    protected const VALID_TYPE = [0, 1, 2, 3];

    /**
     * @var Locker
     */
    private $lock;
    /**
     * @var \ILIAS\DI\Container|mixed
     */
    private $dic;
    /**
     * @var ilPluginAdmin
     */
    private $pluginAdmin;
    /**
     * @var ilLogger
     */
    private $logger;
    /**
     * @var CSVLog
     */
    private $csv_log;
    /**
     * @var QueuedRepository
     */
    private $queuedRepo;

    public function __construct()
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
        $this->dic = $DIC;
        $this->pluginAdmin = $this->dic['ilPluginAdmin'];
        $this->lock = $this->dic['plugin.crsgrpimport.cronjob.locker'];
        $this->queuedRepo = QueuedRepository::getInstance();
    }

    public function getId() : string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    public function hasAutoActivation() : bool
    {
        return false;
    }

    public function hasFlexibleSchedule() : bool
    {
        return true;
    }

    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_IN_HOURS;
    }

    /**
     * @return int[]
     */
    public function getAllScheduleTypes() : array
    {
        return [
            self::SCHEDULE_TYPE_IN_MINUTES,
            self::SCHEDULE_TYPE_IN_HOURS,
            self::SCHEDULE_TYPE_DAILY,
        ];
    }

    public function getDefaultScheduleValue() : int
    {
        return 1;
    }

    /**
     * @throws ilDateTimeException
     */
    public function run() : ilCronJobResult
    {
        $plugin = null;
        $cronResult = new ilCronJobResult();

        try {
            if (
                $this->pluginAdmin->exists('Services', 'UIComponent', 'uihk', 'CrsGrpImport') &&
                $this->pluginAdmin->isActive('Services', 'UIComponent', 'uihk', 'CrsGrpImport')
            ) {
                /**
                 * @var ilCrsGrpEnrollmentPlugin $plugin
                 */
                $plugin = call_user_func(
                    [get_class($this->pluginAdmin), 'getPluginObject'],
                    'Services',
                    'UIComponent',
                    'uihk',
                    'CrsGrpImport'
                );
            }
        } catch (ilPluginException $e) {
        }

        if (!$plugin) {
            $cronResult->setStatus(ilCronJobResult::STATUS_FAIL);
            $cronResult->setMessage('Fatal Error! Plugin not installed!');
            return $cronResult;
        }

        if ($this->lock->acquireLock()) {
            $this->logger->info('Acquired lock.');
        } else {
            $message = sprintf(
                'Terminated import script: %s',
                'Script is probably running, please remove the lock if you are sure no task is running.'
            );
            $this->logger->info($message);
            $cronResult->setStatus(ilCronJobResult::STATUS_NO_ACTION);
            $cronResult->setMessage($message);
            return $cronResult;
        }

        $failedMailDeliveries = 0;

        $queuedImports = $this->queuedRepo->readAll();
        foreach ($queuedImports as $queuedImport) {
            $csvLog = new CSVLog();

            $csv_deserialized = unserialize($queuedImport->getCsvData(), ['allowed_classes' => [ImportCsvObject::class]]);
            foreach ($csv_deserialized as $key => $data) {
                $base_status = BaseObject::STATUS_OK;

                if ($data->getType() === self::COURSE) {
                    $base_status = $this->buildCourseObject($data, $csvLog);
                } elseif ($data->getType() === self::GROUP) {
                    $base_status = $this->buildGroupObject($data, $csvLog);
                } else {
                    $base_status = BaseObject::STATUS_FAILED;
                    $data->setImportResult(BaseObject::RESULT_UNKNOWN_OBJECT_TYPE);
                }
                $csvLog->addEntryToLog(
                    $base_status,
                    $data->getRefId(),
                    $data->getTitle(),
                    $data->getValidatedAdmins(),
                    $data->getImportResult()
                );
            }

            $tempFile = ilUtil::ilTempnam() . '.csv';
            file_put_contents($tempFile, $csvLog->getCSVLog());

            $fileName = 'import_log.csv';

            if (!ilObjUser::_exists($queuedImport->getUserId())) {
                $user = null;
            } else {
                $user = new ilObjUser($queuedImport->getUserId());
            }

            if (!$user) {
                $this->logger->error("Unable to deliver csv result to executive user with id '{$queuedImport->getUserId()}'. User does not exist");
                $this->queuedRepo->removeQueuedImport($queuedImport);
                continue;
            }

            $pluginLngModule = "ui_uihk_crsgrpimport";

            $fileDataMail = new ilFileDataMail(ANONYMOUS_USER_ID);
            $fileDataMail->copyAttachmentFile($tempFile, $fileName);
            $mail = new ilMail(ANONYMOUS_USER_ID);
            $errors = $mail->enqueue(
                $user->getEmail(),
                "",
                "",
                $this->dic->language()->txtlng($pluginLngModule, "{$pluginLngModule}_mail.message.title", $user->getLanguage()),
                $this->dic->language()->txtlng($pluginLngModule, "{$pluginLngModule}_mail.message.text", $user->getLanguage()),
                [$fileName],
                false
            );

            if (count($errors) !== 0) {
                $this->logger->error(
                    sprintf(
                        "Mail delivery of import results failed. ID of import: %s, ID of receiving user: %s",
                        $queuedImport->getId(),
                        $queuedImport->getUserId()
                    )
                );
                $failedMailDeliveries++;
            }
            $this->queuedRepo->removeQueuedImport($queuedImport);
        }

        $cronResult->setStatus(ilCronJobResult::STATUS_OK);
        $cronResult->setMessage(
            sprintf(
                $plugin->txt("cronResult"),
                count($queuedImports),
                $failedMailDeliveries
            )
        );
        $this->lock->releaseLock();


        return $cronResult;
    }

    /**
     * @param        $data
     * @param CSVLog $csvLog
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildCourseObject($data, CSVLog $csvLog) : string
    {
        $new_course = new Course($data, $csvLog, $this->dic);
        return $this->buildObject($new_course, $data);
    }

    /**
     * @param Course|Group $new_object
     * @param              $data
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildObject($new_object, $data) : string
    {
        $base_status = BaseObject::STATUS_OK;
        if ($this->ensureDataIsValid($data)) {
            if ($data->getAction() === BaseObject::INSERT) {
                $ref_id = $new_object->insert();
                $data->setRefId($ref_id);
                if ($ref_id === 0) {
                    $base_status = BaseObject::STATUS_FAILED;
                }
            } elseif ($data->getAction() === BaseObject::UPDATE) {
                $base_status = $new_object->update();
            } elseif ($data->getAction() === BaseObject::IGNORE) {
                $data->setImportResult(BaseObject::RESULT_IGNORE);
                $base_status = BaseObject::STATUS_IGNORED;
            } else {
                $data->setImportResult(BaseObject::RESULT_NO_VALID_ACTION);
                $base_status = BaseObject::STATUS_IGNORED;
            }
        } else {
            $base_status = BaseObject::STATUS_FAILED;
            $data->setImportResult(BaseObject::RESULT_DATASET_INVALID);
        }

        return $base_status;
    }


    protected function ensureDataIsValid(ImportCsvObject $data) : bool
    {
        if (!in_array(strtolower($data->getAction()), [BaseObject::INSERT, BaseObject::UPDATE, BaseObject::IGNORE])) {
            return false;
        }
        if ($data->getTitle() === '') {
            return false;
        }
        if (!in_array($data->getType(), [self::COURSE, self::GROUP])) {
            return false;
        }
        if (!in_array($data->getRegistrationNative(), self::VALID_TYPE)) {
            return false;
        }
        if (!in_array($data->getGrpTypeNative(), self::VALID_TYPE)) {
            return false;
        }
        if ($data->getAdmins() === '') {
            return false;
        }

        $usr_ids = ilObjUser::_lookupId($data->getValidatedAdmins());
        if (count($usr_ids) === 0) {
            return false;
        }
        if (!in_array($data->getAdmissionLink(), [0, 1])) {
            return false;
        }
        return true;
    }

    /**
     * @param $data
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildGroupObject($data, CSVLog $csvLog) : string
    {
        $new_group = new Group($data, $csvLog, $this->dic);
        return $this->buildObject($new_group, $data);
    }
}
