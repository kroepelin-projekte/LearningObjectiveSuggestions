<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class InternalMail
{
    protected User $sender;
    protected User $receiver;
    protected array $cc = array();
    protected array $bcc = array();
    protected string $subject = '';
    protected string $body = '';
    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }
    public function body(string $body): static
    {
        $this->body = $body;
        return $this;
    }
    public function from(User $user): static
    {
        $this->sender = $user;
        return $this;
    }
    public function to(User $user): static
    {
        $this->receiver = $user;
        return $this;
    }
    public function cc(User|\ilObjRole $user_or_role): static
    {
        if ($user_or_role instanceof User) {
            $this->cc[] = $user_or_role->getLogin();
        } elseif ($user_or_role instanceof \ilObjRole) {
            $this->cc[] = "#" . $user_or_role->getTitle();
        }
        return $this;
    }
    public function bcc(User|\ilObjRole $user_or_role): static
    {
        if ($user_or_role instanceof User) {
            $this->cc[] = $user_or_role->getLogin();
        } elseif ($user_or_role instanceof \ilObjRole) {
            $this->cc[] = "#" . $user_or_role->getTitle();
        }
        return $this;
    }
    /**
     * @throws \ilException
     */
    public function send(): bool
    {
        $mailer = new \ilMail($this->sender->getId());
        $mailer->setSaveInSentbox(true);

        $result = $mailer->enqueue(
            $this->receiver->getLogin(),
            implode(',', $this->cc),
            implode(',', $this->bcc),
            $this->subject,
            $this->body,
            [],
            false
        );

        if (!empty($result)) {
            $message = (is_array($result)) ? implode(', ', $result) : $result;
            throw new \ilException("Failed to send mail with error: " . $message);
        }
        return true;
    }
}
