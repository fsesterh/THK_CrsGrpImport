<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilDateTime;
use ilObjGroup;
use ilDate;
use ilDateTimeException;

class Group extends BaseObject
{
    /**
     * @throws ilDateTimeException
     */
    public function insert() : int
    {
        if ($this->getData() !== null && $this->checkPrerequisitesForInsert()) {
            $group = $this->createGroup();
            $ref_id = $this->writeGroupAdvancedData($group);
            $this->writeAvailability($ref_id);
            if ($this->addAdminsToGroup($group) === true) {
                $this->getData()->setImportResult(BaseObject::RESULT_CREATED_SUCCESSFULLY);
            }

            return (int) $ref_id;
        }
        return 0;
    }

    public function checkPrerequisitesForInsert() : bool
    {
        $valid_data = parent::checkPrerequisitesForInsert();
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
     * @return void
     * @throws ilDateTimeException
     */
    public function update() : string
    {
        $parentRefId = $this->getData()->getParentRefId();
        $ref_id = $this->getData()->getRefId();
        $obj_id = $this->dataCache->lookupObjId($ref_id);
        $type = $this->dataCache->lookupType($obj_id);
        if($this->dic->repositoryTree()->isGrandChild($parentRefId, $ref_id) && $type === 'grp')
        {
            $ref_id = $this->getData()->getRefId();
            if ($this->checkPrerequisitesForUpdate($ref_id, $this->getData())) {
                $obj = new ilObjGroup($ref_id, true);
                $obj->setTitle($this->getData()->getTitle());
                $obj->setDescription($this->getData()->getDescription());
                $obj->update();
                $this->writeGroupAdvancedData($obj);
                if ($this->writeAvailability($ref_id) === false) {
                    return BaseObject::STATUS_FAILED;
                }
                if ($this->addAdminsToGroup($obj) === true) {
                    $this->getData()->setImportResult(BaseObject::RESULT_UPDATED_SUCCESSFULLY);
                    return BaseObject::STATUS_UPDATED;
                }
            } else {
                $this->getData()->setImportResult(BaseObject::RESULT_DATASET_INVALID);
                return BaseObject::STATUS_FAILED;
            }
        } else
        {
            if( ! $this->dic->repositoryTree()->isGrandChild($parentRefId, $ref_id)) {
                $this->getData()->setImportResult(BaseObject::RESULT_UPDATE_OBJECT_NOT_IN_SUBTREE);
                return BaseObject::STATUS_FAILED;
            } elseif( $type != 'grp') {
                $this->getData()->setImportResult(BaseObject::RESULT_UPDATE_OBJECT_HAS_DIFFERENT_TYPE);
                return BaseObject::STATUS_FAILED;
            }
        }

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
        $start = new ilDateTime($this->getData()->getEventStart(), BaseObject::IL_CSV_IMPORT_DATE_TIME);
        $end = new ilDateTime($this->getData()->getEventEnd(), BaseObject::IL_CSV_IMPORT_DATE_TIME);
        $group->setPeriod($start, $end);
        $group->setOfflineStatus(!(bool) $this->getData()->getOnline());
        $group->setRegistrationType($this->getData()->getRegistration());
        $group->setPassword($this->getData()->getRegistrationPass());
        $group->enableRegistrationAccessCode($this->getData()->getAdmissionLink());

        if($this->getData()->getRegistrationStart() !== "" &&
            $this->getData()->getRegistrationEnd() !== "") {
            $subscription_start = new ilDateTime($this->getData()->getRegistrationStart(), BaseObject::IL_CSV_IMPORT_DATE_TIME);
            $subscription_end = new ilDateTime($this->getData()->getRegistrationEnd(), BaseObject::IL_CSV_IMPORT_DATE_TIME);
            $group->setRegistrationStart($subscription_start);
            $group->setRegistrationEnd($subscription_end);
        }
        $unsubscribe_end = new ilDate($this->getData()->getUnsubscribeEnd(), BaseObject::IL_CSV_IMPORT_DATE);
        $group->setCancellationEnd($unsubscribe_end);
        $group->update();
        return $group->getRefId();
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
