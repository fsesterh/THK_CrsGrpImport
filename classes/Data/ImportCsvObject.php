<?php

namespace ILIAS\Plugin\CrsGrpImport\Data;

class ImportCsvObject
{
    private $action = '';
    private $type = '';
    private $ref_id = 0;
    private $grp_type = 0;
    private $title = '';
    private $description = '';
    private $event_start = null;
    private $event_end = null;
    private $online = 0;
    private $availability_start = null;
    private $availability_end = null;
    private $registration = 0;
    private $registration_pass = '';
    private $admission_link = 0;
    private $registration_start = null;
    private $registration_end = null;
    private $unsubscribe_end = null;
    private $admins = '';
    private $parent_ref_id = null;
    private $import_result;

    public function __construct(
        string $action,
        string $type,
        int $ref_id,
        int $grp_type,
        string $title,
        string $description,
        ?string $event_start,
        ?string $event_end,
        int $online,
        ?string $availability_start,
        ?string $availability_end,
        int $registration,
        string $registration_pass,
        int $admission_link,
        ?string $registration_start,
        ?string $registration_end,
        ?string $unsubscribe_end,
        string $admins,
        ?int $parent_ref_id
    ) {
        $this->action = $action;
        $this->type = $type;
        $this->ref_id = $ref_id;
        $this->grp_type = $grp_type;
        $this->title = $title;
        $this->description = $description;
        $this->event_start = $event_start;
        $this->event_end = $event_end;
        $this->online = $online;
        $this->availability_start = $availability_start;
        $this->availability_end = $availability_end;
        $this->registration = $registration;
        $this->registration_pass = $registration_pass;
        $this->admission_link = $admission_link;
        $this->registration_start = $registration_start;
        $this->registration_end = $registration_end;
        $this->unsubscribe_end = $unsubscribe_end;
        $this->admins = $admins;
        $this->parent_ref_id = $parent_ref_id;
        $this->import_result = '';
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

    /**
     * @return int
     */
    public function getGrpType() : int
    {
        return $this->grp_type;
    }

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
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
    public function getRegistration() : int
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
}
