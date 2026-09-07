<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use DateTimeImmutable;
use ilDateTime;
use ilDateTimeException;
use ILIAS\DI\Container;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\ILIASObject\Properties\Translations\Language;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportStatus;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportType;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;
use ilObjCourse;
use ilObject;
use ilObjectActivation;
use ilObjectDataCache;
use ilObjGroup;

class BaseObject implements ObjectImporter
{
    public const string INSERT = 'insert';
    public const string UPDATE = 'update';
    public const string IGNORE = 'ignore';
    public const string RESULT_IGNORE = 'Entry has set ignore action, ignoring entry.';
    public const string RESULT_NO_VALID_ACTION = 'No valid action found, ignoring entry.';
    public const string RESULT_CREATED_SUCCESSFULLY = 'Object created successfully.';
    public const string RESULT_UPDATED_SUCCESSFULLY = 'Object updated successfully.';
    public const string RESULT_DATASET_IGNORED = 'Dataset ignored.';
    public const string RESULT_REF_ID_NOT_FOUND = 'RefId not found. Data not processed.';
    public const string RESULT_NO_REF_ID_GIVEN_FOR_UPDATE = 'No RefId specified for update. Data not processed.';
    public const string RESULT_REF_ID_AND_TYPE_DO_NOT_MATCH = 'RefId does not match object-type. Data not processed.';
    public const string RESULT_NO_PASSWORD_GIVEN_FOR_TYPE_TWO = 'No registration password specified. Data not processed.';
    public const string RESULT_UNUSABLE_ADMIN_FOUND = 'One or all of the user accounts for admins not found. Data not processed.';
    public const string RESULT_NO_COURSE_IN_COURSE = 'Creation of course in course not possible.';
    public const string RESULT_UNKNOWN_OBJECT_TYPE = 'Object type is not known, Data not processed.';
    public const string RESULT_DATASET_INCOMPLETE = 'Dataset incomplete. Data not processed.';
    public const string RESULT_DATASET_INVALID = 'Dataset invalid. Data not processed.';
    public const string RESULT_UPDATE_OBJECT_NOT_IN_SUBTREE = 'Dataset invalid. Object for update is not in sub tree.';
    public const string RESULT_UPDATE_OBJECT_HAS_DIFFERENT_TYPE = 'Dataset invalid. Object for update has not the right object type.';
    public const string RESULT_OBJECT_IN_TRASH_IGNORE = 'Object is in trash, ignoring.';
    public const string RESULT_AVAILABILITY = 'Setting Availability not successful.';
    public const string RESULT_UNSUPPORTED_ACTION = 'Dataset invalid, the provided action is not supported';
    public const string RESULT_UNSUPPORTED_OBJECT_TYPE = 'Dataset invalid, the provided object type is not supported';
    public const string RESULT_UNSUPPORTED_REGISTRATION_TYPE = 'Dataset invalid, the provided registration type is not supported';
    public const string RESULT_DIDACTIC_TEMPLATE_ID_1_NOT_ALLOWED = 'Dataset invalid, the didactic template ID 1 is not allowed';
    public const string RESULT_DIDACTIC_TEMPLATE_ID_NOT_SUPPORTED_OR_NOT_ENABLED = 'Dataset invalid, the provided didactic template ID is not supported by the object type or the template is disabled';
    public const string RESULT_NO_ADMINS_PROVIDED = 'Dataset invalid, no admins provided';
    public const string RESULT_NO_ADMIN_USERS_COULD_BE_DETERMINED = 'Dataset invalid, no admin user accounts could be determined from the provided string';
    public const string RESULT_MISSING_GERMAN_TITLE = 'Dataset invalid, no German object title provided';
    public const string RESULT_MIN_MEMBERS_GREATER_THAN_MAX_MEMBERS = 'Dataset invalid, the amount of min members is greater than the amount of max members';
    public const string RESULT_NO_UPDATE_ACTION_ALLOWED_FOR_LINKS = 'Dataset invalid, container reference object do not support an update action';
    public const string RESULT_MISSING_REF_ID_FOR_LINK = 'Dataset invalid, a ref_id has to be provided for course or group references/links';
    public const string RESULT_INVALID_REF_ID_FOR_LINK = 'Dataset invalid, the provided ref_id for the reference/link could not be found';
    public const string RESULT_TYPE_MISMATCH_FOR_LINK = 'Dataset invalid, the object type of the provided ref_id for the reference/link does not match the object type of the container';

