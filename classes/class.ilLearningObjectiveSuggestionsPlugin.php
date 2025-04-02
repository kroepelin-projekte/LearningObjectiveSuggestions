<?php

use ILIAS\DI\Container;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\Config;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfig;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Cron\CalculateScoresAndSuggestionsCronJob;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Cron\SendSuggestionsCronJob;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Notification;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\TwigParser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;

//use srag\CustomInputGUIs\LearningObjectiveSuggestions\Loader\CustomInputGUIsLoaderDetector;

/**
 * Class ilLearningObjectiveSuggestionsPlugin
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilLearningObjectiveSuggestionsPlugin extends ilCronHookPlugin
{
    public const PLUGIN_ID = "dhbwautolo";
    public const PLUGIN_NAME = "LearningObjectiveSuggestions";
    protected static ?ilLearningObjectiveSuggestionsPlugin $instance = null;
    protected static ?array $cron_instances = null;

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

    public static function getCronInstances(): array
    {
        global $DIC;
        $ilDB = $DIC->database();
        if (static::$cron_instances === null) {
            $config = new ConfigProvider();
            $log = new Log();
            $cron1 = new CalculateScoresAndSuggestionsCronJob($ilDB, $config, $log);
            $cron2 = new SendSuggestionsCronJob($ilDB, $config, new TwigParser(), $log);
            static::$cron_instances = array(
                $cron1->getId() => $cron1,
                $cron2->getId() => $cron2,
            );
        }

        return static::$cron_instances;
    }

    protected ilDBInterface $db;

    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;
        parent::__construct($db, $component_repository, $id);
        $this->db = $DIC->database();
    }


    /**
     * @return array
     */
    public function getCronJobInstances(): array
    {
        return self::getCronInstances();
    }

    public function getCronJobInstance(string $a_job_id): ilCronJob
    {
        foreach (static::getCronInstances() as $id => $cron) {
            if ($a_job_id == $id) {
                return $cron;
            }
        }
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    protected function init(): void
    {
        parent::init();
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


}
