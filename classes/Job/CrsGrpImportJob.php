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

use ilComponentFactory;
use ilCronJob;
use ilCronJobResult;
use ilCrsGrpImportPlugin;
use ilDateTimeException;
use ilDidacticTemplateSetting;
use ilDidacticTemplateSettings;
use ilFileDataMail;
use ilFileUtils;
use ILIAS\Cron\Schedule\CronJobScheduleType;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpImport\Creator\BaseObject;
use ILIAS\Plugin\CrsGrpImport\Creator\ContainerLink;
use ILIAS\Plugin\CrsGrpImport\Creator\Course;
use ILIAS\Plugin\CrsGrpImport\Creator\Group;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;
use ILIAS\Plugin\CrsGrpImport\Repository\QueuedRepository;
use ilLogger;
use ilMail;
use ilObject;
use ilObjUser;
use ReflectionClass;

class CrsGrpImportJob extends ilCronJob
{
    public const COURSE = 'crs';
    public const GROUP = 'grp';
    public const COURSE_LINK = 'crsr';
    public const GROUP_LINK = 'grpr';
    protected const VALID_TYPE = [0, 1, 2, 3];

    private Container $dic;
    private ilLogger $logger;
    private QueuedRepository $queuedRepo;
    private ilCrsGrpImportPlugin $plugin;
    private ilComponentFactory $componentFactory;

