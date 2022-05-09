<?php
namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilDateTime;
use ilObjGroup;
use ilDate;
use ilObjectActivation;
use ilDateTimeException;

class Group extends BaseObject
{
    /**
     * @param ilObjGroup $group
     * @return bool
     */
    protected function addAdminsToNewGroup(ilObjGroup $group) : bool
    {
        $usr_ids = \ilObjUser::_lookupId($this->getData()->getValidatedAdmins());
        if (is_array($usr_ids) && count($usr_ids) > 0) {
            foreach ($usr_ids as $usr_id) {
                $success = $group->getMembersObject()->add($usr_id, IL_GRP_ADMIN);
                if($success === false) {
                    //Todo: add error to log and csv log
                }
            }
            return true;
        }
        return false;
    }

    public function ignore()
    {
        // TODO: Implement ignore() method.
    }

    public function update()
    {
        // TODO: Implement update() method.
        if($this->getData()->getRefId() !== null && $this->getData()->getRefId() !== 0) {

        }
    }

    /**
     * @throws ilDateTimeException
     */
    public function insert() : int
    {
        if($this->getData() !== null && $this->ensureDataIsValidAndComplete() ) {

            $group = $this->createGroup();
            $ref_id = $this->writeGroupAdvancedData($group);
            $this->writeGroupAvailability($ref_id);
            $this->addAdminsToNewGroup($group);

            return (int) $ref_id;
        }
    }

    public function ensureDataIsValidAndComplete() : bool
    {
        $valid_data = parent::ensureDataIsValidAndComplete();
        if($valid_data) {
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
        return $group;
    }

    /**
     * @param ilObjGroup $group
     * @return int
     * @throws ilDateTimeException
     */
    protected function writeGroupAdvancedData(ilObjGroup $group) : int
    {
        $ref_id = $group->createReference();
        $group->putInTree($this->getData()->getParentRefId());
        $group->setPermissions($this->getData()->getParentRefId());
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
        return $ref_id;
    }

    /**
     * @param int $ref_id
     * @return void
     * @throws ilDateTimeException
     */
    protected function writeGroupAvailability(int $ref_id) : void
    {
        $availability_start = new ilDateTime($this->getData()->getAvailabilityStart(), 2);
        $availability_end = new ilDateTime($this->getData()->getAvailabilityEnd(), 2);
        $activation = new ilObjectActivation();
        $activation->setTimingType(1);
        $activation->setTimingStart($availability_start->getUnixTime());
        $activation->setTimingEnd($availability_end->getUnixTime());
        $activation->update($ref_id);
    }
}