# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## 0.2.0 - 2016-11-22

### Added

* New `ContentType::noSniff()` option to add the `X-Content-Type-Options: nosniff` header (enabled by default)

### Changed

* Updated to `http-interop/http-middleware#0.3`

## 0.1.1 - NEXT

### Fixed

* Remove the `Content-Encoding` header if it doesn't match with the available values

## 0.1.0 - 2016-10-01

First version