    public const array DATE_COLUMNS = [
        "EventStart",
        "EventEnd",
        "AvailabilityStart",
        "AvailabilityEnd",
        "RegistrationStart",
        "RegistrationEnd",
        "UnsubscribeEnd",
        "NewsStartDate"
    ];
    public Container $dic;
    public ilObjectDataCache $dataCache;

    public function __construct(private readonly ImportCsvObject $data, private readonly CSVLog $csv_log, Container $dic)
    {
        global $ilObjDataCache;
        $this->dic = $dic;
        $this->dataCache = $ilObjDataCache;
    }

    protected function getEffectiveActorTimeZone(): string
    {
        $time_zone = $this->getData()->getActorTimezone();
        if (null === $time_zone || '' === $time_zone) {
            $time_zone = date_default_timezone_get();
        }

        return $time_zone;
    }

    /**
     * @throws ilDateTimeException
     */
    protected function writeAvailability(int $ref_id, ilObjGroup|ilObjCourse $crs_or_grp_object = null): bool
    {
        try {
            if ($this->getData()->getAvailabilityStart() !== '' && $this->getData()->getAvailabilityEnd() !== '') {
                $availability_start = $this->checkAndParseDateStringToObject($this->getData()->getAvailabilityStart());
                $availability_end = $this->checkAndParseDateStringToObject($this->getData()->getAvailabilityEnd());

                $activation = new ilObjectActivation();
                $activation->setTimingType(ilObjectActivation::TIMINGS_DEACTIVATED);
                if ($availability_start !== '' && $availability_end !== '') {
                    $activation->setTimingType(ilObjectActivation::TIMINGS_ACTIVATION);
                    $activation->setTimingStart($availability_start->getTimestamp());
                    $activation->setTimingEnd($availability_end->getTimestamp());
                    $activation->toggleVisible((bool) $this->getData()->getAvailabilityVisible());
                    $activation->update($ref_id);
                }
                if ($crs_or_grp_object != null) {
                    $event_start = $this->getData()->getEventStart();
                    $event_end = $this->getData()->getEventEnd();
                    if ($event_start !== '' && $event_end !== '') {
                        $period_start = $this->checkAndParseDateStringToObject($event_start);
                        $period_end = $this->checkAndParseDateStringToObject($event_end);
                        if ($crs_or_grp_object->getType() === ImportType::COURSE->value && $period_start !== '' && $period_end !== '') {
                            $crs_or_grp_object->setCoursePeriod(new ilDateTime(
                                $period_start->getTimestamp(),
                                IL_CAL_UNIX
                            ), new ilDateTime($period_end->getTimestamp(), IL_CAL_UNIX));
                        }
                    }
                    if ($crs_or_grp_object->getType() === ImportType::COURSE->value) {
                        if ($availability_start !== '' && $availability_end !== '') {
                            $crs_or_grp_object->setActivationStart($availability_start->getTimestamp());
                            $crs_or_grp_object->setActivationEnd($availability_end->getTimestamp());
                            $crs_or_grp_object->setActivationVisibility($activation->enabledVisible());
                        }
                    }

                    $crs_or_grp_object->update();
                }
            }
            return true;
        } catch (Exception $e) {
            $this->getData()->setImportResult(self::RESULT_AVAILABILITY . '( ' . $e->getMessage() . ')');
            return false;
        }
    }

    public function handleI18nTitleAndDescription(ilObjGroup|ilObjCourse $object, bool $is_update = false): void
    {
        if (!$is_update && (is_string($this->getData()->getTitleEn()) && $this->getData()->getTitleEn() !== '')) {
            $translation = ($object->getObjectProperties()->getPropertyTranslations())
                ->withDefaultTitle((string) $this->getData()->getTitleDe())
                ->withDefaultDescription((string) $this->getData()->getDescriptionDe())
                ->withResetLanguages()
                ->withLanguage(new Language(
                    'en',
                    $this->getData()->getTitleEn(),
                    $this->getData()->getDescriptionEn(),
                    false
                ))
                ->withLanguage(new Language(
                    'de',
                    $this->getData()->getTitleDe(),
                    $this->getData()->getDescriptionDe(),
                    true,
                    true
                ));
            $object->getObjectProperties()->storePropertyTranslations($translation);
        } elseif ($is_update) {
            $translation = $object->getObjectProperties()->getPropertyTranslations();
            if (is_string($this->getData()->getTitleEn()) && $this->getData()->getTitleEn() !== '') {
                if (isset($languages['en'])) {
                    $translation = $translation->withoutLanguage('en');
                }

                $translation = $translation->withLanguage(new Language(
                    'en',
                    $this->getData()->getTitleEn(),
                    $this->getData()->getDescriptionEn(),
                    false
                ));
                $object->getObjectProperties()->storePropertyTranslations($translation);
            } else {
                $languages = $translation->getLanguages();
                if (isset($languages['en'])) {
                    $translation = $translation->withoutLanguage('en');
                    $object->getObjectProperties()->storePropertyTranslations($translation);
                }
            }
        }
    }

