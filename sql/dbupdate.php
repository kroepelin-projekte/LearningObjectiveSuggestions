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
    $sug->setIsCronActive(1);
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
