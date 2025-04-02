<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjective;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class Placeholders
{
    public function getAvailablePlaceholders(): array
    {
        return array(
            'user.getLogin' => 'login',
            'user.getFirstname' => 'firstname',
            'user.getLastname' => 'lastname',
            'user.getEmail' => 'email',
            'course.getTitle' => 'course_title',
            'course.getLink' => 'course_link',
            'objectives' => 'objectives'
        );
    }
    public function getPlaceholders(LearningObjectiveCourse $course, User $user, array $objectives): array
    {
        return array(
            'user' => $user,
            'course' => $course,
            'objectives' => $this->renderObjectives($objectives),
        );
    }
    /**
     * @param LearningObjective[] $objectives
     */
    protected function renderObjectives(array $objectives): string
    {
        $titles = array_map(function ($objective) {
            /** @var LearningObjective $objective */
            return ($objective) ? $objective->getTitle() : '';
        }, $objectives);

        return implode("\n", $titles);
    }
}
