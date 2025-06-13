<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

interface ObjectImporter
{
    public function ignore(): void;

    public function update(): string;

    public function insert(): int;

    public function checkPrerequisitesForInsert(): bool;
}
