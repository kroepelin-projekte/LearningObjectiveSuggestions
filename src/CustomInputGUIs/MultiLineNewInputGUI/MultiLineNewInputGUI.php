<?php

namespace  SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\MultiLineNewInputGUI;

use ilFormPropertyGUI;
use ILIAS\DI\UIServices;
use ILIAS\UI\Component\Symbol\Glyph\Factory as GlyphFactory;
use ilTableFilterItem;
use ilTemplate;
use ilTemplateException;
use ilToolbarItem;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\PropertyFormGUI\Items\Items;

class MultiLineNewInputGUI extends ilFormPropertyGUI implements ilTableFilterItem, ilToolbarItem
{
    public const PLUGIN_DIRECTORY = 'Customizing/global/plugins/Services/EventHandling/EventHook/LearningObjectiveSuggestions';

    const SHOW_INPUT_LABEL_ALWAYS = 3;
    const SHOW_INPUT_LABEL_NONE = 1;
    const SHOW_INPUT_LABEL_ONCE = 2;
    protected static int $counter = 0;
    protected static bool $init = false;
    protected GlyphFactory $glyph_factory;
    /**
     * @var ilFormPropertyGUI[]
     */
    protected array $inputs = [];
    /**
     * @var ilFormPropertyGUI[]|null
     */
    protected ?array $inputs_generated = null;
    protected int $show_input_label = self::SHOW_INPUT_LABEL_ONCE;
    protected bool $show_sort = true;
    protected array $value = [];
    protected UIServices $ui;

    public function __construct(string $title = "", string $post_var = "")
    {
        global $DIC;
        $this->ui = $DIC->ui();
        $this->glyph_factory = $DIC->ui()->factory()->symbol()->glyph();
        parent::__construct($title, $post_var);
        self::init($this->ui);
    }

    public static function init(UIServices $ui): void
    {
        if (self::$init === false) {
            self::$init = true;

            $ui->mainTemplate()->addCss(self::PLUGIN_DIRECTORY  . '/src/CustomInputGUIs/MultiLineNewInputGUI/css/multi_line_new_input_gui.css');
            $ui->mainTemplate()->addJavaScript(self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/js/multi_line_new_input_gui.min.js');
        }
    }

    public function addInput(ilFormPropertyGUI $input): void
    {
        $this->inputs[] = $input;
        $this->inputs_generated = null;
    }

    public function checkInput() : bool
    {
        //ToDo: consider to eliminate $ok, immediately return false if some var is empty
        $ok = true;

        foreach ($this->getInputs() as $i => $inputs) {
            foreach ($inputs as $org_post_var => $input) {
                // still needed?
                #$b_value = $_POST[$input->getPostVar()];
                if (($_POST[$this->getPostVar()] !== null ) && (key_exists($i,$_POST[$this->getPostVar()]))) {
                    $org_post = $_POST[$this->getPostVar()][$i][$org_post_var];
                    //null not alowed for min, max (and role though never seen)
                    if ($org_post == NULL) {
                        $ok = false;
                    }
                    //empty role ist posted as 0 (not null), not allowed, too
                    if (($org_post_var == 'role') && ($org_post == 0 )) {
                        $ok = false;
                    }
                }
                // still needed (see above)?
                #$_POST[$input->getPostVar()] = $b_value;
            }
        }

        $this->inputs_generated = null;

        if ($ok) {
            return true;
        } else {
            return false;
        }
    }

    public function getInputs(bool $need_one_line_at_least = true) : array
    {
        if ($this->inputs_generated === null) {
            $this->inputs_generated = [];

            foreach (array_values($this->getValue($need_one_line_at_least)) as $i => $value) {
                $inputs = [];

                foreach ($this->inputs as $input) {
                    $input = clone $input;

                    $org_post_var = $input->getPostVar();

                    if(array_key_exists($org_post_var, $value)) {
                        Items::setValueToItem($input, $value[$org_post_var]);
                    }

                    $post_var = $this->getPostVar() . "[" . $i . "][";
                    if (strpos($org_post_var, "[") !== false) {
                        $post_var .= strstr($input->getPostVar(), "[", true) . "][" . strstr($org_post_var, "[");
                    } else {
                        $post_var .= $org_post_var . "]";
                    }
                    $input->setPostVar($post_var);

                    $inputs[$org_post_var] = $input;
                }

                $this->inputs_generated[] = $inputs;
            }
        }

        return $this->inputs_generated;
    }

    /**
     * @param ilFormPropertyGUI[] $inputs
     */
    public function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
        $this->inputs_generated = null;
    }

    public function getShowInputLabel() : int
    {
        return $this->show_input_label;
    }

    public function setShowInputLabel(int $show_input_label): void
    {
        $this->show_input_label = $show_input_label;
    }
    /**
     * @throws ilTemplateException
     */
    public function getTableFilterHTML() : string
    {
        return $this->render();
    }
    /**
     * @throws ilTemplateException
     */
    public function getToolbarHTML() : string
    {
        return $this->render();
    }

    public function getValue(bool $need_one_line_at_least = false) : array
    {
        $values = $this->value;

        if ($need_one_line_at_least && empty($values)) {
            $values = [[]];
        }

        return $values;
    }

    public function setValue(array $value): void
    {
        if (is_array($value)) {
            $this->value = $value;
        } else {
            $this->value = [];
        }
    }

