<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion;

class Stack
{
    protected $elements = [];

    /**
     * @param mixed $element
     * @return void
     */
    public function push(mixed $element): void
    {
        $this->elements[] = $element;
    }

    /**
     * @return mixed
     */
    public function pop(): mixed
    {
        return array_pop($this->elements);
    }

    /**
     * @return mixed
     */
    public function peek(): mixed
    {
        return ($this->isEmpty()) ? null : $this->elements[$this->count() - 1];
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->elements);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return ($this->count() == 0);
    }
}
