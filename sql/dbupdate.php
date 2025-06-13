<#1>
<?php
// Empty Step
/** @var $ilDB ilDBInterface */
?>
<#2>
<?php
$tableName = "crsgrp_import_queue";

if (!$ilDB->tableExists($tableName)) {
    $fields = [
        'id' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
        ],
        'data' => [
            'type' => 'clob',
            'notnull' => false,
        ],
        'user' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
        ],
        'created_timestamp' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true,
        ]
    ];

    $ilDB->createTable($tableName, $fields);
    $ilDB->addPrimaryKey($tableName, ["id"]);
    $ilDB->createSequence($tableName);
}
?>
<#3>
<?php
$task_ids = [];
$bucket_ids = [];

$result = $ilDB->query(
    'SELECT id, bucket_id FROM il_bt_task WHERE ' . $ilDB->like(
        'type',
        'text',
        '%ILIAS\\\\Plugin\\\\CrsGrpImport%'
    )
);
while ($row = $ilDB->fetchAssoc($result)) {
    $task_ids[(int) $row['id']] = (int) $row['id'];
    $bucket_ids[(int) $row['bucket_id']] = (int) $row['bucket_id'];
}

$ilDB->manipulate(
    'DELETE FROM il_bt_value WHERE id IN (SELECT value_id FROM il_bt_value_to_task WHERE ' . $ilDB->in(
        'task_id',
        $task_ids,
        false,
        'integer'
    ) . ')'
);

$ilDB->manipulate(
    'DELETE FROM il_bt_value_to_task WHERE ' . $ilDB->in(
        'task_id',
        $task_ids,
        false,
        'integer'
    )
);

$ilDB->manipulate(
    'DELETE FROM il_bt_bucket WHERE ' . $ilDB->in(
        'id',
        $bucket_ids,
        false,
        'integer'
    )
);

$ilDB->manipulate(
    'DELETE FROM il_bt_task WHERE ' . $ilDB->in(
        'id',
        $task_ids,
        false,
        'integer'
    )
);

?>
