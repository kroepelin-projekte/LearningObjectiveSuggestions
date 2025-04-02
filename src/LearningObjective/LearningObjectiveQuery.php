<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;

class LearningObjectiveQuery
{
    protected CourseConfigProvider $config;
    public function __construct(CourseConfigProvider $config)
    {
        $this->config = $config;
    }
    /**
     * Get all learning objectives
     */
    public function getAll(): array
    {
        static $cache = array();
        $ref_id = $this->config->getCourse()->getRefId();
        if (isset($cache[$ref_id])) {
            return $cache[$ref_id];
        }
        $objectives = array();
        $course = $this->config->getCourse()->getILIASCourse();
        $ids = \ilCourseObjective::_getObjectiveIds($course->getId());
        foreach ($ids as $id) {
            $objectives[] = new LearningObjective(new \ilCourseObjective($course, $id));
        }

        $cache[$ref_id] = $objectives;

        return $objectives;
    }
    public function getByObjectiveId(int $objective_id): LearningObjective
    {
        $filtered = array_filter($this->getAll(), function ($objective) use ($objective_id) {
            /** @var $objective LearningObjective */
            return ($objective->getId() == $objective_id);
        });

        return array_pop($filtered);
    }
    /**
     * Get the learning objectives belonging to the main section
     *
     * @return LearningObjective[]
     */
    public function getMain(): array
    {
        $main = json_decode($this->config->get('learning_objectives_main'), true);

        return array_filter($this->getAll(), function ($objective) use ($main) {
            /** @var $objective LearningObjective */
            return (in_array($objective->getId(), $main));
        });
    }
    /**
     * Get the learning objectives belonging to the extended section
     *
     * @return LearningObjective[]
     */
    public function getExtended(): array
    {
        $extended = json_decode($this->config->get('learning_objectives_extended'), true);

        return array_filter($this->getAll(), function ($objective) use ($extended) {
            /** @var $objective LearningObjective */
            return (in_array($objective->getId(), $extended));
        });
    }
}
