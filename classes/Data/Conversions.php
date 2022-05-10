<?php

namespace ILIAS\Plugin\CrsGrpImport\Data;

class Conversions
{

    /**
     * @param mixed $value
     * @return string
     */
    public function ensureStringType($value) : string
    {
        if ($value === null) {
            return '';
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return int
     */
    public function ensureIntType($value) : int
    {
        if ($value === null) {
            return -1;
        }
        return (int) $value;
    }
}