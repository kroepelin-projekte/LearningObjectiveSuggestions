<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class LearningObjectiveSuggestion extends \ActiveRecord
{
    public const TABLE_NAME = "alo_suggestion";
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
    protected int $is_cron_active = 1;
    public function create(): void
    {
        global $DIC;
        $ilUser = $DIC->user();
        $this->created_at = date('Y-m-d H:i:s');
        $this->created_user_id = $ilUser->getId();
        parent::create();
    }
    public function update(): void
    {
        global $DIC;
        $ilUser = $DIC->user();
        $this->updated_at = date('Y-m-d H:i:s');
        $this->updated_user_id = $ilUser->getId();

        $course = new LearningObjectiveCourse(new \ilObjCourse($this->getCourseObjId(), false));
        $user = new User($ilUser);

        $learning_objective_suggestions = new LearningObjectiveSuggestions($course, $user);
        if ($learning_objective_suggestions->isCronInactive()) {
            $this->setIsCronActive(0);
        }

        parent::update();
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function getSort(): int
    {
        return $this->sort;
    }
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }
    public function getCreatedAt(): string
    {
        return $this->created_at;
    }
    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }
    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }
    public function setUpdatedAt(string $updated_at): void
    {
        $this->updated_at = $updated_at;
    }
    public function getCreatedUserId(): int
    {
        return $this->created_user_id;
    }
    public function getUpdatedUserId(): int
    {
        return $this->updated_user_id;
    }
    public function getUserId(): int
    {
        return $this->user_id;
    }
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }
    public function getCourseObjId(): int
    {
        return $this->course_obj_id;
    }
    public function setCourseObjId(int $course_obj_id): void
    {
        $this->course_obj_id = $course_obj_id;
    }
    public function getObjectiveId(): int
    {
        return $this->objective_id;
    }
    public function setObjectiveId(int $objective_id): void
    {
        $this->objective_id = $objective_id;
    }
    public function getIsCronActive(): int
    {
        return $this->is_cron_active;
    }
    public function setIsCronActive(int $is_cron_active): void
    {
        $this->is_cron_active = $is_cron_active;
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
