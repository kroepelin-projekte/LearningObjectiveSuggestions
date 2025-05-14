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
        switch ($a_component) {
            case "Services/AccessControl":
                if ($a_event == 'assignUser' && $a_parameter['type'] == 'crs') {
                    $user = new User(new ilObjUser($a_parameter['usr_id']));
                    if ($this->startCalculation($user)) {
                        $this->sendSuggestions($user);
                    }
                }
                break;
            case 'Modules/Course':
                switch ($a_event) {
                    case 'participantHasPassedCourse':
                        $user = new User(new ilObjUser($a_parameter['usr_id']));
                        if ($this->startCalculation($user)) {
                            $this->sendSuggestions($user);
                        }
                        break;
                }
                break;
            case 'Services/Tracking':
                switch ($a_event) {
                    case 'updateStatus':
                        $user = new User(new ilObjUser($a_parameter['usr_id']));
                        // check status, old_status, obj_id und usr_id aus $a_parameter
                        if ($this->startCalculation($user)) {
                            $this->sendSuggestions($user);
                        }
                        break;
                }
                break;
        }
    }

    protected function startCalculation(User $user): bool
    {
        $calculation = new CalculateScoresAndSuggestions(
            $this->db,
            new ConfigProvider(),
            new Log()
        );
        return $calculation->run($user);
    }

    protected function sendSuggestions(User $user): void
    {
        $send_suggestions = new SendSuggestions(
            $this->db,
            new ConfigProvider(),
            new TwigParser(),
            new Log()
        );
        $send_suggestions->run($user);
    }
}
