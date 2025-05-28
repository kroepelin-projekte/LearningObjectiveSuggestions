<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjective;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgram;

class CourseConfigProvider
{
    protected LearningObjectiveCourse $course;
    public function __construct(LearningObjectiveCourse $course)
    {
        $this->course = $course;
    }
    public function getCourse(): LearningObjectiveCourse
    {
        return $this->course;
    }
    public function get(string $key): ?string
    {
        /** @var CourseConfig $config */
        $config = CourseConfig::where(array(
            'cfg_key' => $key,
            'course_obj_id' => $this->course->getId()
        ))->first();
        return ($config) ? $config->getValue() : null;
    }
    public function delete(): void
    {
        /** @var CourseConfig $config */
        foreach (CourseConfig::where(array(
            'course_obj_id' => $this->course->getId()
        ))->get() as $course_config) {
            $course_config->delete();
        }
    }
    public function set(string $key, string $value): void
    {
        global $ilLog;
        $ilLog->write($key . " " . $value);

        $config = CourseConfig::where(array(
            'cfg_key' => $key,
            'course_obj_id' => $this->course->getId(),
        ))->first();
        if ($config === null) {
            $config = new CourseConfig();
            $config->setKey($key);
            $config->setCourseObjId($this->course->getId());
        }
        $config->setValue($value);
        $config->save();
    }
    public function getMaxSuggestions(): int
    {
        return (int) $this->get('max_amount_suggestions');
    }
    public function getMinSuggestions(): int
    {
        return (int) $this->get('min_amount_suggestions');
    }
    public function getWeightRough(LearningObjective $learning_objective, StudyProgram $study_program): int|string
    {
        return $this->get('weight_rough_' . $learning_objective->getId() . '_' . $study_program->getId());
    }
    public function getWeightFine(LearningObjective $learning_objective): string
    {
        return $this->get('weight_fine_' . $learning_objective->getId());
    }
    public function getBias(): int
    {
        return (int) $this->get('bias');
    }
    public function getOffset(): int
    {
        return (int) $this->get('offset');
    }
    public function getSteps(): int
    {
        return (int) $this->get('steps');
    }
    public function getEmailSubjectTemplate(): string
    {
        return (string) $this->get('email_subject');
    }
    public function getEmailBodyTemplate(): string
    {
        return (string) $this->get('email_body');
    }
    public function getIsCronInactive(): bool
    {
        return (bool) $this->get('is_cron_inactive');
    }
    public function getRoleAssignmentConfig(): string
    {
        return $this->get('role_assignment_config') ?? '';
    }
}
