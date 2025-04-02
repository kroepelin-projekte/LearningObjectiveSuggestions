<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjective;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\ModificationLog;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class LearningObjectiveSuggestionModification
{
    protected LearningObjectiveCourse $course;
    protected User $user;
    protected ModificationLog $log;
    protected User $editor;
    protected LearningObjectiveQuery $learning_objective_query;


    /**
     * @param LearningObjectiveCourse $course
     * @param User                    $user
     * @param User                    $editor
     * @param ModificationLog         $log
     */
    public function __construct(LearningObjectiveCourse $course, User $user, User $editor, ModificationLog $log)
    {
        $this->course = $course;
        $this->user = $user;
        $this->log = $log;
        $this->editor = $editor;
        $this->learning_objective_query = new LearningObjectiveQuery(new CourseConfigProvider($course));
    }


    /**
     * Replace current suggestions with the given learning objectives
     * Note: Suggestions will be created in the same order as given by $learning_objectives, independent from score
     *
     * @param LearningObjective[] $learning_objectives
     */
    public function replaceSuggestions(array $learning_objectives): void
    {
        $current_suggestions = $this->getSuggestions();
        $new_suggestions = array();
        $added_suggestions = array();
        foreach ($learning_objectives as $objective) {

            // Filter current suggestions to find a match
            $filtered_suggestions = array_filter($current_suggestions, function ($suggestion) use ($objective) {
                /** @var $suggestion LearningObjectiveSuggestion */
                return ($suggestion->getObjectiveId() == $objective->getId());
            });

            // Get the last element of the filtered suggestions
            $suggestion = !empty($filtered_suggestions) ? array_pop($filtered_suggestions) : null;


            // Check if the objective is already part of the current suggestions
            /*$suggestion = array_pop(array_filter($current_suggestions, function ($suggestion) use ($objective) {
                // @var $suggestion LearningObjectiveSuggestion
                return ($suggestion->getObjectiveId() == $objective->getId());
            }));*/

            if ($suggestion) {
                // This objective is part of the current suggestions, so we will update its sorting
                $new_suggestions[] = $suggestion;
                continue;
            }
            // Create a new suggestion for the given objective since it is not part of the current suggestions
            $suggestion = new LearningObjectiveSuggestion();
            $suggestion->setUserId($this->user->getId());
            $suggestion->setCourseObjId($this->course->getId());
            $suggestion->setObjectiveId($objective->getId());
            $new_suggestions[] = $suggestion;
            $added_suggestions[] = $suggestion;
        }
        // Delete all current suggestions no longer being part of the new ones
        $delete_suggestions = array_filter($current_suggestions, function ($suggestion) use ($new_suggestions) {
            return (!in_array($suggestion, $new_suggestions));
        });
        foreach ($delete_suggestions as $suggestion) {
            $suggestion->delete();
        }
        foreach ($new_suggestions as $sort => $suggestion) {
            $suggestion->setSort(++$sort);
            $suggestion->save();
        }
        $this->log->write("Manually change suggested learning objectives in course {$this->course} for {$this->user}");
        $this->log->write("Editor: {$this->editor}");
        $this->log->write("Learning objective suggestions before modification:\n"
            . implode("\n", $this->getLearningObjectives($current_suggestions)));
        $this->log->write("Newly added learning objective suggestions:\n" . implode("\n", $this->getLearningObjectives($added_suggestions)));
        $this->log->write("Deleted learning objective suggestions:\n" . implode("\n", $this->getLearningObjectives($delete_suggestions)));
        $this->log->write("Current learning objective suggestions:\n" . implode("\n", $this->getLearningObjectives($new_suggestions)));
    }
    protected function getLearningObjectives(array $suggestions): array
    {
        $objectives = array();
        foreach ($suggestions as $suggestion) {
            /** @var $suggestion LearningObjectiveSuggestion */
            $objective = $this->learning_objective_query->getByObjectiveId($suggestion->getObjectiveId());
            $objectives[] = $objective->getTitle();
        }

        return $objectives;
    }

    /**
     * @return LearningObjectiveSuggestion[]
     * @throws \arException
     */
    protected function getSuggestions(): array
    {
        return LearningObjectiveSuggestion::where(array(
            'user_id' => $this->user->getId(),
            'course_obj_id' => $this->course->getId()
        ))->orderBy('sort')->get();
    }
}
