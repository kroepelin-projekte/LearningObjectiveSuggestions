<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI\Items;

use ilDateTime;
use ilFormPropertyGUI;
use ilFormSectionHeaderGUI;
use ILIAS\DI\UIServices;
use ilNumberInputGUI;
use ilPropertyFormGUI;
use ilRadioOption;
use ilRepositorySelector2InputGUI;
use ilSystemStyleException;
use ilTemplate;
use ilTemplateException;
use ilUtil;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI\Exception\PropertyFormGUIException;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI\PropertyFormGUI;

use TypeError;

final class Items
{
    public const PLUGIN_DIRECTORY = 'Customizing/global/plugins/Services/EventHandling/EventHook/LearningObjectiveSuggestions';

    protected static bool $init = false;

    private function __construct()
    {

    }

    public static final function getItem(string $key, array $field, ilPropertyFormGUI|ilFormPropertyGUI $parent_item, PropertyFormGUI $parent): ilFormPropertyGUI|ilFormSectionHeaderGUI|ilRadioOption
    {
        /**
         * @var ilFormPropertyGUI|ilFormSectionHeaderGUI|ilRadioOption $item
         */
        /*
        if ($field[PropertyFormGUI::PROPERTY_CLASS] instanceof Input) {
            $item = new UIInputComponentWrapperInputGUI($field[PropertyFormGUI::PROPERTY_CLASS], $key);

            if (empty($item->getTitle())) {
                if (!$field["setTitle"]) {
                    $item->setTitle($parent->txt($key));
                }
            }

            if (empty($item->getInfo())) {
                if (!$field["setInfo"]) {
                    $item->setInfo($parent->txt($key . "_info", ""));
                }
            }
        } else {*/
            if (!class_exists($field[PropertyFormGUI::PROPERTY_CLASS])) {
                throw new PropertyFormGUIException("Class " . $field[PropertyFormGUI::PROPERTY_CLASS]
                    . " not exists!", PropertyFormGUIException::CODE_INVALID_PROPERTY_CLASS);
            }

            if ($field[PropertyFormGUI::PROPERTY_CLASS] === ilRepositorySelector2InputGUI::class) {
                $item = new $field[PropertyFormGUI::PROPERTY_CLASS]("", $key, false, get_class($parent));
            } else {
                $item = new $field[PropertyFormGUI::PROPERTY_CLASS]();
            }

            if ($item instanceof ilFormSectionHeaderGUI) {
                if (!$field["setTitle"]) {
                    $item->setTitle($parent->txt($key));
                }
            } else {
                if ($item instanceof ilRadioOption) {
                    if (!$field["setTitle"]) {
                        $item->setTitle($parent->txt($parent_item->getPostVar() . "_" . $key));
                    }

                    $item->setValue($key);
                } else {
                    if (!$field["setTitle"]) {
                        $item->setTitle($parent->txt($key));
                    }

                    $item->setPostVar($key);
                }
            }

            if (!$field["setInfo"]) {
                $item->setInfo($parent->txt($key . "_info", ""));
            }
        //}

        self::setPropertiesToItem($item, $field);

        if ($item instanceof ilFormPropertyGUI) {
            if (isset($field[PropertyFormGUI::PROPERTY_VALUE])) {
                $value = $field[PropertyFormGUI::PROPERTY_VALUE];

                Items::setValueToItem($item, $value);
            }
        }

        return $item;
    }

    public static function getValueFromItem(ilFormPropertyGUI|ilFormSectionHeaderGUI|ilRadioOption $item): mixed
    {
        /*if ($item instanceof MultiLineInputGUI) {
            //return filter_input(INPUT_POST,$item->getPostVar()); // Not work because MultiLineInputGUI modify $_POST
            return $_POST[$item->getPostVar()];
        }*/

        if (method_exists($item, "getChecked")) {
            return boolval($item->getChecked());
        }

        if (method_exists($item, "getDate")) {
            return $item->getDate();
        }

        if (method_exists($item, "getImage")) {
            return $item->getImage();
        }

        if (method_exists($item, "getValue") && !($item instanceof ilRadioOption)) {
            if ($item->getMulti()) {
                return $item->getMultiValues();
            } else {
                $value = $item->getValue();

                if ($item instanceof ilNumberInputGUI) {
                    $value = floatval($value);
                } else {
                    if (empty($value) && !is_array($value)) {
                        $value = "";
                    }
                }

                return $value;
            }
        }

        return null;
    }

    public static function getter(object $object, string $property): mixed
    {
        if (method_exists($object, $method = "get" . self::strToCamelCase($property))) {
            return $object->{$method}();
        }

        if (method_exists($object, $method = "is" . self::strToCamelCase($property))) {
            return $object->{$method}();
        }

        return null;
    }

    public static function init(UIServices $ui): void
    {
        if (self::$init === false) {
            self::$init = true;
            $ui->mainTemplate()->addCss(self::PLUGIN_DIRECTORY . '/src/PropertyFormGUI/Items/css/input_gui_input.css');
        }
    }

