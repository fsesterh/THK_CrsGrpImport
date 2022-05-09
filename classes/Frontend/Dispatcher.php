<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\Frontend;

use ILIAS\DI\Container;

/**
 * Class Dispatcher
 * @package ILIAS\Plugin\CrsGrpImport\Frontend
 * @author  Michael Jansen <mjansen@databay.de>
 */
class Dispatcher
{
    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var \ilCrsGrpImportUIHookGUI
     */
    protected $coreController;

    /**
     * @var string
     */
    protected $defaultController = '';

    /**
     * @var Container
     */
    protected $dic;

    /**
     * Dispatcher constructor.
     * @param \ilCrsGrpImportUIHookGUI $baseController
     * @param string                   $defaultController
     */
    private function __construct(\ilCrsGrpImportUIHookGUI $baseController, $defaultController = '')
    {
        $this->coreController = $baseController;
        $this->defaultController = $defaultController;
    }

    /**
     * @param \ilCrsGrpImportUIHookGUI $base_controller
     * @return self
     */
    public static function getInstance(\ilCrsGrpImportUIHookGUI $base_controller)
    {
        if (self::$instance === null) {
            self::$instance = new self($base_controller);
        }

        return self::$instance;
    }

    /**
     *
     */
    private function __clone()
    {
    }

    /**
     * @param string $controller
     */
    protected function requireController($controller)
    {
        require_once $this->getControllerPath() . $controller . '.php';
    }

    /**
     * @return string
     */
    protected function getControllerPath()
    {
        $path = $this->getCoreController()->getPluginObject()->getDirectory() .
            DIRECTORY_SEPARATOR .
            'classes' .
            DIRECTORY_SEPARATOR .
            'Frontend' .
            DIRECTORY_SEPARATOR .
            'Controller' .
            DIRECTORY_SEPARATOR;

        return $path;
    }

    /**
     * @param Container $dic
     */
    public function setDic(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * @param string $cmd
     * @return string
     */
    public function dispatch($cmd)
    {
        $controller = $this->getController($cmd);
        $command = $this->getCommand($cmd);
        $controller = $this->instantiateController($controller);

        return $controller->$command();
    }

    /**
     * @param string $cmd
     * @return string
     */
    protected function getController($cmd)
    {
        $parts = \explode('.', $cmd);

        if (\count($parts) == 2) {
            return $parts[0];
        }

        return $this->defaultController ? $this->defaultController : 'Error';
    }

    /**
     * @param string $cmd
     * @return string
     */
    protected function getCommand($cmd)
    {
        $parts = \explode('.', $cmd);

        if (\count($parts) == 2) {
            $cmd = $parts[1];

            return $cmd . 'Cmd';
        }

        return '';
    }

    /**
     * @param string $controller
     * @return mixed
     */
    protected function instantiateController($controller)
    {
        $class = "ILIAS\\Plugin\\CrsGrpImport\\Frontend\\Controller\\$controller";

        return new $class($this->getCoreController(), $this->dic);
    }

    /**
     * @return \ilCrsGrpImportUIHookGUI
     */
    public function getCoreController()
    {
        return $this->coreController;
    }

    /**
     * @param \ilCrsGrpImportUIHookGUI $coreController
     */
    public function setCoreController(\ilCrsGrpImportUIHookGUI $coreController)
    {
        $this->coreController = $coreController;
    }
}