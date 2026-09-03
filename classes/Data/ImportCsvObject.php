<?php

namespace ILIAS\Plugin\CrsGrpImport\Data;

use ilCourseConstants;

class ImportCsvObject
{
    private string $import_result;

    public function __construct(
        private readonly string $action,
        private readonly string $type,
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
        $this->import_result = '';
    }

    public function getActorTimezone(): ?string
    {
        return $this->actor_timezone;
    }

    public function getAction(): string
    {
        return strtolower($this->action);
    }

    public function getType(): string
    {
        return strtolower($this->type);
    }

    public function getRefId(): int
    {
        return $this->ref_id;
    }

    public function setRefId(int $ref_id): void
    {
        $this->ref_id = $ref_id;
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

    public function getImportResult(): ?string
    {
        return $this->import_result;
    }

    public function setImportResult(?string $import_result): void
    {
        $this->import_result = $import_result;
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
}
