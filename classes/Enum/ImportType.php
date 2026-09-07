<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\CrsGrpImport\Enum;

use ilObject;

enum ImportType: string
{
    case COURSE = 'crs';
    case GROUP = 'grp';
    case COURSE_LINK = 'crsr';
    case GROUP_LINK = 'grpr';

    case UNSUPPORTED = 'unsupported';

    public function isCorrectObjType(int $ref_id): bool
    {
        if ($this === self::UNSUPPORTED) {
            return false;
        }

        $obj_type = ilObject::_lookupType($ref_id, true);
        return $obj_type === $this->value;
    }
}
