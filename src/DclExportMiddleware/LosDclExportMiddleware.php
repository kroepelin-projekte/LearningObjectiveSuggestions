<?php

namespace SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\DclExportMiddleware;

//require_once __DIR__ ."../../../../../WebServices/SoapHook/DataCollectionSOAPServices/vendor/autoload.php";


use ilDclBaseRecordModel;
use ilObjCourse;
use ilObjUser;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Cron\SendSuggestionsCronJob;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

//use srag\Plugins\DataCollectionSOAPServices\RecordsOfDataCollectionViewExtendMiddleware;

class LosDclExportMiddleware implements RecordsOfDataCollectionViewExtendMiddleware
{
    public static function new(): RecordsOfDataCollectionViewExtendMiddleware
    {
        return new static();
    }

    public function process(array $record_data, ilDclBaseRecordModel $record): array
    {
        $crs_ref_ids = SendSuggestionsCronJob::getCrsRefIdsWithInitialTestStates($record->getOwner());

        $record_data["UsrId"] = $record->getOwner();
        $record_data["PercentageDet"] = -1;
        $record_data["StudyProgram"] = null;
        $record_data["DclRefid"] = $record->getTable()->getCollectionObject()->getRefId();
        $record_data["DclTitle"] = $record->getTable()->getCollectionObject()->getTitle();


        if (count($crs_ref_ids) === 0) {
            return $record_data;
        }

        $crs_ref_id = $crs_ref_ids[0]; //we take the first one. There should by concept be only one!


        $learning_objective_course = new LearningObjectiveCourse(new ilObjCourse($crs_ref_id));
        $config = new CourseConfigProvider($learning_objective_course);
        $study_program_query = new StudyProgramQuery($config);



        $record_data["PercentageDet"] = SendSuggestionsCronJob::getTestUserResult($record->getOwner(), $crs_ref_id);
        $study_program = $study_program_query->getByUser(new User(new ilObjUser($record->getOwner())));
        if (is_object($study_program)) {
            $record_data["StudyProgram"] = $study_program->getTitle();
        }
        return $record_data;
    }
}
