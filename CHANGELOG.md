# Changelog

## [2.1.0]
### Added
* Scores, suggestions and notifications of a user are deleted when the user is deleted (event deleteUser)
* The sender of the notifications is optional: with an empty sender no emails with learning objective suggestions are sent; once a sender is configured, they are sent on the user's next learning progress status change in the course (not by the manual generation)

### Fixed
* The update to 2.1.0 removes the data of users deleted before
* No email is sent if the configured sender does not exist
* Logs contain only the user id instead of login and email; existing log files are not changed
* Study programs are read from the current CascadingSelect definition (udf_definition) instead of the frozen table udf_plugin_cselect
* Study program of users with a first-level option without children (e.g. "DuO → Allgemein → keine") is recognized
* CascadingSelect fields: study program ids are stored per field (alo_config), new study programs no longer shift the ids of the rough weights (normal select fields keep the option's position in the option list as id)
* CascadingSelect fields: renamed study programs get a new id, enter their rough weights again
* Install the update to 2.1.0 before udf_plugin_cselect is removed (CascadingSelect), otherwise the ids are built from the current definition

## [2.0.0]
### Updated

* Update für ILIAS11

## [1.2.0]
### Removed
* Main and extended courses from learning objective's configuration 
* Main and extended courses from learning objective suggestions generator 
