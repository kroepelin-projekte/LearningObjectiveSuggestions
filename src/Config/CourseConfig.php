<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Notification;

class CourseConfig extends \ActiveRecord
{
    public const TABLE_NAME = "alo_crs_config";
    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }
    /**
     * @deprecated
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }
    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_primary   true
     * @db_sequence     true
     */
    protected ?int $id;
    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected int $course_obj_id;
    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       64
     */
    protected string $cfg_key;
    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    clob
     */
    protected string $value;

    public function delete(): void
    {
        foreach (LearningObjectiveSuggestion::where(['course_obj_id' => $this->getCourseObjId()])->get() as $learning_objective_suggestions) {
            /**
             * @var LearningObjectiveSuggestion $learning_objective_suggestions
             */
            $learning_objective_suggestions->delete();
        }

        foreach (LearningObjectiveScore::where(['course_obj_id' => $this->getCourseObjId()])->get() as $learning_objective_score) {
            /**
             * @var LearningObjectiveScore $learning_objective_score
             */
            $learning_objective_score->delete();
        }

        foreach (Notification::where(['course_obj_id' => $this->getCourseObjId()])->get() as $notification) {
            /**
             * @var Notification $notification
             */
            $notification->delete();
        }

        parent::delete();
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function getCourseObjId(): int
    {
        return $this->course_obj_id;
    }
    public function setCourseObjId(int $course_obj_id): void
    {
        $this->course_obj_id = $course_obj_id;
    }
    public function getKey(): string
    {
        return $this->cfg_key;
    }
    public function setKey(string $key): void
    {
        $this->cfg_key = $key;
    }
    public function getValue(): string
    {
        return $this->value;
    }
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
