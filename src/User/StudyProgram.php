<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User;

class StudyProgram
{
    protected int $id;
    protected string $title;
    public function __construct(int $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
}
