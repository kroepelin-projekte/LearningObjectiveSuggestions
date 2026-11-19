<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI;

use Closure;
use ilFormPropertyGUI;
use ilFormSectionHeaderGUI;
use ilPropertyFormGUI;
use ilRadioGroupInputGUI;
use ilRadioOption;
use ilSubEnabledFormPropertyGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\MultiLineInputGUI\MultiLineInputGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\MultiLineNewInputGUI\MultiLineNewInputGUI;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI\Exception\PropertyFormGUIException;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI\Items\Items;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\TabsInputGUI\TabsInputGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\TabsInputGUI\TabsInputGUITab;


abstract class PropertyFormGUI extends ilPropertyFormGUI
{
    /**
     * @var string
     *
     * @deprecated
     */
    public const string LANG_MODULE = '';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_CLASS = 'class';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_DISABLED = 'disabled';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_MULTI = 'multi';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_NOT_ADD = 'not_add';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_OPTIONS = 'options';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_REQUIRED = 'required';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_SUBITEMS = 'subitems';

    /**
     * @var string
     *
     * @deprecated
     */
    public const string PROPERTY_VALUE = 'value';

    /**
     * @var array
     *
     * @deprecated
     */
    protected array $fields = [];

    /**
     * @var object
     *
     * @deprecated
     */
    protected $parent;

    /**
     * @var ilFormPropertyGUI[]|ilFormSectionHeaderGUI[]
     *
     * @deprecated
     */
    private array $items_cache = [];


    /**
     * PropertyFormGUI constructor
     *
     * @param object $parent
     *
     * @deprecated
     */
    public function __construct(/*object*/ $parent)
    {
        $this->initId();

        parent::__construct();

        $this->parent = $parent;

        $this->initForm();
    }


    /**
     * @inheritDoc
     *
     * @deprecated
     */
    public function checkInput() : bool
    {
        return parent::checkInput();
    }


    /**
     * @return bool
     *
     * @deprecated
     */
    public function storeForm() : bool
    {
        if (!$this->storeFormCheck()) {
            return false;
        }

        $this->storeFormItems($this->fields);

        return true;
    }


    /**
     * @param string      $key
     * @param string|null $default
     *
     * @return string
     *
     * @deprecated
     */
    public function txt(string $key, ?string $default = null) : string
    {
        if ($default !== null) {
            return self::plugin()->translate($key, static::LANG_MODULE, [], true, '', $default);
        } else {
            return self::plugin()->translate($key, static::LANG_MODULE);
        }
    }


    /**
     * @param string $key
     *
     * @return mixed
     *
     * @deprecated
     */
    protected abstract function getValue(string $key);


    /**
     * @deprecated
     */
    protected function initAction(): void
    {
        $this->setFormAction(self::dic()->ctrl()->getFormAction($this->parent));
    }


    /**
     * @deprecated
     */
    abstract protected function initCommands(): void;


    /**
     * @deprecated
     */
    abstract protected function initFields(): void;


    /**
     * @deprecated
     */
    abstract protected function initId(): void;


    /**
     * @deprecated
     */
    abstract protected function initTitle(): void;


    /**
     * @return bool
     *
     * @deprecated
     */
    final protected function storeFormCheck() : bool
    {
        $this->setValuesByPost();

        $this->check_input_called = false; // Fix 'Error: ilPropertyFormGUI->checkInput() called twice.'

        if (!$this->checkInput()) {
            return false;
        }

        return true;
    }


    /**
     * @param string $key
     * @param mixed  $value
     *
     * @deprecated
     */
    abstract protected function storeValue(string $key, $value): void;


