# Changelog

## [Unreleased]
### Fixed

* Missing language entry `body_info` for the mail content in the notification settings (shown as `-body_info-`)
* Duplicate language entries `cancel` and `calculation` removed (the effective texts are unchanged)

### Notes

* After updating, refresh the plugin languages (Administration → Plugins → Refresh Languages), since the version is unchanged

## [2.3.0]
### Added
* Scores, suggestions and notifications of a user are deleted when the user is deleted (event deleteUser)
* The sender of the notifications is optional: with an empty sender no emails with learning objective suggestions are sent; once a sender is configured, they are sent on the user's next learning progress status change in the course (not by the manual generation)

### Fixed
* The update to 2.3.0 removes the data of users deleted before
* No email is sent if the configured sender does not exist
* Logs contain only the user id instead of login and email; existing log files are not changed
* Study programs are read from the current CascadingSelect definition (udf_definition) instead of the frozen table udf_plugin_cselect
* Study program of users with a first-level option without children (e.g. "DuO → Allgemein → keine") is recognized
* CascadingSelect fields: study program ids are stored per field (alo_config), new study programs no longer shift the ids of the rough weights (normal select fields keep the option's position in the option list as id)
* CascadingSelect fields: renamed study programs get a new id, enter their rough weights again
* Install the update to 2.3.0 before udf_plugin_cselect is removed (CascadingSelect), otherwise the ids are built from the current definition

## [2.2.0]
### Fixed

* `Notification::getSentAt()` caused a TypeError for notifications without send date (`sent_at` NULL)

## [2.0.1]
### Fixed

* Mail errors from `ilMail::enqueue()` (`ilMailError` objects) caused a fatal error instead of being logged
* Logging a failed suggestion save caused a fatal error and lost the original exception
* Errors while handling a learning progress change (configuration, course and user lookup, calculating and saving scores and suggestions, assigning roles, sending mails) are logged instead of aborting the request that changed the learning progress
* A failure to save the notification after the mail was sent is logged as such and no longer reported as a failed mail
* `InternalMail::bcc()` added recipients to CC instead of BCC (currently unused)
* Logged errors no longer contain stack traces with call arguments (possibly personal data), only exception class, message, file and line

## [2.0.0]
### Updated

* Update für ILIAS11

## [1.2.0]
### Removed
* Main and extended courses from learning objective's configuration 
* Main and extended courses from learning objective suggestions generator 
