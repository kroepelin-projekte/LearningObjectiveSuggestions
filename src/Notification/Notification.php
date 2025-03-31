<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification;

class Notification extends \ActiveRecord {
	const TABLE_NAME = "alo_notification";
	public function getConnectorContainerName(): string
    {
		return self::TABLE_NAME;
	}
	/**
	 * @deprecated
	 */
	public static function returnDbTableName(): string
    {
		return self::TABLE_NAME;
	}
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 * @db_is_primary   true
	 * @db_sequence     true
	 */
	protected ?int $id;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 * @db_index        true
	 */
	protected int $user_id;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 * @db_index        true
	 */
	protected int $course_obj_id;
	/**
	 * @var string|null
	 *
	 * @db_has_field    true
	 * @db_fieldtype    timestamp
	 */
	protected ?string $sent_at;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected int $sent_user_id;
	public function getId(): int
    {
		return $this->id;
	}
	public function getUserId(): int
    {
		return $this->user_id;
	}
	public function setUserId(int $user_id): void
    {
		$this->user_id = $user_id;
	}
	public function getCourseObjId(): int
    {
		return $this->course_obj_id;
	}
	public function setCourseObjId(int $course_obj_id): void
    {
		$this->course_obj_id = $course_obj_id;
	}
	public function getSentAt(): string
    {
		return $this->sent_at;
	}
	public function setSentAt(?string $sent_at): void
    {
		$this->sent_at = $sent_at;
	}
	public function getSentUserId(): int
    {
		return $this->sent_user_id;
	}
	public function setSentUserId(int $sent_user_id): void
    {
		$this->sent_user_id = $sent_user_id;
	}
}
