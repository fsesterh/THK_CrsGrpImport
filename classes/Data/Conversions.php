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

    public function ensureIntOrNullType($value) : ?int
    {
        global $DIC;

        return $DIC->refinery()->byTrying([
            $DIC->refinery()->kindlyTo()->int(),
            $DIC->refinery()->kindlyTo()->null(),
            $DIC->refinery()->custom()->transformation(static function ($from) {
                return null;
            })
        ])->transform($value);
    }
}
