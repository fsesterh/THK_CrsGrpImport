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
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ilDateTimeException;

/**
 *
 */
class ilCrsGrpImportJob extends AbstractJob
{
    const COURSE = 'crs';
    const GROUP = 'grp';

    private ?ilLogger $logger = null;
    private CSVLog $csv_log;

    public function __construct()
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
        $this->csv_log = new CSVLog();
    }

    /**
     * @param        $data
     * @param string $base_status
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildGroupObject($data, string $base_status) : string
    {
        $new_group = new Group($data, $this->csv_log);
        return $this->buildObject($new_group, $data, $base_status);
    }

    /**
     * @param        $data
     * @param string $base_status
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildCourseObject($data, string $base_status) : string
    {
        $new_course = new Course($data, $this->csv_log);
        return $this->buildObject($new_course, $data, $base_status);
    }

    /**
     * @param Course|Group $new_object
     * @param        $data
     * @param string $base_status
     * @return string
     * @throws ilDateTimeException
     */
    protected function buildObject($new_object, $data, string $base_status) : string
    {
        $import_data = $new_object->getData();

        if ($data->getAction() === BaseObject::INSERT) {
            $ref_id = $new_object->insert();
            $import_data->setRefId($ref_id);
        } elseif ($data->getAction() === BaseObject::UPDATE) {
            $base_status = $new_object->update();
        } elseif ($data->getAction() === BaseObject::IGNORE) {
            $import_data->setImportResult(BaseObject::RESULT_IGNORE);
            $base_status = BaseObject::STATUS_IGNORED;
        } else {
            $import_data->setImportResult(BaseObject::RESULT_NO_VALID_ACTION);
            $base_status = BaseObject::STATUS_IGNORED;
        }
        $this->csv_log->addEntryToLog(
            $base_status,
            $import_data->getRefId(),
            $import_data->getTitle(),
            $import_data->getValidatedAdmins(),
            $import_data->getImportResult()
        );
        return $base_status;
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
               $base_status = $this->buildCourseObject($data, $base_status);
            } elseif ($data->getType() === self::GROUP) {
                $base_status = $this->buildGroupObject($data, $base_status);
            } else {
                    //Todo: unknown object type error to log
                }
        }
        $output->setValue($this->csv_log->getCSVLog());
        return $output;
    }

    /**
     * @inheritdoc
     */
    public function getExpectedTimeOfTaskInSeconds()
    {
        return 600;
    }
}
