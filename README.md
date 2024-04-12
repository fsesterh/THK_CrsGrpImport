# CrsGrpImport

UIHook plugin for importing courses, groups, course references and group references from a CSV file.
User need the permission to create courses or groups in order to use the plugin. Objects are created at the tree node where users upload the file.
It is NOT possible to set a repository target (a category for example) for the new objects.

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL"
in this document are to be interpreted as described in
[RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

<!-- TOC -->
* [CrsGrpImport](#crsgrpimport)
  * [Requirements](#requirements)
  * [Installation](#installation)
    * [Composer](#composer)
  * [Configuration](#configuration)
  * [Specifications](#specifications)
  * [Validation](#validation)
  * [Example CSV file](#example-csv-file)
  * [Other Information](#other-information)
    * [Correlations](#correlations)
    * [Bugs](#bugs)
    * [License](#license)
<!-- TOC -->

## Requirements

* PHP: [![Minimum PHP Version](https://img.shields.io/badge/Minimum_PHP-7.4-blue.svg)](https://php.net/) [![Maximum PHP Version](https://img.shields.io/badge/Maximum_PHP-8.0-blue.svg)](https://php.net/)
* ILIAS: [![Minimum ILIAS Version](https://img.shields.io/badge/Minimum_ILIAS-8.0-orange.svg)](https://ilias.de/) [![Maximum ILIAS Version](https://img.shields.io/badge/Maximum_ILIAS-8.999-orange.svg)](https://ilias.de/)


## Installation

This plugin MUST be installed as a UIHook Plugin.

	<ILIAS>/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpImport

Correct file and folder permissions MUST be
ensured by the responsible system administrator.

### Composer

After the plugin files have been installed as described above,
please install the [`composer`](https://getcomposer.org/) dependencies:

```bash
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpImport
composer install --no-dev
```

Developers MUST omit the `--no-dev` argument.

## Configuration

* You SHOULD configure the roles in the configuration of the plugin.
* You MUST activate the cronjob in order to process import files.

## Settings
There are some values which must be set in a specific way, to create courses an groups, these values are as following:
* Action
  * Insert | Update | Ignore
* Type
  * crs | grp | crsr | grpr
  * please note: creating a course or group reference requires a RefId! Crsr and grpr objects will ALWAYS be created with the option to use the title of the referenced object. It is not possible currently to set a custom title via the import file.
* RefId
  * integer
* Template
  * integer
  * please check the ids for your didactic templates on your installation for courses and groups
* TitleDE: max 255 chars, must be set
* TitleEN: max 255 chars
* Description DE, max 128 chars
* Description EN, max 128 chars
* EventStart: DD.MM.YYYY HH:mm
* EventEnd: DD.MM.YYYY HH:mm
* Online: 0 | 1
* AvailabilityStart: DD.MM.YYYY HH:mm
* AvailabilityEnd: DD.MM.YYYY HH:mm
* AvailabilityVisible: 0 | 1
  * visibility of object outside of availability
* Registration: 0 - none | 1 = direct | 2 = with password | 3 = manually by admin
* RegistrationPass: string, used with Registration = 2
* AdmissionLink: 0 | 1
* RegistrationStart: DD.MM.YYYY HH:mm
* RegistrationEnd: DD.MM.YYYY HH:mm
* UnsubscribeEnd: DD.MM.YYYY
  * please note that you cannot set HH:mm for this field currently!
* LimitMembers: 0 | 1
* MinMembers: integer
* MaxMembers: integer
* WaitingList: 0 = none | 1 = automatic | 2 = manual
* Admins: username,username,username

### Example CSV file
```
Action;Type;RefId;Template;TitleDE;TitleEN;DescriptionDE;DescriptionEN;EventStart;EventEnd;Online;AvailabilityStart;AvailabilityEnd;AvailabilityVisible;Registration;RegistrationPass;AdmissionLink;RegistrationStart;RegistrationEnd;UnsubscribeEnd;LimitMembers;MinMembers;MaxMembers;WaitingList;Admins
Insert;crs;;;Mein Titel;My Title;Meine Beschreibung;My Description;12.04.2024 10:00;12.04.2024 12:00;1;01.04.2024 10:00;15.05.2024 10:00;1;;;;;;;;;;;username

```

See [LICENSE](./LICENSE) file in this repository.