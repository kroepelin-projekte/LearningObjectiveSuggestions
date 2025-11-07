<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjective;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveResult;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class LearningObjectiveSuggestionGenerator
{
    protected CourseConfigProvider $config;
    protected LearningObjectiveQuery $learning_objective_query;
    protected Log $log;

    /**
     * @param CourseConfigProvider $config
     * @param LearningObjectiveQuery $learning_objective_query
     * @param Log $log
     */
    public function __construct(
        CourseConfigProvider $config,
        LearningObjectiveQuery $learning_objective_query,
        Log $log
    ) {
        $this->config = $config;
        $this->learning_objective_query = $learning_objective_query;
        $this->log = $log;
    }

    /**
     * Determines the learning objectives being suggested among the given objectives
     *
     * @param LearningObjectiveScore[] $scores
     * @return LearningObjectiveScore[]
     */
    public function generate(array $scores): array
    {
        $scores = $this->sortDescByScore($scores);
        $min = $this->config->getMinSuggestions();
        $max = $this->config->getMaxSuggestions();
        $bias = $this->config->getBias();
        $offset = $this->config->getOffset();
        $steps = $this->config->getSteps();

        // Basic check: If for some reason we do not have enough scores to satisfy the min condition, return early
        if (count($scores) <= $min) {
            return $scores;
        }

        // For each learning objective we calculate a target score which determines if a learning objective is suggested
        $target_scores = array();
        foreach ($scores as $i => $score) {
            $pos = $i + 1;
            $target_scores[] = ($pos == 1) ? $bias : $bias + $offset + ($pos - 2) * $steps;
        }

        // Start algorithm
        // ------------------------------------------------------------------------------------------------------------

        $suggestions = array();
        foreach ($scores as $i => $score) {
            // A suggestion is made if the reached score is equal or above the target score
            if ($score->getScore() >= $target_scores[$i]) {
                $suggestions[] = $score;
            }
        }

        // Check for min condition
        if (count($suggestions) < $min) {
            $diff = $min - count($suggestions);
            // Pick the highest scores which is not currently being suggested and append them
            $candidates = array_values(array_filter($scores, function ($score) use ($suggestions) {
                return (!in_array($score, $suggestions));
            }));
            for ($i = 0; $i < $diff; $i++) {
                if (isset($candidates[$i])) {
                    $suggestions[] = $candidates[$i];
                }
            }
        }

        // Check for max condition
        if (count($suggestions) > $max) {
            $suggestions = array_values(array_slice($suggestions, 0, $max));
        }

        $objectiveIds = [];
        foreach ($suggestions as $suggestion) {
            $objectiveIds[] = $suggestion->getObjectiveId();
        }

        $candidates = array_values(array_filter($scores, function ($score) use ($objectiveIds) {
            return (in_array($score->getObjectiveId(), $objectiveIds));
        }));

        if (count($candidates)) {
            $sorted = $this->sortDescByScore($candidates);
            $suggestions[count($suggestions) - 1] = $sorted[0];
        }

        // TODO confirm the calculation logic
        // TODO remove DB entries for learning_objectives_main and learning_objectives_extended

        return $suggestions;



    }

    /**
     * @param LearningObjectiveScore[] $scores
     * @return LearningObjectiveScore[]
     */
    protected function sortDescByScore(array $scores): array
    {
        if (!count($scores)) {
            return array();
        }
        $learning_objective_query = $this->learning_objective_query;

        $config = $this->config;
        /** @var LearningObjectiveScore $score */
        $score = array_values($scores)[0];

        $user = $this->getUser($score->getUserId());
        usort($scores, function ($a, $b) use ($learning_objective_query, $user, $config) {
            /** @var $a LearningObjectiveScore */
            /** @var $b LearningObjectiveScore */
            if ($a->getScore() == $b->getScore()) {
                // Identical scores.... we use the fine weights to identify the order
                $objective_a = $learning_objective_query->getByObjectiveId($a->getObjectiveId());
                $objective_b = $learning_objective_query->getByObjectiveId($b->getObjectiveId());
                $objective_result_a = new LearningObjectiveResult($objective_a, $user);
                $objective_result_b = new LearningObjectiveResult($objective_b, $user);
                $weight_fine_a = $config->getWeightFine($objective_a);
                $weight_fine_b = $config->getWeightFine($objective_b);
                if ($objective_result_a->getPercentage() < 90 || $objective_result_b->getPercentage() < 90) {
                    // If the user reached less than 90 percent on both objectives, sort DESC depending on fine weights
                    return ($weight_fine_a > $weight_fine_b) ? -1 : 1;
                } elseif ($objective_result_a->getPercentage() >= 90 && $objective_result_b->getPercentage() >= 90) {
                    // If the user reached more or equal than 90 percent on both objectives, sort ASC depending on fine weights
                    return ($weight_fine_a > $weight_fine_b) ? 1 : -1;
                }
                return 0;
            }
            return ($a->getScore() > $b->getScore()) ? -1 : 1;
        });
        return array_values($scores);
    }

    /**
     * @param int $user_id
     * @return User
     */
    protected function getUser(int $user_id): User
    {
        static $cache = array();
        if (isset($cache[$user_id])) {
            return $cache[$user_id];
        }
        $user = new User(new \ilObjUser($user_id));
        $cache[$user_id] = $user;
        return $user;
    }
    protected function getMainObjectiveIds(): array
    {
        return array_map(function ($objective) {
            /** @var $objective LearningObjective */
            return $objective->getId();
        }, $this->learning_objective_query->getMain());
    }
    protected function getExtendedObjectiveIds(): array
    {
        return array_map(function ($objective) {
            /** @var $objective LearningObjective */
            return $objective->getId();
        }, $this->learning_objective_query->getExtended());
    }
}
