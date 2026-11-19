<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class LearningObjectiveSuggestion extends \ActiveRecord
{
    public const string TABLE_NAME = 'alo_suggestion';

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
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected int $sort;

    /**
     * @var string
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
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     * @db_length       1
     */
    protected int $is_calculation_active = 1;

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

        $course = new LearningObjectiveCourse(new \ilObjCourse($this->getCourseObjId(), false));
        $user = new User($ilUser);

        $learning_objective_suggestions = new LearningObjectiveSuggestions($course, $user);
        if ($learning_objective_suggestions->isCalculationInactive()) {
            $this->setIsCalculationActive(0);
        }

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
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     * @return void
     */
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
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
    public function setUpdatedAt(string $updated_at): void
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
     * @return int
     */
    public function getIsCalculationActive(): int
    {
        return $this->is_calculation_active;
    }

    /**
     * @param int $is_calculation_active
     * @return void
     */
    public function setIsCalculationActive(int $is_calculation_active): void
    {
        $this->is_calculation_active = $is_calculation_active;
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public function suggestionExists(
        int $userId,
        int $courseId
    ): bool {
        $suggestions = LearningObjectiveSuggestion::where(array(
                'user_id' => $userId,
                'course_obj_id' => $courseId
            )
        );

        if ($suggestions->count() > 0) {
            return true;
        }
        return false;
    }
}
