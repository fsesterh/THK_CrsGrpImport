<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ilDateTimeException;
use ilDateTime;
use ilObjectActivation;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;
use ILIAS\DI\Exceptions\Exception;

class BaseObject implements ObjectImporter
{
    const INSERT = 'insert';
    const UPDATE = 'update';
    const IGNORE = 'ignore';
    const STATUS_IGNORED = 'Ignored';
    const STATUS_UPDATED = 'Updated';
    const STATUS_FAILED = 'Failed';
    const STATUS_OK = 'OK';
    const RESULT_IGNORE = 'Entry has set ignore action, ignoring entry.';
    const RESULT_NO_VALID_ACTION = 'No valid action found, ignoring entry.';
    const RESULT_CREATED_SUCCESSFULLY= 'Object created successfully.';
    const RESULT_UPDATED_SUCCESSFULLY= 'Object updated successfully.';
    const RESULT_DATASET_IGNORED = 'Dataset ignored.';
    const RESULT_REF_ID_NOT_FOUND = 'RefId not found. Data not processed.';
    const RESULT_NO_REF_ID_GIVEN_FOR_UPDATE = 'No RefId specified for update. Data not processed.';
    const RESULT_REF_ID_AND_TYPE_DO_NOT_MATCH = 'RefId does not match object-type. Data not processed.';
    const RESULT_NO_PASSWORD_GIVEN_FOR_TYPE_TWO = 'No registration password specified. Data not processed.';
    const RESULT_UNUSABLE_ADMIN_FOUND = 'One or all of the user accounts for admins not found. Data not processed.';
    const RESULT_UNKNOWN_OBJECT_TYPE = 'Object type is not known, Data not processed.';
    const RESULT_DATASET_INCOMPLETE = 'Dataset incomplete. Data not processed.';
    const RESULT_OBJECT_IN_TRASH_IGNORE = 'Object is in trash, ignoring.';
    const RESULT_AVAILABILITY = 'Setting Availability not successful.';


    private ?ImportCsvObject $data;
    private CSVLog $csv_log;

    public function __construct(ImportCsvObject $data, CSVLog $csv_log)
    {
        $this->data = $data;
        $this->csv_log = $csv_log;
    }

    public function ignore()
    {
    }

    public function update() : string
    {
    }

    public function insert() : int
    {
    }

    public function ensureDataIsValidAndComplete() : bool
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

    /**
     * @throws ilDateTimeException
     */
    protected function writeAvailability(int $ref_id) : bool
    {
        try {
            $availability_start = new ilDateTime($this->getData()->getAvailabilityStart(), 2);
            $availability_end = new ilDateTime($this->getData()->getAvailabilityEnd(), 2);
            $activation = new ilObjectActivation();
            $activation->setTimingType(1);
            $activation->setTimingStart($availability_start->getUnixTime());
            $activation->setTimingEnd($availability_end->getUnixTime());
            $activation->update($ref_id);
            return true;
        } catch (Exception $e) {
            $this->getData()->setImportResult(self::RESULT_AVAILABILITY . '( ' . $e->getMessage() . ')');
            return false;
        }

    }

}