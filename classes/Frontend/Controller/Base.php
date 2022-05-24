<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\Frontend\Controller;

use ILIAS\DI\Container;

/**
 * @author Michael Jansen <mjansen@databay.de>
 */
abstract class Base
{
    public const CTX_IS_BASE_CLASS = 'baseClass';
    public const CTX_IS_COMMAND_CLASS = 'cmdClass';
    public const CTX_IS_COMMAND = 'cmd';
    /**
     * @var Container
     */
    protected $dic;
    /**
     * @var array
     */
    protected $parameters = [];
    /**
     * The main controller of the Plugin
     * @var \ilCrsGrpImportUIHookGUI
     */
    public $coreController;

    /**
     * Base constructor.
     * @param \ilCrsGrpImportUIHookGUI $controller
     * @param Container                $dic
     */
    final public function __construct(\ilCrsGrpImportUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;

        $this->init();
    }

    /**
     *
     */
    protected function init()
    {
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    final public function __call($name, $arguments)
    {
        return \call_user_func_array([$this, $this->getDefaultCommand()], []);
    }

    /**
     * @return string
     */
    abstract public function getDefaultCommand();

    /**
     * @return \ilCrsGrpImportUIHookGUI
     */
    public function getCoreController()
    {
        return $this->coreController;
    }

    /**
     * @param string $a_context
     * @param string $a_value_a
     * @param string $a_value_b
     * @return bool
     */
    final public function isContext($a_context, $a_value_a = '', $a_value_b = '')
    {
        switch ($a_context) {
            case self::CTX_IS_BASE_CLASS:
            case self::CTX_IS_COMMAND_CLASS:
                $class = isset($_GET[$a_context]) ? $_GET[$a_context] : '';
                return \strlen($class) > 0 && \in_array(
                    strtolower($class),
                    \array_map('strtolower', (array) $a_value_a)
                );

            case self::CTX_IS_COMMAND:
                $cmd = isset($_GET[$a_context]) ? $_GET[$a_context] : '';
                return \strlen($cmd) > 0 && \in_array(strtolower($cmd), \array_map('strtolower', (array) $a_value_a));
        }

        return false;
    }

    /**
     * @return string
     */
    final public function getControllerName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
