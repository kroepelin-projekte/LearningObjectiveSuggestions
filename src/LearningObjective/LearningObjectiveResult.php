<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class LearningObjectiveResult
{
    /**
     * @var LearningObjective
     */
    protected LearningObjective $objective;
    /**
     * @var User
     */
    protected User $user;
    /**
     * @param LearningObjective $objective
     * @param User              $user
     */
    public function __construct(LearningObjective $objective, User $user)
    {
        $this->objective = $objective;
        $this->user = $user;
    }
    /**
     * @return int
     */
    public function getPercentage(): int
    {
        $data = \ilLOUserResults::lookupResult($this->objective->getCourse()
            ->getId(), $this->user->getId(), $this->objective->getId(), \ilLOUserResults::TYPE_INITIAL);
        return $data['result_perc'];
    }
    public function getLearningObjective(): LearningObjective
    {
        return $this->objective;
    }
    public function getUser(): User
    {
        return $this->user;
    }
}
