<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ilDateTimeException;
use ilDateTime;
use ilObjectActivation;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;
use ILIAS\DI\Exceptions\Exception;
use ilObject;
use ILIAS\DI\Container;
use ILIAS\UI\Implementation\Component\Input\Field\DateTime;
use DateTimeImmutable;

class BaseObject implements ObjectImporter
{
    public const IL_CSV_IMPORT_DATE_TIME = IL_CAL_DATETIME;
    public const IL_CSV_IMPORT_DATE = IL_CAL_DATE;


    public const INSERT = 'insert';
    public const UPDATE = 'update';
    public const IGNORE = 'ignore';
    public const STATUS_IGNORED = 'Ignored';
    public const STATUS_UPDATED = 'Updated';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_OK = 'OK';
    public const RESULT_IGNORE = 'Entry has set ignore action, ignoring entry.';
    public const RESULT_NO_VALID_ACTION = 'No valid action found, ignoring entry.';
    public const RESULT_CREATED_SUCCESSFULLY = 'Object created successfully.';
    public const RESULT_UPDATED_SUCCESSFULLY = 'Object updated successfully.';
    public const RESULT_DATASET_IGNORED = 'Dataset ignored.';
    public const RESULT_REF_ID_NOT_FOUND = 'RefId not found. Data not processed.';
    public const RESULT_NO_REF_ID_GIVEN_FOR_UPDATE = 'No RefId specified for update. Data not processed.';
    public const RESULT_REF_ID_AND_TYPE_DO_NOT_MATCH = 'RefId does not match object-type. Data not processed.';
    public const RESULT_NO_PASSWORD_GIVEN_FOR_TYPE_TWO = 'No registration password specified. Data not processed.';
    public const RESULT_UNUSABLE_ADMIN_FOUND = 'One or all of the user accounts for admins not found. Data not processed.';
    public const RESULT_NO_COURSE_IN_COURSE = 'Creation of course in course not possible.';
    public const RESULT_UNKNOWN_OBJECT_TYPE = 'Object type is not known, Data not processed.';
    public const RESULT_DATASET_INCOMPLETE = 'Dataset incomplete. Data not processed.';
    public const RESULT_DATASET_INVALID = 'Dataset invalid. Data not processed.';
    public const RESULT_UPDATE_OBJECT_NOT_IN_SUBTREE = 'Dataset invalid. Object for update is not in sub tree.';
    public const RESULT_UPDATE_OBJECT_HAS_DIFFERENT_TYPE = 'Dataset invalid. Object for update has not the right object type.';
    public const RESULT_OBJECT_IN_TRASH_IGNORE = 'Object is in trash, ignoring.';
    public const RESULT_AVAILABILITY = 'Setting Availability not successful.';

    private $data;
    private $csv_log;
    public $dic;
    public $dataCache;

    public function __construct(ImportCsvObject $data, CSVLog $csv_log, Container $dic)
    {
        global $ilObjDataCache;
        $this->data = $data;
        $this->csv_log = $csv_log;
        $this->dic = $dic;
        $this->dataCache = $ilObjDataCache;
    }

