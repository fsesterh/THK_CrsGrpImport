<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\Frontend\Controller;

use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Data\Conversions;
use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use ILIAS\Plugin\CrsGrpImport\BackgroundTasks\ilCrsGrpImportJob;
use ILIAS\Plugin\CrsGrpImport\BackgroundTasks\ilCrsGrpImportReport;
use ilLink;
use ilObject;
use ilCrsGrpImportPlugin;
use ilUtil;

/**
 * Class Index
 * @package ILIAS\Plugin\CrsGrpImport\Frontend\Controller
 * @author  Michael Jansen <mjansen@databay.de>
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
     * @return void
     * @throws \ILIAS\FileUpload\Exception\IllegalStateException
     */
    public function import()
    {
        global $DIC;

        $request_body = $DIC->http()->request()->getParsedBody();
        if (!array_key_exists('parent_ref_id', $request_body)) {
        }
        $parent_ref_id = $request_body['parent_ref_id'];
        if (false === $DIC->upload()->hasBeenProcessed()) {
            $DIC->upload()->process();
        }

        if (false === $DIC->upload()->hasUploads()) {
            ilUtil::sendFailure(ilCrsGrpImportPlugin::getInstance()->txt('upload_error'), true);
            $this->redirectToRefId($parent_ref_id);
        }

        $uploadResults = $DIC->upload()->getResults();
        $uploadResult = array_values($uploadResults)[0];
        if (!($uploadResult instanceof UploadResult)) {
            ilUtil::sendFailure(ilCrsGrpImportPlugin::getInstance()->txt('upload_error'), true);
            $this->redirectToRefId($parent_ref_id);
        }

        if ($uploadResult->getStatus()->getCode() === ProcessingStatus::REJECTED) {
            ilUtil::sendFailure(ilCrsGrpImportPlugin::getInstance()->txt('upload_error'), true);
            $this->redirectToRefId($parent_ref_id);
        }

        $csv_array = $this->convertCSVToArray($uploadResult->getPath(), $parent_ref_id);
        $bucket = new BasicBucket();
        $bucket->setUserId($this->dic->user()->getId());
        $csvExport = $this->dic->backgroundTasks()->taskFactory()->createTask(ilCrsGrpImportJob::class, [
            serialize($csv_array)
        ]);

        $task = $this->dic->backgroundTasks()->taskFactory()->createTask(ilCrsGrpImportReport::class, [
            $csvExport,
            'import_log.csv'
        ]);
        $bucket->setTask($task);
        $bucket->setTitle(ilCrsGrpImportPlugin::getInstance()->txt('import_title') . time());
        $bucket->setDescription(ilCrsGrpImportPlugin::getInstance()->txt('import_description'));
        $this->dic->backgroundTasks()->taskManager()->run($bucket);

        $this->redirectToRefId($parent_ref_id);
    }

    protected function redirectToRefId(int $ref_id) : void
    {
        $url = '#';
        if ($ref_id > 0) {
            $type = ilObject::_lookupType($ref_id, true);
            $url = ilLink::_getStaticLink(
                $ref_id,
                $type,
                true
            );
        }
        $this->dic->ctrl()->redirectToURL($url);
    }

    /**
     * @param string   $importFile
     * @param int|null $parent_ref_id
     * @return ImportCsvObject[]
     */
    public function convertCSVToArray(string $importFile, ?int $parent_ref_id = null) : array
    {
        $conversion = new Conversions();
        $row = 0;
        $csv_array = [];
        if (($handle = fopen($importFile, 'rb')) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                if (count($data) > 1) {
                    $row++;
                    if ($row === 1) {
                        continue;
                    }
                    $i = 0;

                    $action = $conversion->ensureStringType($data[$i++]); // 0
                    $type = $conversion->ensureStringType($data[$i++]); // 1
                    $ref_id = $conversion->ensureIntType($data[$i++]); // 2
                    $template = $conversion->ensureIntOrNullType($data[$i++]); // 3
                    $title_de = $conversion->ensureStringType($data[$i++]); // 4
                    $title_en = $conversion->ensureStringType($data[$i++]); // 5
                    $description_de = $conversion->ensureStringType($data[$i++]); // 6
                    $description_en = $conversion->ensureStringType($data[$i++]); // 7
                    $event_start = $conversion->ensureStringType($data[$i++]); // 8
                    $event_end = $conversion->ensureStringType($data[$i++]); // 9
                    $online = $conversion->ensureIntType($data[$i++]); // 10
                    $availability_start = $conversion->ensureStringType($data[$i++]); // 11
                    $availability_end = $conversion->ensureStringType($data[$i++]); // 12
                    $availability_visible = $conversion->ensureIntOrNullType($data[$i++]); // 13
                    $registration = $conversion->ensureIntType($data[$i++]); // 14
                    $registration_pass = $conversion->ensureStringType($data[$i++]); // 15
                    $admission_link = $conversion->ensureIntType($data[$i++]); // 16
                    $registration_start = $conversion->ensureStringType($data[$i++]); // 17
                    $registration_end = $conversion->ensureStringType($data[$i++]); // 18
                    $unsubscribe_end = $conversion->ensureStringType($data[$i++]); // 19
                    $limit_members = $conversion->ensureIntOrNullType($data[$i++]); // 20
                    $min_members = $conversion->ensureIntOrNullType($data[$i++]); // 21
                    $max_members = $conversion->ensureIntOrNullType($data[$i++]); // 22
                    $waiting_list = $conversion->ensureIntOrNullType($data[$i++]); // 23
                    $admins = $conversion->ensureStringType($data[$i++]); // 24

                    $import_row = new ImportCsvObject(
                        $action,
                        $type,
                        $ref_id,
                        $template,
                        $title_de,
                        $title_en,
                        $description_de,
                        $description_de,
                        $event_start,
                        $event_end,
                        $online,
                        $availability_start,
                        $availability_end,
                        $availability_visible,
                        $registration,
                        $registration_pass,
                        $admission_link ?: 0,
                        $registration_start,
                        $registration_end,
                        $unsubscribe_end,
                        $limit_members,
                        $min_members,
                        $max_members,
                        $waiting_list,
                        $admins,
                        $parent_ref_id,
                        $this->dic->user()->getTimeZone()
                    );
                    $csv_array[] = $import_row;
                }
            }
            fclose($handle);
        }
        return $csv_array;
    }
}
