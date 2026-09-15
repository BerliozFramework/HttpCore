# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Display response status, reason phrase, protocol and headers in the debug console's HTTP / Router section
- HTTP Core refreshes the router server-parameter context before matching each request, keeping route and asset URLs consistent with the supplied PSR-7 request even when URI rewriting is disabled
- Optional `ForwardedPrefixMiddleware`, applied last in the pipeline when both `berlioz.router.rewriteRequestUri` and `X-Forwarded-Prefix` handling are enabled, rewrites the current request URI with the reverse-proxy prefix so URLs derived from `getUri()` are correctly prefixed; the resolved prefix is also exposed via the `berlioz.forwarded_prefix` request attribute
- `berlioz.router.rewriteRequestUri` defaults to `false` in v3, preserving the internal request path unless rewriting is explicitly enabled
- `HttpApp::setRequest()` to update the application-wide server request
- Request rewriting uses a processing attribute to avoid applying the prefix twice to the same rewritten request, while preserving internal paths that overlap the proxy mount

### Security

- The reverse-proxy prefix is only applied when `REMOTE_ADDR` matches the router's trusted proxies (inherited from `berlioz.proxies.trusted` unless explicitly overridden); HTTP Core injects the concrete router's `ForwardedPrefixResolver` into the middleware to share the same validation and effective options

### Deprecated

- Handling a valid trusted forwarded prefix without request URI rewriting now emits `E_USER_DEPRECATED`; enable `berlioz.router.rewriteRequestUri` to adopt the mandatory v4 behavior. Requests without a valid trusted prefix do not emit this deprecation

### Fixed

- Log caught HTTP server exceptions and error-handler failures to the configured PHP error log, even when debug is disabled

## [3.2.1] - 2026-09-11

### Security

- Prevent debug console access-control bypass through case variants and alternate routes to the debug controller

## [3.2.0] - 2026-07-02

### Added

- Copy-to-clipboard buttons on debug console code blocks and values
- "Clear all caches" button in debug console cache page (internal cache, OPcache and cache directories)
- Cache clear shortcuts on the debug console dashboard (clear internal cache or all caches)
- Support for inline modal content in the debug console via `data-content` on `data-toggle="detail"` triggers (in addition to remote `data-target`)
- Display the number of open PHP resources in the debug console environment page

### Changed

- Use a more meaningful cache icon in the debug console (menu and dashboard)
- Use a distinct icon for the environment section in the debug console menu (avoid clash with Hector ORM section)
- Debug console cache-clearing actions now require `POST` (cache page and dashboard buttons are forms instead of links)

### Fixed

- Round load average values in debug performances page to avoid excessive decimals
- Re-initialize debug console JS components (copy buttons, tooltips, syntax highlighting) on content loaded into modals (remote or inline)
- Prevent `TypeError` ("Cannot read properties of null") in debug console when repeatedly clicking copy buttons, caused by accumulated `hidden.bs.tooltip` listeners disposing the tooltip twice

### Security

- Gate the entire `/_console` debug surface (including `phpinfo`) behind debug-enabled + client IP allow-list via a new `DebugConsoleMiddleware`; non-allowed requests get a 404
- Require `POST` for cache-clearing actions, removing the unauthenticated GET trigger

## [3.1.1] - 2026-06-10

_No changes in this release._

## [3.1.0] - 2026-05-18

### Fixed

- Fix `RouterBuilder::createRoutesFromArray()` unsetting wrong key (`children` instead of `routes`), breaking nested config routes
- Fix `MaintenanceMiddleware` ignoring `start`/`end` dates of scheduled maintenance windows
- Fix `ControllerHandler` silently returning empty 200 response when `json_encode()` fails
- Fix middlewares accumulating on repeated `HttpApp::handle()` calls by resetting before adding
- Fix non-exhaustive `match` for font extensions in `DebugController::distFiles()` causing `UnhandledMatchError`
- Fix `ErrorHandler::fallback()` using `??` instead of `?:` for status code fallback, allowing invalid code `0`

## [3.0.0] - 2026-02-19

The version 3.0.0 marks the transition of the Berlioz Framework to a **Monorepo** structure.

Why this move?

- **Consistency:** Ensures all Berlioz components are tested and released together, preventing version mismatches.
- **Maintenance:** Simplifies the management of issues and pull requests by centralizing them in one place.
- **Better CI/CD:** Global testing ensures that a change in one package never breaks another.
