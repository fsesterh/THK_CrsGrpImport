<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

interface ObjectImporter
{
    public function ignore();

    public function update();

    public function insert() : int;

    public function checkPrerequisitesForInsert() : bool;
}