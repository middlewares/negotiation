# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## NEXT

### Changed

* Updated tests
* Updated to `http-interop/http-middleware#0.4`
* Updated `friendsofphp/php-cs-fixer#2.0`

## 0.2.0 - 2016-11-22

### Added

* New `ContentType::noSniff()` option to add the `X-Content-Type-Options: nosniff` header (enabled by default)

### Changed

* Updated to `http-interop/http-middleware#0.3`

### Fixed

* *ContentEncoding* middleware removes the `Accept-Encoding` header if it does not match with any available option.

## 0.1.0 - 2016-10-01

First version
