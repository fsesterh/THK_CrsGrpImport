<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilDateTime;
use ilObjGroup;
use ilDate;
use ilDateTimeException;
use ilObject;

class Group extends BaseObject
{
    /**
     * @param ilObjGroup $group
     * @return int
     */
    protected function putGroupInTree(ilObjGroup $group) : int
    {
        $ref_id = $group->createReference();
        $group->putInTree($this->getData()->getParentRefId());
        $group->setPermissions($this->getData()->getParentRefId());
        $group->update();
        return $ref_id;
    }

    /**
     * @throws ilDateTimeException
     */
    public function insert() : int
    {
        if ($this->getData() !== null && $this->ensureDataIsValidAndComplete()) {

            $group = $this->createGroup();
            $ref_id = $this->writeGroupAdvancedData($group);
            $this->writeAvailability($ref_id);
            if($this->addAdminsToGroup($group) === true) {
                $this->getData()->setImportResult(BaseObject::RESULT_CREATED_SUCCESSFULLY);
            }

            return (int) $ref_id;
        }
    }

    public function ensureDataIsValidAndComplete() : bool
    {
        $valid_data = parent::ensureDataIsValidAndComplete();
        if ($valid_data) {
            return true;
        }
        return false;
    }

    protected function createGroup() : ilObjGroup
    {
        $group = new ilObjGroup();
        $group->setTitle($this->getData()->getTitle());
        $group->setDescription($this->getData()->getDescription());
        $group->create();
        $ref_id = $this->putGroupInTree($group);
        return $group;
    }

    /**
     * @param ilObjGroup $group
     * @return int
     * @throws ilDateTimeException
     */
    protected function writeGroupAdvancedData(ilObjGroup $group) : int
    {
        $group->updateGroupType($this->getData()->getGrpType());
        $start = new ilDateTime($this->getData()->getEventStart());
        $end = new ilDateTime($this->getData()->getEventEnd());
        $group->setPeriod($start, $end);
        $group->setOfflineStatus((bool) $this->getData()->getOnline());
        $start = new ilDateTime($this->getData()->getEventStart(), 2);
        $end = new ilDateTime($this->getData()->getEventEnd(), 2);
        $group->setPeriod($start, $end);
        $group->setOfflineStatus(!(bool) $this->getData()->getOnline());
        $group->setRegistrationType($this->getData()->getRegistration());
        $group->setPassword($this->getData()->getRegistrationPass());
        $group->enableRegistrationAccessCode($this->getData()->getAdmissionLink());
        $subscription_start = new ilDateTime($this->getData()->getRegistrationStart(), 2);
        $subscription_end = new ilDateTime($this->getData()->getRegistrationEnd(), 2);
        $group->setRegistrationStart($subscription_start);
        $group->setRegistrationEnd($subscription_end);
        $unsubscribe_end = new ilDate($this->getData()->getUnsubscribeEnd(), 2);
        $group->setCancellationEnd($unsubscribe_end);
        $group->update();
        return $group->getRefId();
    }

    /**
     * @return void
     * @throws ilDateTimeException
     */
    public function update() : string
    {
        $ref_id = $this->getData()->getRefId();
        if ($ref_id > 0) {
            if( ! ilObject::_isInTrash($ref_id)) {
                $obj = new ilObjGroup($ref_id, true);
                $this->writeGroupAdvancedData($obj);
                if($this->writeAvailability($ref_id) === false) {
                    return BaseObject::STATUS_FAILED;
                }
                if($this->addAdminsToGroup($obj) === true) {
                    $this->getData()->setImportResult(BaseObject::RESULT_UPDATED_SUCCESSFULLY);
                    return BaseObject::STATUS_OK;
                }
            } else {
                $this->getData()->setImportResult(BaseObject::RESULT_OBJECT_IN_TRASH_IGNORE);
                return BaseObject::STATUS_FAILED;
            }
        } else {
            $this->getData()->setImportResult(BaseObject::RESULT_NO_REF_ID_GIVEN_FOR_UPDATE);
            return BaseObject::STATUS_FAILED;
        }
    }

    /**
     * @param ilObjGroup $group
     * @return bool
     */
    protected function addAdminsToGroup(ilObjGroup $group) : bool
    {
        $usr_ids = \ilObjUser::_lookupId($this->getData()->getValidatedAdmins());
        if (is_array($usr_ids) && count($usr_ids) > 0) {
            foreach ($usr_ids as $usr_id) {
                $success = $group->getMembersObject()->add($usr_id, IL_GRP_ADMIN);
                if ($success === false) {
                    $this->getData()->setImportResult('One or all of the user accounts for admins not found. Data not processed.');
                }
            }
            return true;
        }
        return false;
    }
}