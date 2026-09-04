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

namespace ILIAS\Plugin\CrsGrpImport\Repository;

use DateTime;
use ilDBConstants;
use ilDBInterface;
use ILIAS\Plugin\CrsGrpImport\Model\QueuedImport;

class QueuedRepository
{
    protected const TABLE_NAME = "crsgrp_import_queue";

    private static ?QueuedRepository $instance = null;

    protected ilDBInterface $db;

    public function __construct(ilDBInterface $db = null)
    {
        global $DIC;

        if ($db) {
            $this->db = $db;
        } else {
            $this->db = $DIC->database();
        }
    }

    public static function getInstance(?ilDBInterface $db = null): self
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new self($db);
    }

    /**
     * @return QueuedImport[]
     */
    public function readAll(): array
    {
        $result = $this->db->query("SELECT * FROM " . self::TABLE_NAME);

        $data = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $data[] = $this->map($row);
        }
        return $data;
    }

    public function queueImport(string $csvData, int $userId): bool
    {
        $queuedImport = new QueuedImport(
            $this->db->nextId(self::TABLE_NAME),
            $csvData,
            $userId,
            new DateTime()
        );

        return $this->db->insert(self::TABLE_NAME, [
                "id" => [ilDBConstants::T_INTEGER, $queuedImport->getId()],
                "data" => [ilDBConstants::T_CLOB, $queuedImport->getCsvData()],
                "created_timestamp" => [ilDBConstants::T_INTEGER, $queuedImport->getCreationDate()->getTimestamp()],
                "user" => [ilDBConstants::T_INTEGER, $queuedImport->getUserId()]
            ]) === 1;
    }

    public function removeQueuedImport(QueuedImport $queuedImport): bool
    {
        return $this->db->manipulateF(
            "DELETE FROM " . self::TABLE_NAME . " WHERE id = %s",
            [ilDBConstants::T_INTEGER],
            [$queuedImport->getId()]
        ) === 1;
    }

    protected function map(array $row): QueuedImport
    {
        return (new QueuedImport(
            (int) $row["id"],
            $row["data"],
            (int) $row["user"],
            (new DateTime())->setTimestamp((int) $row["created_timestamp"])
        ));
    }
}
