<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class LearningObjectiveSuggestions
{
    protected LearningObjectiveCourse $course;
    protected User $user;


    /**
     * LearningObjectiveSuggestions constructor.
     *
     * @param LearningObjectiveCourse $course
     * @param User         $user
     */
    public function __construct(LearningObjectiveCourse $course, User $user)
    {
        $this->course = $course;
        $this->user = $user;
    }
    /**
     * @return LearningObjectiveSuggestion[]
     */
    public function getSuggestions(): array
    {
        return LearningObjectiveSuggestion::where(array(
            'user_id' => $this->user->getId(),
            'course_obj_id' => $this->course->getId()))->get();
    }
    public function isCalculationInactive(): bool
    {
        return LearningObjectiveSuggestion::where(array(
            'user_id' => $this->user->getId(),
            'course_obj_id' => $this->course->getId(),
            'is_calculation_active' => 0))->hasSets();
    }
    public function setCalculationActive(): void
    {
        foreach ($this->getSuggestions() as $suggestion) {
            $suggestion->setIsCalculationActive(1);
            $suggestion->store();
        }
    }
    public function setCalculationInactive(): void
    {
        foreach ($this->getSuggestions() as $suggestion) {
            $suggestion->setIsCalculationActive(0);
            $suggestion->store();
        }
    }
    /**
     * Checks if calculation is set to inactive for the given course/user pair
     */
    protected function isCalculationInactiveForUserSuggestions(): bool
    {
        return LearningObjectiveSuggestion::where(array(
            'user_id' => $this->user->getId(),
            'course_obj_id' => $this->course->getILIASCourse()->getId(),
            'is_calculation_active' => 0
        ))->hasSets();
    }
}
