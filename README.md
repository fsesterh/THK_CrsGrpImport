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

## Other Information

### Correlations

None

### Bugs

None

### License

See [LICENSE](./LICENSE) file in this repository.