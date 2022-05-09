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

    public function ignore()
    {
    }

    public function update()
    {
    }

    public function insert() : int
    {
    }

    public function ensureDataIsValidAndComplete() : bool
    {
        if ($this->getData()->getTitle() === '') {
            return false;
        }
        return true;
    }

    public function getData() : ImportCsvObject
    {
        return $this->data;
    }

}