<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config;

class Config extends \ActiveRecord
{
    public const TABLE_NAME = "alo_config";
    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }
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
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       64
     */
    protected string $cfg_key;
    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    clob
     */
    protected string $value;
    public function getId(): int
    {
        return $this->id;
    }
    public function getKey(): string
    {
        return $this->cfg_key;
    }
    public function setKey(string $key): void
    {
        $this->cfg_key = $key;
    }
    public function getValue(): string
    {
        return $this->value;
    }
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
