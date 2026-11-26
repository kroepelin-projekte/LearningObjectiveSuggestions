# Changelog

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
