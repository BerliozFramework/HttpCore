# Change Log

All notable changes to this project will be documented in this file. This project adheres
to [Semantic Versioning] (http://semver.org/). For change log format,
use [Keep a Changelog] (http://keepachangelog.com/).

## [2.5.0] - 2025-03-14

### Changed

- PHP 8.4 compatibility

## [2.4.0] - 2024-11-04

### Added

- `RouterHelperTrait::finalize_path()` helper function to add prefix path

### Changed

- Bump library `berlioz/router` to ^2.5
- JavaScript dependencies update
- Console and toolbar are compatibles with a `X-Forwarded-Prefix` header

### Fixed

- Toolbar background
- Default config

## [2.3.0] - 2024-11-04

### Changed

- Bump library `berlioz/core` to ^2.4
- `RouterHelperTrait::path()` accepts `RouteInterface` or string instead of only a string
- New options: "Berlioz.router" to give parameters to the Router

## [2.2.7] - 2024-10-04

### Fixed

- `DebugController` now opens files read-only

## [2.2.6] - 2024-08-29

### Fixed

- Deprecated use of spaceless filter on Twig

## [2.2.5] - 2023-05-03

### Fixed

- Debug toolbar background on dark mode

## [2.2.4] - 2022-11-04

### Fixed

- Order of middlewares called

## [2.2.3] - 2022-10-25

### Fixed

- Seek on non seekable stream

## [2.2.2] - 2022-09-08

### Fixed

- Fix display of server request in debug console

## [2.2.1] - 2022-03-24

### Fixed

- Fix exception not added to debug from middlewares

## [2.2.0] - 2022-03-23

### Changed

- Bump package `berlioz/core` to ^2.2
- Bump package `berlioz/twig-package` to ^2.1
- `Route` service is now nullable

## [2.1.0] - 2022-01-13

### Added

- New method `DefaultErrorHandler::getTemplateName()` to facilitate extends
- Add route name for berlioz console dist files
- Tests for `ServiceProvider`
- Add route as service

### Changed

- Bump package `berlioz/core` to 2.1

### Fixed

- Default error http handler class in config
- Middlewares not ordered

## [2.0.0] - 2021-09-09

### Added

- New method `AppProfile::getEnv(): string`

## [2.0.0-beta2] - 2021-07-07

### Changed

- Replace parameter of `HttpApp` inflector by object instead of alias

### Removed

- Remove service container inflector of `HttpApp` from service provider

### Fixed

- Fix display of events in debug console
- Fix display of activities debug with no duration

## [2.0.0-beta1] - 2021-06-08

### Added

- Implementation of middlewares and handlers (`psr/http-server-middleware`)
- Helpers to use in services
- `Maintenance` service and associate middleware for best management of your website maintenances

### Changed

- Refactoring
- Namespace move from `Berlioz\HttpCore` to `Berlioz\Http\Core`
- Usage of PHP 8 attributes for routes
- Bump minimal compatibility to PHP 8
- Upgrade `berlioz/core` to 2.x
- Upgrade `berlioz/http-message` to 2.x
- Upgrade `berlioz/router` to 2.x
- Upgrade `berlioz/twig-package` to 2.x
- Improvement of debug console
- Debug console in dark mode

### Removed

- Magic methods `__b_pre` and `_b_post`

## [1.1.0] - 2020-11-05

### Added

- PHP 8 compatibility in `composer.json`

### Changed

- psr/http-server-handler required version in `composer.json`
- Update NPM dependencies

## [1.0.3] - 2020-11-05

### Fixed

- Composer provide rule for package `psr/http-server-handler-implementation`

## [1.0.2] - 2020-09-11

### Changed

- Display of config in debug console fixed

## [1.0.1] - 2020-09-02

### Changed

- Print response by sequence to prevent exhausted memory error
- Update NPM dependencies

## [1.0.0] - 2020-05-29

First version