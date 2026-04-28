<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Calculation;

use ilDBInterface;
use ilObjUser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjective;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveResult;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScoreCalculator;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestionGenerator;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfig;

class CalculateScoresAndSuggestions
{
    protected \ilDBInterface $db;
    protected ConfigProvider $config;
    protected Log $log;
    protected \ilLearningObjectiveSuggestionsPlugin $pl;

    /**
     * @param ilDBInterface          $db
     * @param ConfigProvider $config
     * @param Log            $log
     */
    public function __construct(ilDBInterface $db, ConfigProvider $config, Log $log)
    {
        $this->db = $db;
        $this->config = $config;
        $this->log = $log;
        $this->pl = \ilLearningObjectiveSuggestionsPlugin::getInstance();
    }
    public function run(LearningObjectiveCourse $course, User $user): bool
    {
        $hasAtLeastOneUpdate = false;

        if ($course->getIsCalculationInactive()) {
            return $hasAtLeastOneUpdate;
        }
        if ($this->runForUser($course, $user)) {
            $hasAtLeastOneUpdate = true;
        }

        return $hasAtLeastOneUpdate;
    }

    protected function runForUser(LearningObjectiveCourse $course, User $user): bool
    {
        $config = new CourseConfigProvider($course);
        $study_program_query = new StudyProgramQuery($config);
        $learning_objective_query = new LearningObjectiveQuery($config);

        if ($this->isCalculationInactiveForUserSuggestions($course, $user)) {
            return false;
        }

        $set = $this->db->query($this->getSQL($course, $user));
        $objective_results = [];

        while ($row = $this->db->fetchObject($set)) {
            $objective = $this->getLearningObjective($course, $row->objective_id);

            if ($study_program_query->getByUser($user) != null) {
                $objective_results[] = new LearningObjectiveResult($objective, $user);
            }
        }

        if (empty($objective_results)) {
            return false;
        }

        $atLeastOneActionDone = false;

        foreach ($objective_results as $objective_result) {
            $score = $this->getLearningObjectiveScore($objective_result);

            if ($score->getCreatedAt() !== null) {
                continue;
            }

            $calculator = new LearningObjectiveScoreCalculator($config, $study_program_query, $this->log);

            try {
                $calculatedScore = $calculator->calculate($objective_result);
                if ($calculatedScore == -1) {
                    continue;
                }

                $score->setScore($calculatedScore);
                $score->save();
            } catch (\Exception $e) {
                $this->log->write("Exception when trying to calculate the score");
                $this->log->write($e->getMessage());
                $this->log->write($e->getTraceAsString());
            }
        }

        $generator = new LearningObjectiveSuggestionGenerator($config, $learning_objective_query, $this->log);
        $currentScores = $this->getScores($course, $user);

        $suggestedScores = $generator->generate($currentScores);

        if (!empty($suggestedScores)) {
            $this->createSuggestions($suggestedScores);
            $atLeastOneActionDone = true;
        }
        return $atLeastOneActionDone;
    }
    /**
     * @return LearningObjectiveScore[]
     */
    protected function getScores(LearningObjectiveCourse $course, User $user): array
    {
        return LearningObjectiveScore::where(array(
            'user_id' => $user->getId(),
            'course_obj_id' => $course->getId()
        ))->get();
    }

    /**
     * Checks if there already exist computed suggestions for the given course/user pair
     *
     * @param LearningObjectiveCourse $course
     * @param User                    $user
     * @return bool
     */
    protected function existSuggestions(LearningObjectiveCourse $course, User $user): bool
    {
        return LearningObjectiveSuggestion::where(array(
            'user_id' => $user->getId(),
            'course_obj_id' => $course->getId(),
        ))->hasSets();
    }

    /**
     * Checks if calculation is setz to inactve for the given course/user pair
     */
    protected function isCalculationInactiveForUserSuggestions(LearningObjectiveCourse $course, User $user): bool
    {
        return LearningObjectiveSuggestion::where(array(
            'user_id' => $user->getId(),
            'course_obj_id' => $course->getId(),
            'is_calculation_active' => 0
        ))->hasSets();
    }

