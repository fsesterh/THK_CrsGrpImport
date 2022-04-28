<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilObjCourse;
use ilDateTime;
use ilDate;

class Course extends BaseObject
{
    /**
     * @param ilObjCourse $course
     * @return bool
     */
    protected function addAdminsToNewCourse(ilObjCourse $course) : bool
    {
        $usr_ids = \ilObjUser::_lookupId($this->getData()->getValidatedAdmins());
        if (is_array($usr_ids) && count($usr_ids) > 0) {
            foreach ($usr_ids as $usr_id) {
                $course->getMemberObject()->add($usr_id, IL_CRS_ADMIN);
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
    }

    public function insert()
    {
        if($this->getData() !== null) {
            $course = new ilObjCourse();
            $course->setTitle($this->getData()->getTitle());
            $course->setDescription($this->getData()->getDescription());
            $course->create();
            $ref_id = $course->createReference();
            //Todo: validate no course in course!
            $course->putInTree($this->getData()->getParentRefId());
            $course->setPermissions($this->getData()->getParentRefId());
            $start = new ilDateTime($this->getData()->getEventStart(), 2);
            $end = new ilDateTime($this->getData()->getEventEnd(), 2);
            $course->setCoursePeriod($start, $end);
            $course->setOfflineStatus(! (bool)$this->getData()->getOnline());
            #//Todo: Availability
            $course->setSubscriptionType($this->getData()->getRegistration());
            $course->setSubscriptionPassword($this->getData()->getRegistrationPass());
            $course->enableRegistrationAccessCode($this->getData()->getAdmissionLink());
            $subscription_start = new ilDateTime($this->getData()->getRegistrationStart(), 2);
            $subscription_end = new ilDateTime($this->getData()->getRegistrationEnd(), 2);
            $course->setSubscriptionStart($subscription_start);
            $course->setSubscriptionEnd($subscription_end);
            $unsubscribe_end = new ilDate($this->getData()->getUnsubscribeEnd(), 2);
            $course->setCancellationEnd($unsubscribe_end);
            $course->update();
            #$lp = new \ilLPObjSettings($group->getId());
            #$lp->setMode(\ilLPObjSettings::LP_MODE_BY_ENROLMENT);
            #$lp->update();

            $this->addAdminsToNewCourse($course);

            return (int) $ref_id;
        }
    }

}