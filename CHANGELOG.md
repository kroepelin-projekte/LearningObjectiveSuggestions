# Changelog

## [2.0.2]
### Added
* Scores, suggestions and notifications of a user are deleted when the user is deleted (event deleteUser)

### Fixed
* The update to 2.0.2 removes the data of users deleted before

## [2.0.1]
### Fixed
* Study programs are read from the current CascadingSelect definition (udf_definition) instead of the frozen table udf_plugin_cselect
* Study program of users with a first-level option without children (e.g. "DuO → Allgemein → keine") is recognized
* Study program ids are stored per field (alo_config), new study programs no longer shift the ids of the rough weights
* Renamed study programs get a new id: enter their rough weights again
* Install the update to 2.0.1 before udf_plugin_cselect is removed (CascadingSelect), otherwise the ids are built from the current definition

## [2.0.0]
### Updated

* Update für ILIAS11

## [1.2.0]
### Removed
* Main and extended courses from learning objective's configuration 
* Main and extended courses from learning objective suggestions generator 
