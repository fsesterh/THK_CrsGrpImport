<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilObjCourse;
use ilDateTime;
use ilDate;
use ilDateTimeException;
use DateTimeImmutable;

class Course extends BaseObject
{
    /**
     * @throws ilDateTimeException
     */
    public function insert() : int
    {
        if ($this->getData() !== null && $this->checkPrerequisitesForInsert()) {
            $course = $this->createCourse();
            if ($course !== null) {
                $ref_id = $this->writeCourseAdvancedData($course);
                $this->writeAvailability($ref_id, $course);
                if ($this->addAdminsToCourse($course) === true) {
                    $this->getData()->setImportResult(BaseObject::RESULT_CREATED_SUCCESSFULLY);
                }

                return (int) $ref_id;
            } else {
                $this->getData()->setImportResult(BaseObject::RESULT_NO_COURSE_IN_COURSE);
            }
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

    protected function createCourse() : ?ilObjCourse
    {
        $course_found_in_parent_tree = $this->dic->repositoryTree()->checkForParentType(
            $this->getData()->getParentRefId(),
            'crs'
        );
        if ($course_found_in_parent_tree === false || $course_found_in_parent_tree === 0) {
            $course = new ilObjCourse();
            $course->setTitle((string) $this->getData()->getTitleDe());
            $course->setDescription((string) $this->getData()->getDescriptionDe());
            $course->create();
            $ref_id = $this->putCourseInTree($course);

            $this->handleI18nTitleAndDescription($course);

            return $course;
        }

        return null;
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
        $parentRefId = $this->getData()->getParentRefId();
        $ref_id = $this->getData()->getRefId();
        $obj_id = $this->dataCache->lookupObjId($ref_id);
        $type = $this->dataCache->lookupType($obj_id);
        if ($ref_id !== 0 && $this->dic->repositoryTree()->isGrandChild($parentRefId, $ref_id) && $type === 'crs') {
            if ($this->checkPrerequisitesForUpdate($ref_id, $this->getData())) {
                $obj = new ilObjCourse($ref_id, true);
                $obj->setTitle((string) $this->getData()->getTitleDe());
                $obj->setDescription((string) $this->getData()->getDescriptionDe());
                $obj->update();
                $this->handleI18nTitleAndDescription($obj, true);
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
        } else {
            if ($ref_id === 0) {
                $this->getData()->setImportResult(BaseObject::RESULT_NO_REF_ID_GIVEN_FOR_UPDATE);
                return BaseObject::STATUS_FAILED;
            }
            if (!$this->dic->repositoryTree()->isGrandChild($parentRefId, $ref_id)) {
                $this->getData()->setImportResult(BaseObject::RESULT_UPDATE_OBJECT_NOT_IN_SUBTREE);
                return BaseObject::STATUS_FAILED;
            } elseif ($type != 'crs') {
                $this->getData()->setImportResult(BaseObject::RESULT_UPDATE_OBJECT_HAS_DIFFERENT_TYPE);
                return BaseObject::STATUS_FAILED;
            }
        }
    }

    /**
     * @param ilObjCourse $course
     * @return int
     * @throws ilDateTimeException
     */
    protected function writeCourseAdvancedData(ilObjCourse $course) : int
    {
        /** @var \ilDidacticTemplateSetting $template */
        $templates = \ilDidacticTemplateSettings::getInstanceByObjectType($this->getData()->getType())->getTemplates();
        $enabled_templates_by_id = [];
        foreach ($templates as $template) {
            if ($template->isEnabled() && $this->getData()->getTemplateIdNativeType() === $template->getId()) {
                $course->applyDidacticTemplate($template->getId());
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
                $course->setCoursePeriod(new ilDateTime($start->getTimestamp(), IL_CAL_UNIX), new ilDateTime($end->getTimestamp(), IL_CAL_UNIX));
            }
        }

        $course->setOfflineStatus(!(bool) $this->getData()->getOnline());
        $course->setSubscriptionType($this->getData()->getRegistrationTypeForCourse());
        if ((int) $this->getData()->getRegistrationTypeForCourse() !== 0) {
            $course->setSubscriptionLimitationType(IL_CRS_SUBSCRIPTION_UNLIMITED);
        }

        $course->setSubscriptionPassword($this->getData()->getRegistrationPass());
        $course->enableRegistrationAccessCode($this->getData()->getAdmissionLink());
        if ($this->getData()->getRegistrationStart() !== "" &&
            $this->getData()->getRegistrationEnd() !== "" &&
            $this->getData()->getRegistrationTypeForCourse() !== 0) {
            $subscription_start = $this->checkAndParseDateStringToObject($this->getData()->getRegistrationStart());
            $subscription_end = $this->checkAndParseDateStringToObject($this->getData()->getRegistrationEnd());
            if ($subscription_start !== '' && $subscription_end !== '') {
                $course->setSubscriptionStart($subscription_start->getTimestamp());
                $course->setSubscriptionEnd($subscription_end->getTimestamp());
            }
        }
        $unsubscribe_value = $this->getData()->getUnsubscribeEnd();
        if (strlen($unsubscribe_value) > 0) {
            $unsubscribe_end = $this->checkAndParseDateStringToObject($this->getData()->getUnsubscribeEnd());
            if ($unsubscribe_end !== '') {
                $course->setCancellationEnd(new ilDate($unsubscribe_end->getTimestamp(), IL_CAL_UNIX));
            }
        }

        $course->enableSubscriptionMembershipLimitation(
            (bool) $this->getData()->getLimitMembers()
        );
        $course->setSubscriptionMinMembers((int) $this->getData()->getMinMembers());
        $course->setSubscriptionMaxMembers((int) $this->getData()->getMaxMembers());
        switch ((int) $this->getData()->getWaitingList()) {
            case 2:
                $course->enableWaitingList(true);
                $course->setWaitingListAutoFill(true);
                break;

            case 1:
                $course->enableWaitingList(true);
                $course->setWaitingListAutoFill(false);
                break;

            default:
                $course->enableWaitingList(false);
                $course->setWaitingListAutoFill(false);
                break;
        }

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
