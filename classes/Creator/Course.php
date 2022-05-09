<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilObjCourse;
use ilDateTime;
use ilDate;
use ilObjectActivation;
use ilDateTimeException;

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
                $success = $course->getMembersObject()->add($usr_id, IL_CRS_ADMIN);
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

    public function insert() : int
    {
        if($this->getData() !== null && $this->ensureDataIsValidAndComplete() ) {
            $course = $this->createCourse();
            $ref_id = $this->writeCourseAdvancedData($course);
            $this->writeCourseAvailability($ref_id);
            $this->addAdminsToNewCourse($course);

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

    protected function createCourse() : ilObjCourse
    {
        //Todo: validate no course in course!
        $course = new ilObjCourse();
        $course->setTitle($this->getData()->getTitle());
        $course->setDescription($this->getData()->getDescription());
        $course->create();
        return $course;
    }

    /**
     * @param ilObjCourse $course
     * @return int
     * @throws \ilDateTimeException
     */
    protected function writeCourseAdvancedData(ilObjCourse $course) : int
    {
        $ref_id = $course->createReference();
        $course->putInTree($this->getData()->getParentRefId());
        $course->setPermissions($this->getData()->getParentRefId());
        $start = new ilDateTime($this->getData()->getEventStart(), 2);
        $end = new ilDateTime($this->getData()->getEventEnd(), 2);
        $course->setCoursePeriod($start, $end);
        $course->setOfflineStatus(!(bool) $this->getData()->getOnline());
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
        return $ref_id;
    }

    /**
     * @param int $ref_id
     * @return void
     * @throws ilDateTimeException
     */
    protected function writeCourseAvailability(int $ref_id) : void
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