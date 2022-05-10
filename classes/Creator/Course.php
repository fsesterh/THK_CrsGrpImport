<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilObjCourse;
use ilDateTime;
use ilDate;
use ilDateTimeException;

class Course extends BaseObject
{
    /**
     * @throws ilDateTimeException
     */
    public function insert() : int
    {
        if ($this->getData() !== null && $this->checkPrerequisitesForInsert()) {
            $course = $this->createCourse();
            $ref_id = $this->writeCourseAdvancedData($course);
            $this->writeAvailability($ref_id);
            if ($this->addAdminsToCourse($course) === true) {
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

    protected function createCourse() : ilObjCourse
    {
        //Todo: validate no course in course!
        $course = new ilObjCourse();
        $course->setTitle($this->getData()->getTitle());
        $course->setDescription($this->getData()->getDescription());
        $course->create();
        $ref_id = $this->putCourseInTree($course);
        return $course;
    }

    /**
     * @param ilObjCourse $course
     * @return int
     */
    protected function putCourseInTree(ilObjCourse $course) : int
    {
        $ref_id = $course->createReference();
        $course->putInTree($this->getData()->getParentRefId());
        $course->setPermissions($this->getData()->getParentRefId());
        $course->update();
        return $ref_id;
    }

    /**
     * @return void
     * @throws ilDateTimeException
     */
    public function update() : string
    {
        $ref_id = $this->getData()->getRefId();
        if ($this->checkPrerequisitesForUpdate($ref_id, $this->getData())) {
            $obj = new ilObjCourse($ref_id, true);
            $this->writeCourseAdvancedData($obj);
            if ($this->writeAvailability($ref_id) === false) {
                return BaseObject::STATUS_FAILED;
            }
            if ($this->addAdminsToCourse($obj) === true) {
                $this->getData()->setImportResult(BaseObject::RESULT_UPDATED_SUCCESSFULLY);
                return BaseObject::STATUS_UPDATED;
            }
        } else {
            $this->getData()->setImportResult(BaseObject::RESULT_DATASET_INVALID);
            return BaseObject::STATUS_FAILED;
        }
    }

    /**
     * @param ilObjCourse $course
     * @return int
     * @throws ilDateTimeException
     */
    protected function writeCourseAdvancedData(ilObjCourse $course) : int
    {
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
        return $course->getRefId();
    }

    /**
     * @param ilObjCourse $course
     * @return bool
     */
    protected function addAdminsToCourse(ilObjCourse $course) : bool
    {
        $usr_ids = \ilObjUser::_lookupId($this->getData()->getValidatedAdmins());
        if (is_array($usr_ids) && count($usr_ids) > 0) {
            foreach ($usr_ids as $usr_id) {
                $success = $course->getMembersObject()->add($usr_id, IL_CRS_ADMIN);
                if ($success === false) {
                    $this->getData()->setImportResult('One or all of the user accounts for admins not found. Data not processed.');
                }
            }
            return true;
        }
        return false;
    }
}