    public function insert(ilTemplate $tpl): void
    {
        $html = $this->render();

        $tpl->setCurrentBlock("prop_generic");
        $tpl->setVariable("PROP_GENERIC", $html);
        $tpl->parseCurrentBlock();
    }

    public function isShowSort() : bool
    {
        return $this->show_sort;
    }

    public function setShowSort(bool $show_sort): void
    {
        $this->show_sort = $show_sort;
    }

    /**
     * @throws ilTemplateException|\ilSystemStyleException
     */
    public function render() : string
    {
        $counter = ++self::$counter;

        $tpl = new ilTemplate(
            'multi_line_new_input_gui.html',
            true,
            true,
            'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/',
            \ilGlobalTemplateInterface::DEFAULT_BLOCK,
            true
        );

        $tpl->setVariable("COUNTER", htmlspecialchars($counter));

        $remove_first_line = (!$this->getRequired() && empty($this->getValue(false)));
        $tpl->setVariable("REMOVE_FIRST_LINE", htmlspecialchars($remove_first_line));
        $tpl->setVariable("REQUIRED", htmlspecialchars($this->getRequired()));
        $tpl->setVariable("SHOW_INPUT_LABEL", htmlspecialchars($this->getShowInputLabel()));

        if (!$this->getRequired()) {
            $tpl->setCurrentBlock("add_first_line");

            if (!empty($this->getInputs())) {
                $hiddenInputGUI = new ilTemplate(
                    'multi_line_new_input_gui_hide.html',
                    false,
                    false,
                    'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/',
                    \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                    true
                );

                $tpl->setVariable("HIDE_ADD_FIRST_LINE", $hiddenInputGUI->get());
            }

            $tpl->setVariable("ADD_FIRST_LINE", $this->ui->renderer()->render($this->ui->factory()->symbol()->glyph()->add()->withAdditionalOnLoadCode(function (string $id) use ($counter) : string {
                return 'il.MultiLineNewInputGUI.init(' . $counter . ', $("#' . $id . '").parent().parent().parent(), true)';
            })));

            $tpl->parseCurrentBlock();
        }

        $tpl->setCurrentBlock("line");

        foreach ($this->getInputs() as $i => $inputs) {
            if ($remove_first_line) {
                $hiddenInputGUI = new ilTemplate(
                    'multi_line_new_input_gui_hide.html',
                    false,
                    false,
                    'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/',
                    \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                    true
                );

                $tpl->setVariable("HIDE_LINE", $hiddenInputGUI->get());
            }

            $tpl->setVariable("INPUTS", Items::renderInputs($inputs));

            if ($this->isShowSort()) {
                $sort_tpl = new ilTemplate(
                    'multi_line_new_input_gui_sort.html',
                    true,
                    true,
                    'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/',
                    \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                    true
                );

                $sort_tpl->setVariable("UP", $this->ui->renderer()->render($this->glyph_factory->sortAscending()));
                if ($i === 0) {
                    $hiddenInputGUI = new ilTemplate(
                        'multi_line_new_input_gui_hide.html',
                        false,
                        false,
                        'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/',
                        \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                        true
                    );

                    $sort_tpl->setVariable("HIDE_UP", $hiddenInputGUI->get());
                }

                $sort_tpl->setVariable("DOWN", $this->ui->renderer()->render($this->ui->factory()->symbol()->glyph()->sortDescending()));
                if ($i === (count($this->getInputs()) - 1)) {
                    $hiddenInputGUI = new ilTemplate(
                        'multi_line_new_input_gui_hide.html',
                        false,
                        false,
                        'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/',
                        \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                        true
                    );
                    $sort_tpl->setVariable("HIDE_DOWN", $hiddenInputGUI->get());
                }

                $tpl->setVariable("SORT", $sort_tpl->get());
            }

            $tpl->setVariable("ADD", $this->ui->renderer()->render($this->ui->factory()->symbol()->glyph()->add()->withAdditionalOnLoadCode(function (string $id) use ($i, $counter) : string {
                return 'il.MultiLineNewInputGUI.init(' . $counter . ', $("#' . $id . '").parent().parent().parent())' . ($i === (count($this->getInputs()) - 1) ? ';il.MultiLineNewInputGUI.update('
                        . $counter . ', $("#'
                        . $id
                        . '").parent().parent().parent().parent())' : '');
            })));

            $tpl->setVariable("REMOVE", $this->ui->renderer()->render($this->ui->factory()->symbol()->glyph()->remove()));
            if ($this->getRequired() && count($this->getInputs()) < 2) {
                $hiddenInputGUI = new ilTemplate(
                    'multi_line_new_input_gui_hide.html',
                    false,
                    false,
                    'public/' . self::PLUGIN_DIRECTORY . '/src/CustomInputGUIs/MultiLineNewInputGUI/',
                    \ilGlobalTemplateInterface::DEFAULT_BLOCK,
                    true
                );
                $tpl->setVariable("HIDE_REMOVE", $hiddenInputGUI->get());
            }

            $tpl->parseCurrentBlock();
        }

        return $tpl->get();
    }

    public function setValueByArray(array $values): void
    {
        if(array_key_exists($this->getPostVar(), $values)) {
            $this->setValue($values[$this->getPostVar()]);
        }
    }
}
