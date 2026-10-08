<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User;

class User
{
    protected \ilObjUser $user;

    /**
     * @param \ilObjUser $user
     */
    public function __construct(\ilObjUser $user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->user->getId();
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->user->getLogin();
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->user->getEmail();
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->user->getFirstname();
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->user->getLastname();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return '[' . implode(', ', array(
                $this->getId(),
                $this->getLogin(),
                $this->getEmail(),
            )) . ']';
    }
}
