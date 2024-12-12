# Changelog
## NEXT
### Changed
* Added new health command for daemon-only instances

## Version 1.2
### New
* Added timed scheduled runs

### Changed
* The translations are now provided in yaml format
* User command has been extended to be more usable

## Version 1.1

### New
* User settings page. Eliminating the need to call the helpline for changing your password or language
* Version tag in footer

### Changed
* Docker images are following the docker philosophy (2 containers providing 1 functionality each. Although you can still use the fat image)
* Translations can be contributed via [crowdin](https://crowdin.com/project/webcron-management)
* Data migrations are done using doctrine migrations
* User command now has update action
* Mail-failed-runs now takes an argument with recipients. Eliminating the need for a user account
* Docker images are build for amd64, arm and arm64
* Symfony framework has been updated to version 6.2

### Fixed
* Some flashes were not translated
* Trusted proxies were not parsed
* When running in a different container the health of the daemon could not be checked

## Version 1.0

(Initial release)
