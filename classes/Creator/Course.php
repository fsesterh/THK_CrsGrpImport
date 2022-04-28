<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

class Course extends BaseObject
{
    /**
     * @param \ilObjCourse $course
     * @return bool
     */
    protected function addAdminsToNewCourse(\ilObjCourse $course) : bool
    {
        $usr_ids = \ilObjUser::_lookupId($this->getData()->getValidatedAdmins());
        if (is_array($usr_ids)) {
            foreach ($usr_ids as $usr_id) {
                $course->getMemberObject()->add($usr_id, IL_CRS_ADMIN);
            }
            return true;
        }
        return false;
    }

    public function import() {
        if($this->getData() !== null) {
            $course = new \ilObjCourse();
            $course->setTitle($this->getData()->getTitle());
            $course->setDescription($this->getData()->getDescription());
            $course->create();
            $ref_id = $course->createReference();
            //Todo: validate no course in course!
            $course->putInTree($this->getData()->getParentRefId());
            $course->setPermissions($this->getData()->getParentRefId());
            $course->update();
            #$lp = new \ilLPObjSettings($group->getId());
            #$lp->setMode(\ilLPObjSettings::LP_MODE_BY_ENROLMENT);
            #$lp->update();

            $this->addAdminsToNewCourse($course);

            return (int) $ref_id;
        }
    }

}