<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use DateTimeImmutable;
use ilDateTime;
use ilDateTimeException;
use ILIAS\DI\Container;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\ILIASObject\Properties\Translations\Language;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportResult;
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
    public ilObjectDataCache $data_cache;

    public function __construct(private readonly ImportCsvObject $data, private readonly CSVLog $csv_log, Container $dic)
    {
        global $ilObjDataCache;
        $this->dic = $dic;
        $this->data_cache = $ilObjDataCache;
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
            $this->getData()->setImportResult(ImportResult::AVAILABILITY, $e->getMessage());
            return false;
        }
    }

    public function handleI18nTitleAndDescription(ilObjGroup|ilObjCourse $object, bool $is_update = false): void
    {
        if (!$is_update && (is_string($this->getData()->getTitleEn()) && $this->getData()->getTitleEn() !== '')) {
            $translation = ($object->getObjectProperties()->getPropertyTranslations())
                ->withResetLanguages()
                ->withDefaultTitle((string) $this->getData()->getTitleDe())
                ->withDefaultDescription((string) $this->getData()->getDescriptionDe())
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
            $this->getData()->setImportResult(ImportResult::DATASET_INCOMPLETE);
            return false;
        }

        return $this->checkDateColumnsBeforeUnix();
    }

    public function checkDateColumnsBeforeUnix(): bool
    {
        $invalid_columns = $this->getColumnsBeforeUnix($this->getData());

        if ($invalid_columns !== []) {
            foreach ($invalid_columns as $column_name => $value) {
                $this->dic->logger()->root()->error(sprintf(
                    "Date in column '%s' is before the unix timestamp, unable to import. Value: '%s'",
                    $column_name,
                    $value
                ));
            }
            $this->getData()->setImportResult(ImportResult::DATASET_INVALID);
            return false;
        }
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function getColumnsBeforeUnix(ImportCsvObject $data): array
    {
        $invalid_columns = [];
        foreach (self::DATE_COLUMNS as $column_name) {
            $getter = "get" . ucfirst($column_name);
            $value = $data->$getter();

            $timestamp = strtotime($value);
            if ($value !== "" && (!$timestamp || $timestamp < 0)) {
                $invalid_columns[ucfirst($column_name)] = $value;
            }
        }
        return $invalid_columns;
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

                    $data->setImportResult(ImportResult::REF_ID_AND_TYPE_DO_NOT_MATCH);
                } else {
                    $data->setImportResult(ImportResult::REF_ID_NOT_FOUND);
                }
            } else {
                $data->setImportResult(ImportResult::OBJECT_IN_TRASH_IGNORE);
            }
        } else {
            $data->setImportResult(ImportResult::NO_REF_ID_GIVEN_FOR_UPDATE);
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
