<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion;

class Stack
{
    protected $elements = array();
    public function push(mixed $element): void
    {
        $this->elements[] = $element;
    }
    public function pop(): mixed
    {
        return array_pop($this->elements);
    }
    public function peek(): mixed
    {
        return ($this->isEmpty()) ? null : $this->elements[$this->count() - 1];
    }
    public function count(): int
    {
        return count($this->elements);
    }
    public function isEmpty(): bool
    {
        return ($this->count() == 0);
    }
}
