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
use ILIAS\Plugin\CrsGrpImport\Creator\ContainerLink;
use ILIAS\Plugin\CrsGrpImport\Creator\Course;
use ILIAS\Plugin\CrsGrpImport\Creator\Group;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportAction;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportResult;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportStatus;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportType;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;
use ILIAS\Plugin\CrsGrpImport\Repository\QueuedRepository;
use ilLogger;
use ilMail;
use ilObject;
use ilObjUser;
use ReflectionClass;
use Throwable;

class CrsGrpImportJob extends ilCronJob
{
    protected const VALID_TYPE = [0, 1, 2, 3];

    private readonly Container $dic;
    private readonly ilLogger $logger;
    private readonly QueuedRepository $queued_repo;
    private readonly ilCrsGrpImportPlugin $plugin;
    private readonly ilComponentFactory $component_factory;

    public function __construct()
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
        $this->dic = $DIC;
        $this->component_factory = $DIC['component.factory'];
        $this->queued_repo = QueuedRepository::getInstance();
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
    public function getValidScheduleTypes(): array
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
        $cron_result = new ilCronJobResult();


        try {
            $failed_mail_deliveries = 0;
            $queued_imports = $this->queued_repo->readAll();
            foreach ($queued_imports as $queued_import) {
                $csv_log = new CSVLog();

                $csv_deserialized = json_decode($queued_import->getCsvData(), true, 512, JSON_THROW_ON_ERROR);

                $import_objects = [];
                foreach ($csv_deserialized as $csv_import_data) {
                    $import_objects[] = ImportCsvObject::jsonUnserialize($csv_import_data);
                }

                /**
                 * @var ImportCsvObject $data
                 */
                foreach ($import_objects as $data) {
                    $base_status = ImportStatus::OK;

                    if ($data->getType() === ImportType::COURSE) {
                        $base_status = $this->buildCourseObject($data, $csv_log);
                    } elseif ($data->getType() === ImportType::GROUP) {
                        $base_status = $this->buildGroupObject($data, $csv_log);
                    } elseif ($data->getType() === ImportType::COURSE_LINK) {
                        $base_status = $this->buildCourseLinkObject($data, $csv_log);
                    } elseif ($data->getType() === ImportType::GROUP_LINK) {
                        $base_status = $this->buildGroupLinkObject($data, $csv_log);
                    } else {
                        $base_status = ImportStatus::FAILED;
                        $data->setImportResult(ImportResult::UNKNOWN_OBJECT_TYPE);
                    }
                    $csv_log->addEntryToLog(
                        $base_status,
                        $data->getRefId(),
                        $data->getTitleDe(),
                        $data->getValidatedAdmins(),
                        $data->getImportResult(),
                        $data->getAdditionalImportResultInfo(),
                    );
                }

                $temp_file = ilFileUtils::ilTempnam() . '.csv';
                file_put_contents($temp_file, $csv_log->getCSVLog());

                $file_name = 'import_log.csv';

                if (!ilObjUser::_exists($queued_import->getUserId())) {
                    $user = null;
                } else {
                    $user = new ilObjUser($queued_import->getUserId());
                }

                if (!$user) {
                    $this->logger->error("Unable to deliver csv result to executive user with id '{$queued_import->getUserId()}'. User does not exist");
                    $this->queued_repo->removeQueuedImport($queued_import);
                    continue;
                }

                $plugin_lng_module = "ui_uihk_crsgrpimport";

                $file_data_mail = new ilFileDataMail(ANONYMOUS_USER_ID);
                $file_data_mail->copyAttachmentFile($temp_file, $file_name);
                $mail = new ilMail(ANONYMOUS_USER_ID);
                $errors = $mail->enqueue(
                    $user->getLogin(),
                    "",
                    "",
                    $this->dic->language()->txtlng(
                        $plugin_lng_module,
                        "{$plugin_lng_module}_mail.message.title",
                        $user->getLanguage()
                    ),
                    $this->dic->language()->txtlng(
                        $plugin_lng_module,
                        "{$plugin_lng_module}_mail.message.text",
                        $user->getLanguage()
                    ),
                    [$file_name],
                    false
                );

                if (count($errors) !== 0) {
                    $this->logger->error(
                        sprintf(
                            "Mail delivery of import results failed. ID of import: %s, ID of receiving user: %s",
                            $queued_import->getId(),
                            $queued_import->getUserId()
                        )
                    );
                    $failed_mail_deliveries++;
                }
                $this->queued_repo->removeQueuedImport($queued_import);
            }

            $cron_result->setStatus(ilCronJobResult::STATUS_OK);
            $cron_result->setMessage(
                sprintf(
                    $this->plugin->txt("cronResult"),
                    count($queued_imports),
                    $failed_mail_deliveries
                )
            );
        } catch (Throwable $ex) {
            $this->logger->error("An error occurred while processing the import queue. The cronjob has been deactivated. Ex.: {$ex->getMessage()}. Stacktrace: \n{$ex->getTraceAsString()}");
            $cron_result->setStatus(ilCronJobResult::STATUS_CRASHED);
            $cron_result->setMessage($this->plugin->txt("cronResult.crashed"));
            $this->dic->cron()->manager()->deactivateJob($this, $this->dic->user());
        }

