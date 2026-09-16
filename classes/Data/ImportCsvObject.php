<?php

namespace ILIAS\Plugin\CrsGrpImport\Data;

use Exception;
use ilCourseConstants;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportAction;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportResult;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportType;
use JsonSerializable;
use Throwable;

class ImportCsvObject implements JsonSerializable
{
    private ImportResult $import_result;
    private string $additional_import_result_info = '';

    public function __construct(
        private readonly ImportAction $action,
        private readonly ImportType $type,
        private int $ref_id,
        private readonly ?int $template_id,
        private readonly ?string $title_de,
        private readonly ?string $title_en,
        private readonly ?string $description_de,
        private readonly ?string $description_en,
        private readonly ?string $event_start,
        private readonly ?string $event_end,
        private readonly int $online,
        private readonly ?string $availability_start,
        private readonly ?string $availability_end,
        private readonly ?int $availability_visible,
        private readonly int $registration,
        private readonly string $registration_pass,
        private readonly int $admission_link,
        private readonly ?string $registration_start,
        private readonly ?string $registration_end,
        private readonly ?string $unsubscribe_end,
        private readonly ?int $limit_members,
        private readonly ?int $min_members,
        private readonly ?int $max_members,
        private readonly ?int $waiting_list,
        private readonly bool $news,
        private readonly bool $news_block,
        private readonly bool $news_default_access,
        private readonly bool $news_rss_feed,
        private readonly bool $news_timeline,
        private readonly bool $news_time_auto_entry,
        private readonly bool $news_time_landing,
        private readonly string $news_start_date,
        private readonly ?int $member_gallery,
        private readonly string $admins,
        private readonly ?int $parent_ref_id,
        private readonly ?string $actor_timezone
    ) {
        $this->import_result = ImportResult::NONE;
    }

    public function getActorTimezone(): ?string
    {
        return $this->actor_timezone;
    }

    public function getAction(): ImportAction
    {
        return $this->action;
    }

    public function getType(): ImportType
    {
        return $this->type;
    }

    public function getRefId(): int
    {
        return $this->ref_id;
    }

    public function setRefId(int $ref_id): self
    {
        $this->ref_id = $ref_id;
        return $this;
    }

    public function getTemplateIdNativeType(): ?int
    {
        return $this->template_id;
    }

    public function getTitleDe(): ?string
    {
        return $this->title_de;
    }

    public function getDescriptionDe(): ?string
    {
        return $this->description_de;
    }

    public function getEventStart(): string
    {
        return $this->event_start;
    }

    public function getEventEnd(): string
    {
        return $this->event_end;
    }

    public function getOnline(): int
    {
        return $this->online;
    }

    public function getAvailabilityStart(): string
    {
        return $this->availability_start;
    }

    public function getAvailabilityEnd(): string
    {
        return $this->availability_end;
    }

    public function getRegistrationNative(): int
    {
        return $this->registration;
    }

    public function getRegistrationTypeForCourse(): int
    {
        return match ($this->registration) {
            1 => ilCourseConstants::IL_CRS_SUBSCRIPTION_DIRECT,
            2 => ilCourseConstants::IL_CRS_SUBSCRIPTION_PASSWORD,
            3 => ilCourseConstants::IL_CRS_SUBSCRIPTION_CONFIRMATION,
            default => ilCourseConstants::IL_CRS_SUBSCRIPTION_DEACTIVATED,
        };
    }

    public function getRegistrationType(): int
    {
        return $this->registration;
    }

    public function getRegistrationPass(): string
    {
        return $this->registration_pass;
    }

    public function getAdmissionLink(): int
    {
        return $this->admission_link;
    }

    public function getRegistrationStart(): string
    {
        return $this->registration_start;
    }

    public function getRegistrationEnd(): string
    {
        return $this->registration_end;
    }

    public function getUnsubscribeEnd(): string
    {
        return $this->unsubscribe_end;
    }

    public function getValidatedAdmins(): array
    {
        if ($this->getAdmins() !== '') {
            $logins = explode(',', $this->admins);
            return array_map(trim(...), $logins);
        }
        return [];
    }

    public function getAdmins(): string
    {
        return $this->admins;
    }

    public function getMemberGallery(): ?int
    {
        return $this->member_gallery;
    }

    public function getParentRefId(): ?int
    {
        return $this->parent_ref_id;
    }

    public function getImportResult(): ImportResult
    {
        return $this->import_result;
    }

    public function setImportResult(ImportResult $import_result, string $additional_import_result_info = ""): self
    {
        $this->import_result = $import_result;
        $this->additional_import_result_info = $additional_import_result_info;
        return $this;
    }

    public function getAdditionalImportResultInfo(): string
    {
        return $this->additional_import_result_info;
    }

    public function getTitleEn(): ?string
    {
        return $this->title_en;
    }

    public function getDescriptionEn(): ?string
    {
        return $this->description_en;
    }

