<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;

interface Parser
{
    /**
     * Parse the template and fill placeholders
     *
     * @param string $template
     * @param array $placeholders
     * @return string
     */
    public function parse(string $template, array $placeholders): string;
    /**
     * Check if the template can be parsed
     *
     * @param string $template
     * @param array $placeholders
     * @return bool
     */
    public function isValid(string $template, array $placeholders): bool;
}