    /**
     * @param ilFormPropertyGUI[] $inputs
     * @throws ilTemplateException|ilSystemStyleException
     */
    public static function renderInputs(array $inputs) : string
    {
        global $DIC;
        self::init($DIC->ui());

        $input_tpl = new ilTemplate(
            'input_gui_input.html',
            true,
            true,
            'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/PropertyFormGUI/Items/',
            \ilGlobalTemplateInterface::DEFAULT_BLOCK,
            true
        );

        $input_tpl->setCurrentBlock("input");

        foreach ($inputs as $input) {
            $input_tpl->setVariable("TITLE", htmlspecialchars($input->getTitle()));

            if ($input->getRequired()) {
                $requiredInputGUI = new ilTemplate(
                    'input_gui_input_required.html',
                    true,
                    false,
                    'public/' . self::PLUGIN_DIRECTORY . '/src/PropertyFormGUI/Items/',
                    \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                    true
                );

                $input_tpl->setVariable("REQUIRED", $requiredInputGUI->get());
            }

            $input_html = $input->render();


            $input_html = str_replace('<div class="help-block"></div>', "", $input_html);
            $input_tpl->setVariable("INPUT", $input_html);

            if ($input->getInfo()) {
                $input_info_tpl = new ilTemplate(
                    'input_gui_input_info.html',
                    true,
                    true,
                    'public/' . self::PLUGIN_DIRECTORY . '/src/PropertyFormGUI/Items/',
                    \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                    true
                );

                $input_info_tpl->setVariable("INFO", htmlspecialchars($input->getInfo()));

                $input_tpl->setVariable("INFO", self::output()->getHTML($input_info_tpl));
            }

            if ($input->getAlert()) {
                $input_alert_tpl = new ilTemplate(
                    'input_gui_input_alert.html',
                    true,
                    true,
                    'public/' . self::PLUGIN_DIRECTORY . '/src/PropertyFormGUI/Items/',
                    \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                    true
                );

                $input_alert_tpl->setVariable("IMG",
                    self::output()->getHTML(self::dic()->ui()->factory()->image()->standard(ilUtil::getImagePath("icon_alert.svg"), self::dic()->language()->txt("alert"))));
                $input_alert_tpl->setVariable("TXT", htmlspecialchars($input->getAlert()));
                $input_tpl->setVariable("ALERT", self::output()->getHTML($input_alert_tpl));
            }

            $input_tpl->parseCurrentBlock();
        }

        return $input_tpl->get();
    }

    public static function setValueToItem(ilFormPropertyGUI|ilFormSectionHeaderGUI|ilRadioOption $item, mixed $value): void
    {
        /*if ($item instanceof MultiLineInputGUI) {
            $item->setValueByArray([
                $item->getPostVar() => $value
            ]);

            return;
        }*/

        if (method_exists($item, "setChecked")) {
            $item->setChecked($value);

            return;
        }

        if (method_exists($item, "setDate")) {
            if (is_string($value)) {
                $value = new ilDateTime($value, IL_CAL_DATE);
            }

            $item->setDate($value);

            return;
        }

        if (method_exists($item, "setImage")) {
            $item->setImage($value);

            return;
        }

        if (method_exists($item, "setValue") && !($item instanceof ilRadioOption)) {
            $item->setValue($value);
        }
    }

    public static function setter(object $object, string $property, mixed $value): mixed
    {
        $res = null;

        if (method_exists($object, $method = "with" . self::strToCamelCase($property)) || method_exists($object, $method = "set" . self::strToCamelCase($property))) {
            try {
                $res = $object->{$method}($value);
            } catch (TypeError $ex) {
                try {
                    $res = $object->{$method}(intval($value));
                } catch (TypeError $ex) {
                    $res = $object->{$method}(boolval($value));
                }
            }
        }

        return $res;
    }

    public static function strToCamelCase(string $string) : string
    {
        return str_replace("_", "", ucwords($string, "_"));
    }

    private static function setPropertiesToItem(ilFormPropertyGUI|ilFormSectionHeaderGUI|ilRadioOption $item, array $properties): void
    {
        foreach ($properties as $property_key => $property_value) {
            $property = "";

            switch ($property_key) {
                case PropertyFormGUI::PROPERTY_DISABLED:
                    $property = "setDisabled";
                    break;

                case PropertyFormGUI::PROPERTY_MULTI:
                    $property = "setMulti";
                    break;

                case PropertyFormGUI::PROPERTY_OPTIONS:
                    $property = "setOptions";
                    $property_value = [$property_value];
                    break;

                case PropertyFormGUI::PROPERTY_REQUIRED:
                    $property = "setRequired";
                    break;

                case PropertyFormGUI::PROPERTY_CLASS:
                case PropertyFormGUI::PROPERTY_NOT_ADD:
                case PropertyFormGUI::PROPERTY_SUBITEMS:
                case PropertyFormGUI::PROPERTY_VALUE:
                    break;

                default:
                    $property = $property_key;
                    break;
            }

            if (!empty($property)) {
                if (!is_array($property_value)) {
                    $property_value = [$property_value];
                }

                if (method_exists($item, $property)) {
                    call_user_func_array([$item, $property], $property_value);
                } else {
                    if ($item instanceof ilRepositorySelector2InputGUI) {
                        if (method_exists($item->getExplorerGUI(), $property)) {
                            call_user_func_array([$item->getExplorerGUI(), $property], $property_value);
                        } else {
                            throw new PropertyFormGUIException("Class " . get_class($item)
                                . " has no method " . $property . "!", PropertyFormGUIException::CODE_INVALID_FIELD);
                        }
                    } else {
                        throw new PropertyFormGUIException("Class " . get_class($item)
                            . " has no method " . $property . "!", PropertyFormGUIException::CODE_INVALID_FIELD);
                    }
                }
            }
        }
    }
}