<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User;

use Leifos\CascadingSelect\DataObjects\Definition;
use Leifos\CascadingSelect\DataObjects\Parser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;

class StudyProgramQuery
{
    private const string USER_FIELD_TYPE_CASCADING_SELECT = 'Leifos\CascadingSelect\UserFieldType';
    private const string ID_MAP_KEY_PREFIX = 'study_program_ids_';
    private const string PATH_SEPARATOR = '→';

    protected CourseConfigProvider $config;

    /** @var array<string, array|null> */
    private static array $udf_definitions = [];

    /** @var array<string, Definition|null> */
    private static array $definitions = [];

    /**
     * @param CourseConfigProvider $config
     */
    public function __construct(CourseConfigProvider $config)
    {
        $this->config = $config;
    }

    public function getByUser(User $user): ?StudyProgram
    {
        global $DIC;

        $study_program_field_id = $this->getStudyProgramFieldId();
        if ($study_program_field_id === null) {
            return null;
        }

        $values = $DIC['user']->getProfile()
                              ->getDataFor($user->getId())
                              ->getAdditionalFieldByIdentifier($study_program_field_id);

        $title = $values[0] ?? null;
        if ($title === null || $title === '') {
            return null;
        }

        if (self::isCascadingSelect($study_program_field_id)) {
            $title = self::titleFromPath($title, self::cascadingSelectDefinition($study_program_field_id));
            if ($title === null) {
                return null;
            }
        }

        $filtered = array_filter($this->getAll(), function ($study_program) use ($title) {
            return ($study_program->getTitle() == $title);
        });

        return count($filtered) ? array_pop($filtered) : null;
    }

    private function getStudyProgramFieldId(): ?string
    {
        return self::resolveFieldId((string) $this->config->get('udf_id_study_program'));
    }

    /**
     * Configured value is the field UUID, or the numeric field id from ILIAS <= 10.
     */
    public static function resolveFieldId(string $configured_id): ?string
    {
        global $DIC;

        if ($configured_id === '') {
            return null;
        }
        if (!ctype_digit($configured_id)) {
            return $configured_id;
        }

        $db = $DIC->database();
        if (!$db->tableExists('udf_field_id_map')) {
            return null;
        }
        $res = $db->queryF(
            'SELECT field_id FROM udf_field_id_map WHERE old_field_id = %s',
            [\ilDBConstants::T_INTEGER],
            [(int) $configured_id]
        );
        $row = $db->fetchAssoc($res);

        return $row['field_id'] ?? null;
    }

    /**
     * @return StudyProgram[]
     * @throws \JsonException
     */
    public function getAll(): array
    {
        static $cache = [];

        $config_course_id = $this->config->getCourse()->getId();
        if (isset($cache[$config_course_id])) {
            return $cache[$config_course_id];
        }
        $programs = [];

        $study_program_field_id = $this->getStudyProgramFieldId();
        if ($study_program_field_id === null) {
            return [];
        }

        if (!self::isCascadingSelect($study_program_field_id)) {
            $field_values = self::getFieldValues($study_program_field_id);
            $data = json_decode($field_values, true)
                ?? unserialize($field_values, ['allowed_classes' => false])
                ?: [];
            foreach ($data['options'] ?? $data as $id => $title) {
                $programs[] = new StudyProgram($id, $title);
            }
        } else {
            $definition = self::cascadingSelectDefinition($study_program_field_id);
            $titles = $definition === null ? [] : self::titlesFromDefinition($definition);
            $ids = self::assignIds($study_program_field_id, $titles);
            foreach (array_unique($titles) as $title) {
                $programs[] = new StudyProgram($ids[$title], $title);
            }
        }
        $cache[$config_course_id] = $programs;

        return $programs;
    }

    /**
     * Study programs are the second level options, or the first level option if it has no children.
     * @return array<int, string> keyed by list position as in 2.0.0 (empty names skipped), duplicates kept
     */
    public static function titlesFromDefinition(Definition $definition): array
    {
        $names = [];
        foreach ($definition->options() as $level1) {
            if ($level1->childOptions() === []) {
                $names[] = $level1->name();
                continue;
            }
            foreach ($level1->childOptions() as $level2) {
                $names[] = $level2->name();
            }
        }
        return array_filter($names, static fn(string $name): bool => trim($name) !== '');
    }

    /**
     * Returns the stored title => id map of the field; unknown titles get max + 1, so weight_rough_* keys stay valid.
     * @param array<int, string> $titles
     * @return array<string, int>
     */
    private static function assignIds(string $field_id, array $titles): array
    {
        $stored = self::loadIds($field_id);
        $ids = self::appendIds($stored === [] ? self::initialIds($field_id, $titles) : $stored, $titles);
        if ($ids !== $stored) {
            self::saveIds($field_id, $ids);
        }
        return $ids;
    }

    /**
     * Rewrites the map with the ids of the frozen XML as base, so a map created before the update step is corrected.
     * @param array<int, string> $titles
     * @return array<string, int>
     */
    public static function rebuildIds(string $field_id, array $titles): array
    {
        $stored = self::loadIds($field_id);
        $legacy_titles = self::legacyTitles($field_id);
        $base = $legacy_titles !== null
            ? self::appendIds(self::positionIds($legacy_titles), array_keys($stored))
            : ($stored === [] ? self::positionIds($titles) : $stored);
        $ids = self::appendIds($base, $titles);
        if ($ids !== $stored) {
            self::saveIds($field_id, $ids);
        }
        return $ids;
    }

