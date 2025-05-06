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
use ilDBInterface;
use ILIAS\Plugin\CrsGrpImport\Model\QueuedImport;

/**
 * Class QueuedRepository
 *
 * @package ILIAS\Plugin\CrsGrpImport\Repository
 * @author  Marvin Beym <mbeym@databay.de>
 */
class QueuedRepository
{
    private static ?QueuedRepository $instance = null;

    protected ilDBInterface $db;
    /**
     * @var string
     */
    protected const TABLE_NAME = "crsgrp_import_queue";

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
                "id" => ["integer", $queuedImport->getId()],
                "data" => ["clob", $queuedImport->getCsvData()],
                "created_timestamp" => ["integer", $queuedImport->getCreationDate()->getTimestamp()],
                "user" => ["integer", $queuedImport->getUserId()]
            ]) === 1;
    }

    public function removeQueuedImport(QueuedImport $queuedImport): bool
    {
        return $this->db->manipulateF(
            "DELETE FROM " . self::TABLE_NAME . " WHERE id = %s",
            ["integer"],
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
