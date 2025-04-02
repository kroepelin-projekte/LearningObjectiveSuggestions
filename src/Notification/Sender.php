<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class Sender
{
    protected string $subject;
    protected string $body;
    protected LearningObjectiveCourse $course;
    protected User $user;
    protected Log $log;

    /**
     * @param LearningObjectiveCourse $course
     * @param User                    $user
     * @param Log                     $log
     */
    public function __construct(LearningObjectiveCourse $course, User $user, Log $log)
    {
        $this->course = $course;
        $this->user = $user;
        $this->log = $log;
    }
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
    public function send(): bool
    {
        $config = new CourseConfigProvider($this->course);
        $sender = new User(new \ilObjUser($config->get('notification_sender_user_id')));
        $mail = new InternalMail();
        $mail->subject($this->subject)->body($this->body)->from($sender)->to($this->user);
        if ($cc_role_id = $config->get('notification_cc_role_id')) {
            $mail->cc($this->getRole($cc_role_id));
        }
        try {
            $mail->send();
            $notification = $this->getNotification();
            $notification->setSentUserId($sender->getId());
            $notification->setSentAt(date('Y-m-d H:i:s'));
            $notification->save();

            return true;
        } catch (\Exception $e) {
            $this->log->write($e->getMessage());

            return false;
        }
    }
    protected function getRole(int $role_id): \ilObjRole
    {
        static $cache = array();
        if (isset($cache[$role_id])) {
            return $cache[$role_id];
        }
        $role = new \ilObjRole((int) $role_id);
        $cache[$role_id] = $role;

        return $role;
    }
    protected function getNotification(): Notification|\ActiveRecord
    {
        $notification = Notification::where(array(
            'user_id' => $this->user->getId(),
            'course_obj_id' => $this->course->getId()
        ))->first();
        if ($notification !== null) {
            return $notification;
        }
        $notification = new Notification();
        $notification->setUserId($this->user->getId());
        $notification->setCourseObjId($this->course->getId());

        return $notification;
    }
}
