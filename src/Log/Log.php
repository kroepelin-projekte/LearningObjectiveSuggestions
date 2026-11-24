<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log;

class Log extends \ilLog
{
    public function __construct()
    {
        parent::__construct(ILIAS_DATA_DIR, 'learning-objective-suggestions.log');
    }

    /**
     * Without stack trace, whose call arguments may contain personal data.
     */
    public function writeThrowable(\Throwable $e): void
    {
        $this->write(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
}
