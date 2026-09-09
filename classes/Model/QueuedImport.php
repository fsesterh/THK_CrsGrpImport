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

namespace ILIAS\Plugin\CrsGrpImport\Model;

use DateTime;

readonly class QueuedImport
{
    public function __construct(private int $id, private string $csv_data, private int $user_id, private DateTime $creation_date)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCsvData(): string
    {
        return $this->csv_data;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creation_date;
    }
}
