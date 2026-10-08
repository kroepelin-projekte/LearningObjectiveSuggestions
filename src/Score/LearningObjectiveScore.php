<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score;

class LearningObjectiveScore extends \ActiveRecord
{
    public const string TABLE_NAME = 'alo_score';

    /**
     * @return string
     */
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
     * @db_index        true
     */
    protected int $user_id;

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_index        true
     */
    protected int $course_obj_id;

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_index        true
     */
    protected int $objective_id;

    /**
     * @var float
     *
     * @db_has_field    true
     * @db_fieldtype    float
     * @db_length       8
     */
    protected float $score;

    /**
     * @var ?string
     *
     * @db_has_field    true
     * @db_fieldtype    timestamp
     */
    protected ?string $created_at = null;

    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    timestamp
     */
    protected ?string $updated_at = null;

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected ?int $created_user_id = null;

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected ?int $updated_user_id = null;

    /**
     * @return void
     */
    public function create(): void
    {
        global $DIC;

        $ilUser = $DIC->user();
        $this->created_at = date('Y-m-d H:i:s');
        $this->created_user_id = $ilUser->getId();
        parent::create();
    }

    /**
     * @return void
     */
    public function update(): void
    {
        global $DIC;

        $ilUser = $DIC->user();
        $this->updated_at = date('Y-m-d H:i:s');
        $this->updated_user_id = $ilUser->getId();
        parent::update();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return int
     */
    public function getCourseObjId(): int
    {
        return $this->course_obj_id;
    }

    /**
     * @param int $course_obj_id
     * @return void
     */
    public function setCourseObjId(int $course_obj_id): void
    {
        $this->course_obj_id = $course_obj_id;
    }

    /**
     * @return int
     */
    public function getObjectiveId(): int
    {
        return $this->objective_id;
    }

    /**
     * @param int $objective_id
     * @return void
     */
    public function setObjectiveId(int $objective_id): void
    {
        $this->objective_id = $objective_id;
    }

    /**
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * @param float $score
     * @return void
     */
    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    /**
     * @param string $created_at
     * @return void
     */
    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }

    /**
     * @param string $updated_at
     * @return void
     */
    public function setUpdatedAt(string $updated_at)
    {
        $this->updated_at = $updated_at;
    }

    /**
     * @return int
     */
    public function getCreatedUserId(): int
    {
        return $this->created_user_id;
    }

    /**
     * @return int
     */
    public function getUpdatedUserId(): int
    {
        return $this->updated_user_id;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return implode(', ', array(
            "userId => {$this->user_id}",
            "courseObjId => {$this->course_obj_id}",
            "objectiveId => {$this->objective_id}",
            "score => {$this->score}",
        ));
    }
}
