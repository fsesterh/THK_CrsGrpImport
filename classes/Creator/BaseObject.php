<?php

namespace ILIAS\Plugin\CrsGrpImport\Creator;

use ILIAS\Plugin\CrsGrpImport\Data\ImportCsvObject;

class BaseObject implements ObjectImporter
{
    const INSERT = 'insert';
    const UPDATE = 'update';
    const IGNORE = 'ignore';

    private ?ImportCsvObject $data;

    public function __construct(ImportCsvObject $data)
    {
        $this->data = $data;
    }

    public function getData() : ImportCsvObject {
        return $this->data;
    }

    public function ignore()
    {
        // TODO: Implement ignore() method.
    }

    public function update()
    {
        // TODO: Implement update() method.
    }

    public function insert()
    {
        // TODO: Implement insert() method.
    }

}