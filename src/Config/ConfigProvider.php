<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config;

class ConfigProvider
{
    public function get(string $key): ?string
    {
        /** @var CourseConfig $config */
        $config = Config::where(array(
            'cfg_key' => $key,
        ))->first();
        return ($config) ? $config->getValue() : null;
    }
    public function set(string $key, string $value): void
    {
        $config = Config::where(array(
            'cfg_key' => $key,
        ))->first();
        if ($config === null) {
            $config = new Config();
            $config->setKey($key);
        }
        $config->setValue($value);
        $config->save();
    }
    public function getCourseRefIds(): array
    {
        return (array) json_decode($this->get('course_ref_ids') ?? '', true);
    }
}