    public function getAvailabilityVisible(): ?int
    {
        return $this->availability_visible;
    }

    public function getLimitMembers(): ?int
    {
        return $this->limit_members;
    }

    public function getMinMembers(): ?int
    {
        return $this->min_members;
    }

    public function getMaxMembers(): ?int
    {
        return $this->max_members;
    }

    public function getWaitingList(): ?int
    {
        return $this->waiting_list;
    }

    public function getNews(): bool
    {
        return $this->news;
    }

    public function isNewsBlock(): bool
    {
        return $this->news_block;
    }

    public function getNewsDefaultAccess(): bool
    {
        return $this->news_default_access;
    }

    public function getNewsRssFeed(): bool
    {
        return $this->news_rss_feed;
    }

    public function getNewsTimeline(): bool
    {
        return $this->news_timeline;
    }

    public function getNewsTimeAutoEntry(): bool
    {
        return $this->news_time_auto_entry;
    }

    public function getNewsTimeLanding(): bool
    {
        return $this->news_time_landing;
    }

    public function getNewsStartDate(): string
    {
        return $this->news_start_date;
    }

    public function jsonSerialize(): array
    {
        return [
            'action' => $this->getAction()->value,
            'type' => $this->getType()->value,
            'import_result' => $this->getImportResult()->value,
            'additional_import_result_info' => $this->getAdditionalImportResultInfo(),
            'ref_id' => $this->getRefId(),
            'template_id' => $this->getTemplateIdNativeType(),
            'title_de' => $this->getTitleDe(),
            'title_en' => $this->getTitleEn(),
            'description_de' => $this->getDescriptionDe(),
            'description_en' => $this->getDescriptionEn(),
            'event_start' => $this->getEventStart(),
            'event_end' => $this->getEventEnd(),
            'online' => $this->getOnline(),
            'availability_start' => $this->getAvailabilityStart(),
            'availability_end' => $this->getAvailabilityEnd(),
            'availability_visible' => $this->getAvailabilityVisible(),
            'registration' => $this->getRegistrationNative(),
            'registration_pass' => $this->getRegistrationPass(),
            'admission_link' => $this->getAdmissionLink(),
            'registration_start' => $this->getRegistrationStart(),
            'registration_end' => $this->getRegistrationEnd(),
            'unsubscribe_end' => $this->getUnsubscribeEnd(),
            'limit_members' => $this->getLimitMembers(),
            'min_members' => $this->getMinMembers(),
            'max_members' => $this->getMaxMembers(),
            'waiting_list' => $this->getWaitingList(),
            'news' => $this->getNews(),
            'news_block' => $this->isNewsBlock(),
            'news_default_access' => $this->getNewsDefaultAccess(),
            'news_rss_feed' => $this->getNewsRssFeed(),
            'news_timeline' => $this->getNewsTimeline(),
            'news_time_auto_entry' => $this->getNewsTimeAutoEntry(),
            'news_time_landing' => $this->getNewsTimeLanding(),
            'news_start_date' => $this->getNewsStartDate(),
            'member_gallery' => $this->getMemberGallery(),
            'admins' => $this->getAdmins(),
            'parent_ref_id' => $this->getParentRefId(),
            'actor_timezone' => $this->getActorTimezone()
        ];
    }

    /**
     * @throws Exception
     */
    public static function jsonUnserialize(array $data): self
    {
        try {
            return (new self(
                ImportAction::tryFrom($data['action']) ?? ImportAction::UNSUPPORTED,
                ImportType::tryFrom($data['type']) ?? ImportType::UNSUPPORTED,
                $data['ref_id'],
                $data['template_id'],
                $data['title_de'],
                $data['title_en'],
                $data['description_de'],
                $data['description_en'],
                $data['event_start'],
                $data['event_end'],
                $data['online'],
                $data['availability_start'],
                $data['availability_end'],
                $data['availability_visible'],
                $data['registration'],
                $data['registration_pass'],
                $data['admission_link'],
                $data['registration_start'],
                $data['registration_end'],
                $data['unsubscribe_end'],
                $data['limit_members'],
                $data['min_members'],
                $data['max_members'],
                $data['waiting_list'],
                $data['news'],
                $data['news_block'],
                $data['news_default_access'],
                $data['news_rss_feed'],
                $data['news_timeline'],
                $data['news_time_auto_entry'],
                $data['news_time_landing'],
                $data['news_start_date'],
                $data['member_gallery'],
                $data['admins'],
                $data['parent_ref_id'],
                $data['actor_timezone'],
            ))
                ->setImportResult(
                    ImportResult::tryFrom($data['import_result']) ?? ImportResult::NONE,
                    $data['additional_import_result_info']
                );
        } catch (Throwable $ex) {
            throw new Exception(sprintf(
                "Error occurred trying to unserialize json into ImportCsvObject. Ex.: %s",
                $ex->getMessage()
            ));
        }
    }
}
