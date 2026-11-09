<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\NumberInputGUI;

use ilNumberInputGUI;
use ilTableFilterItem;
use ilToolbarItem;

class NumberInputGUI extends ilNumberInputGUI implements ilTableFilterItem, ilToolbarItem
{
    /**
     * @return string
     */
    public function getTableFilterHTML() : string
    {
        return $this->render();
    }

    /**
     * @return string
     */
    public function getToolbarHTML() : string
    {
        return $this->render();
    }
}
