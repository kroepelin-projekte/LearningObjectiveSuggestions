<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User;

class User
{
    protected \ilObjUser $user;
    public function __construct(\ilObjUser $user)
    {
        $this->user = $user;
    }
    public function getId(): int
    {
        return $this->user->getId();
    }
    public function getLogin(): string
    {
        return $this->user->getLogin();
    }
    public function getEmail(): string
    {
        return $this->user->getEmail();
    }
    public function getFirstname(): string
    {
        return $this->user->getFirstname();
    }
    public function getLastname(): string
    {
        return $this->user->getLastname();
    }
    public function __toString(): string
    {
        return '[' . implode(', ', array(
                $this->getId(),
                $this->getLogin(),
                $this->getEmail(),
            )) . ']';
    }
}
