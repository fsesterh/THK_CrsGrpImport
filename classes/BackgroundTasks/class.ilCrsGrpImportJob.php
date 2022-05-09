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
     * @throws \ilDateTimeException
     */
    public function run(array $input, Observer $observer)
    {
        $output = new StringValue();
        $this->logger->info('ilCrsGrpImportJob started...');
        $csv_serialized = $input[0]->getValue();
        $csv_deserialized = unserialize($csv_serialized);
        foreach ($csv_deserialized as $key => $data) {
            if ($data->getType() === self::COURSE) {
                $new_course = new Course($data, $this->csv_log);
                if ($data->getAction() === BaseObject::INSERT) {
                    $new_course->insert();
                    $this->csv_log->addEntryToLog(BaseObject::OK,
                        $new_course->getData()->getRefId(),
                        $new_course->getData()->getTitle(),
                        $new_course->getData()->getValidatedAdmins(),
                        $new_course->getData()->getImportResult()
                    );
                } elseif ($data->getAction() === BaseObject::UPDATE) {
                    $new_course->update();
                    $this->csv_log->addEntryToLog(BaseObject::OK,
                        $new_course->getData()->getRefId(),
                        $new_course->getData()->getTitle(),
                        $new_course->getData()->getValidatedAdmins(),
                        $new_course->getData()->getImportResult()
                    );
                } elseif ($data->getAction() === BaseObject::IGNORE) {
                    $new_course->getData()->setImportResult('Entry has ignore action set, ignoring entry.');
                    $this->csv_log->addEntryToLog(BaseObject::IGNORE,
                        $new_course->getData()->getRefId(),
                        $new_course->getData()->getTitle(),
                        $new_course->getData()->getValidatedAdmins(),
                        $new_course->getData()->getImportResult()
                    );
                } else {
                    $new_course->getData()->setImportResult('No valid action found, ignoring entry.');
                }

            } elseif ($data->getType() === self::GROUP) {
                    $new_group = new Group($data, $this->csv_log);
                    if ($data->getAction() === BaseObject::INSERT) {
                        $new_group->insert();
                        $this->csv_log->addEntryToLog(BaseObject::OK,
                            $new_group->getData()->getRefId(),
                            $new_group->getData()->getTitle(),
                            $new_group->getData()->getValidatedAdmins(),
                            $new_group->getData()->getImportResult()
                        );
                    } elseif ($data->getAction() === BaseObject::UPDATE) {
                        $new_group->update();
                        $this->csv_log->addEntryToLog(BaseObject::OK,
                            $new_group->getData()->getRefId(),
                            $new_group->getData()->getTitle(),
                            $new_group->getData()->getValidatedAdmins(),
                            $new_group->getData()->getImportResult()
                        );
                    } elseif ($data->getAction() === BaseObject::IGNORE) {
                        $new_group->getData()->setImportResult('Entry has ignore action set, ignoring entry.');
                        $this->csv_log->addEntryToLog(BaseObject::IGNORE,
                            $new_group->getData()->getRefId(),
                            $new_group->getData()->getTitle(),
                            $new_group->getData()->getValidatedAdmins(),
                            $new_group->getData()->getImportResult()
                        );
                    } else {
                        $new_group->getData()->setImportResult('No valid action found, ignoring entry.');
                    }

                }
            else {
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
