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
            $this->writeAvailability($ref_id, $group);
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
        $group->setTitle((string) $this->getData()->getTitleDe());
        $group->setDescription((string) $this->getData()->getDescriptionDe());
        $group->create();
        $ref_id = $this->putGroupInTree($group);
        $this->handleI18nTitleAndDescription($group);
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
        if ($ref_id !== 0 && $this->dic->repositoryTree()->isGrandChild($parentRefId, $ref_id) && $type === 'grp') {
            $ref_id = $this->getData()->getRefId();
            if ($this->checkPrerequisitesForUpdate($ref_id, $this->getData())) {
                $obj = new ilObjGroup($ref_id, true);
                $obj->setTitle((string) $this->getData()->getTitleDe());
                $obj->setDescription((string) $this->getData()->getDescriptionDe());
                $obj->update();
                $this->handleI18nTitleAndDescription($obj, true);
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
        } else {
            if ($ref_id === 0) {
                $this->getData()->setImportResult(BaseObject::RESULT_NO_REF_ID_GIVEN_FOR_UPDATE);
                return BaseObject::STATUS_FAILED;
            }
            if (!$this->dic->repositoryTree()->isGrandChild($parentRefId, $ref_id)) {
                $this->getData()->setImportResult(BaseObject::RESULT_UPDATE_OBJECT_NOT_IN_SUBTREE);
                return BaseObject::STATUS_FAILED;
            } elseif ($type != 'grp') {
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
        /** @var \ilDidacticTemplateSetting $template */
        $templates = \ilDidacticTemplateSettings::getInstanceByObjectType($this->getData()->getType())->getTemplates();
        $enabled_templates_by_id = [];
        foreach ($templates as $template) {
            if ($template->isEnabled() && (int) $this->getData()->getTemplateIdNativeType() === (int) $template->getId()) {
                $group->applyDidacticTemplate($template->getId());
            }
        }

        if ($this->getData()->getEventStart() !== '0' &&
            $this->getData()->getEventStart() !== '' &&
            $this->getData()->getEventEnd() !== '0' &&
            $this->getData()->getEventEnd() !== ''
        ) {
            $start = $this->checkAndParseDateStringToObject($this->getData()->getEventStart());
            $end = $this->checkAndParseDateStringToObject($this->getData()->getEventEnd());
            if ($start !== '' && $end !== '') {
                $start_time = new \ilDateTime($start->getTimestamp(), IL_CAL_UNIX, \ilTimeZone::UTC);
                $end_time = new \ilDateTime($end->getTimestamp(), IL_CAL_UNIX, \ilTimeZone::UTC);
                $group->setPeriod($start_time, $end_time);
            }
        }

        $group->setOfflineStatus(!(bool) $this->getData()->getOnline());
        $group->setRegistrationType($this->getData()->getRegistrationType());

        $group->setPassword($this->getData()->getRegistrationPass());
        $group->enableRegistrationAccessCode($this->getData()->getAdmissionLink());

        if ($this->getData()->getRegistrationStart() !== "" &&
            $this->getData()->getRegistrationEnd() !== "") {
            $subscription_start = $this->checkAndParseDateStringToObject($this->getData()->getRegistrationStart());
            $subscription_end = $this->checkAndParseDateStringToObject($this->getData()->getRegistrationEnd());
            if ($subscription_start !== '' && $subscription_end !== '') {
                $group->setRegistrationStart(new ilDateTime($subscription_start->getTimestamp(), IL_CAL_UNIX));
                $group->setRegistrationEnd(new ilDateTime($subscription_end->getTimestamp(), IL_CAL_UNIX));
            }
        }

        $unsubscribe_value = $this->getData()->getUnsubscribeEnd();
        if (strlen($unsubscribe_value) > 0) {
            $unsubscribe_end = $this->checkAndParseDateStringToObject($this->getData()->getUnsubscribeEnd());
            $group->setCancellationEnd(new ilDate($unsubscribe_end));
        }

        $group->enableMembershipLimitation(
            (bool) $this->getData()->getLimitMembers()
        );
        $group->setMinMembers((int) $this->getData()->getMinMembers());
        $group->setMaxMembers((int) $this->getData()->getMaxMembers());
        switch ((int) $this->getData()->getWaitingList()) {
            case 2:
                $group->enableWaitingList(true);
                $group->setWaitingListAutoFill(true);
                break;

            case 1:
                $group->enableWaitingList(true);
                $group->setWaitingListAutoFill(false);
                break;

            default:
                $group->enableWaitingList(false);
                $group->setWaitingListAutoFill(false);
                break;
        }

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
