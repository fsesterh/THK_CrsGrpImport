<?php
namespace ILIAS\Plugin\CrsGrpImport\Creator;

class Group extends BaseObject
{
    public function import() {
        if($this->getData() !== null) {
            $group = new \ilObjGroup();
            $group->setTitle($this->getData()->getTitle());
            $group->setDescription($this->getData()->getDescription());
            $group->create();
            $ref_id = $group->createReference();
            $group->putInTree($this->getData()->getParentRefId());
            $group->setPermissions($this->getData()->getParentRefId());
            $group->updateGroupType($this->getData()->getGrpType());
            $group->update();
            #$lp = new \ilLPObjSettings($group->getId());
            #$lp->setMode(\ilLPObjSettings::LP_MODE_BY_ENROLMENT);
            #$lp->update();
            return (int) $ref_id;
        }
    }
}