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

namespace ILIAS\Plugin\CrsGrpImport\Frontend;

use ilCrsGrpImportUIHookGUI;
use ILIAS\DI\Container;

class Dispatcher
{
    private static ?self $instance = null;
    protected Container $dic;

    private function __construct(protected ilCrsGrpImportUIHookGUI $core_controller, protected string $default_controller = '')
    {
    }

    public static function getInstance(ilCrsGrpImportUIHookGUI $base_controller): ?self
    {
        self::$instance ??= new self($base_controller);

        return self::$instance;
    }

    private function __clone()
    {
    }

    protected function requireController(string $controller): void
    {
        require_once $this->getControllerPath() . $controller . '.php';
    }

    protected function getControllerPath(): string
    {
        return $this->getCoreController()->getPluginObject()->getDirectory() .
            DIRECTORY_SEPARATOR .
            'classes' .
            DIRECTORY_SEPARATOR .
            'Frontend' .
            DIRECTORY_SEPARATOR .
            'Controller' .
            DIRECTORY_SEPARATOR;
    }

    public function getCoreController(): ilCrsGrpImportUIHookGUI
    {
        return $this->core_controller;
    }

    public function setCoreController(ilCrsGrpImportUIHookGUI $core_controller)
    {
        $this->core_controller = $core_controller;
    }

    public function setDic(Container $dic): void
    {
        $this->dic = $dic;
    }

    public function dispatch(string $cmd): string
    {
        $controller = $this->getController($cmd);
        $command = $this->getCommand($cmd);
        $controller = $this->instantiateController($controller);

        return $controller->$command();
    }

    protected function getController(string $cmd): string
    {
        $parts = explode('.', $cmd);

        if (count($parts) === 2) {
            return $parts[0];
        }

        return $this->default_controller ?: 'Error';
    }

    protected function getCommand(string $cmd): string
    {
        $parts = explode('.', $cmd);

        if (count($parts) == 2) {
            return $parts[1];
        }

        return '';
    }

    protected function instantiateController(string $controller): mixed
    {
        $class = "ILIAS\\Plugin\\CrsGrpImport\\Frontend\\Controller\\$controller";

        return new $class($this->getCoreController(), $this->dic);
    }
}
