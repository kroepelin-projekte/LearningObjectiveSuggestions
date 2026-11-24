<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Notification;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;

class UserDataCleanup
{
    private const array TABLES = [
        LearningObjectiveScore::TABLE_NAME,
        LearningObjectiveSuggestion::TABLE_NAME,
        Notification::TABLE_NAME,
    ];

    public function __construct(
        protected \ilDBInterface $db,
        protected \ilLogger $logger
    ) {
    }

    public function deleteUserData(int $usr_id): void
    {
        if ($usr_id <= 0) {
            return;
        }

        foreach (self::TABLES as $table) {
            try {
                $this->db->manipulateF(
                    'DELETE FROM ' . $table . ' WHERE user_id = %s',
                    [\ilDBConstants::T_INTEGER],
                    [$usr_id]
                );
            } catch (\Throwable $e) {
                $this->logger->error("Could not delete data of user {$usr_id} from {$table}: " . $e->getMessage());
            }
        }
    }
}
