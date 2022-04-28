<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;

class BaseObject implements ObjectImporter
{
    private ?ImportCsvObject $data;

    public function __construct(ImportCsvObject $data)
    {
        $this->data = $data;
    }

    public function import()
    {
    }

    public function getData() : ImportCsvObject {
        return $this->data;
    }
}