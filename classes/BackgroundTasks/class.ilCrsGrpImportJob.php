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

/**
 *
 */
class ilCrsGrpImportJob extends AbstractJob
{
    private ?ilLogger $logger = null;

    public function __construct()
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
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
     * @inheritDoc
     * @throws \ILIAS\BackgroundTasks\Exceptions\InvalidArgumentException
     */
    public function run(array $input, Observer $observer)
    {
        $output = new StringValue();
        $this->logger->info('ilCrsGrpImportJob started...');
        $csv_serialized = $input[0]->getValue();
        $csv_deserialized = unserialize($csv_serialized);
        foreach ($csv_deserialized as $key => $data) {
            if ($data->getType() === 'crs') {
                if ($data->getAction() === BaseObject::INSERT) {
                    $new_course = new Course($data);
                    $new_course->insert();
                } elseif ($data->getAction() === BaseObject::UPDATE) {
                    $new_course = new Course($data);
                    $new_course->update();
                } elseif ($data->getAction() === BaseObject::IGNORE) {

                } else {
                    //Todo: error
                }

            } else {
                if ($data->getType() === 'grp') {
                    if ($data->getAction() === BaseObject::INSERT) {
                        $new_group = new Group($data);
                        $new_group->insert();
                    } elseif ($data->getAction() === BaseObject::UPDATE) {
                        $new_group = new Group($data);
                        $new_group->update();
                    } elseif ($data->getAction() === BaseObject::IGNORE) {

                    } else {
                        //Todo: error
                    }

                } else {
                    //Todo: Error
                }
            }
        }
        $output->setValue('Reporting CSV Import.csv');
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
