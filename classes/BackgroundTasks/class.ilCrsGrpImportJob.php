<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\BackgroundTasks;

use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Observer;
use ilLogger;
use ILIAS\Plugin\CrsGrpImport\Creator\BaseObject;
use ILIAS\Plugin\CrsGrpImport\Creator\Course;
use ILIAS\Plugin\CrsGrpImport\Creator\Group;
use ILIAS\Plugin\CrsGrpImport\Log\CSVLog;
use ilDateTimeException;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;

/**
 *
 */
class ilCrsGrpImportJob extends AbstractJob
{
    public const COURSE = 'crs';
    public const GROUP = 'grp';
    protected const VALID_TYPE = [0,1,2,3];

    private $logger = null;
    private $csv_log;
    private $dic;

    public function __construct()
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
        $this->dic = $DIC;
        $this->csv_log = new CSVLog();
    }

    /**
     * @return SingleType[]
     */
    public function getInputTypes()
    {
        return
            [
                new SingleType(StringValue::class)
            ];
    }

    /**
     * @inheritDoc
     */
    public function getOutputType()
    {
        return new SingleType(StringValue::class);
    }

    /**
     * @return bool
     */
    public function isStateless()
    {
        return true;
    }

    /**
     * @param array    $input
     * @param Observer $observer
     * @return StringValue
     * @throws \ILIAS\BackgroundTasks\Exceptions\InvalidArgumentException
     * @throws ilDateTimeException
     */
    public function run(array $input, Observer $observer)
    {
        $output = new StringValue();
        $this->logger->info('ilCrsGrpImportJob started...');
        $csv_serialized = $input[0]->getValue();
        $csv_deserialized = unserialize($csv_serialized);
        $base_status = BaseObject::STATUS_OK;
        foreach ($csv_deserialized as $key => $data) {
            if ($data->getType() === self::COURSE) {
                $base_status = $this->buildCourseObject($data);
            } elseif ($data->getType() === self::GROUP) {
                $base_status = $this->buildGroupObject($data);
            } else {
                $base_status = BaseObject::STATUS_FAILED;
                $data->setImportResult(BaseObject::RESULT_UNKNOWN_OBJECT_TYPE);
            }
            $this->csv_log->addEntryToLog(
                $base_status,
                $data->getRefId(),
                $data->getTitle(),
                $data->getValidatedAdmins(),
                $data->getImportResult()
            );
        }

        $output->setValue($this->csv_log->getCSVLog());
        return $output;
    }

    /**
     * @param        $data
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildCourseObject($data) : string
    {
        $new_course = new Course($data, $this->csv_log, $this->dic);
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
        if($this->ensureDataIsValid($data)) {
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
        if( ! in_array(strtolower($data->getAction()), [BaseObject::INSERT, BaseObject::UPDATE, BaseObject::IGNORE])) {
            return false;
        }
        if($data->getTitle() === '') {
            return false;
        }
        if(! in_array($data->getType(),  [self::COURSE, self::GROUP])) {
            return false;
        }
        if(! in_array($data->getRegistrationNative(), self::VALID_TYPE)) {
            return false;
        }
        if(! in_array($data->getGrpTypeNative(), self::VALID_TYPE)) {
            return false;
        }
        if($data->getAdmins() === '') {
            return false;
        } else {
            $usr_ids = \ilObjUser::_lookupId($data->getValidatedAdmins());
            if( count($usr_ids) === 0 ) {
                return false;
            }
        }
        if( ! in_array($data->getAdmissionLink(), [0,1])) {
            return false;
        }
        return true;
    }

    /**
     * @param        $datas
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildGroupObject($data) : string
    {
        $new_group = new Group($data, $this->csv_log, $this->dic);
        return $this->buildObject($new_group, $data);
    }

    /**
     * @inheritdoc
     */
    public function getExpectedTimeOfTaskInSeconds()
    {
        return 600;
    }
}
