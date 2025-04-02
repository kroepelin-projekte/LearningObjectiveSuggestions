<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Cron;

use ilCrsInitialTestState;
use ilCrsInitialTestStates;
use ilDBInterface;
use ilObjectTest;
use ilObjTest;
use ilTemplate;
use ilPluginAdmin;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjective;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Notification;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Parser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Placeholders;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Sender;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;
use ILIAS\Cron\Schedule\CronJobScheduleType;

class SendSuggestionsCronJob extends \ilCronJob
{
    public const CRON_JOB_ID = "alo_send_suggestions";
    protected \ilDBInterface $db;
    protected ConfigProvider $config;
    protected Log $log;
    protected Parser $parser;
    protected \ilLearningObjectiveSuggestionsPlugin $pl;
    protected static function initStyle(): void
    {
        global $DIC, $ilPluginAdmin;

        if (isset($GLOBALS['styleDefinition'])) {
            return;
        }

        // load style definitions
        self::initGlobal(
            "styleDefinition",
            "ilStyleDefinition",
            "./Services/Style/System/classes/class.ilStyleDefinition.php"
        );

        // add user interface hook for style initialisation
        $pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_SERVICE, "UIComponent", "uihk");
        foreach ($pl_names as $pl) {
            $ui_plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", $pl);
            $gui_class = $ui_plugin->getUIClassInstance();
            $gui_class->modifyGUI("Services/Init", "init_style", array("styleDefinition" => $DIC->systemStyle()));
        }
    }
    protected static function initGlobal(string $a_name, string $a_class, ?string $a_source_file = null): void
    {
        global $DIC;

        if ($a_source_file) {
            include_once $a_source_file;
            $GLOBALS[$a_name] = new $a_class();
        } else {
            $GLOBALS[$a_name] = $a_class;
        }

        $DIC[$a_name] = function ($c) use ($a_name) {
            return $GLOBALS[$a_name];
        };
    }
    public function __construct(ilDBInterface $db, ConfigProvider $config, Parser $parser, Log $log)
    {
        $this->db = $db;
        $this->config = $config;
        $this->parser = $parser;
        $this->log = $log;
        $this->pl = \ilLearningObjectiveSuggestionsPlugin::getInstance();
        static::initStyle();
    }
    public function getId(): string
    {
        return self::CRON_JOB_ID;
    }
    public function getTitle(): string
    {
        return $this->pl->txt("send_suggestions");
    }
    public function getDescription(): string
    {
        return $this->pl->txt("send_suggestions_description");
    }
    public function hasAutoActivation(): bool
    {
        return true;
    }
    public function hasFlexibleSchedule(): bool
    {
        return true;
    }
    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_IN_MINUTES;
    }
    public function getDefaultScheduleValue(): int
    {
        return 60;
    }
    public function run(): \ilCronJobResult
    {
        foreach ($this->config->getCourseRefIds() as $ref_id) {
            if (!\ilObject::_exists($ref_id, true)) {
                continue;
            }
            $course = new LearningObjectiveCourse(new \ilObjCourse($ref_id));
            $this->runForCourse($course);
        }
        $result = new \ilCronJobResult();
        $result->setStatus(\ilCronJobResult::STATUS_OK);
        return $result;
    }
    protected function runForCourse(LearningObjectiveCourse $course): void
    {
        $set = $this->db->query($this->getSQL($course));
        while ($row = $this->db->fetchObject($set)) {

            $this->assignToRole($course, $row->user_id);
            if ($row->sent_at === null) {
                $this->send($course, $this->getUser($row->user_id));
            }
        }
    }
    protected function assignToRole(LearningObjectiveCourse $course, int $user_id): void
    {
        global $DIC;
        try {
            $test_result = self::getTestUserResult($user_id, $course->getRefId());
            if ($test_result >= 0) {
                $config = new CourseConfigProvider($course);
                $assign_role_config = json_decode($config->getRoleAssignmentConfig(), true);
                if (is_array($assign_role_config)) {
                    foreach ($assign_role_config as $config) {
                        if ($config['min_points'] <= round($test_result) && round($test_result) <= $config['max_points']) {
                            $DIC->rbac()->admin()->assignUser($config['role'], $user_id);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->log->write("Error while trying to assign roles for learning objective suggestions: " . $e->getMessage());
            $this->log->write($e->getTraceAsString());
        }
    }
    public static function getCrsRefIdsWithInitialTestStates(int $user_id): array
    {
        $arr_initial_test_states = ilCrsInitialTestStates::getData([$user_id]);

        $arr_crs_ref_ids = [];
        if (count($arr_initial_test_states) > 0) {
            foreach ($arr_initial_test_states as $initial_test_state) {
                $arr_crs_ref_ids[] = $initial_test_state->getCrsitestCrsRefId();
            }
        }

        return $arr_crs_ref_ids;
    }
    public static function getTestUserResult(int $user_id, int $crs_ref_id): float
    {
        // Fix missing tpl ui in cron context used in test question object constructor
        global $DIC;
        if (!$DIC->offsetExists("tpl")) {
            $DIC["tpl"] = $GLOBALS["tpl"] = new ilTemplate("tpl.main_menu.html", true, true, "Services/MainMenu");
        }

        $arr_initial_test_states = ilCrsInitialTestStates::getData([$user_id], $crs_ref_id);
        if (count($arr_initial_test_states) > 0) {
            /**
             * @var ilCrsInitialTestState
             */
            $crs_inital_test_state = $arr_initial_test_states[$user_id];

            if (!is_object($crs_inital_test_state)) {
                return -1;
            }
            if ($crs_inital_test_state->getCrsitestCrsRefId() !== $crs_ref_id) {
                return -1;
            }

            $user_rec['usr_id'] = $user_id;
            $user_rec['firstname'] = '';
            $user_rec['lastname'] = '';
            $user_rec['matriculation'] = '';
            $user_rec['login'] = '';
            $user_rec['passed'] = true;

            $test_obj = new ilObjTest($crs_inital_test_state->getCrsitestItestObjId(), false);
            $all_participants = $test_obj->getTestParticipants();
            foreach ($all_participants as $part) {
                if ($part['usr_id'] == $user_id) {
                    $participants[$part['active_id']] = $user_rec;
                }
            }



            $data = $test_obj->getAllTestResults($participants, false);
            $perc = 0;
            foreach ($data as $row) {
                if ($row['user_id'] == $user_id) {
                    $perc = round((float) $row['percent_value'] * 100, 2);
                    $max = $row["max_points"];
                    $res = $row["reached_points"];
                }
            }
            if ($max == 0) {
                return (-1);
            }
            return $perc;
        }
        return (-1);
    }
    protected function send(LearningObjectiveCourse $course, User $user): void
    {
        $config = new CourseConfigProvider($course);
        $query = new LearningObjectiveQuery($config);
        $placeholders = new Placeholders();
        // Note: If we can't parse the mail templates, we fail silently but write to log
        try {
            $objectives = $this->getSuggestedLearningObjectives($course, $user, $query);
            $p = $placeholders->getPlaceholders($course, $user, $objectives);
            $subject = $this->parser->parse($config->getEmailSubjectTemplate(), $p);
            $body = $this->parser->parse($config->getEmailBodyTemplate(), $p);
            $sender = new Sender($course, $user, $this->log);
            $sender->subject($subject)->body($body);
            if (!$sender->send()) {
                $msg = "Failed to send learning objective suggestions for course %s and User %s";
                $this->log->write(sprintf($msg, $course->getTitle(), $user->__toString()));
            }
        } catch (\Exception $e) {
            $this->log->write("Error while trying to send learning objective suggestions: " . $e->getMessage());
            $this->log->write($e->getTraceAsString());
        }
    }
    protected function getUser(int $user_id): User
    {
        static $cache = array();
        if (isset($cache[$user_id])) {
            return $cache[$user_id];
        }
        $user = new User(new \ilObjUser($user_id));
        $cache[$user_id] = $user;

        return $user;
    }

    /**
     * @return LearningObjective[]
     * @throws \arException
     */
    protected function getSuggestedLearningObjectives(LearningObjectiveCourse $course, User $user, LearningObjectiveQuery $query): array
    {
        $suggestions = LearningObjectiveSuggestion::where(array(
            'user_id' => $user->getId(),
            'course_obj_id' => $course->getId(),
        ))->orderBy('sort')->get();
        $objectives = array();
        foreach ($suggestions as $suggestion) {
            /** @var $suggestion LearningObjectiveSuggestion */
            $objectives[] = $query->getByObjectiveId($suggestion->getObjectiveId());
        }

        return $objectives;
    }
    /**
     * @param LearningObjectiveCourse $course
     */
    protected function getSQL(LearningObjectiveCourse $course): string
    {
        $sql = 'SELECT 
				' . LearningObjectiveSuggestion::TABLE_NAME . '.user_id,
				' . Notification::TABLE_NAME . '.sent_at
				FROM ' . LearningObjectiveSuggestion::TABLE_NAME . '
				LEFT JOIN ' . Notification::TABLE_NAME . ' ON 
					(' . Notification::TABLE_NAME . '.course_obj_id = ' . LearningObjectiveSuggestion::TABLE_NAME . '.course_obj_id AND '
            . Notification::TABLE_NAME . '.user_id = ' . LearningObjectiveSuggestion::TABLE_NAME . '.user_id)
				WHERE 
					' . LearningObjectiveSuggestion::TABLE_NAME . '.course_obj_id = ' . $this->db->quote($course->getId(), 'integer') . ' 
					AND ' . LearningObjectiveSuggestion::TABLE_NAME . '.is_cron_active = 1 ';
        $member_ids = $course->getMemberIds();
        if (count($member_ids)) {
            $sql .= 'AND ' . LearningObjectiveSuggestion::TABLE_NAME . '.user_id IN (' . implode(',', $member_ids) . ') ';
        }
        $sql .= 'GROUP BY ' . LearningObjectiveSuggestion::TABLE_NAME . '.user_id';

        return $sql;
    }
}
