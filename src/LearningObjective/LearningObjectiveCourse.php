<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;

class LearningObjectiveCourse
{
    protected \ilObjCourse $course;

    /**
     * LearningObjectiveCourse constructor.
     */
    public function __construct(\ilObjCourse $course)
    {
        $this->course = $course;
    }

    /**
     * @return \ilObjCourse
     */
    public function getILIASCourse(): \ilObjCourse
    {
        return $this->course;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->course->getId();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->course->getTitle();
    }

    /**
     * @return int
     */
    public function getRefId(): int
    {
        return $this->course->getRefId();
    }

    /**
     * @return bool
     */
    public function getIsCalculationInactive(): bool
    {
        $config = new CourseConfigProvider($this);
        return $config->getIsCalculationInactive();
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return \ilLink::_getStaticLink($this->getRefId(), 'crs');
    }

    /**
     * Get the user-IDs of all members of this course
     */
    public function getMemberIds(): array
    {
        $participants = \ilCourseParticipants::getInstanceByObjId($this->getId());
        return $participants->getMembers();
    }

    public function __toString()
    {
        return '[' . implode(', ', array(
                $this->getRefId(),
                $this->getTitle()
            )) . ']';
    }
}
