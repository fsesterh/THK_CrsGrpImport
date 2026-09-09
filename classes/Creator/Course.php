<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ilBlockSetting;
use ilCourseConstants;
use ilDate;
use ilDateTime;
use ilDateTimeException;
use ilDidacticTemplateSetting;
use ilDidacticTemplateSettings;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportResult;
use ILIAS\Plugin\CrsGrpImport\Enum\ImportStatus;
use ilObjCourse;
use ilObjUser;
use ilParticipants;

class Course extends BaseObject
{
    /**
     * @throws ilDateTimeException
     */
    public function insert(): int
    {
        if ($this->getData() !== null && $this->checkPrerequisitesForInsert()) {
            $course = $this->createCourse();
            if ($course !== null) {
                $ref_id = $this->writeCourseAdvancedData($course);
                $this->writeAvailability($ref_id, $course);
                if ($this->addAdminsToCourse($course) === true) {
                    $this->getData()->setImportResult(ImportResult::CREATED_SUCCESSFULLY);
                }

                return (int) $ref_id;
            }

            $this->getData()->setImportResult(ImportResult::NO_COURSE_IN_COURSE);
        }
        return 0;
    }

    public function checkPrerequisitesForInsert(): bool
    {
        $valid_data = parent::checkPrerequisitesForInsert();
        if ($valid_data) {
            return true;
        }
        return false;
    }

    protected function createCourse(): ?ilObjCourse
    {
        $course_found_in_parent_tree = $this->dic->repositoryTree()->checkForParentType(
            $this->getData()->getParentRefId(),
            'crs'
        );
        if ($course_found_in_parent_tree === 0) {
            $course = new ilObjCourse();
            $course->setTitle((string) $this->getData()->getTitleDe());
            $course->setDescription((string) $this->getData()->getDescriptionDe());
            $course->create();
            $ref_id = $this->putCourseInTree($course);
            $course->read();
            $this->handleI18nTitleAndDescription($course);

            return $course;
        }

        return null;
    }

    protected function putCourseInTree(ilObjCourse $course): int
    {
        $ref_id = $course->createReference();
        $course->putInTree($this->getData()->getParentRefId());
        $course->setPermissions($this->getData()->getParentRefId());
        $course->update();
        return $ref_id;
    }

    /**
     * @throws ilDateTimeException
     */
    public function update(): ImportStatus
    {
        $parent_ref_id = $this->getData()->getParentRefId();
        $ref_id = $this->getData()->getRefId();
        $obj_id = $this->data_cache->lookupObjId($ref_id);
        $type = $this->data_cache->lookupType($obj_id);
        if ($ref_id !== 0 && $this->dic->repositoryTree()->isGrandChild($parent_ref_id, $ref_id) && $type === 'crs') {
            if ($this->checkPrerequisitesForUpdate($ref_id, $this->getData())) {
                $obj = new ilObjCourse($ref_id, true);
                $obj->setTitle((string) $this->getData()->getTitleDe());
                $obj->setDescription((string) $this->getData()->getDescriptionDe());
                $obj->update();
                $this->handleI18nTitleAndDescription($obj, true);
                $this->writeCourseAdvancedData($obj);
                $obj->read();
                if ($this->writeAvailability($ref_id, $obj) === false) {
                    return ImportStatus::FAILED;
                }
                if ($this->addAdminsToCourse($obj) === true) {
                    $this->getData()->setImportResult(ImportResult::UPDATED_SUCCESSFULLY);
                    return ImportStatus::UPDATED;
                }
            } else {
                $this->getData()->setImportResult(ImportResult::DATASET_INVALID);
                return ImportStatus::FAILED;
            }
        } else {
            if ($ref_id === 0) {
                $this->getData()->setImportResult(ImportResult::NO_REF_ID_GIVEN_FOR_UPDATE);
                return ImportStatus::FAILED;
            }
            if (!$this->dic->repositoryTree()->isGrandChild($parent_ref_id, $ref_id)) {
                $this->getData()->setImportResult(ImportResult::UPDATE_OBJECT_NOT_IN_SUBTREE);
                return ImportStatus::FAILED;
            }

            if ($type !== 'crs') {
                $this->getData()->setImportResult(ImportResult::UPDATE_OBJECT_HAS_DIFFERENT_TYPE);
                return ImportStatus::FAILED;
            }
        }

        return ImportStatus::IGNORED;
    }

