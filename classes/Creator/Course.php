<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

class Course extends BaseObject
{
    public function import() {
        if($this->getData() !== null) {
            $course = new \ilObjCourse();
            $course->setTitle($this->getData()->getTitle());
            $course->setDescription($this->getData()->getDescription());
            $course->create();
            $ref_id = $course->createReference();
            $course->putInTree($this->getData()->getParentRefId());
            $course->setPermissions($this->getData()->getParentRefId());
            $course->update();
            #$lp = new \ilLPObjSettings($group->getId());
            #$lp->setMode(\ilLPObjSettings::LP_MODE_BY_ENROLMENT);
            #$lp->update();
            return (int) $ref_id;
        }
    }

}