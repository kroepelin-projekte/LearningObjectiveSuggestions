<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log;

class Log extends \ilLog
{
    public function __construct()
    {
        parent::__construct(ILIAS_DATA_DIR, 'learning-objective-suggestions.log');
    }

}
