<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use ilObjUser;
use Leifos\CascadingSelect\DataObjects\Parser as CascadingSelectParser;
use Leifos\CascadingSelect\Service\Container as CascadingSelectContainer;


class StudyProgramQuery
{
    private const string DEFAULT_STUDY_PROGRAM_TITLE = 'Allgemein';

    protected CourseConfigProvider $config;

    protected \ilSetting $udf_setting;

    /**
     * @param CourseConfigProvider $config
     */
    public function __construct(CourseConfigProvider $config)
    {
        $this->config = $config;
        $this->udf_setting = new \ilSetting('udfd');
    }

    /**
     * Returns the StudyProgram of the given User
     * @param User $user
     * @return StudyProgram|null
     */
    public function getByUser(User $user): ?StudyProgram
    {
        global $DIC;

        $study_program_field_id  =$this->getStudyProgramFieldId();

        $values = [];
        if (!empty($study_program_field_id)) {
            $values = $DIC['user']->getProfile()
                                  ->getDataFor($user->getId())
                                  ->getAdditionalFieldByIdentifier($study_program_field_id);
        }

        $title = $values[0] ?? null;

        if ($title == null) {
            return null;
        }
        // The data is separated with an arrow, wtf...
        //13.04.2021 Modification DHBW from old master
        if ($this->isCascadingSelect($study_program_field_id)) {
            $title_exploded = explode("→", $title);
            list($level1, $title, $_) = array_pad(array_map('trim', $title_exploded), 3, null);

            if (empty($title)) {
                $title = $level1;
            }
        }
        $filtered = array_filter($this->getAll($user), function ($study_program) use ($title) {
            return ($study_program->getTitle() == $title);
        });

        return count($filtered) ? array_pop($filtered) : null;
    }

    /**
     * @return string
     */
    protected function getStudyProgramFieldId(): ?string
    {
        global $DIC;

        $id = (string) $this->config->get('udf_id_study_program');

        // Validate that $id is not integer. In ILIAS11 it's UUID
        if (!ctype_digit($id)) {
            return $id;
        }

        $db = $DIC->database();
        $res = $db->queryF(
            'SELECT field_id FROM udf_field_id_map WHERE old_field_id = %s',
            [\ilDBConstants::T_INTEGER],
            [(int) $id]
        );
        $row = $db->fetchAssoc($res);

        return $row['field_id'] ?? null;
    }

    /**
     * @param $user
     * @return array
     * @throws \JsonException
     */
    public function getAll($user): array
    {
        static $cache = [];

        $config_course_id = $this->config->getCourse()->getId();
        if (isset($cache[$config_course_id])) {
            return $cache[$config_course_id];
        }
        $programs = [];

        $study_program_field_id = $this->getStudyProgramFieldId();
        $field_values = $this->getFieldValues($study_program_field_id);

        // Check if field is of type CascadingSelect
        if (!$this->isCascadingSelect($study_program_field_id)) {
            // TODO test this
            $data = json_decode($field_values, true)
                ?? unserialize($field_values, ['allowed_classes' => false])
                ?: [];
            foreach ($data['options'] ?? $data as $id => $title) {
                $programs[] = new StudyProgram($id, $title);
            }
        } else {
            $parser = (new CascadingSelectContainer())->definitionParser();

            $udf_definition = $this->getUdfDefinition($study_program_field_id);
            $study_program_old_filed_id = $this->getOldFieldId($study_program_field_id);
            $xml = $this->getXMLCascadingSelect($study_program_old_filed_id);

            $program_titles = [];
            if ($xml !== '') {
                $parser = new \Leifos\CascadingSelect\DataObjects\Parser(null);
                $result = $parser->definitionFromXML($xml);

                if ($result->isValid()) {
                    $definition = $result->definition();
                    $definition_field_values = $definition->serialize();

                    // The study programs are options on the second level of all data available on the first level
                    foreach ($definition->options() as $level1) {
                        // avoid error if level2 is empty. Use level1->name() as fallback (functionality still to be tested)
                        if (property_exists($level1, 'child_options') && !empty($level1->childOptions())) {
                            foreach ($level1->childOptions() as $level2) {
                                $program_titles[] = $level2->name();
                            }
                        } else {
                            $program_titles[] = $level1->name();
                        }
                    }
                } else {
                    throw new \RuntimeException('No entry found in udf_plugin_cselect for keyword xml_' . $study_program_old_filed_id);
                }
            }

            foreach (array_unique($program_titles) as $id => $title) {
                $programs[] = new StudyProgram($id, $title);
            }
        }
        $cache[$config_course_id] = $programs;

        return $programs;
    }

    /**
     * @param string $study_program_field_id
     * @return string
     */
    protected function getFieldValues(string $study_program_field_id): string
    {
        $udf_definition = $this->getUdfDefinition($study_program_field_id);

        return (string) ($udf_definition['field_values'] ?? '');
    }

    /**
     * Check if the the UDF field is of type cascading select
     *
     * @param $study_program_field_id
     * @return bool
     */
    protected function isCascadingSelect($study_program_field_id): bool
    {
        $udf_definition = $this->getUdfDefinition($study_program_field_id);

        // type is hardcoded here, since CascadingSelect may be not installed
        return ((string) $udf_definition['field_type'] === '51');
    }

    /**
     * @param string $study_programm_field_id
     * @return array|null
     */
    private function getUdfDefinition(string $study_programm_field_id)
    {
        global $DIC;

        $db = $DIC->database();

        $res = $db->queryF(
            'SELECT * FROM udf_definition WHERE field_id = %s',
            [\ilDBConstants::T_TEXT],
            [$study_programm_field_id]
        );
        $row = $db->fetchAssoc($res);

        return $row;
    }

    /**
     * @param string $field_id
     * @return mixed|null
     */
    private function getOldFieldId(string $field_id)
    {
        global $DIC;

        $query = 'SELECT * FROM udf_field_id_map WHERE field_id = %s';

        $results = $DIC->database()->queryF(
            $query,
            ['integer'],
            [$field_id]
        );

        $old_field_id = null;
        while ($row = $DIC->database()->fetchAssoc($results)) {
            $old_field_id = $row['old_field_id'];
        }
        return $old_field_id;
    }

    /**
     * @param string $old_field_id
     * @return string
     * @throws \JsonException
     */
    private function getXMLCascadingSelect(string $old_field_id)
    {
        global $DIC;
        $db = $DIC->database();

        $xml = '';

        $res = $db->queryF(
            'SELECT keyword_value FROM udf_plugin_cselect WHERE keyword = %s',
            [\ilDBConstants::T_TEXT],
            ['xml_' . $old_field_id]
        );

        $xml = '';
        if ($row = $db->fetchAssoc($res)) {
            $xml = (string) $row['keyword_value'];
        }
        return $xml;
    }
}
