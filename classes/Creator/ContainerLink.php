<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ILIAS\Plugin\CrsGrpImport\BackgroundTasks\ilCrsGrpImportJob;
use ilObjCourse;
use ilDateTime;
use ilDate;
use ilDateTimeException;
use DateTimeImmutable;

class ContainerLink extends BaseObject
{
    public function insert() : int
    {
        if ($this->getData() !== null && $this->checkPrerequisitesForInsert()) {
            $container_reference = null;
            if ($this->getData()->getType() === ilCrsGrpImportJob::COURSE_LINK) {
                $container_reference = new \ilObjCourseReference();
            } elseif ($this->getData()->getType() ===  ilCrsGrpImportJob::GROUP_LINK) {
                $container_reference = new \ilObjGroupReference();
            }

            if ($container_reference === null) {
                return 0;
            }

            $container_reference->setTargetId(\ilObject::_lookupObjId($this->getData()->getRefId()));
            $container_reference->setTitleType(\ilContainerReference::TITLE_TYPE_REUSE);
            $container_reference->create();
            $this->putInTree($container_reference);

            return $container_reference->getRefId();
        }

        return 0;
    }

    private function putInTree(\ilContainerReference $obj) : int
    {
        $ref_id = $obj->createReference();
        $obj->putInTree($this->getData()->getParentRefId());
        $obj->setPermissions($this->getData()->getParentRefId());
        $obj->update();

        return $ref_id;
    }

    public function update() : string
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
