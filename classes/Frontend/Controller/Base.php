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

namespace ILIAS\Plugin\CrsGrpImport\Frontend\Controller;

use ilCrsGrpImportUIHookGUI;
use ILIAS\DI\Container;
use ILIAS\HTTP\Wrapper\WrapperFactory;
use ILIAS\Plugin\CrsGrpImport\Utils\UiUtil;
use ILIAS\Refinery\Factory;
use ReflectionClass;

abstract class Base
{
    public const CTX_IS_BASE_CLASS = 'baseClass';
    public const CTX_IS_COMMAND_CLASS = 'cmdClass';
    public const CTX_IS_COMMAND = 'cmd';

    protected Container $dic;
    protected array $parameters = [];
    public ilCrsGrpImportUIHookGUI $coreController;
    protected UiUtil $uiUtil;
    protected WrapperFactory $httpWrapper;
    protected Factory $refinery;

    final public function __construct(ilCrsGrpImportUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;
        $this->uiUtil = new UiUtil();
        $this->httpWrapper = $this->dic->http()->wrapper();
        $this->refinery = $this->dic->refinery();

        $this->init();
    }

    protected function init(): void
    {
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
                $class = $_GET[$a_context] ?? '';
                return $class !== '' && in_array(
                    strtolower($class),
                    array_map('strtolower', (array) $a_value_a),
                    true
                );

            case self::CTX_IS_COMMAND:
                $cmd = $_GET[$a_context] ?? '';
                return $cmd !== '' && in_array(
                    strtolower($cmd),
                    array_map('strtolower', (array) $a_value_a),
                    true
                );
        }

        return false;
    }

    final public function getControllerName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }
}
