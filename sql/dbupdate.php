<#1>
<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\Config;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfig;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Notification\Notification;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;

LearningObjectiveScore::updateDB();
LearningObjectiveSuggestion::updateDB();
CourseConfig::updateDB();
Config::updateDB();
Notification::updateDB();
?>
<#2>
<?php
//
?>
<#3>
<?php
SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion::updateDB();
?>
<#4>
<?php
foreach (SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion::get() as $sug) {
    /**
     * @@var LearningObjectiveSuggestion $sug
     */
    $sug->setIsCalculationActive(1);
    $sug->save();
}
?>

<#5>
<?php
    global $DIC;

$DIC->database()->query("DELETE t
     FROM alo_score t JOIN
          (SELECT user_id, course_obj_id, objective_id, min(id) AS min_id
           FROM alo_score t
           GROUP BY user_id, course_obj_id, objective_id
          ) tt
          USING (user_id, course_obj_id, objective_id)
    WHERE id > min_id;");

$DIC->database()->query("DELETE t
         FROM alo_suggestion t JOIN
              (SELECT user_id, course_obj_id, objective_id, min(id) AS min_id
               FROM alo_suggestion t
               GROUP BY user_id, course_obj_id, objective_id
              ) tt
              USING (user_id, course_obj_id, objective_id)
        WHERE id > min_id;");


$DIC->database()->query("ALTER TABLE alo_score ADD UNIQUE INDEX (user_Id, course_obj_id, objective_id)");
$DIC->database()->query("ALTER TABLE alo_suggestion ADD UNIQUE INDEX (user_Id, course_obj_id, objective_id)");
?>

<#6>
<?php
global $DIC;

if ($DIC->database()->tableExists('alo_suggestion') && $DIC->database()->tableColumnExists('alo_suggestion', 'is_cron_active')) {
    $DIC->database()->renameTableColumn('alo_suggestion', 'is_cron_active', 'is_calculation_active');
}

?>

<#7>
<?php
global $DIC;

if ($DIC->database()->tableExists('udf_field_id_map')) {
    $query = "SELECT * FROM udf_field_id_map";

    $results = $this->db->query($query);

    $udf_definition_data = [];
    while ($row = $this->db->fetchAssoc($results)) {
        $udf_definition_data[$row['old_field_id']] = $row['field_id'];
    }

    /** @var CourseConfig $config */
    $configs = SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfig::where([
            'cfg_key' => 'udf_id_study_program',
    ])->get();

    if (!empty($configs)) {
        foreach ($configs as $config) {
            $value = (int) $config->getValue();

            if (!empty($udf_definition_data[$value])) {
                $config->setValue($udf_definition_data[$value]);
                $config->update();
            }
        }
    }
}
?>
<#8>
<?php
global $DIC;

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;

// freeze study program ids (weight_rough_<objective>_<id>) as computed before 2.0.1
$db = $DIC->database();
if ($db->tableExists('alo_crs_config') && $db->tableExists('udf_definition')) {
    $configured_ids = [];
    $res = $db->queryF(
        'SELECT value FROM alo_crs_config WHERE cfg_key = %s',
        [\ilDBConstants::T_TEXT],
        ['udf_id_study_program']
    );
    while ($row = $db->fetchAssoc($res)) {
        $configured_ids[(string) $row['value']] = true;
    }

    $field_ids = [];
    foreach (array_keys($configured_ids) as $configured_id) {
        $field_id = StudyProgramQuery::resolveFieldId((string) $configured_id);
        if ($field_id !== null) {
            $field_ids[$field_id] = true;
        }
    }

    foreach (array_keys($field_ids) as $field_id) {
        $definition = StudyProgramQuery::cascadingSelectDefinition((string) $field_id);
        if ($definition !== null) {
            StudyProgramQuery::rebuildIds((string) $field_id, StudyProgramQuery::titlesFromDefinition($definition));
        }
    }
}
?>
<#9>
<?php
global $DIC;

// orphans of users deleted while deleteUser was not handled
$db = $DIC->database();
foreach (['alo_score', 'alo_suggestion', 'alo_notification'] as $table) {
    if ($db->tableExists($table)) {
        $db->manipulate(
            'DELETE FROM ' . $table . ' WHERE NOT EXISTS (SELECT 1 FROM usr_data u WHERE u.usr_id = ' . $table . '.user_id)'
        );
    }
}
?>
