# CrsGrpImport

UIHook plugin for importing courses, groups, course references and group references from a CSV file.
User need the permission to create courses or groups in order to use the plugin. Objects are created at the tree node where users upload the file.
It is NOT possible to set a repository target (a category for example) for the new objects.

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL"
in this document are to be interpreted as described in
[RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

## Requirements

| Component | Version(s)                                                                                    | Link                      |
|-----------|-----------------------------------------------------------------------------------------------|---------------------------|
| PHP       | ![](https://img.shields.io/badge/8.1-blue.svg) ![](https://img.shields.io/badge/8.2-blue.svg) | [PHP](https://php.net)    |
| ILIAS     | ![](https://img.shields.io/badge/9-orange.svg)                                                | [ILIAS](https://ilias.de) |

---

## Table of contents

<!-- TOC -->
* [CrsGrpImport](#crsgrpimport)
  * [Requirements](#requirements)
  * [Table of contents](#table-of-contents)
  * [Installation](#installation)
  * [Configuration](#configuration)
  * [Settings](#settings)
    * [Example Files:](#example-files)
  * [License](#license)
<!-- TOC -->

---

## Installation

1. Clone this repository to **Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpImport**
2. Install the Composer dependencies
   ```bash
   cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpImport
   composer install --no-dev
   ```
   Developers **MUST** omit the `--no-dev` argument.
3. Run ``composer install --no-dev`` in the ilias root directory!
4. Login to ILIAS with an administrator account (e.g. root)
5. Select **Plugins** in **Extending ILIAS** inside the **Administration** main menu.
6. Search for the **CrsGrpImport** plugin in the list of plugin and choose **Install** from the **Actions** drop-down.
7. Choose **Activate** from the **Actions** dropdown.

## Configuration

* You SHOULD configure the roles (by ID) in the configuration of the plugin.
* You MUST activate the cronjob in order to process import files.

## Settings
There are some values which must be set in a specific way, to create courses and groups, these values are as following:
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
* News: 0 | 1
* NewsBlock: 0 | 1
* NewsDefaultAccess: 0 | 1
* NewsRSSFeed: 0 | 1
* NewsTimeline: 0 | 1
* NewsTimeAutoEntry: 0 | 1
* NewsTimeLanding: 0 | 1
* NewsStartDate: DD.MM.YYYY HH:mm
* MemberGallery: 0 | 1
* Admins: username,username,username

### Example Files:
```
example_files/crsgrp_importtemplate_v9_1_0.ods
example_files/crsgrp_importtemplate_v9_1_0.xlsx
example_files/test_csv_9-1-0.csv

```

## License
See [LICENSE](./LICENSE) file in this repository.
