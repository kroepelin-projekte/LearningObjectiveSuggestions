<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form;

use ilCrsInitialTestStates;
use ilLearningObjectiveSuggestionsConfigGUI;
use ilObject;
use ilRbacReview;
use ilSelectInputGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\MultiLineNewInputGUI\MultiLineNewInputGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\CustomInputGUIs\NumberInputGUI\NumberInputGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;

class CourseConfigFormGUI extends \ilPropertyFormGUI
{
    protected CourseConfigProvider $config;
    protected LearningObjectiveQuery $objective_query;
    protected StudyProgramQuery $study_program_query;
    protected \ilLearningObjectiveSuggestionsPlugin $pl;

    public function __construct(CourseConfigProvider $config, LearningObjectiveQuery $objective_query, StudyProgramQuery $study_program_query)
    {
        parent::__construct();
        $this->config = $config;
        $this->objective_query = $objective_query;
        $this->study_program_query = $study_program_query;
        $this->pl = \ilLearningObjectiveSuggestionsPlugin::getInstance();
        $this->init();
    }
    protected function init(): void
    {
        $this->setTitle($this->pl->txt("configuration"));

        $options = array();
        $definitions = \ilUserDefinedFields::_getInstance()->getDefinitions();
        foreach ($definitions as $field_id => $data) {
            $options[$field_id] = $data['field_name'];
        }

        $udf = new \ilSelectInputGUI($this->pl->txt("udf_study_program"), 'udf_id_study_program');
        $udf->setInfo($this->pl->txt("udf_study_program_info"));
        $udf->setOptions($options);
        $udf->setRequired(true);
        $udf->setValue($this->config->get('udf_id_study_program'));

        if ($this->config->get('udf_id_study_program')) {
            $item = new \ilCheckboxInputGUI($this->pl->txt("change_udf_study_program"), 'change_mapping_ids');
            $item->setInfo($this->pl->txt("change_udf_study_program_info"));
            $item->addSubItem($udf);
            $this->addItem($item);
            $this->addGeneralConfig();
            $this->addWeightFineConfig();
            $this->addWeightRoughConfig();
            $this->addRoleAssignmentConfig();
        } else {
            $this->addItem($udf);
        }

        $this->addCommandButton(\ilLearningObjectiveSuggestionsConfigGUI::CMD_SAVE, $this->pl->txt("save"));
        $this->addCommandButton(\ilLearningObjectiveSuggestionsConfigGUI::CMD_CANCEL, $this->pl->txt("cancel"));
    }
    protected function addGeneralConfig(): void
    {
        $item = new \ilFormSectionHeaderGUI();
        $item->setTitle($this->pl->txt("general"));
        $this->addItem($item);

        $item = new \ilMultiSelectInputGUI($this->pl->txt("objectives_main"), 'learning_objectives_main');
        $item->setInfo($this->pl->txt("objectives_main_info"));
        $item->setRequired(true);
        $item->setWidth(100);
        $item->setWidthUnit('%');
        $item->setHeight(150);
        $objectives = $this->getObjectives();
        $options = array();
        foreach ($objectives as $objective) {
            $options[$objective->getId()] = $objective->getTitle();
        }
        $item->setOptions($options);

        $jsonString = $this->config->get($item->getPostVar());
        $item->setValue(json_decode($jsonString ?: '{}', true));
        $this->addItem($item);

        $item = clone $item;
        $item->setTitle($this->pl->txt("objectives_extended"));
        $item->setPostVar('learning_objectives_extended');
        $item->setInfo($this->pl->txt("objectives_extended_info"));

        $jsonString = $this->config->get($item->getPostVar());
        $item->setValue(json_decode($jsonString ?: '{}', true));
        $this->addItem($item);

        $item = new \ilNumberInputGUI($this->pl->txt("min_amount_suggestions"), 'min_amount_suggestions');
        $item->setInfo($this->pl->txt("min_amount_suggestions_info"));
        $item->setValue($this->config->get($item->getPostVar()));
        $item->setRequired(true);
        $this->addItem($item);

        $item = new \ilNumberInputGUI($this->pl->txt("max_amount_suggestions"), 'max_amount_suggestions');
        $item->setInfo($this->pl->txt("max_amount_suggestions_info"));
        $item->setValue($this->config->get($item->getPostVar()));
        $item->setRequired(true);
        $this->addItem($item);

        $item = new \ilNumberInputGUI($this->pl->txt("bias"), 'bias');
        $item->setValue($this->config->get($item->getPostVar()));
        $item->setRequired(true);
        $this->addItem($item);

        $item = new \ilNumberInputGUI($this->pl->txt("offset"), 'offset');
        $item->setValue($this->config->get($item->getPostVar()));
        $item->setRequired(true);
        $this->addItem($item);

        $item = new \ilNumberInputGUI($this->pl->txt("steps"), 'steps');
        $item->setValue($this->config->get($item->getPostVar()));
        $item->setRequired(true);
        $this->addItem($item);
    }
    protected function addWeightFineConfig(): void
    {
        $item = new \ilFormSectionHeaderGUI();
        $item->setTitle($this->pl->txt("weight_fine"));
        $this->addItem($item);
        foreach ($this->getObjectives() as $objective) {
            $item = new \ilNumberInputGUI($objective->getTitle(), 'weight_fine_' . $objective->getId());
            $item->setRequired(true);
            $item->allowDecimals(true);
            $item->setValue($this->config->get($item->getPostVar()));
            $this->addItem($item);
        }
    }
    protected function addWeightRoughConfig(): void
    {
        foreach ($this->study_program_query->getAll() as $study_program) {
            $item = new \ilFormSectionHeaderGUI();
            $item->setTitle($this->pl->txt("weight_rough") . ' "' . $study_program->getTitle() . '"');
            $this->addItem($item);
            foreach ($this->getObjectives() as $objective) {
                $post_var = 'weight_rough_' . $objective->getId() . '_' . $study_program->getId();
                $item = new \ilNumberInputGUI($objective->getTitle(), $post_var);
                $item->setRequired(true);
                $item->allowDecimals(true);
                $value = $this->config->get($item->getPostVar());
                $item->setValue($value === null ? 100 : $value);
                $this->addItem($item);
            }
        }
    }
    protected function addRoleAssignmentConfig(): void
    {
        global $DIC;

        $item = new \ilFormSectionHeaderGUI();
        $item->setTitle($this->pl->txt("role_assignment"));
        $this->addItem($item);

        $item = new MultiLineNewInputGUI($this->pl->txt('role_assignment_config'), 'role_assignment_config');

        $subitem = new NumberInputGUI('min_points', 'min_points');
        $item->addInput($subitem);
        $subitem = new NumberInputGUI('max_points', 'max_points');
        $item->addInput($subitem);
        $subitem = new ilSelectInputGUI('role', 'role');
        $subitem->setOptions($this->getAllRoles());
        $item->addInput($subitem);
        $item->setRequired(true);

        $jsonString = $this->config->get($item->getPostVar()) ?: '[]';

        $item->setValue((array) json_decode($jsonString, true));
        $this->addItem($item);
        $item->setRequired(false);
    }
    protected function getAllRoles(): array
    {
        global $DIC;
        $roles = $DIC->rbac()->review()->getRolesByFilter(ilRbacReview::FILTER_NOT_INTERNAL);

        $options = [];
        $options[0] = '';
        foreach ($roles as $role_arr) {
            $options[$role_arr['obj_id']] = ilObject::_lookupTitle($role_arr['obj_id']);
        }

        return $options;
    }
    protected function getAllTests(): array
    {
        global $DIC;
        $roles = $DIC->rbac()->review()->getGlobalRoles();

        $options = [];
        $options[0] = '';
        foreach ($roles as $role_id) {
            $options[$role_id] = ilObject::_lookupTitle($role_id);
        }
        return $options;
    }
    protected function getObjectives(): ?array
    {
        static $objectives = null;
        if ($objectives !== null) {
            return $objectives;
        }
        $objectives = $this->objective_query->getAll();

        return $objectives;
    }
}
