# CrsGrpImport

UIHook plugin for course and group creation with a csv

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL"
in this document are to be interpreted as described in
[RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

**Table of Contents**

* [Installation](#installation)
    * [Composer](#composer)
* [Configuration](#configuration)
* [Specifications](#specifications)
* [Validation](#validation)
* [Example](#example_csv_file)
* [Other information](#other-information)
    * [Correlations](#correlations)
    * [Bugs](#bugs)
    * [License](#license)

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

You SHOULD configure the roles in the configuration of the plugin.

## Specifications

An ILIAS plugin that applies defaults to news settings of new objects and provides
a user interface for news setting migrations.

## Validation
There are some values which must be set in a specific way, to create courses an groups, these values are as following:
* Action
  * Values: insert, update, ignore
* Title
  * Value: Must be set
* Type
  * Values: crs, grp
* Registration
  * Values: 0,1,2,3
    * 0: Deactivated
    * 1: Direct registration
    * 2: Password registration
    * 3: Request confirmation
    * anything else: deactivated
* Grp_type
  * Values: 0,1,2,3
    * 0: Deactivated
    * 1: Direct registration
    * 2: Password registration
    * 3: Request registration
    * anything else: deactivated
* Admins
  * Value: Must be set
* AdmissionLink
  * Values: 0,1

## Example CSV file
```
Action;Type;RefId;GrpType;Title;Description;EventStart;EventEnd;Online;AvailabilityStart;AvailabilityEnd;Registration;RegistrationPass;AdmissionLink;RegistrationStart;RegistrationEnd;UnsubscribeEnd;Admins; 
Insert;crs;;;My Course;Lorem Ipsum;10.03.2022 12:00;31.12.2022 23:55;1;15.03.2022 12:00;15.03.2023 12:00;1;geheim;0;15.03.2023 12:00;15.03.2023 12:00;15.03.2023 12:00;root;
Insert;grp;;0;My Group;Lorem Ipsum;10.03.2022 12:00;31.12.2022 23:55;0;15.03.2022 12:00;15.03.2023 12:00;0;geheim;1;15.03.2023 12:01;15.03.2023 12:01;15.03.2023 12:01;root;
```
## Other Information

### Correlations

None

### Bugs

None

### License

See [LICENSE](./LICENSE) file in this repository.