    protected function getEffectiveActorTimeZone() : string
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
    protected function writeAvailability(int $ref_id, $crs_or_grp_object = null) : bool
    {
        try {

            if($this->getData()->getAvailabilityStart() !== '' && $this->getData()->getAvailabilityEnd() !== '')
            {
                $availability_start = $this->checkAndParseDateStringToObject($this->getData()->getAvailabilityStart());
                $availability_end = $this->checkAndParseDateStringToObject( $this->getData()->getAvailabilityEnd());

                $activation = new ilObjectActivation();
                $activation->setTimingType(1);
                if($availability_start !== '' && $availability_end !== '') {
                    $activation->setTimingStart($availability_start->getTimestamp());
                    $activation->setTimingEnd($availability_end->getTimestamp());
                    $activation->update($ref_id);
                }
                if($crs_or_grp_object != null) {
                    $event_start = $this->getData()->getEventStart();
                    $event_end = $this->getData()->getEventEnd();
                    if($event_start !== '' && $event_end !== '') {
                        $period_start = $this->checkAndParseDateStringToObject($event_start);
                        $period_end = $this->checkAndParseDateStringToObject($event_end);
                        if($crs_or_grp_object->getType() === 'crs' && $period_start !== '' && $period_end !== '') {
                            $crs_or_grp_object->setCoursePeriod(new ilDateTime($period_start->getTimestamp(), IL_CAL_UNIX), new ilDateTime($period_end->getTimestamp(), IL_CAL_UNIX));
                        }
                    }
                if($crs_or_grp_object->getType() === 'crs') {
                    if($availability_start !== '' && $availability_end !== '') {
                        $crs_or_grp_object->setActivationStart($availability_start->getTimestamp());
                        $crs_or_grp_object->setActivationEnd($availability_end->getTimestamp());
                        $crs_or_grp_object->setActivationVisibility(1);
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

    public function update() : string
    {
    }

    public function ignore()
    {
    }

    public function insert() : int
    {
    }

    public function checkPrerequisitesForInsert() : bool
    {
        if ($this->getData()->getTitle() === '') {
            $this->getData()->setImportResult(self::RESULT_DATASET_INCOMPLETE);
            return false;
        }
        return true;
    }

    public function getData() : ImportCsvObject
    {
        return $this->data;
    }

    public function checkPrerequisitesForUpdate(int $ref_id, ImportCsvObject $data) : bool
    {
        if ($ref_id > 0) {
            if (!$this->isInTrash($ref_id)) {
                if ($this->objectExists($ref_id)) {
                    if ($this->isCorrectObjectType($ref_id, $data->getType())) {
                        return true;
                    } else {
                        $data->setImportResult(self::RESULT_REF_ID_AND_TYPE_DO_NOT_MATCH);
                    }
                } else {
                    $data->setImportResult(BaseObject::RESULT_REF_ID_NOT_FOUND);
                }
            } else {
                $data->setImportResult(BaseObject::RESULT_OBJECT_IN_TRASH_IGNORE);
            }
        } else {
            $data->setImportResult(BaseObject::RESULT_NO_REF_ID_GIVEN_FOR_UPDATE);
        }
        return false;
    }

    protected function isInTrash(int $ref_id) : bool
    {
        return ilObject::_isInTrash($ref_id);
    }

    protected function objectExists(int $ref_id) : bool
    {
        return ilObject::_exists($ref_id, true);
    }

    protected function isCorrectObjectType(int $ref_id, string $type) : bool
    {
        $obj_type = ilObject::_lookupType($ref_id, true);
        return $obj_type === $type;
    }

    /**
     * @param string $date
     * @return DateTimeImmutable|string
     */
    protected function checkAndParseDateStringToObject(string $date) {
        $date_immutable = '';
        if (!preg_match("/(\d{2}).(\d{2}).(\d{2}) (\d{2}):(\d{2})/", $date, $d_parts)) {
            $this->dic->logger()->root()->warning('Date for object has not the correct format (d.m.y H:i), tying other parser for: ' . $date);
        } else {
            $date_immutable = DateTimeImmutable::createFromFormat('d.m.y H:i', $date);
            $this->dic->logger()->root()->info('Parsing complete for date: ' . $date);
        }
        if (!preg_match("/(\d{2}).(\d{2}).(\d{4}) (\d{2}):(\d{2})/", $date, $d_parts)) {
            $this->dic->logger()->root()->warning('Date for object has not the correct format (d.m.Y H:i), ignoring: ' . $date);
        } else {
            $date_immutable = DateTimeImmutable::createFromFormat('d.m.Y H:i', $date);
            $this->dic->logger()->root()->info('Parsing complete for date: ' . $date);
        }

        if($date_immutable === false) {
            return '';
        }
        return $date_immutable;
    }
}
