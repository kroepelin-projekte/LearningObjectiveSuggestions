<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective;

class LearningObjective
{
    protected \ilCourseObjective $objective;

    /**
     * @param \ilCourseObjective $objective
     */
    public function __construct(\ilCourseObjective $objective)
    {
        $this->objective = $objective;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->objective->getObjectiveId();
    }

    /**
     * @return \ilObject
     */
    public function getCourse(): \ilObject
    {
        return $this->objective->getCourse();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->objective->getTitle();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->objective->isActive();
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->objective->getDescription();
    }

    /**
     * @return array
     */
    public function getRefIdsOfAssignedObjects(): array
    {
        return \ilCourseObjectiveMaterials::_getAssignedMaterials($this->getId());
    }
}
