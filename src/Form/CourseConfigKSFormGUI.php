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

    private \ilCtrlInterface $ctrl;

    private \ILIAS\UI\Factory $ui;

    private \ilLearningObjectiveSuggestionsPlugin $pl;

    private ?array $objectives;

    private ?array $udf_definition;

    private ?StudyProgramQuery $study_program_query;

    public function __construct(
        \ilLearningObjectiveSuggestionsPlugin $pl,
        ?CourseConfigProvider $config = null,
        ?array $objectives = null,
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

        $all_sections = [
            'udf_id_study_program' => $udf_id_study_programm_section
        ];

        if ($this->config->get('udf_id_study_program')) {
            $all_sections['general'] = $this->buildGeneralConfigSection();
            $all_sections['weight_fine'] = $this->buildWeightFineConfigSection();

            foreach ($this->study_program_query->getAll() as $study_program) {
                $all_sections['weight_rough_' . $study_program->getId()] = $this->buildWeightRoughConfigSections($study_program);
            }

            // the form only accepts FormInput instances, drop sections without inputs
            $all_sections = array_filter($all_sections, static fn($section) => $section !== null);
        }

        return $this->ui->input()->container()->form()->standard(
            $this->ctrl->getFormActionByClass(ilLearningObjectiveSuggestionsConfigGUI::class, 'saveMainConfig'),
            $all_sections
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

            $input = $field->numeric(htmlspecialchars($objective->getTitle()))
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
     * @return \ILIAS\UI\Component\Input\Field\Section|null
     * @throws \JsonException
     */
    protected function buildWeightRoughConfigSections(StudyProgram $study_program)
    {
        $field = $this->ui->input()->field();
        $objectives = $this->objectives;

        if (empty($objectives)) {
            return null;
        }

        $inputs = [];
        foreach ($objectives as $objective) {
            $key = 'weight_rough_' . $objective->getId() . '_' . $study_program->getId();
            $value = $this->config->get($key);
            $inputs[$key] = $field->numeric(htmlspecialchars($objective->getTitle()))
                                  ->withRequired(true)
                                  ->withStepSize(0.01)
                                  ->withDedicatedName($key)
                                  ->withValue(is_numeric($value) ? (float) $value : 100.0);
        }

        if (empty($inputs)) {
            return null;
        }
        return $field->section($inputs, $this->pl->txt('weight_rough') . ' ' . htmlspecialchars($study_program->gettitle()));
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
     * Returns null on success, otherwise the form containing the validation errors
     * so the caller can render it again.
     *
     * @return \ILIAS\UI\Component\Input\Container\Form\Standard|null
     */
    public function save()
    {
        global $DIC;

        $request = $DIC->http()->request();
        $tpl = $DIC->ui()->mainTemplate();

        $form = $this->getMainConfigForm();

        if (
            $request->getMethod() !== 'POST'
            || !array_key_exists('course_ref_id', $request->getQueryParams())
        ) {
            return $form;
        }

        $form = $form->withRequest($request);
        $data = $form->getData();

        if ($data === null || ($data['udf_id_study_program']['udf_id_study_program'] === null ||
                (isset($data['udf_id_study_program']['udf_id_study_program'][0]) &&
                    $data['udf_id_study_program']['udf_id_study_program'][0] === ''))
        ) {
            // getData() is null as soon as one input fails validation, the
            // messages are attached to the inputs and shown when the form is rendered
            $errors = $this->collectErrors($form->getInputs());
            $tpl->setOnScreenMessage(
                'failure',
                $DIC->language()->txt('form_input_not_valid') . ($errors !== [] ? '<br>' . htmlspecialchars(implode('<br>', $errors)) : '')
            );
            return $form;
        }

        foreach ($data as $values) {
            foreach ($values as $key => $value) {
                if ($key === 'udf_id_study_program') {
                    // optional group: null if unchecked, otherwise [0 => selected value]
                    if (is_array($value)) {
                        $this->config->set($key, $value[0]);
                    }
                } else {
                    $this->config->set($key, $value);
                }
            }
        }

        $tpl->setOnScreenMessage('success', $this->pl->txt('saved'), true);

        return null;
    }

    /**
     * @param \ILIAS\UI\Component\Input\Input[] $inputs
     * @return string[]
     */
    private function collectErrors(array $inputs): array
    {
        $errors = [];
        foreach ($inputs as $input) {
            if ($input->getError() !== null) {
                $errors[] = $input->getLabel() . ': ' . $input->getError();
            }
            if ($input instanceof \ILIAS\UI\Component\Input\Field\Group) {
                $errors = array_merge($errors, $this->collectErrors($input->getInputs()));
            }
        }
        return $errors;
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
                        ->withDedicatedName('ref_id');

        $section = $field->section(
            ['ref_id' => $ref_id],
            $this->pl->txt('create_course')
        );

        return $factory->input()->container()->form()->standard(
            $this->ctrl->getFormActionByClass(ilLearningObjectiveSuggestionsConfigGUI::class, 'saveCourse'),

            ['course' => $section]
        )->withSubmitLabel($this->pl->txt('save'));
    }
}
