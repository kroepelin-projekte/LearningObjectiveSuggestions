<?php

use ILIAS\DI\Container;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Calculation\CalculateScoresAndSuggestions;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\Config;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfig;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Notification;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\TwigParser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Calculation\SendSuggestions;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;

/**
 * Class ilLearningObjectiveSuggestionsPlugin
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilLearningObjectiveSuggestionsPlugin extends ilEventHookPlugin
{
    public const PLUGIN_ID = "dhbwautolo";
    public const PLUGIN_NAME = "LearningObjectiveSuggestions";
    protected static ?ilLearningObjectiveSuggestionsPlugin $instance = null;
    protected ilDBInterface $db;

    public static function getInstance(): ilLearningObjectiveSuggestionsPlugin
    {
        if (static::$instance === null) {
            global $DIC;

            /** @var $component_factory ilComponentFactory */
            $component_factory = $DIC['component.factory'];
            /** @var $plugin ilLearningObjectiveSuggestionsPlugin */
            $plugin = $component_factory->getPlugin(ilLearningObjectiveSuggestionsPlugin::PLUGIN_ID);

            static::$instance = $plugin;
        }

        return static::$instance;
    }

    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        parent::__construct($db, $component_repository, $id);
        $this->db = $db;
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    protected function beforeUninstall(): bool
    {
        $this->db->dropTable(LearningObjectiveScore::TABLE_NAME, false);
        $this->db->dropTable(LearningObjectiveSuggestion::TABLE_NAME, false);
        $this->db->dropTable(CourseConfig::TABLE_NAME, false);
        $this->db->dropTable(Config::TABLE_NAME, false);
        $this->db->dropTable(Notification::TABLE_NAME, false);

        if (file_exists(ILIAS_DATA_DIR . "/learning-objective-modifications.log")) {
            unlink(ILIAS_DATA_DIR . "/learning-objective-modifications.log");
        }
        if (file_exists(ILIAS_DATA_DIR . "/learning-objective-suggestions.log")) {
            unlink(ILIAS_DATA_DIR . "/learning-objective-suggestions.log");
        }
        return true;
    }

    public function handleEvent(string $a_component, string $a_event, array $a_parameter): void
    {
        if ($a_component == "Services/Tracking"
            && $a_event == 'updateStatus'
            && $a_parameter['old_status'] == \ilLPStatus::LP_STATUS_IN_PROGRESS_NUM
            && $a_parameter['status'] > \ilLPStatus::LP_STATUS_IN_PROGRESS_NUM) {
            global $DIC;
            $already_calculated = false;
            $config = new ConfigProvider();
            $ref_ids = $config->getCourseRefIds();
            $crs_ref_id = ilObject::_getAllReferences($a_parameter['obj_id']);
            $parent = $DIC->repositoryTree()->getParentId(current($crs_ref_id));
            if (ilObject::_lookupType($parent, true) === 'crs') {
                $course = new LearningObjectiveCourse(new ilObjCourse($parent, true));
                $crsconfig = new CourseConfigProvider($course);
                $assign_role_config = json_decode($crsconfig->getRoleAssignmentConfig(), true);
                $assigned_roles = $DIC->rbac()->review()->assignedRoles($a_parameter['usr_id']);
                if (is_array($assign_role_config)) {
                    foreach ($assign_role_config as $config) {
                        if (in_array($config['role'], $assigned_roles)) {
                            $already_calculated = true;
                            break;
                        }
                    }
                }
                if (in_array($parent, $ref_ids) && !$course->getIsCronInactive() && !$already_calculated) {
                    $user = new User(new ilObjUser($a_parameter['usr_id']));
                    if ($this->startCalculation($course, $user)) {
                        $this->sendSuggestions($course, $user);
                    }
                }
            }
        }
    }

    protected function startCalculation(LearningObjectiveCourse $course, User $user): bool
    {
        $calculation = new CalculateScoresAndSuggestions(
            $this->db,
            new ConfigProvider(),
            new Log()
        );
        return $calculation->run($course, $user);
    }

    protected function sendSuggestions(LearningObjectiveCourse $course, User $user): void
    {
        $send_suggestions = new SendSuggestions(
            $this->db,
            new ConfigProvider(),
            new TwigParser(),
            new Log()
        );
        $send_suggestions->run($course, $user);
    }
}
