<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form\CourseConfigFormGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form\CourseConfigKSFormGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Form\NotificationConfigFormGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourseQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\LearningObjectiveCourseTableGUI;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\TwigParser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;

/**
 * Class ilLearningObjectiveSuggestionsConfigGUI
 * @ilCtrl_IsCalledBy  ilLearningObjectiveSuggestionsConfigGUI: ilObjComponentSettingsGUI
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilLearningObjectiveSuggestionsConfigGUI extends ilPluginConfigGUI implements ilCtrlSecurityInterface
{
    public const string CMD_ADD_COURSE = 'addCourse';

    public const string CMD_LEARNING_SUGGESTIONS_GENERATE = 'learningSuggestionsGenerate';

    public const string CMD_CANCEL = 'cancel';

    public const string CMD_CONFIGURE = 'configure';

    public const string CMD_CONFIGURE_COURSE = 'configureCourse';

    public const string CMD_CONFIRM_DELETE_COURSE_CONFIG = 'confirmDeleteCourse';

    public const string CMD_DELETE_COURSE = 'deleteCourse';

    public const string CMD_CONFIGURE_NOTIFICATIONS = 'configureNotifications';

    public const string CMD_SAVE_ROLES = 'saveRoles';

    public const string CMD_SAVE_COURSE = 'saveCourse';

    public const string CMD_SAVE_NOTIFICATIONS = 'saveNotifications';

    public const string CMD_DEACTIVATE_CALCULATION = 'deactivateCalculation';

    public const string CMD_ACTIVATE_CALCULATION = 'activateCalculation';

    public const string CMD_SAVE_MAIN_CONFIG = 'saveMainConfig';

    public const string CMD_HANDLE_TABLE_ACTION = 'handleTableAction';

    /**
     * Only these commands may be dispatched by performCommand()
     */
    private const array ALLOWED_COMMANDS = [
        self::CMD_CONFIGURE,
        self::CMD_ADD_COURSE,
        self::CMD_SAVE_COURSE,
        self::CMD_CANCEL,
        self::CMD_CONFIGURE_COURSE,
        self::CMD_SAVE_MAIN_CONFIG,
        self::CMD_SAVE_ROLES,
        self::CMD_CONFIGURE_NOTIFICATIONS,
        self::CMD_SAVE_NOTIFICATIONS,
        self::CMD_CONFIRM_DELETE_COURSE_CONFIG,
        self::CMD_DELETE_COURSE,
        self::CMD_ACTIVATE_CALCULATION,
        self::CMD_DEACTIVATE_CALCULATION,
        self::CMD_LEARNING_SUGGESTIONS_GENERATE,
        self::CMD_HANDLE_TABLE_ACTION,
    ];

    /**
     * GET commands that change data; ilCtrl adds a CSRF token to their links and rejects requests without it
     */
    private const array UNSAFE_GET_COMMANDS = [
        self::CMD_DELETE_COURSE,
        self::CMD_ACTIVATE_CALCULATION,
        self::CMD_DEACTIVATE_CALCULATION,
        self::CMD_LEARNING_SUGGESTIONS_GENERATE,
        self::CMD_HANDLE_TABLE_ACTION,
    ];

    public const string TAB_CONFIGURE_COURSE = 'configureCourse';

    public const string TAB_CONFIGURE_NOTIFICATIONS = 'configureNotifications';

    protected ilTemplate|ilGlobalTemplateInterface $tpl;

    protected ilCtrl|ilCtrlInterface $ctrl;

    protected ilTabsGUI $tabs;

    protected ilToolbarGUI $toolbar;

    protected ilLearningObjectiveSuggestionsPlugin $pl;

    public function __construct()
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->pl = ilLearningObjectiveSuggestionsPlugin::getInstance();
    }

    /***
     * @param string $cmd
     * @return void
     * @throws ilCtrlException
     */
    public function performCommand(string $cmd): void
    {
        global $DIC;

        // Core only checks read permission on the plugin administration before forwarding to this GUI
        if (!$this->hasWritePermission()) {
            $DIC['ilErr']->raiseError($DIC->language()->txt('no_permission'), $DIC['ilErr']->WARNING);
            return;
        }

        if (!in_array($cmd, self::ALLOWED_COMMANDS, true)) {
            $cmd = self::CMD_CONFIGURE;
        }

        $this->ctrl->saveParameter($this, 'course_ref_id');
        $this->$cmd();
    }

    /**
     * @return string[]
     */
    public function getUnsafeGetCommands(): array
    {
        return self::UNSAFE_GET_COMMANDS;
    }

    /**
     * @return string[]
     */
    public function getSafePostCommands(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    private function hasWritePermission(): bool
    {
        global $DIC;

        foreach (array_keys(ilObject::_getObjectsByType('cmps')) as $obj_id) {
            foreach (ilObject::_getAllReferences((int) $obj_id) as $ref_id) {
                if ($DIC->rbac()->system()->checkAccess('write', (int) $ref_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Target of the course table's row actions, CSRF protected (see UNSAFE_GET_COMMANDS)
     *
     * @return void
     * @throws ilCtrlException
     */
    protected function handleTableAction(): void
    {
        $table = new LearningObjectiveCourseTableGUI($this);
        $table->handleTableAction();
        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @throws ilCtrlException
     */
    protected function configure(): void
    {
        global $DIC;

        $table = new LearningObjectiveCourseTableGUI($this);

        $this->toolbar->addComponent(
            $DIC->ui()->factory()->button()->standard(
                $this->pl->txt('add_course'),
                $this->ctrl->getLinkTarget($this, self::CMD_ADD_COURSE)
            )
        );

        $this->toolbar->addComponent(
            $DIC->ui()->factory()->button()->standard(
                $this->pl->txt('learning_suggestion_generate'),
                $this->ctrl->getLinkTarget($this, self::CMD_LEARNING_SUGGESTIONS_GENERATE)
            )
        );

        $query = new LearningObjectiveCourseQuery(new ConfigProvider());
        $table->setCourses($query->getAll());
        $this->tpl->setContent($table->getHTML());
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function configureCourse(): void
    {
        global $DIC;

        $this->addActions(self::TAB_CONFIGURE_COURSE);
        $this->tabs->setBackTarget(
            $this->pl->txt('back'),
            $this->ctrl->getLinkTarget($this, self::CMD_CONFIGURE)
        );

        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $this->initCourseHeader($course);
        $config = new CourseConfigProvider($course);

        $udf_definition = $this->getUdfDefinition();

        $learning_objective_query = new LearningObjectiveQuery($config);
        $objectives = $this->getObjectives($learning_objective_query);

        $form = new CourseConfigFormGUI(
            $config,
            $learning_objective_query
        );
        $form->setFormAction($this->ctrl->getFormAction($this));

        $renderer = $DIC->ui()->renderer();

        $course_main_config_form = new CourseConfigKSFormGUI(
            $this->pl,
            $config,
            $objectives,
            new StudyProgramQuery($config),
            $udf_definition
        );
        $main_form = $course_main_config_form->getMainConfigForm();
        $html = $renderer->render($main_form);

        if ($config->get('udf_id_study_program')) {
            $html .= $form->getHTML();
        }

        $this->tpl->setContent($html);
    }

    /**
     * @throws ilCtrlException
     */
    protected function confirmDeleteCourse(): void
    {
        $confirmation_gui = new ilConfirmationGUI();
        $confirmation_gui->setFormAction($this->ctrl->getFormAction($this));
        $confirmation_gui->setConfirm($this->pl->txt('delete_learning_objective_course'), self::CMD_DELETE_COURSE, self::CMD_DELETE_COURSE);

        $this->ctrl->setParameter($this, 'cmd', self::CMD_CANCEL);

        $confirmation_gui->setCancel($this->pl->txt('cancel'), self::CMD_CANCEL, self::CMD_CANCEL);
        $confirmation_gui->setHeaderText($this->pl->txt('confirm_delete_crs'));

        $this->ctrl->clearParameterByClass(self::class, 'course_ref_id');
        $this->tpl->setContent($confirmation_gui->getHTML());
    }

    /**
     * @throws ilCtrlException
     */
    protected function deleteCourse(): void
    {
        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $config = new ConfigProvider();
        $course_config = new CourseConfigProvider($course);
        $ref_ids = (array) json_decode($config->get('course_ref_ids'), true);

        if (($key = array_search($_GET['course_ref_id'], $ref_ids)) !== false) {
            unset($ref_ids[$key]);
        }

        $config->set('course_ref_ids', json_encode(array_unique($ref_ids)));
        $course_config->delete();
        $this->tpl->setOnScreenMessage('success', $this->pl->txt("course_removed"), true);
        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function configureNotifications(): void
    {
        global $DIC;

        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $renderer = $DIC->ui()->renderer();

        $this->addActions(self::TAB_CONFIGURE_NOTIFICATIONS);
        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $this->initCourseHeader($course);
        $this->tabs->setBackTarget($this->pl->txt("back"), $this->ctrl->getLinkTarget($this, self::CMD_CONFIGURE));

        $notification_config_form = new NotificationConfigFormGUI(
            new CourseConfigProvider($course), new TwigParser()
        );
        $main_form = $notification_config_form->buidForm();

        $this->tpl->setContent($renderer->render($main_form));
    }

    /**
     * @param LearningObjectiveCourse $course
     * @return void
     */
    protected function initCourseHeader(LearningObjectiveCourse $course): void
    {
        $this->tpl->setTitle(htmlspecialchars($course->getTitle()));
    }

    /**
     * @return void
     */
    protected function addCourse(): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();

        $course_main_config_form = new CourseConfigKSFormGUI(
            $this->pl
        );
        $form = $course_main_config_form->getAddCourseFormGUI();

        $this->tpl->setContent($renderer->render($form));
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function saveCourse(): void
    {
        global $DIC;

        $request = $DIC->http()->request();

        $course_main_config_form = new CourseConfigKSFormGUI(
            $this->pl
        );

        $form = $course_main_config_form->getAddCourseFormGUI();

        if (
            $request->getMethod() == 'POST'
        ) {
            $form = $form->withRequest($request);
            $data = $form->getData();
            $tpl = $DIC->ui()->mainTemplate();

            if(empty($data) || ilObject::_lookupType((int) $data['course']['ref_id'], true) != 'crs') {
                $tpl->setOnScreenMessage('failure', $this->pl->txt('ref_id_not_a_course'), true);
                $this->ctrl->redirect($this, self::CMD_CONFIGURE);
            }

            $config = new ConfigProvider();
            $ref_ids = (array) json_decode($config->get('course_ref_ids') ?? '', true);
            $ref_ids[] = (int) $data['course']['ref_id'];
            $config->set('course_ref_ids', json_encode(array_unique($ref_ids)));
            $this->tpl->setOnScreenMessage('success', $this->pl->txt('course_added'), true);
            $this->ctrl->redirect($this, self::CMD_CONFIGURE);
        }
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function cancel(): void
    {
        $this->configure();
    }

    /**
     * @param CourseConfigProvider $config
     * @param ilPropertyFormGUI    $form
     * @return void
     */
    protected function storeConfig(CourseConfigProvider $config, ilPropertyFormGUI $form): void
    {
        foreach ($form->getItems() as $item) {
            /** @var ilFormPropertyGUI $item */
            $value = $form->getInput($item->getPostVar());

            if ($value === null) {
                continue;
            }
            $value = (is_array($value)) ? json_encode($value) : $value;

            $config->set($item->getPostVar(), $value);
        }
    }

    /**
     * @throws ilCtrlException
     */
    private function learningSuggestionsGenerate(): void
    {
        global $DIC;

        $config = new ConfigProvider();
        $ref_ids = $config->getCourseRefIds();

        foreach ($ref_ids as $ref_id) {
            $obj_id = ilObject::_lookupObjectId($ref_id);
            $settings = ilLOSettings::getInstanceByObjId($obj_id);
            $initial_test_ref_id = $settings->getInitialTest();


            if (empty($initial_test_ref_id)) {
                $this->tpl->setOnScreenMessage('failure', $this->pl->txt('initial_test_missing') . ':' . $ref_id, true);
                $this->ctrl->redirect($this, self::CMD_CONFIGURE);
            }



            $test = new ilObjTest($initial_test_ref_id, true);

            $test_ref_ids = ilObject::_getAllReferences($test->getId());
            $learning_objective_suggestion = new LearningObjectiveSuggestion();
            $user_ids = [];
            if ($test->participantDataExist()) {
                $active_participants_list = $test->getActiveParticipantList();
                $user_ids = $this->removeInactiveUsers($active_participants_list->getAllUserIds());
            }

            foreach ($user_ids as $key => $userId) {
                $learning_suggestion_exists = $learning_objective_suggestion->suggestionExists(
                    $userId,
                    $obj_id
                );

                if ($learning_suggestion_exists) {
                    unset($user_ids[$key]);
                }
            }
            $user_ids = array_values($user_ids);

            $parent_found = false;
            $parent = 0;
            foreach ($test_ref_ids as $ref_id) {
                $parent = $DIC->repositoryTree()->getParentId($ref_id);
                if (in_array($parent, $ref_ids)) {
                    $parent_found = true;
                    break;
                }
            }

            if ($parent_found && ilObject::_lookupType($parent, true) === 'crs') {
                $course = new LearningObjectiveCourse(new ilObjCourse($parent, true));

                if (!$course->getIsCalculationInactive()) {
                    foreach ($user_ids as $userId) {
                        $user = new User(new ilObjUser($userId));
                        if ($this->pl->startCalculation($course, $user)) {
                            $this->pl->sendSuggestions($course, $user);
                        }
                    }
                }
            }
        }
        $this->tpl->setOnScreenMessage('success', $this->pl->txt('learning_suggestions_generated'), true);
        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @param array $user_ids
     * @return array
     */
    private function removeInactiveUsers(array $user_ids): array
    {
        foreach ($user_ids as $key => $user_id) {
            if (!ilObjUser::_lookupActive($user_id)) {
                unset($user_ids[$key]);
            }
        }
        return array_values($user_ids);
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function saveNotifications(): void
    {
        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $config = new CourseConfigProvider($course);

        $course_main_config_form = new NotificationConfigFormGUI(
            $config, new TwigParser()
        );
        $course_main_config_form->save($config);

        $this->ctrl->redirect($this, self::CMD_CONFIGURE_NOTIFICATIONS);
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function saveMainConfig(): void
    {
        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $config = new CourseConfigProvider($course);

        $learning_objective_query = new LearningObjectiveQuery($config);
        $objectives = $this->getObjectives($learning_objective_query);
        $udef_definition = $this->getUdfDefinition();

        $course_main_config_form = new CourseConfigKSFormGUI(
            $this->pl,
            $config,
            $objectives,
            new StudyProgramQuery($config),
            $udef_definition
        );
        $form_with_errors = $course_main_config_form->save();

        if ($form_with_errors === null) {
            $this->ctrl->redirect($this, self::CMD_CONFIGURE_COURSE);
        }

        // validation failed: render the submitted form again so the field errors are visible
        global $DIC;
        $this->addActions(self::TAB_CONFIGURE_COURSE);
        $this->initCourseHeader($course);
        $this->tpl->setContent($DIC->ui()->renderer()->render($form_with_errors));
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function saveRoles(): void
    {
        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $this->addActions(self::TAB_CONFIGURE_COURSE);
        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $this->tabs->setBackTarget(
            $this->pl->txt('back'),
            $this->ctrl->getLinkTarget($this, self::CMD_CONFIGURE)
        );
        $this->initCourseHeader($course);
        $config = new CourseConfigProvider($course);

        $form = new CourseConfigFormGUI(
            $config,
            new LearningObjectiveQuery($config)
        );
        if ($form->checkInput()) {
            $this->storeConfig($config, $form);
            $this->tpl->setOnScreenMessage('success', $this->pl->txt('configuration_saved'), true);
            $this->ctrl->redirect($this, self::CMD_CONFIGURE_COURSE);
        }
        $form->setValuesByPost();

        $this->tpl->setContent($form->getHTML());
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function activateCalculation(): void
    {
        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $config = new CourseConfigProvider($course);
        $config->set('is_calculation_inactive', 0);
        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function deactivateCalculation(): void
    {
        if(!ilObject::_exists((int) $_GET['course_ref_id'], true, 'crs')) {
            $this->ctrl->returnToParent($this);
        }

        $course = new LearningObjectiveCourse(new ilObjCourse((int) $_GET['course_ref_id']));
        $config = new CourseConfigProvider($course);
        $config->set('is_calculation_inactive', 1);
        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @param string $active
     * @return void
     * @throws ilCtrlException
     */
    protected function addActions(string $active = ''): void
    {
        $this->tabs->addTab(
            self::TAB_CONFIGURE_COURSE,
            $this->pl->txt('basic_configuration'),
            $this->ctrl->getLinkTarget($this, self::CMD_CONFIGURE_COURSE)
        );
        $this->tabs->addTab(
            self::TAB_CONFIGURE_NOTIFICATIONS,
            $this->pl->txt('notifications'),
            $this->ctrl->getLinkTarget($this, self::CMD_CONFIGURE_NOTIFICATIONS)
        );

        if ($active) {
            $this->tabs->activateTab($active);
        }
    }

    /**
     * @return array
     */
    private function getUdfDefinition(): array
    {
        global $DIC;

        $sql = "SELECT * FROM udf_definition";

        $results = $DIC->database()->query($sql);

        $data = [];
        while ($row = $DIC->database()->fetchAssoc($results)) {
            $data[$row['field_id']] = $row['field_name'];
        }

        return $data;
    }

    /**
     * @param $learning_objective_query
     * @return array|null
     */
    protected function getObjectives($learning_objective_query): ?array
    {
        static $objectives = null;
        if ($objectives !== null) {
            return $objectives;
        }
        $objectives = $learning_objective_query->getAll();

        return $objectives;
    }
}
