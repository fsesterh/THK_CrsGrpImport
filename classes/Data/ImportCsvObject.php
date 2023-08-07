<?php

namespace ILIAS\Plugin\CrsGrpImport\Data;

use ilCourseConstants;

class ImportCsvObject
{
    private $action = '';
    private $type = '';
    private $ref_id = 0;
    /** @var int|null */
    private $template_id = null;
    /** @var null|string */
    private $title_de = null;
    /** @var null|string */
    private $title_en = null;
    /** @var null|string */
    private $description_de = null;
    /** @var null|string */
    private $description_en = null;
    private $event_start = null;
    private $event_end = null;
    private $online = 0;
    private $availability_start = null;
    private $availability_end = null;
    /** @var null|int */
    private $availability_visible = null;
    private $registration = 0;
    private $registration_pass = '';
    private $admission_link = 0;
    private $registration_start = null;
    private $registration_end = null;
    private $unsubscribe_end = null;
    private $admins = '';
    private $parent_ref_id = null;
    private $import_result;
    private $actor_timezone = null;
    /** @var int|null */
    private $limit_members;
    /** @var int|null */
    private $min_members;
    /** @var int|null */
    private $max_members;
    /** @var int|null */
    private $waiting_list;

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
        $this->admins = $admins;
        $this->parent_ref_id = $parent_ref_id;
        $this->import_result = '';
        $this->actor_timezone = $actor_timezone;
    }

    public function getActorTimezone() : ?string
    {
        return $this->actor_timezone;
    }

    /**
     * @return string
     */
    public function getAction() : string
    {
        return strtolower($this->action);
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return strtolower($this->type);
    }

    /**
     * @return int
     */
    public function getRefId() : int
    {
        return $this->ref_id;
    }

    /**
     * @param int $ref_id
     */
    public function setRefId(int $ref_id) : void
    {
        $this->ref_id = $ref_id;
    }

    public function getTemplateIdNativeType() : ?int
    {
        return $this->template_id;
    }

    /**
     * @return int
     */
    public function getEffectiveTemplateId() : int
    {
        if ($this->template_id === 0) {
            return GRP_REGISTRATION_DEACTIVATED;
        } elseif ($this->template_id === 1) {
            return GRP_REGISTRATION_DIRECT;
        } elseif ($this->template_id === 2) {
            return GRP_REGISTRATION_PASSWORD;
        } elseif ($this->template_id === 3) {
            return GRP_REGISTRATION_REQUEST;
        } else {
            return GRP_REGISTRATION_DEACTIVATED;
        }
    }

    /**
     * @return string
     */
    public function getTitleDe() : string
    {
        return $this->title_de;
    }

    /**
     * @return string
     */
    public function getDescriptionDe() : string
    {
        return $this->description_de;
    }

    /**
     * @return string
     */
    public function getEventStart() : string
    {
        return $this->event_start;
    }

    /**
     * @return string
     */
    public function getEventEnd() : string
    {
        return $this->event_end;
    }

    /**
     * @return int
     */
    public function getOnline() : int
    {
        return $this->online;
    }

    /**
     * @return string
     */
    public function getAvailabilityStart() : string
    {
        return $this->availability_start;
    }

    /**
     * @return string
     */
    public function getAvailabilityEnd() : string
    {
        return $this->availability_end;
    }

    /**
     * @return int
     */
    public function getRegistrationNative() : int
    {
        return $this->registration;
    }


    /**
     * @return int
     */
    public function getRegistrationTypeForCourse() : int
    {
        $init_crs_constants = new ilCourseConstants();
        if ($this->registration === 0) {
            return IL_CRS_SUBSCRIPTION_DEACTIVATED;
        } elseif ($this->registration === 1) {
            return IL_CRS_SUBSCRIPTION_DIRECT;
        } elseif ($this->registration === 2) {
            return IL_CRS_SUBSCRIPTION_PASSWORD;
        } elseif ($this->registration === 3) {
            return IL_CRS_SUBSCRIPTION_CONFIRMATION;
        } else {
            return IL_CRS_SUBSCRIPTION_DEACTIVATED;
        }
    }

    public function getRegistrationType() : int
    {
        return $this->registration;
    }


    /**
     * @return string
     */
    public function getRegistrationPass() : string
    {
        return $this->registration_pass;
    }

    /**
     * @return int
     */
    public function getAdmissionLink() : int
    {
        return $this->admission_link;
    }

    /**
     * @return string
     */
    public function getRegistrationStart() : string
    {
        return $this->registration_start;
    }

    /**
     * @return string
     */
    public function getRegistrationEnd() : string
    {
        return $this->registration_end;
    }

    /**
     * @return string
     */
    public function getUnsubscribeEnd() : string
    {
        return $this->unsubscribe_end;
    }

    /**
     * @return array
     */
    public function getValidatedAdmins() : array
    {
        if (strlen($this->getAdmins()) > 0) {
            $logins = explode(',', $this->admins);
            $logins = array_map('trim', $logins);
            return $logins;
        }
        return [];
    }

    /**
     * @return string
     */
    public function getAdmins() : string
    {
        return $this->admins;
    }

    /**
     * @return int|null
     */
    public function getParentRefId() : ?int
    {
        return $this->parent_ref_id;
    }

    /**
     * @return string|null
     */
    public function getImportResult() : ?string
    {
        return $this->import_result;
    }

    /**
     * @param string|null $import_result
     */
    public function setImportResult(?string $import_result) : void
    {
        $this->import_result = $import_result;
    }

    public function getTitleEn() : ?string
    {
        return $this->title_en;
    }

    public function getDescriptionEn() : ?string
    {
        return $this->description_en;
    }

    public function getAvailabilityVisible() : ?int
    {
        return $this->availability_visible;
    }

    public function getLimitMembers() : ?int
    {
        return $this->limit_members;
    }

    public function getMinMembers() : ?int
    {
        return $this->min_members;
    }

    public function getMaxMembers() : ?int
    {
        return $this->max_members;
    }

    public function getWaitingList() : ?int
    {
        return $this->waiting_list;
    }
}