    /**
     * @throws ilDateTimeException
     */
    protected function writeCourseAdvancedData(ilObjCourse $course): int
    {
        /** @var ilDidacticTemplateSetting $template */
        $templates = ilDidacticTemplateSettings::getInstanceByObjectType($this->getData()->getType()->value)->getTemplates();
        foreach ($templates as $template) {
            if ($template->isEnabled() && (int) $this->getData()->getTemplateIdNativeType() === (int) $template->getId()) {
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
                $course->setCoursePeriod(
                    new ilDateTime($start->getTimestamp(), IL_CAL_UNIX),
                    new ilDateTime($end->getTimestamp(), IL_CAL_UNIX)
                );
            }
        }

        $course->setOfflineStatus(!(bool) $this->getData()->getOnline());

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

        if ($this->getData()->getRegistrationTypeForCourse() === ilCourseConstants::IL_CRS_SUBSCRIPTION_DEACTIVATED) {
            $course->setSubscriptionLimitationType(ilCourseConstants::IL_CRS_SUBSCRIPTION_DEACTIVATED);
            $course->setSubscriptionType(ilCourseConstants::IL_CRS_SUBSCRIPTION_DIRECT);
        } else {
            $course->setSubscriptionLimitationType(ilCourseConstants::IL_CRS_SUBSCRIPTION_UNLIMITED);
            $course->setSubscriptionType($this->getData()->getRegistrationTypeForCourse());
            if ($course->getSubscriptionStart() && $course->getSubscriptionEnd()) {
                $course->setSubscriptionLimitationType(ilCourseConstants::IL_CRS_SUBSCRIPTION_LIMITED);
            }
        }

        $unsubscribe_value = $this->getData()->getUnsubscribeEnd();
        if ($unsubscribe_value !== '') {
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

        $course->setUseNews($this->getData()->getNews());

        //News Block
        $course->setNewsBlockActivated($this->getData()->isNewsBlock());
        ilBlockSetting::_write(
            "news",
            "default_visibility",
            $this->getData()->getNewsDefaultAccess()
                ? "public"
                : "users",
            0,
            $course->getId()
        );
        ilBlockSetting::_write(
            "news",
            "public_feed",
            ($this->getData()->getNewsRssFeed()),
            0,
            $course->getId()
        );

        // News Timeline
        $course->setNewsTimeline($this->getData()->getNewsTimeline());
        $course->setNewsTimelineAutoEntries($this->getData()->getNewsTimeAutoEntry());
        $course->setNewsTimelineLandingPage($this->getData()->getNewsTimeLanding());

        //Show News Starting From
        $news_start_date = $this->checkAndParseDateStringToObject($this->getData()->getNewsStartDate());
        if ($news_start_date !== '') {
            ilBlockSetting::_write("news", "hide_news_per_date", "1", 0, $course->getId());
            ilBlockSetting::_write(
                "news",
                "hide_news_date",
                (new ilDateTime($news_start_date->getTimestamp(), IL_CAL_UNIX))->get(IL_CAL_DATETIME),
                0,
                $course->getId()
            );
        }

        $course->setShowMembers(
            !($this->getData()->getMemberGallery() === 0)
        );

        $course->update();
        return $course->getRefId();
    }

    protected function addAdminsToCourse(ilObjCourse $course): bool
    {
        $usr_ids = ilObjUser::_lookupId($this->getData()->getValidatedAdmins());
        if (is_array($usr_ids) && count($usr_ids) > 0) {
            foreach ($usr_ids as $usr_id) {
                $success = $course->getMembersObject()->add($usr_id, ilParticipants::IL_CRS_ADMIN);
                if ($success === false) {
                    $this->getData()->setImportResult(ImportResult::UNUSABLE_ADMIN_FOUND);
                }
            }
            return true;
        }
        return false;
    }
}