    public function __construct()
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
        $this->dic = $DIC;
        $this->componentFactory = $DIC['component.factory'];
        $this->queuedRepo = QueuedRepository::getInstance();
        $this->plugin = ilCrsGrpImportPlugin::getInstance();
    }

    public function getTitle(): string
    {
        return $this->plugin->txt("job.title");
    }

    public function getDescription(): string
    {
        return $this->plugin->txt("job.description");
    }

    public function getId(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    public function hasAutoActivation(): bool
    {
        return false;
    }

    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_IN_HOURS;
    }

    /**
     * @return int[]
     */
    public function getAllScheduleTypes(): array
    {
        return [
            CronJobScheduleType::SCHEDULE_TYPE_IN_MINUTES,
            CronJobScheduleType::SCHEDULE_TYPE_IN_HOURS,
            CronJobScheduleType::SCHEDULE_TYPE_DAILY,
        ];
    }

    public function getDefaultScheduleValue(): int
    {
        return 1;
    }

    /**
     * @throws ilDateTimeException
     */
    public function run(): ilCronJobResult
    {
        $cronResult = new ilCronJobResult();

        $failedMailDeliveries = 0;

        $queuedImports = $this->queuedRepo->readAll();
        foreach ($queuedImports as $queuedImport) {
            $csvLog = new CSVLog();

            $csv_deserialized = unserialize(
                $queuedImport->getCsvData(),
                ['allowed_classes' => [ImportCsvObject::class]]
            );
            /**
             * @var  $key
             * @var ImportCsvObject $data
             */
            foreach ($csv_deserialized as $key => $data) {
                $base_status = BaseObject::STATUS_OK;

                if ($data->getType() === self::COURSE) {
                    $base_status = $this->buildCourseObject($data, $csvLog);
                } elseif ($data->getType() === self::GROUP) {
                    $base_status = $this->buildGroupObject($data, $csvLog);
                } elseif ($data->getType() === self::COURSE_LINK) {
                    $base_status = $this->buildCourseLinkObject($data, $csvLog);
                } elseif ($data->getType() === self::GROUP_LINK) {
                    $base_status = $this->buildGroupLinkObject($data, $csvLog);
                } else {
                    $base_status = BaseObject::STATUS_FAILED;
                    $data->setImportResult(BaseObject::RESULT_UNKNOWN_OBJECT_TYPE);
                }
                $csvLog->addEntryToLog(
                    $base_status,
                    $data->getRefId(),
                    $data->getTitleDe(),
                    $data->getValidatedAdmins(),
                    $data->getImportResult()
                );
            }

            $tempFile = ilFileUtils::ilTempnam() . '.csv';
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
                $user->getLogin(),
                "",
                "",
                $this->dic->language()->txtlng(
                    $pluginLngModule,
                    "{$pluginLngModule}_mail.message.title",
                    $user->getLanguage()
                ),
                $this->dic->language()->txtlng(
                    $pluginLngModule,
                    "{$pluginLngModule}_mail.message.text",
                    $user->getLanguage()
                ),
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
                $this->plugin->txt("cronResult"),
                count($queuedImports),
                $failedMailDeliveries
            )
        );

        return $cronResult;
    }

    protected function buildCourseLinkObject(ImportCsvObject $data, CSVLog $csvLog): string
    {
        $container = new ContainerLink($data, $csvLog, $this->dic);
        return $this->buildObject($container, $data);
    }

    protected function buildGroupLinkObject(ImportCsvObject $data, CSVLog $csvLog): string
    {
        $container = new ContainerLink($data, $csvLog, $this->dic);
        return $this->buildObject($container, $data);
    }

    /**
     * @throws ilDateTimeException
     */
    protected function buildGroupObject(mixed $data, CSVLog $csvLog): string
    {
        $new_group = new Group($data, $csvLog, $this->dic);
        return $this->buildObject($new_group, $data);
    }

    /**
     * @throws ilDateTimeException
     */
    protected function buildCourseObject(mixed $data, CSVLog $csvLog): string
    {
        $new_course = new Course($data, $csvLog, $this->dic);
        return $this->buildObject($new_course, $data);
    }

    /**
     * @throws ilDateTimeException
     */
    protected function buildObject(Course|ContainerLink|Group $new_object, ImportCsvObject $data): string
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
            if (!$data->getImportResult()) {
                $data->setImportResult(BaseObject::RESULT_DATASET_INVALID);
            }
        }

        return $base_status;
    }

    protected function ensureDataIsValid(ImportCsvObject $data): bool
    {
        if (!in_array(
            strtolower($data->getAction()),
            [BaseObject::INSERT, BaseObject::UPDATE, BaseObject::IGNORE],
            true
        )) {
            $data->setImportResult(BaseObject::RESULT_UNSUPPORTED_ACTION);
            return false;
        }

        if (!in_array($data->getType(), [self::COURSE, self::GROUP, self::COURSE_LINK, self::GROUP_LINK], true)) {
            $data->setImportResult(BaseObject::RESULT_UNSUPPORTED_OBJECT_TYPE);
            return false;
        }

        if (in_array($data->getType(), [self::COURSE, self::GROUP], true)) {
            if (!in_array($data->getRegistrationNative(), self::VALID_TYPE)) {
                $data->setImportResult(BaseObject::RESULT_UNSUPPORTED_REGISTRATION_TYPE);
                return false;
            }

            if ($data->getType() === self::COURSE) {
                if ($data->getTemplateIdNativeType() === 1) {
                    $data->setImportResult(BaseObject::RESULT_DIDACTIC_TEMPLATE_ID_1_NOT_ALLOWED);
                    return false;
                }
            }

            /** @var ilDidacticTemplateSetting $template */
            $templates = ilDidacticTemplateSettings::getInstanceByObjectType($data->getType())->getTemplates();
            $enabled_templates_by_id = [];
            foreach ($templates as $template) {
                if ($template->isEnabled()) {
                    $enabled_templates_by_id[$template->getId()] = $template;
                }
            }

            if ($data->getTemplateIdNativeType() > 0 && !isset($enabled_templates_by_id[$data->getTemplateIdNativeType()])) {
                $data->setImportResult(BaseObject::RESULT_DIDACTIC_TEMPLATE_ID_NOT_SUPPORTED_OR_NOT_ENABLED);
                return false;
            }

            if ($data->getAdmins() === '') {
                $data->setImportResult(BaseObject::RESULT_NO_ADMINS_PROVIDED);
                return false;
            }

            $usr_ids = \ilObjUser::_lookupId($data->getValidatedAdmins());
            if (count($usr_ids) === 0) {
                $data->setImportResult(BaseObject::RESULT_NO_ADMIN_USERS_COULD_BE_DETERMINED);
                return false;
            }

            if (!in_array($data->getAdmissionLink(), [0, 1])) {
                return false;
            }

            if ($data->getTitleDe() === '') {
                $data->setImportResult(BaseObject::RESULT_MISSING_GERMAN_TITLE);
                return false;
            }

            if ($data->getMinMembers() !== null &&
                $data->getMaxMembers() !== null &&
                $data->getMinMembers() > $data->getMaxMembers()) {
                $data->setImportResult(BaseObject::RESULT_MIN_MEMBERS_GREATER_THAN_MAX_MEMBERS);
                return false;
            }
        }

        if (in_array($data->getType(), [self::COURSE_LINK, self::GROUP_LINK], true)) {
            if (!in_array(strtolower($data->getAction()), [BaseObject::INSERT, BaseObject::IGNORE], true)) {
                // course links and group links don't support an `update` action
                $data->setImportResult(BaseObject::RESULT_NO_UPDATE_ACTION_ALLOWED_FOR_LINKS);
                return false;
            }

            if (!is_numeric($data->getRefId())) {
                $data->setImportResult(BaseObject::RESULT_MISSING_REF_ID_FOR_LINK);
                return false;
            }

            $obj_id = ilObject::_lookupObjId($data->getRefId());
            if (!$obj_id) {
                $data->setImportResult(BaseObject::RESULT_INVALID_REF_ID_FOR_LINK);
                return false;
            }

            if ($data->getType() === self::COURSE_LINK && ilObject::_lookupType($obj_id) !== self::COURSE) {
                $data->setImportResult(BaseObject::RESULT_TYPE_MISMATCH_FOR_LINK);
                return false;
            }

            if ($data->getType() === self::GROUP_LINK && ilObject::_lookupType($obj_id) !== self::GROUP) {
                $data->setImportResult(BaseObject::RESULT_TYPE_MISMATCH_FOR_LINK);
                return false;
            }
        }

        return true;
    }
}
