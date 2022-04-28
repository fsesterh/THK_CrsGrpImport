<?php
namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilDateTime;
use ilObjGroup;
use ilDate;

class Group extends BaseObject
{
    public function ignore()
    {
        // TODO: Implement ignore() method.
    }

    public function update()
    {
        // TODO: Implement update() method.
    }

    /**
     * @throws \ilDateTimeException
     */
    public function insert()
    {
        if($this->getData() !== null) {
            $group = new ilObjGroup();
            $group->setTitle($this->getData()->getTitle());
            $group->setDescription($this->getData()->getDescription());
            $group->create();
            $ref_id = $group->createReference();
            $group->putInTree($this->getData()->getParentRefId());
            $group->setPermissions($this->getData()->getParentRefId());
            $group->updateGroupType($this->getData()->getGrpType());
            $start = new ilDateTime($this->getData()->getEventStart());
            $end = new ilDateTime($this->getData()->getEventEnd());
            $group->setPeriod($start, $end);
            $group->setOfflineStatus((bool)$this->getData()->getOnline());
            $start = new ilDateTime($this->getData()->getEventStart(), 2);
            $end = new ilDateTime($this->getData()->getEventEnd(), 2);
            $group->setPeriod($start, $end);
            $group->setOfflineStatus(! (bool)$this->getData()->getOnline());
            #//Todo: Availability
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
            $group->update();
            #$lp = new \ilLPObjSettings($group->getId());
            #$lp->setMode(\ilLPObjSettings::LP_MODE_BY_ENROLMENT);
            #$lp->update();
            return (int) $ref_id;
        }
    }
}