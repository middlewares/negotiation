# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## UNRELEASED

### Added

* **ContentType:** New static function `ContentType::getDefaultFormats()` that returns the default formats used.
* **ContentType:** New option `useDefault` option to enable/disable the default format. By default is enabled. If it's disabled, a 406 response is returned when no content-type is found.

### Removed

* **ContentType:** Removed the `defaultFormat` option and use always the first element in the formats list.

### Changed

* Append `.dist` suffix to phpcs.xml and phpunit.xml files
* Changed the configuration of phpcs and php_cs
* Upgraded phpunit to the latest version and improved its config file

## [0.3.1] - 2017-05-18

### Added

* Added `kml` to the list of the default formats

## [0.3.0] - 2016-12-26

### Changed

* Updated tests
* Updated to `http-interop/http-middleware#0.4`
* Updated `friendsofphp/php-cs-fixer#2.0`

### Added

* New method `ContentType::charsets()` to define the available charsets and negotiate the `Accept-Charset` header.

### Fixed

* Only text-based formats (html, text, css, etc) adds the `charset=[charset]` sufix to `Content-Type` header.

## [0.2.0] - 2016-11-22

### Added

* New `ContentType::noSniff()` option to add the `X-Content-Type-Options: nosniff` header (enabled by default)

### Changed

* Updated to `http-interop/http-middleware#0.3`

### Fixed

* *ContentEncoding* middleware removes the `Accept-Encoding` header if it does not match with any available option.

## 0.1.0 - 2016-10-01

First version

[0.3.1]: https://github.com/middlewares/negotiation/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/middlewares/negotiation/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/negotiation/compare/v0.1.0...v0.2.0
