<?php

namespace ILIAS\Plugin\CrsGrpImport\Data;

use ilCourseConstants;

class ImportCsvObject
{
    private string $action;
    private string $type;
    private int $ref_id;
    private ?int $template_id;
    private ?string $title_de;
    private ?string $title_en;
    private ?string $description_de;
    private ?string $description_en;
    private ?string $event_start;
    private ?string $event_end;
    private int $online;
    private ?string $availability_start;
    private ?string $availability_end;
    private ?int $availability_visible;
    private int $registration;
    private string $registration_pass;
    private int $admission_link;
    private ?string $registration_start;
    private ?string $registration_end;
    private ?string $unsubscribe_end;
    private string $admins;
    private ?int $parent_ref_id;
    private string $import_result;
    private ?string $actor_timezone;
    private ?int $limit_members;
    private ?int $min_members;
    private ?int $max_members;
    private ?int $waiting_list;
    private bool $news;
    private bool $news_block;
    private bool $news_default_access;
    private bool $news_rss_feed;
    private bool $news_timeline;
    private bool $news_time_auto_entry;
    private bool $news_time_landing;
    private string $news_start_date;

    public function __construct(
        string $action,
        string $type,
        int $ref_id,
        ?int $template_id,
        ?string $title_de,
        ?string $title_en,
        ?string $description_de,
        ?string $description_en,
        ?string $event_start,
        ?string $event_end,
        int $online,
        ?string $availability_start,
        ?string $availability_end,
        ?int $availability_visible,
        int $registration,
        string $registration_pass,
        int $admission_link,
        ?string $registration_start,
        ?string $registration_end,
        ?string $unsubscribe_end,
        ?int $limit_members,
        ?int $min_members,
        ?int $max_members,
        ?int $waiting_list,
        bool $news,
        bool $news_block,
        bool $news_default_access,
        bool $news_rss_feed,
        bool $news_timeline,
        bool $news_time_auto_entry,
        bool $news_time_landing,
        string $news_start_date,
        string $admins,
        ?int $parent_ref_id,
        ?string $actor_timezone
    ) {
        $this->action = $action;
        $this->type = $type;
        $this->ref_id = $ref_id;
        $this->template_id = $template_id;
        $this->title_de = $title_de;
        $this->title_en = $title_en;
        $this->description_de = $description_de;
        $this->description_en = $description_en;
        $this->event_start = $event_start;
        $this->event_end = $event_end;
        $this->online = $online;
        $this->availability_start = $availability_start;
        $this->availability_end = $availability_end;
        $this->availability_visible = $availability_visible;
        $this->registration = $registration;
        $this->registration_pass = $registration_pass;
        $this->admission_link = $admission_link;
        $this->registration_start = $registration_start;
        $this->registration_end = $registration_end;
        $this->unsubscribe_end = $unsubscribe_end;
        $this->limit_members = $limit_members;
        $this->min_members = $min_members;
        $this->max_members = $max_members;
        $this->waiting_list = $waiting_list;
        $this->news = $news;
        $this->news_block = $news_block;
        $this->news_default_access = $news_default_access;
        $this->news_rss_feed = $news_rss_feed;
        $this->news_timeline = $news_timeline;
        $this->news_time_auto_entry = $news_time_auto_entry;
        $this->news_time_landing = $news_time_landing;
        $this->news_start_date = $news_start_date;
        $this->admins = $admins;
        $this->parent_ref_id = $parent_ref_id;
        $this->import_result = '';
        $this->actor_timezone = $actor_timezone;
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
            return array_map('trim', $logins);
        }
        return [];
    }

    public function getAdmins(): string
    {
        return $this->admins;
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