    public function update(): ImportStatus
    {
        return ImportStatus::OK;
    }

    public function ignore(): void
    {
    }

    public function insert(): int
    {
        return 0;
    }

    public function checkPrerequisitesForInsert(): bool
    {
        if ($this->getData()->getTitleDe() === '') {
            $this->getData()->setImportResult(self::RESULT_DATASET_INCOMPLETE);
            return false;
        }

        return $this->checkDateColumnsBeforeUnix();
    }

    public function checkDateColumnsBeforeUnix(): bool
    {
        $invalidColumns = $this->getColumnsBeforeUnix($this->getData());

        if ($invalidColumns !== []) {
            foreach ($invalidColumns as $columnName => $value) {
                $this->dic->logger()->root()->error(sprintf(
                    "Date in column '%s' is before the unix timestamp, unable to import. Value: '%s'",
                    $columnName,
                    $value
                ));
            }
            $this->getData()->setImportResult(self::RESULT_DATASET_INVALID);
            return false;
        }
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function getColumnsBeforeUnix(ImportCsvObject $data): array
    {
        $invalidColumns = [];
        foreach (self::DATE_COLUMNS as $columnName) {
            $getter = "get" . ucfirst($columnName);
            $value = $data->$getter();

            $timestamp = strtotime($value);
            if ($value !== "" && (!$timestamp || $timestamp < 0)) {
                $invalidColumns[ucfirst($columnName)] = $value;
            }
        }
        return $invalidColumns;
    }

    public function getData(): ImportCsvObject
    {
        return $this->data;
    }

    public function checkPrerequisitesForUpdate(int $ref_id, ImportCsvObject $data): bool
    {
        if ($ref_id > 0) {
            if (!$this->isInTrash($ref_id)) {
                if ($this->objectExists($ref_id)) {
                    if ($data->getType()->isCorrectObjType($ref_id)) {
                        return $this->checkDateColumnsBeforeUnix();
                    }

                    $data->setImportResult(self::RESULT_REF_ID_AND_TYPE_DO_NOT_MATCH);
                } else {
                    $data->setImportResult(self::RESULT_REF_ID_NOT_FOUND);
                }
            } else {
                $data->setImportResult(self::RESULT_OBJECT_IN_TRASH_IGNORE);
            }
        } else {
            $data->setImportResult(self::RESULT_NO_REF_ID_GIVEN_FOR_UPDATE);
        }
        return false;
    }

    protected function isInTrash(int $ref_id): bool
    {
        return ilObject::_isInTrash($ref_id);
    }

    protected function objectExists(int $ref_id): bool
    {
        return ilObject::_exists($ref_id, true);
    }

    protected function checkAndParseDateStringToObject(string $date): DateTimeImmutable|string
    {
        $date_immutable = '';
        if (!preg_match("/(\d{2}).(\d{2}).(\d{2}) (\d{2}):(\d{2})/", $date, $d_parts)) {
            $this->dic->logger()->root()->warning('Date for object has not the correct format (d.m.y H:i), tying other parser for: ' . $date);
        } else {
            $date_immutable = DateTimeImmutable::createFromFormat('d.m.y H:i', $date);
            $this->dic->logger()->root()->info('Parsing complete for date: ' . $date);
            return $date_immutable ?: '';
        }
        if (!preg_match("/(\d{2}).(\d{2}).(\d{4}) (\d{2}):(\d{2})/", $date, $d_parts)) {
            $this->dic->logger()->root()->warning('Date for object has not the correct format (d.m.Y H:i), ignoring: ' . $date);
        } else {
            $date_immutable = DateTimeImmutable::createFromFormat('d.m.Y H:i', $date);
            $this->dic->logger()->root()->info('Parsing complete for date: ' . $date);
            return $date_immutable ?: '';
        }

        if (!preg_match("/(\d{2}).(\d{2}).(\d{4})/", $date, $d_parts)) {
            $this->dic->logger()->root()->warning('Date for object has not the correct format (d.m.Y), ignoring: ' . $date);
        } else {
            $date_immutable = DateTimeImmutable::createFromFormat('d.m.Y', $date);
            $this->dic->logger()->root()->info('Parsing complete for date: ' . $date);
            return $date_immutable ?: '';
        }

        return $date_immutable;
    }
}
