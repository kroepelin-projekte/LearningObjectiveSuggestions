<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config;

use ilUtil;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;

class LearningObjectiveCourseTableGUI extends \ilTable2GUI
{
    protected \ilCtrl $ctrl;
    protected \ilLearningObjectiveSuggestionsPlugin $pl;
    public function __construct($a_parent_obj)
    {
        global $DIC;
        $this->pl = \ilLearningObjectiveSuggestionsPlugin::getInstance();
        parent::__construct($a_parent_obj, '', '');
        $this->ctrl = $DIC->ctrl();
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->setRowTemplate('tpl.row_generic.html', $this->pl->getDirectory());
        $this->setTitle($this->pl->txt("courses"));
        $this->addColumns();
    }
    public function setCourses(array $courses): void
    {
        $data = array();
        foreach ($courses as $course) {
            /**
             * @var LearningObjectiveCourse $course
             */

            $data[] = array(
                'ref_id' => $course->getRefId(),
                'title' => $course->getTitle(),
                'is_calculation_active' => !$course->getIsCalculationInactive()
            );
        }
        $this->setData($data);
    }
    protected function addColumns(): void
    {
        foreach ($this->getSelectableColumns() as $column => $data) {
            if ($this->isColumnSelected($column)) {
                $this->addColumn($data['txt'], $column);
            }
        }
        $this->addColumn($this->pl->txt("actions"));
    }
    protected function fillRow(array $a_set): void
    {
        global $DIC;

        foreach (array_keys($this->getSelectableColumns()) as $column) {
            if (!$this->isColumnSelected($column)) {
                continue;
            }

            $value = '&nbsp;';
            switch ($column) {
                case 'is_calculation_active':
                    $factory = $DIC->ui()->factory();
                    if ($a_set[$column] === true) {
                        $value = "active";
                    } else {
                        $value = "inactive";
                    }
                    break;
                default:
                    $value = $a_set[$column];
                    break;
            }

            $this->tpl->setCurrentBlock('td');
            $this->tpl->setVariable('VALUE', $value ? $value : '&nbsp;');
            $this->tpl->parseCurrentBlock();
        }
        $list = new \ilAdvancedSelectionListGUI();
        static $id = 0;
        $list->setId(++$id);
        $this->ctrl->setParameter($this->parent_obj, 'course_ref_id', $a_set['ref_id']);
        $list->addItem($this->pl->txt("configurate"), '', $this->ctrl->getLinkTarget($this->parent_obj, \ilLearningObjectiveSuggestionsConfigGUI::CMD_CONFIGURE_COURSE));
        $list->addItem($this->pl->txt("delete_learning_objective_course"), '', $this->ctrl->getLinkTarget($this->parent_obj, \ilLearningObjectiveSuggestionsConfigGUI::CMD_CONFIRM_DELETE_COURSE_CONFIG));



        switch ($a_set['is_calculation_active']) {
            case 1:
                $list->addItem($this->pl->txt('deactivate_calculation'), '', $this->ctrl->getLinkTarget($this->parent_obj, \ilLearningObjectiveSuggestionsConfigGUI::CMD_DEACTIVATE_CALCULATION));
                break;
            default:
                $list->addItem($this->pl->txt('activate_calculation'), '', $this->ctrl->getLinkTarget($this->parent_obj, \ilLearningObjectiveSuggestionsConfigGUI::CMD_ACTIVATE_CALCULATION));
                break;
        }


        $this->ctrl->clearParameters($this->parent_obj);
        $list->setListTitle($this->pl->txt("actions"));
        $this->tpl->setCurrentBlock('td');
        $this->tpl->setVariable('VALUE', $list->getHTML());
        $this->tpl->parseCurrentBlock();
    }
    public function getSelectableColumns(): array
    {
        return array(
            'ref_id' => array( 'txt' => $this->pl->txt("ref_id"), 'default' => true ),
            'title' => array( 'txt' => $this->pl->txt("title"), 'default' => true ),
            'is_calculation_active' => array( 'txt' => $this->pl->txt("calculation"), 'default' => true )
        );
    }
}
