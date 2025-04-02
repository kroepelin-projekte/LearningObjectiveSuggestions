<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective;

class LearningObjective
{
    protected \ilCourseObjective $objective;
    public function __construct(\ilCourseObjective $objective)
    {
        $this->objective = $objective;
    }
    public function getId(): int
    {
        return $this->objective->getObjectiveId();
    }
    public function getCourse(): \ilObject
    {
        return $this->objective->getCourse();
    }
    public function getTitle(): string
    {
        return $this->objective->getTitle();
    }
    public function isActive(): bool
    {
        return $this->objective->isActive();
    }
    public function getDescription(): string
    {
        return $this->objective->getDescription();
    }
    public function getRefIdsOfAssignedObjects(): array
    {
        return \ilCourseObjectiveMaterials::_getAssignedMaterials($this->getId());
    }
}
