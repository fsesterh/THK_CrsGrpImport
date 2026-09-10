<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\CrsGrpImport\Frontend\Controller;

use ilCrsGrpImportPlugin;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\Plugin\CrsGrpImport\Data\Conversions;
use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportAction;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportType;
use ILIAS\Plugin\CrsGrpImport\Repository\QueuedRepository;
use ilLink;
use ilObject;
use ilRepositoryGUI;

class Import extends Base
{
    private QueuedRepository $queued_repo;

    private const array CSV_HEADERS = [
        "Action",
        "Type",
        "RefId",
        "Template",
        "TitleDE",
        "TitleEN",
        "DescriptionDE",
        "DescriptionEN",
        "EventStart",
        "EventEnd",
        "Online",
        "AvailabilityStart",
        "AvailabilityEnd",
        "AvailabilityVisible",
        "Registration",
        "RegistrationPass",
        "AdmissionLink",
        "RegistrationStart",
        "RegistrationEnd",
        "UnsubscribeEnd",
        "LimitMembers",
        "MinMembers",
        "MaxMembers",
        "WaitingList",
        "News",
        "NewsBlock",
        "NewsDefaultAccess",
        "NewsRSSFeed",
        "NewsTimeline",
        "NewsTimeAutoEntry",
        "NewsTimeLanding",
        "NewsStartDate",
        "MemberGallery",
        "Admins"
    ];

    protected function init(): void
    {
        parent::init();
        $this->queued_repo = QueuedRepository::getInstance();
    }

    public function getDefaultCommand(): string
    {
        return 'Import.cancel';
    }

    /**
     * @throws IllegalStateException
     */
    public function import(): void
    {
        global $DIC;

        $plugin = ilCrsGrpImportPlugin::getInstance();

        $parent_ref_id = $this->http_wrapper->post()->retrieve(
            'parent_ref_id',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->int(),
                $this->refinery->always(null)
            ])
        );

        if (false === $DIC->upload()->hasBeenProcessed()) {
            $DIC->upload()->process();
        }

        if (false === $DIC->upload()->hasUploads()) {
            $this->ui_util->sendFailure($plugin->txt('upload_error'), true);
            $this->redirectToRefId($parent_ref_id);
        }

        $upload_results = $DIC->upload()->getResults();
        $upload_result = array_values($upload_results)[0];
        if (!($upload_result instanceof UploadResult)) {
            $this->ui_util->sendFailure($plugin->txt('upload_error'), true);
            $this->redirectToRefId($parent_ref_id);
        }

        if ($upload_result->getStatus()->getCode() === ProcessingStatus::REJECTED) {
            $this->ui_util->sendFailure($plugin->txt('upload_error'), true);
            $this->redirectToRefId($parent_ref_id);
        }

        $csv_array = $this->convertCSVToArray($plugin, $upload_result->getPath(), $parent_ref_id);
        if ($this->queued_repo->queueImport(serialize($csv_array), $this->dic->user()->getId())) {
            $this->ui_util->sendSuccess(
                $this->getCoreController()->getPluginObject()->txt("import.queued.success"),
                true
            );
        } else {
            $this->ui_util->sendFailure(
                $this->getCoreController()->getPluginObject()->txt("import.queued.failure"),
                true
            );
        }

        $this->redirectToRefId($parent_ref_id);
    }

    public function cancel(): void
    {
        $this->dic->ctrl()->redirectByClass(ilRepositoryGUI::class);
    }

    protected function redirectToRefId(int $ref_id): void
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
     * @return ImportCsvObject[]
     */
    public function convertCSVToArray(ilCrsGrpImportPlugin $plugin, string $import_file, ?int $parent_ref_id = null): array
    {
        $conversion = new Conversions();
        $row = 0;
        $csv_array = [];
        if (($handle = fopen($import_file, 'rb')) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                if (count($data) > 1) {
                    $row++;
                    if ($row === 1) {
                        //Header
                        $failures = [];

                        foreach (self::CSV_HEADERS as $expected_position => $expected_column_title) {
                            $found_position = array_search($expected_column_title, $data, true);
                            if (is_int($found_position)) {
                                if ($found_position !== $expected_position) {
                                    $failures[] = sprintf(
                                        $plugin->txt("csv.verification.header.column.position.wrong"),
                                        $expected_column_title,
                                        $expected_position,
                                        $found_position
                                    );
                                }
                            } else {
                                $failures[] = sprintf(
                                    $plugin->txt("csv.verification.header.column.missing"),
                                    $expected_column_title,
                                    $expected_position
                                );
                            }
                        }

                        if ($failures !== []) {
                            $error_message = "<ul>";
                            foreach ($failures as $failure_message) {
                                $error_message .= "<li>$failure_message</li>";
                            }
                            $error_message .= "</ul>";
                            $this->ui_util->sendFailure($error_message, true);

                            $this->dic->ctrl()->setParameterByClass(ilRepositoryGUI::class, "ref_id", $parent_ref_id);
                            $this->dic->ctrl()->setParameterByClass(ilRepositoryGUI::class, "new_type", "crs"); //What type shouldn't matter (either crs or grp)
                            $this->dic->ctrl()->redirectByClass(ilRepositoryGUI::class, "create");
                        }

                        continue;
                    }
                    $i = 0;

                    $action = ImportAction::tryFrom(strtolower($conversion->ensureStringType($data[$i++]))) ?? ImportAction::UNSUPPORTED; // 0
                    $type = ImportType::tryFrom(strtolower($conversion->ensureStringType($data[$i++]))) ?? ImportType::UNSUPPORTED; // 1
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
                    $news = $conversion->ensureIntOrNullType($data[$i++]); // 24
                    $news_block = $conversion->ensureIntOrNullType($data[$i++]); // 25
                    $news_default_access = $conversion->ensureIntOrNullType($data[$i++]); // 26
                    $news_rss_feed = $conversion->ensureIntOrNullType($data[$i++]); // 27
                    $news_timeline = $conversion->ensureIntOrNullType($data[$i++]); // 28
                    $news_time_auto_entry = $conversion->ensureIntOrNullType($data[$i++]); // 29
                    $news_time_landing = $conversion->ensureIntOrNullType($data[$i++]); // 30
                    $news_start_date = $conversion->ensureStringType($data[$i++]); // 31
                    $member_gallery = $conversion->ensureIntOrNullType($data[$i++]); // 32
                    $admins = $conversion->ensureStringType($data[$i++]); // 33

                    $import_row = new ImportCsvObject(
                        $action,
                        $type,
                        $ref_id,
                        $template,
                        $title_de,
                        $title_en,
                        $description_de,
                        $description_en,
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
                        (bool) $news,
                        (bool) $news_block,
                        (bool) $news_default_access,
                        (bool) $news_rss_feed,
                        (bool) $news_timeline,
                        (bool) $news_time_auto_entry,
                        (bool) $news_time_landing,
                        $news_start_date,
                        $member_gallery,
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
