<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;

class StudyProgramQuery
{
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
        $data = new \ilUserDefinedData($user->getId());
        $title = $data->get('f_' . $this->config->get('udf_id_study_program'));

	if ($title == null) {
		return null;
	}
        // The data is separated with an arrow, wtf...
        //13.04.2021 Modification DHBW from old master 
          if ($this->isCascadingSelect()) {
        list($level1, $title, $_) = array_map('trim', explode("→", $title));
		  if ($title=='') {
			  $title=$level1;
		  }
          }
        $filtered = array_filter($this->getAll(), function ($study_program) use ($title) {
            /** @var $study_program StudyProgram */
            return ($study_program->getTitle() == $title);
        });

        return count($filtered) ? array_pop($filtered) : null;
    }

    /**
     * Returns all the StudyPrograms
     * @return StudyProgram[]
     */
    public function getAll(): array
    {
	global $DIC;
	    
        static $cache = array();
        if (isset($cache[$this->config->getCourse()->getId()])) {
            return $cache[$this->config->getCourse()->getId()];
        }
        $programs = array();
        /** @var \ilUserDefinedFields $udf */
        $udf = \ilUserDefinedFields::_getInstance();
        // Check if field is of type CascadingSelect
        if (!$this->isCascadingSelect()) {
            $data = $udf->getDefinition($this->config->get('udf_id_study_program'));
            foreach ($data['field_values'] as $id => $title) {
                $programs[] = new StudyProgram($id, $title);
            }
        } else {
	    $CSplugin = \ilCustomUserFieldsHelper::getInstance()->getPluginForType(\ilCascadingSelectPlugin::CASCADING_TYPE_ID);
            $settings = new \Leifos\CascadingSelect\Settings($DIC->database(),$CSplugin->getFactory());
            $data = $settings->getJSON($this->config->get('udf_id_study_program'))->raw();

            $program_titles = array();
            // The study programs are options on the second level of all data available on the first level
            foreach ($data->options as $level1) {
		// avoid error if level2 is empty. Use level1->name as fallback (functionality still to be tested)    
		if (property_exists($level1,'options')) {    
                    foreach ($level1->options as $level2) {
                        $program_titles[] = $level2->name;
                    }
		} else {
		    $program_titles[] = $level1->name;
		}
            }
            foreach (array_unique($program_titles) as $id => $title) {
                $programs[] = new StudyProgram($id, $title);
            }
        }

        $cache[$this->config->getCourse()->getId()] = $programs;

        return $programs;
    }

    /**
     * Check if the the UDF field is of type cascading select
     * @return bool
     */
    protected function isCascadingSelect(): bool
    {
        $udf = \ilUserDefinedFields::_getInstance();
        $data = $udf->getDefinition($this->config->get('udf_id_study_program'));
	    // type is hardcoded here, since CascadingSelect may be not installed
        return ((string) $data['field_type'] === "51");
    }
}
