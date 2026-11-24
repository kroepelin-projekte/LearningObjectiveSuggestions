# Changelog

## [Unreleased]
### Fixed

* Mail errors from `ilMail::enqueue()` (`ilMailError` objects) caused a fatal error instead of being logged
* Logging a failed suggestion save caused a fatal error and lost the original exception
* PHP errors (not only exceptions) when calculating and saving scores and suggestions, assigning roles and sending mails are logged instead of aborting the request that changed the learning progress

## [2.0.0]
### Updated

* Update für ILIAS11

## [1.2.0]
### Removed
* Main and extended courses from learning objective's configuration 
* Main and extended courses from learning objective suggestions generator 
