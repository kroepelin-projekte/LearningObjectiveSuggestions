<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgram;
use ilLearningObjectiveSuggestionsConfigGUI;

/**
 * Class CourseConfigKSFormGUI
 */
class CourseConfigKSFormGUI
{
    private ?CourseConfigProvider $config;

    private $ctrl;

    private $ui;

    private $pl;

    private $objectives;

    private ?array $udf_definition;

    private ?StudyProgramQuery $study_program_query;

    public function __construct(
        $pl,
        ?CourseConfigProvider $config = null,
        $objectives = null,
        ?StudyProgramQuery $study_program_query = null,
        ?array $udf_definition = null
    ) {
        global $DIC;

        $this->config = $config;
        $this->ctrl = $DIC->ctrl();
        $this->ui = $DIC->ui()->factory();
        $this->pl = $pl;
        $this->objectives = $objectives;
        $this->study_program_query = $study_program_query;
        $this->udf_definition = $udf_definition;
    }
    /**
     * @param       $pl
     * @param array $options
     * @return \ILIAS\UI\Component\Input\Container\Form\Standard
     */
    public function getMainConfigForm()
    {
        $select = $this->ui->input()->field()->select(
            $this->pl->txt('udf_study_program'),
            $this->udf_definition
        )->withValue($this->config->get('udf_id_study_program'))
        ->withByline($this->pl->txt('udf_study_program_info'));

        $checkbox_input = $this->ui->input()->field()->optionalGroup(
            [
                $select
            ],
            $this->pl->txt('change_udf_study_program'),
            $this->pl->txt('change_udf_study_program_info')
        );

        $udf_id_study_programm_section = $this->ui->input()->field()->section(
            ['udf_id_study_program' => $checkbox_input],
            $this->pl->txt('configuration')
        );

        $sections_weight_rough = [];
        $study_programms_all = $this->study_program_query->getAll();
        foreach ($study_programms_all as $study_program) {
            $sections_weight_rough[] = $this->buildWeightRoughConfigSections($study_program);
        }

        $sections = [
            $udf_id_study_programm_section,
            $this->buildGeneralConfigSection(),
            $this->buildWeightFineConfigSection()
        ];

        return $this->ui->input()->container()->form()->standard(
            $this->ctrl->getFormAction(new ilLearningObjectiveSuggestionsConfigGUI(), 'saveMainConfig'),
            array_merge($sections, $sections_weight_rough)
        );
    }

    /**
     * @return \ILIAS\UI\Component\Input\Field\Section
     */
    protected function buildGeneralConfigSection()
    {
        $inputs = [
            'min_amount_suggestions' => $this->buildNumericInput('min_amount_suggestions', 'min_amount_suggestions_info'),
            'max_amount_suggestions' => $this->buildNumericInput('max_amount_suggestions', 'max_amount_suggestions_info'),
            'bias'                   => $this->buildNumericInput('bias'),
            'offset'                 => $this->buildNumericInput('offset'),
            'steps'                  => $this->buildNumericInput('steps'),
        ];

        return $this->ui->input()->field()->section(
            $inputs,
            $this->pl->txt('general')
        );
    }

    /**
     * @return \ILIAS\UI\Component\Input\Field\Section|null
     */
    protected function buildWeightFineConfigSection()
    {
        $field = $this->ui->input()->field();
        $inputs = [];

        foreach ($this->objectives as $objective) {
            $key = 'weight_fine_' . $objective->getId();

            $input = $field->numeric($objective->getTitle())
                           ->withRequired(true)
                           ->withStepSize(0.01) // allows decimals; adjust precision as needed
                           ->withDedicatedName($key);

            $value = $this->config->get($key);
            if (is_numeric($value)) {
                $input = $input->withValue((float) $value);
            }

            $inputs[$key] = $input;
        }

        if (empty($inputs)) {
            return null;
        }

        return $field->section($inputs, $this->pl->txt('weight_fine'));
    }

    /**
     * @return array|\ILIAS\UI\Component\Input\Field\Section|null
     * @throws \JsonException
     */
    protected function buildWeightRoughConfigSections(StudyProgram $study_program)
    {
        $field = $this->ui->input()->field();
        $objectives = $this->objectives;

        if ($objectives === []) {
            return [];
        }

        $inputs = [];
        foreach ($objectives as $objective) {
            $key = 'weight_rough_' . $objective->getId() . '_' . $study_program->getId();
            $value = $this->config->get($key);
            $inputs[$key] = $field->numeric($objective->getTitle())
                                  ->withRequired(true)
                                  ->withStepSize(0.01)
                                  ->withDedicatedName($key)
                                  ->withValue(is_numeric($value) ? (float) $value : 100.0);
        }

        if (empty($inputs)) {
            return null;
        }
        return $field->section($inputs, $this->pl->txt('weight_rough') . ' ' . $study_program->gettitle());
    }

    /**
     * @param string      $key
     * @param string|null $info_key
     * @return mixed
     */
    private function buildNumericInput(string $key, ?string $info_key = null)
    {
        $input = $this->ui->input()->field()
                                  ->numeric($this->pl->txt($key), $info_key !== null ? $this->pl->txt($info_key) : null)
                                  ->withRequired(true)
                                  ->withDedicatedName($key); // keeps the old post var name

        $value = $this->config->get($key);
        if (is_numeric($value)) {
            $input = $input->withValue((int) $value);
        }

        return $input;
    }

    /**
     * @param CourseConfigProvider $config
     * @param                      $objectives
     * @param StudyProgramQuery    $study_program_query
     * @param array                $udf_definition
     * @return void
     */
    public function save()
    {
        global $DIC;

        $request = $DIC->http()->request();

        $form = $this->getMainConfigForm();

        if (
            $request->getMethod() == 'POST'
            && array_key_exists('ref_id', $request->getQueryParams())
            && array_key_exists('course_ref_id', $request->getQueryParams())
        ) {
            $form = $form->withRequest($request);
            $data = $form->getData();

            foreach ($data as $values) {
                foreach ($values as $key => $value) {
                    if ($key == 'udf_id_study_program') {
                        $this->config->set($key, $value[0]);
                    } else {
                        $this->config->set($key, $value);
                    }
                }
            }

            $tpl = $DIC->ui()->mainTemplate();
            $tpl->setOnScreenMessage('success', $this->pl->txt('saved'), true);
        }
    }

    /**
     * @return \ILIAS\UI\Component\Input\Container\Form\Standard
     * @throws \ilCtrlException
     */
    public function getAddCourseFormGUI()
    {
        global $DIC;

        $factory = $DIC->ui()->factory();

        $field = $factory->input()->field();

        $ref_id = $field->text($this->pl->txt('ref_id'), $this->pl->txt('ref_id_info'))
                        ->withRequired(true)
                        ->withDedicatedName('ref_id')
                        /*->withAdditionalTransformation(
                            $DIC->refinery()->custom()->constraint(
                                fn(?int $id): bool => $id !== null && \ilObject::_lookupType($id, true) === 'crs',
                                $this->pl->txt('ref_id_not_a_course')
                            )
                        )*/;

        $section = $field->section(
            ['ref_id' => $ref_id],
            $this->pl->txt('create_course')
        );

        return $factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction(new ilLearningObjectiveSuggestionsConfigGUI(), 'saveCourse'),
            ['course' => $section]
        )->withSubmitLabel($this->pl->txt('save'));
    }
}
