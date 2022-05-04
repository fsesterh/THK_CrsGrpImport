<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\Frontend\Controller;


use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ZipStream\Exception\FileNotReadableException;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Data\Conversions;
use ILIAS\Plugin\CrsGrpImport\Creator\Course;
use ILIAS\Plugin\CrsGrpImport\Creator\Group;
use ILIAS\Plugin\CrsGrpImport\Creator\BaseObject;
use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use ILIAS\Plugin\CrsGrpImport\BackgroundTasks\ilCrsGrpImportJob;
use ILIAS\BackgroundTasks\Implementation\TaskManager\MockObserver;
use ILIAS\BackgroundTasks\Implementation\TaskManager\BasicTaskManager;
use ILIAS\BackgroundTasks\Implementation\TaskManager\AsyncTaskManager;
use ILIAS\BackgroundTasks\Implementation\TaskManager\SyncTaskManager;
use ILIAS\Plugin\CrsGrpImport\BackgroundTasks\ilCrsGrpImportReport;

/**
 * Class Index
 * @package ILIAS\Plugin\CrsGrpImport\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class Import extends Base
{

	/**
	 * @inheritdoc
	 */
	protected function init()
	{
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function getDefaultCommand()
	{
		return 'import';
	}

	/**
	 * @return string
	 */
	public function import()
	{
        global $DIC;

        $request_body =  $DIC->http()->request()->getParsedBody();
        if( ! array_key_exists('parent_ref_id', $request_body)) {

        }
        $parent_ref_id = $request_body['parent_ref_id'];
        if (false === $DIC->upload()->hasBeenProcessed()) {
            $DIC->upload()->process();
        }

        if (false === $DIC->upload()->hasUploads()) {
            //Todo: Error
        }

        $uploadResults = $DIC->upload()->getResults();
        $uploadResult = array_values($uploadResults)[0];
        if (!($uploadResult instanceof UploadResult)) {
            //Todo: Error
        }

        if ($uploadResult->getStatus()->getCode() === ProcessingStatus::REJECTED) {
            //Todo: Error
        }

        $csv_array = $this->convertCSVToArray($uploadResult->getPath(), $parent_ref_id);
        $bucket = new BasicBucket();
        $bucket->setUserId($this->dic->user()->getId());
        $csvExport = $this->dic->backgroundTasks()->taskFactory()->createTask(ilCrsGrpImportJob::class, [
           serialize($csv_array)
        ]);

        $task = $this->dic->backgroundTasks()->taskFactory()->createTask(ilCrsGrpImportReport::class, [
            $csvExport, 'test.csv'
        ]);
        $bucket->setTask($task);
        $bucket->setTitle('Course and Group CSV import task... ' . time());
        $bucket->setDescription('Course and Group CSV import task');
        $this->dic->backgroundTasks()->taskManager()->run($bucket);

        return '<pre>' . $parent_ref_id . print_r($csv_array,true) . '</pre>';
	}

    /**
     * @param string $importFile
     * @return ImportCSVObject[]
     */
    public function convertCSVToArray(string $importFile, ?int $parent_ref_id = null) : array
    {
        $conversion = new Conversions();
        $row = 0;
        $csv_array = [];
        if (($handle = fopen($importFile, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                if(count($data) > 1) {
                    $row++;
                    if ($row === 1) {
                        continue;
                    }
                    $action = $conversion->ensureStringType($data[0]);
                    $type = $conversion->ensureStringType($data[1]);
                    $ref_id = $conversion->ensureIntType($data[2]);
                    $grp_type = $conversion->ensureIntType($data[3]);
                    $title = $conversion->ensureStringType($data[4]);
                    $description = $conversion->ensureStringType($data[5]);
                    $event_start = $conversion->ensureStringType($data[6]);
                    $event_end = $conversion->ensureStringType($data[7]);
                    $online =  $conversion->ensureIntType($data[8]);
                    $availability_start = $conversion->ensureStringType($data[9]);
                    $availability_end = $conversion->ensureStringType($data[10]);
                    $registration =  $conversion->ensureIntType($data[11]);
                    $registration_pass = $conversion->ensureStringType($data[12]);
                    $admission_link = $conversion->ensureStringType($data[13]);
                    $registration_start = $conversion->ensureStringType($data[14]);
                    $registration_end = $conversion->ensureStringType($data[15]);
                    $unsubscribe_end = $conversion->ensureStringType($data[16]);
                    $admins = $conversion->ensureStringType($data[17]);

                    $import_row = new ImportCsvObject($action,$type,$ref_id,$grp_type,$title,$description,$event_start,$event_end,$online,$availability_start,$availability_end,$registration,$registration_pass,$admission_link,$registration_start,$registration_end,$unsubscribe_end,$admins, $parent_ref_id);
                    $csv_array[] = $import_row;
                } else {
                    // Todo: error csv format not correct!
                }

            }
            fclose($handle);
        }
        return $csv_array;
    }

}