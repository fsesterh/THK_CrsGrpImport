<?php

/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpImport\Frontend\Controller;

use ilCrsGrpImportUIHookGUI;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpImport\Utils\UiUtil;

/**
 * @author Michael Jansen <mjansen@databay.de>
 */
abstract class Base
{
    public const CTX_IS_BASE_CLASS = 'baseClass';
    public const CTX_IS_COMMAND_CLASS = 'cmdClass';
    public const CTX_IS_COMMAND = 'cmd';

    protected Container $dic;
    protected array $parameters = [];
    public ilCrsGrpImportUIHookGUI $coreController;
    protected UiUtil $uiUtil;

    final public function __construct(ilCrsGrpImportUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;
        $this->uiUtil = new UiUtil();

        $this->init();
    }

    protected function init(): void
    {
    }

    /**
     * @return mixed
     */
    final public function __call(string $name, array $arguments)
    {
        return \call_user_func_array([$this, $this->getDefaultCommand()], []);
    }

    abstract public function getDefaultCommand(): string;

    public function getCoreController(): ilCrsGrpImportUIHookGUI
    {
        return $this->coreController;
    }

    final public function isContext(string $a_context, string $a_value_a = '', string $a_value_b = ''): bool
    {
        switch ($a_context) {
            case self::CTX_IS_BASE_CLASS:
            case self::CTX_IS_COMMAND_CLASS:
                $class = isset($_GET[$a_context]) ? $_GET[$a_context] : '';
                return strlen($class) > 0 && \in_array(
                    strtolower($class),
                    array_map('strtolower', (array) $a_value_a),
                    true
                );

            case self::CTX_IS_COMMAND:
                $cmd = isset($_GET[$a_context]) ? $_GET[$a_context] : '';
                return strlen($cmd) > 0 && in_array(
                    strtolower($cmd),
                    array_map('strtolower', (array) $a_value_a),
                    true
                );
        }

        return false;
    }

    final public function getControllerName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