    public static function cascadingSelectDefinition(string $field_id): ?Definition
    {
        if (array_key_exists($field_id, self::$definitions)) {
            return self::$definitions[$field_id];
        }
        // CascadingSelect component may be missing although the field type is still stored
        $definition = null;
        if (class_exists(Parser::class) && self::isCascadingSelect($field_id)) {
            $definition = (new Parser(null))->unserializeDefinition(self::getFieldValues($field_id));
        }
        return self::$definitions[$field_id] = $definition;
    }

    /**
     * @param array<int, string> $titles
     * @return array<string, int>
     */
    private static function initialIds(string $field_id, array $titles): array
    {
        return self::positionIds(self::legacyTitles($field_id) ?? $titles);
    }

    /**
     * Same ids as before 2.1.0: list position after array_unique.
     * @param array<int, string> $titles
     * @return array<string, int>
     */
    private static function positionIds(array $titles): array
    {
        return array_flip(array_unique($titles));
    }

    /**
     * @param array<string, int> $ids
     * @param array<int, string|int> $titles
     * @return array<string, int>
     */
    private static function appendIds(array $ids, array $titles): array
    {
        foreach ($titles as $title) {
            if (!array_key_exists($title, $ids)) {
                $ids[$title] = $ids === [] ? 0 : max($ids) + 1;
            }
        }
        return $ids;
    }

    /**
     * Titles from the XML of the old CascadingSelect plugin (ILIAS <= 10), on which the existing weights are based.
     * @return array<int, string>|null
     */
    private static function legacyTitles(string $field_id): ?array
    {
        global $DIC;

        $db = $DIC->database();
        if (!class_exists(Parser::class)
            || !$db->tableExists('udf_field_id_map')
            || !$db->tableExists('udf_plugin_cselect')
        ) {
            return null;
        }

        $res = $db->queryF(
            'SELECT old_field_id FROM udf_field_id_map WHERE field_id = %s',
            [\ilDBConstants::T_TEXT],
            [$field_id]
        );
        $map_row = $db->fetchAssoc($res);
        if ($map_row === null) {
            return null;
        }

        $res = $db->queryF(
            'SELECT keyword_value FROM udf_plugin_cselect WHERE keyword = %s',
            [\ilDBConstants::T_TEXT],
            ['xml_' . $map_row['old_field_id']]
        );
        $xml = (string) ($db->fetchAssoc($res)['keyword_value'] ?? '');
        if ($xml === '') {
            return null;
        }

        $result = (new Parser(null))->definitionFromXML($xml);
        return $result->isValid() ? self::titlesFromDefinition($result->definition()) : null;
    }

    /**
     * @return array<string, int>
     */
    private static function loadIds(string $field_id): array
    {
        $stored = (new ConfigProvider())->get(self::ID_MAP_KEY_PREFIX . $field_id);
        return array_map('intval', (array) json_decode($stored ?? '', true));
    }

    /**
     * @param array<string, int> $ids
     */
    private static function saveIds(string $field_id, array $ids): void
    {
        (new ConfigProvider())->set(
            self::ID_MAP_KEY_PREFIX . $field_id,
            json_encode($ids, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Profile values look like "A → B → C"; the component fills missing levels with the column defaults,
     * e.g. "DuO → Allgemein → keine", so the title depends on whether A has children.
     */
    private static function titleFromPath(string $value, ?Definition $definition): ?string
    {
        [$level1, $level2] = array_pad(array_map('trim', explode(self::PATH_SEPARATOR, $value)), 2, '');

        foreach ($definition?->options() ?? [] as $option) {
            if (trim($option->name()) !== $level1) {
                continue;
            }
            if ($option->childOptions() === []) {
                return $option->name();
            }
            foreach ($option->childOptions() as $child) {
                if (trim($child->name()) === $level2) {
                    return $child->name();
                }
            }
            return null;
        }

        return null;
    }

    private static function getFieldValues(string $study_program_field_id): string
    {
        return (string) (self::getUdfDefinition($study_program_field_id)['field_values'] ?? '');
    }

    private static function isCascadingSelect(string $study_program_field_id): bool
    {
        // type hardcoded: CascadingSelect may not be installed
        return (string) (self::getUdfDefinition($study_program_field_id)['field_type'] ?? '')
            === self::USER_FIELD_TYPE_CASCADING_SELECT;
    }

    private static function getUdfDefinition(string $study_programm_field_id): ?array
    {
        global $DIC;

        if (array_key_exists($study_programm_field_id, self::$udf_definitions)) {
            return self::$udf_definitions[$study_programm_field_id];
        }

        $db = $DIC->database();
        $res = $db->queryF(
            'SELECT * FROM udf_definition WHERE field_id = %s',
            [\ilDBConstants::T_TEXT],
            [$study_programm_field_id]
        );

        return self::$udf_definitions[$study_programm_field_id] = $db->fetchAssoc($res);
    }
}
