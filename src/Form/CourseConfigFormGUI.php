<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form;

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

    private array $udf_definition;

    /**
     * @param CourseConfigProvider   $config
     * @param LearningObjectiveQuery $objective_query
     * @param StudyProgramQuery      $study_program_query
     * @param array                  $udf_definition
     */
    public function __construct(
        CourseConfigProvider $config,
        LearningObjectiveQuery $objective_query,
        StudyProgramQuery $study_program_query,
        array $udf_definition
    ) {
        parent::__construct();
        $this->config = $config;
        $this->objective_query = $objective_query;
        $this->study_program_query = $study_program_query;
        $this->pl = \ilLearningObjectiveSuggestionsPlugin::getInstance();
        $this->udf_definition = $udf_definition;
        $this->init();
    }

    /**
     * @return void
     */
    protected function init(): void
    {
        $options = $this->udf_definition;

        if ($this->config->get('udf_id_study_program')) {
            $this->addRoleAssignmentConfig();
        } else {
            $udf = new \ilSelectInputGUI($this->pl->txt('udf_study_program'), 'udf_id_study_program');
            $udf->setInfo($this->pl->txt('udf_study_program_info'));
            $udf->setOptions($options);
            $udf->setRequired(true);
            $udf->setValue($this->config->get('udf_id_study_program'));
            $this->addItem($udf);
        }

        $this->addCommandButton(\ilLearningObjectiveSuggestionsConfigGUI::CMD_SAVE_ROLES, $this->pl->txt("save"));
        $this->addCommandButton(\ilLearningObjectiveSuggestionsConfigGUI::CMD_CANCEL, $this->pl->txt("cancel"));
    }

    /**
     * @return void
     */
    protected function addRoleAssignmentConfig(): void
    {
        $item = new \ilFormSectionHeaderGUI();
        $item->setTitle($this->pl->txt('role_assignment'));
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

    /**
     * @return array
     */
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

    /**
     * @return array|null
     */
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
