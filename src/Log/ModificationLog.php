<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log;

class ModificationLog extends \ilLog
{
    public function __construct()
    {
        parent::__construct(ILIAS_DATA_DIR, 'learning-objective-modifications.log');
    }

}
