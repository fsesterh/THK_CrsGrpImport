<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ilDateTimeException;
use ilDateTime;
use ilObjectActivation;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;

class BaseObject implements ObjectImporter
{
    const INSERT = 'insert';
    const UPDATE = 'update';
    const IGNORE = 'ignore';
    const IGNORED = 'Ignored';
    const OK = 'OK';

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

    public function update()
    {
    }

    public function insert() : int
    {
    }

    public function ensureDataIsValidAndComplete() : bool
    {
        if ($this->getData()->getTitle() === '') {
            //Todo: Add error to csv log if validation is false
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
    protected function writeAvailability(int $ref_id) : void
    {
        $availability_start = new ilDateTime($this->getData()->getAvailabilityStart(), 2);
        $availability_end = new ilDateTime($this->getData()->getAvailabilityEnd(), 2);
        $activation = new ilObjectActivation();
        $activation->setTimingType(1);
        $activation->setTimingStart($availability_start->getUnixTime());
        $activation->setTimingEnd($availability_end->getUnixTime());
        $activation->update($ref_id);
    }

}