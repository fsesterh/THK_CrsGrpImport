<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ILIAS\Plugin\CrsGrpImport\Enum\ImportStatus;

interface ObjectImporter
{
    public function ignore(): void;

    public function update(): ImportStatus;

    public function insert(): int;

    public function checkPrerequisitesForInsert(): bool;
}
