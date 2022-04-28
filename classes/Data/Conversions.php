<?php

namespace ILIAS\Plugin\CrsGrpImport\Data;

class Conversions
{

    public function ensureStringType($value) : string {
        if($value === null) {
            return '';
        }
        return $value;
    }

    public function ensureIntType($value) : int {
        if($value === null) {
            return -1;
        }
        return (int) $value;
    }
}