<?php

namespace ILIAS\Plugin\CrsGrpImport\Log;

use ilCSVWriter;
use ilCrsGrpImportPlugin;

class CSVLog
{
    protected ilCSVWriter $csv;
    protected ilCrsGrpImportPlugin $plugin;

    public function __construct()
    {
        $this->plugin = \ilCrsGrpImportPlugin::getInstance();
        $this->csv = new ilCSVWriter();
        $this->csv->addColumn($this->plugin->txt('status'));
        $this->csv->addColumn($this->plugin->txt('ref_id'));
        $this->csv->addColumn($this->plugin->txt('title'));
        $this->csv->addColumn($this->plugin->txt('admins'));
        $this->csv->addColumn($this->plugin->txt('result'));
        $this->csv->addRow();

    }

    protected function addLineToLog(array $entry) : void
    {
        array_push($this->csv_log, $entry);
    }

    public function getCSVLog() : string
    {
        return $this->csv->getCSVString();
    }

    public function addEntryToLog(string $status, ?int $ref_id, ?string $title, array $admins, string $result) : void
    {

        if (count($admins) > 1) {
            $admins = implode(',', $admins);
        }

        $this->csv->addColumn($status);
        $this->csv->addColumn($ref_id);
        $this->csv->addColumn($title);
        $this->csv->addColumn($admins);
        $this->csv->addColumn($result);
        $this->csv->addRow();
    }
}