    /**
     * @param array                               $fields
     * @param ilPropertyFormGUI|ilFormPropertyGUI $parent_item
     *
     * @throws PropertyFormGUIException $fields needs to be an array!
     * @throws PropertyFormGUIException Class $class not exists!
     * @throws PropertyFormGUIException $item must be an instance of ilFormPropertyGUI, ilFormSectionHeaderGUI or ilRadioOption!
     * @throws PropertyFormGUIException $options needs to be an array!
     *
     * @deprecated
     */
    private function getFields(array $fields, $parent_item): void
    {
        if (!is_array($fields)) {
            throw new PropertyFormGUIException("\$fields needs to be an array!", PropertyFormGUIException::CODE_INVALID_FIELD);
        }

        foreach ($fields as $key => $field) {
            if (!is_array($field)) {
                throw new PropertyFormGUIException("\$fields needs to be an array!", PropertyFormGUIException::CODE_INVALID_FIELD);
            }

            if ($field[self::PROPERTY_NOT_ADD]) {
                continue;
            }

            $item = Items::getItem($key, $field, $parent_item, $this);

            if (!($item instanceof ilFormPropertyGUI || $item instanceof ilFormSectionHeaderGUI || $item instanceof ilRadioOption || $item instanceof TabsInputGUITab)) {
                throw new PropertyFormGUIException("\$item must be an instance of ilFormPropertyGUI, ilFormSectionHeaderGUI or ilRadioOption!", PropertyFormGUIException::CODE_INVALID_FIELD);
            }

            $this->items_cache[$key] = $item;

            if ($item instanceof ilFormPropertyGUI) {
                if (!isset($field[self::PROPERTY_VALUE])) {
                    if (!($parent_item instanceof MultiLineInputGUI) && !($parent_item instanceof MultiLineNewInputGUI) && !($parent_item instanceof TabsInputGUI)
                        && !($parent_item instanceof TabsInputGUITab)
                    ) {
                        $value = $this->getValue($key);

                        Items::setValueToItem($item, $value);
                    }
                }
            }

            if (is_array($field[self::PROPERTY_SUBITEMS])) {
                $this->getFields($field[self::PROPERTY_SUBITEMS], $item);
            }

            if ($parent_item instanceof TabsInputGUI) {
                $parent_item->addTab($item);
            } else {
                if ($parent_item instanceof TabsInputGUITab || $parent_item instanceof MultiLineInputGUI || $parent_item instanceof MultiLineNewInputGUI) {
                    $parent_item->addInput($item);
                } else {
                    if ($parent_item instanceof ilRadioGroupInputGUI) {
                        $parent_item->addOption($item);
                    } else {
                        if ($parent_item instanceof ilPropertyFormGUI) {
                            $parent_item->addItem($item);
                        } else {
                            if ($item instanceof ilFormSectionHeaderGUI) {
                                // Fix 'Call to undefined method ilFormSectionHeaderGUI::setParent()'
                                Closure::bind(function (ilFormSectionHeaderGUI $item)/*:void*/ {
                                    $this->sub_items[]
                                        = $item; // https://github.com/ILIAS-eLearning/ILIAS/blob/b8a2a3a203d8fb5bab988849ab43616be7379551/Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php#L45
                                }, $parent_item, ilSubEnabledFormPropertyGUI::class)($item);
                            } else {
                                $parent_item->addSubItem($item);
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * @deprecated
     */
    private function initForm(): void
    {
        $this->initAction();

        $this->initCommands();

        $this->initTitle();

        $this->initItems();
    }


    /**
     * @deprecated
     */
    private function initItems(): void
    {
        $this->initFields();

        $this->getFields($this->fields, $this);
    }


    /**
     * @param array $fields
     *
     * @deprecated
     */
    private function storeFormItems(array $fields): void
    {
        foreach ($fields as $key => $field) {
            if (isset($this->items_cache[$key])) {
                $item = $this->items_cache[$key];

                if ($item instanceof ilFormPropertyGUI) {
                    $value = Items::getValueFromItem($item);

                    $this->storeValue($key, $value);
                }

                if (is_array($field[self::PROPERTY_SUBITEMS])) {
                    if (!($item instanceof MultiLineInputGUI) && !($item instanceof MultiLineNewInputGUI) && !($item instanceof TabsInputGUI) && !($item instanceof TabsInputGUITab)) {
                        $this->storeFormItems($field[self::PROPERTY_SUBITEMS]);
                    }
                }
            }
        }
    }
}
