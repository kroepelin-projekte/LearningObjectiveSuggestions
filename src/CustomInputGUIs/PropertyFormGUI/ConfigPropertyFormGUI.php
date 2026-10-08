<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI\Exception\PropertyFormGUIException;

abstract class ConfigPropertyFormGUI extends PropertyFormGUI
{

    /**
     * @var string
     *
     * @abstract
     *
     * @deprecated
     */
    public const string CONFIG_CLASS_NAME = '';


    /**
     * ConfigPropertyFormGUI constructor
     *
     * @param object $parent
     *
     * @deprecated
     */
    public function __construct(/*object*/ $parent)
    {
        $this->checkConfigClassNameConst();

        parent::__construct($parent);
    }


    /**
     * @inheritDoc
     *
     * @deprecated
     */
    protected function getValue(string $key)
    {
        return call_user_func(static::CONFIG_CLASS_NAME . '::getField', $key);
    }


    /**
     * @inheritDoc
     *
     * @deprecated
     */
    protected function storeValue(string $key, $value): void
    {
        call_user_func(static::CONFIG_CLASS_NAME . '::setField', $key, $value);
    }


    /**
     * @throws PropertyFormGUIException Your class needs to implement the CONFIG_CLASS_NAME constant!
     *
     * @deprecated
     */
    private function checkConfigClassNameConst(): void
    {
        if (!defined('static::CONFIG_CLASS_NAME') || empty(static::CONFIG_CLASS_NAME) || !class_exists(static::CONFIG_CLASS_NAME)) {
            throw new PropertyFormGUIException('Your class needs to implement the CONFIG_CLASS_NAME constant!', PropertyFormGUIException::CODE_MISSING_CONST_CONFIG_CLASS_NAME);
        }
    }
}