    /**
     * @param LearningObjectiveScore[] $scores
     */
    protected function createSuggestions(array $scores): void
    {
        $arr_saved = [];
        foreach ($scores as $sort => $score) {
            $key = $score->getCourseObjId() . "." . $score->getObjectiveId() . "." . $score->getUserId();
            if (array_key_exists($key, $arr_saved)) {
                continue;
            }
            $arr_saved[$key] = 1;

            try {
                $suggestions = LearningObjectiveSuggestion::where(['course_obj_id' => $score->getCourseObjId(), 'objective_id' => $score->getObjectiveId(), 'user_id' => $score->getUserId()])->get();
                if (count($suggestions) > 0) {
                    $suggestion = array_values($suggestions)[0];
                } else {
                    $suggestion = new LearningObjectiveSuggestion();
                }

                $suggestion->setCourseObjId($score->getCourseObjId());
                $suggestion->setObjectiveId($score->getObjectiveId());
                $suggestion->setUserId($score->getUserId());
                $suggestion->setSort(++$sort);
                $suggestion->save();
            } catch (\Exception $e) {
                $this->log->write("Exception when trying to save a suggestions {$suggestion}");
                $this->log->write($e->getMessage());
                $this->log->write($e->getTraceAsString());
            }

        }
    }
    protected function getLearningObjectiveScore(LearningObjectiveResult $objective_result): LearningObjectiveScore
    {

        $scores = LearningObjectiveScore::where(array(
            'course_obj_id' => $objective_result->getLearningObjective()->getCourse()->getId(),
            'objective_id' => $objective_result->getLearningObjective()->getId(),
            'user_id' => $objective_result->getUser()->getId()
        ))->get();
        if (count($scores) === 0) {
            $score = new LearningObjectiveScore();
            $score->setCourseObjId($objective_result->getLearningObjective()->getCourse()->getId());
            $score->setObjectiveId($objective_result->getLearningObjective()->getId());
            $score->setUserId($objective_result->getUser()->getId());
            return $score;
        }

        return array_values($scores)[0];
    }
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

    public function getLearningObjective(LearningObjectiveCourse $course, int $objective_id): LearningObjective
    {
        static $cache = array();
        $cache_key = $course->getId() . $objective_id;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        $objective = new LearningObjective(new \ilCourseObjective($course->getILIASCourse(), $objective_id));
        $cache[$cache_key] = $objective;

        return $objective;
    }

    public function getSQL(LearningObjectiveCourse $course, User $user): string
    {
        $sql = 'SELECT DISTINCT loc_user_results.* FROM loc_user_results
				LEFT JOIN ' . LearningObjectiveScore::TABLE_NAME . ' ON 
					(
						' . LearningObjectiveScore::TABLE_NAME . '.user_id = loc_user_results.user_id 
						AND ' . LearningObjectiveScore::TABLE_NAME . '.course_obj_id = loc_user_results.course_id 
						AND ' . LearningObjectiveScore::TABLE_NAME . '.objective_id = loc_user_results.objective_id 
					)
				INNER JOIN loc_tst_run ON
					(
						loc_tst_run.container_id = loc_user_results.course_id
						AND loc_tst_run.user_id = loc_user_results.user_id
						AND loc_tst_run.objective_id = loc_user_results.objective_id
					)
				INNER JOIN tst_tests ON tst_tests.obj_fi = loc_tst_run.test_id
				INNER JOIN tst_active ON
					(
						tst_active.test_fi = tst_tests.test_id
						AND tst_active.user_fi = loc_user_results.user_id
					)
				WHERE loc_user_results.course_id = ' . $this->db->quote($course->getId(), 'integer') . ' 
					AND loc_user_results.type = 1
					AND tst_active.submitted > 0
                    AND loc_user_results.user_id = ' . $this->db->quote($user->getId(), 'integer') . '
                    ORDER BY loc_user_results.user_id, loc_user_results.course_id';

        //AND ' . LearningObjectiveScore::TABLE_NAME . '.id IS NULL ';

        return $sql;
    }
}