        return $cron_result;
    }

    protected function buildCourseLinkObject(ImportCsvObject $data, CSVLog $csv_log): ImportStatus
    {
        $container = new ContainerLink($data, $csv_log, $this->dic);
        return $this->buildObject($container, $data);
    }

    protected function buildGroupLinkObject(ImportCsvObject $data, CSVLog $csv_log): ImportStatus
    {
        $container = new ContainerLink($data, $csv_log, $this->dic);
        return $this->buildObject($container, $data);
    }

    /**
     * @throws ilDateTimeException
     */
    protected function buildGroupObject(mixed $data, CSVLog $csv_log): ImportStatus
    {
        $new_group = new Group($data, $csv_log, $this->dic);
        return $this->buildObject($new_group, $data);
    }

    /**
     * @throws ilDateTimeException
     */
    protected function buildCourseObject(mixed $data, CSVLog $csv_log): ImportStatus
    {
        $new_course = new Course($data, $csv_log, $this->dic);
        return $this->buildObject($new_course, $data);
    }

    /**
     * @throws ilDateTimeException
     */
    protected function buildObject(Course|ContainerLink|Group $new_object, ImportCsvObject $data): ImportStatus
    {
        $base_status = ImportStatus::OK;
        if ($this->ensureDataIsValid($data)) {
            if ($data->getAction() === ImportAction::INSERT) {
                $ref_id = $new_object->insert();
                $data->setRefId($ref_id);
                if ($ref_id === 0) {
                    $base_status = ImportStatus::FAILED;
                }
            } elseif ($data->getAction() === ImportAction::UPDATE) {
                $base_status = $new_object->update();
            } elseif ($data->getAction() === ImportAction::IGNORE) {
                $data->setImportResult(ImportResult::IGNORE);
                $base_status = ImportStatus::IGNORED;
            } else {
                $data->setImportResult(ImportResult::NO_VALID_ACTION);
                $base_status = ImportStatus::IGNORED;
            }
        } else {
            $base_status = ImportStatus::FAILED;
            if ($data->getImportResult() === ImportResult::NONE) {
                $data->setImportResult(ImportResult::DATASET_INVALID);
            }
        }

        return $base_status;
    }

    protected function ensureDataIsValid(ImportCsvObject $data): bool
    {
        if ($data->getAction() === ImportAction::UNSUPPORTED) {
            $data->setImportResult(ImportResult::UNSUPPORTED_ACTION);
            return false;
        }

        if ($data->getType() === ImportType::UNSUPPORTED) {
            $data->setImportResult(ImportResult::UNSUPPORTED_OBJECT_TYPE);
            return false;
        }

        if (in_array($data->getType(), [ImportType::COURSE, ImportType::GROUP], true)) {
            if (!in_array($data->getRegistrationNative(), self::VALID_TYPE)) {
                $data->setImportResult(ImportResult::UNSUPPORTED_REGISTRATION_TYPE);
                return false;
            }

            if ($data->getType() === ImportType::COURSE) {
                if ($data->getTemplateIdNativeType() === 1) {
                    $data->setImportResult(ImportResult::DIDACTIC_TEMPLATE_ID_1_NOT_ALLOWED);
                    return false;
                }
            }

            /** @var ilDidacticTemplateSetting $template */
            $templates = ilDidacticTemplateSettings::getInstanceByObjectType($data->getType()->value)->getTemplates();
            $enabled_templates_by_id = [];
            foreach ($templates as $template) {
                if ($template->isEnabled()) {
                    $enabled_templates_by_id[$template->getId()] = $template;
                }
            }

            if ($data->getTemplateIdNativeType() > 0 && !isset($enabled_templates_by_id[$data->getTemplateIdNativeType()])) {
                $data->setImportResult(ImportResult::DIDACTIC_TEMPLATE_ID_NOT_SUPPORTED_OR_NOT_ENABLED);
                return false;
            }

            if ($data->getAdmins() === '') {
                $data->setImportResult(ImportResult::NO_ADMINS_PROVIDED);
                return false;
            }

            $usr_ids = ilObjUser::_lookupId($data->getValidatedAdmins());
            if (count($usr_ids) === 0) {
                $data->setImportResult(ImportResult::NO_ADMIN_USERS_COULD_BE_DETERMINED);
                return false;
            }

            if (!in_array($data->getAdmissionLink(), [0, 1])) {
                return false;
            }

            if ($data->getTitleDe() === '') {
                $data->setImportResult(ImportResult::MISSING_GERMAN_TITLE);
                return false;
            }

            if ($data->getMinMembers() !== null &&
                $data->getMaxMembers() !== null &&
                $data->getMinMembers() > $data->getMaxMembers()) {
                $data->setImportResult(ImportResult::MIN_MEMBERS_GREATER_THAN_MAX_MEMBERS);
                return false;
            }
        }

        if (in_array($data->getType(), [ImportType::COURSE_LINK, ImportType::GROUP_LINK], true)) {
            if (!in_array($data->getAction(), [ImportAction::INSERT, ImportAction::IGNORE], true)) {
                // course links and group links don't support an `update` action
                $data->setImportResult(ImportResult::NO_UPDATE_ACTION_ALLOWED_FOR_LINKS);
                return false;
            }

            if (!is_numeric($data->getRefId())) {
                $data->setImportResult(ImportResult::MISSING_REF_ID_FOR_LINK);
                return false;
            }

            $obj_id = ilObject::_lookupObjId($data->getRefId());
            if (!$obj_id) {
                $data->setImportResult(ImportResult::INVALID_REF_ID_FOR_LINK);
                return false;
            }

            if ($data->getType() === ImportType::COURSE_LINK && ilObject::_lookupType($obj_id) !== ImportType::COURSE->value) {
                $data->setImportResult(ImportResult::TYPE_MISMATCH_FOR_LINK);
                return false;
            }

            if ($data->getType() === ImportType::GROUP_LINK && ilObject::_lookupType($obj_id) !== ImportType::GROUP->value) {
                $data->setImportResult(ImportResult::TYPE_MISMATCH_FOR_LINK);
                return false;
            }
        }

        return true;
    }
}
