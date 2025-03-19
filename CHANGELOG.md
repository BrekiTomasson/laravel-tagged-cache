# CHANGELOG

All notable changes to this project will be documented in this file.

The format is looselyy based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v2.0.5] - Update, 2025-03-19

- Changed: Added support for Laravel 12.

## [v2.0.4] - Update, 2025-01-04

- Changed: Added support for Laravel 11.

## [v2.0.3] - Update, 2023-02-22

- Changed: Added support for Laravel 10.

## [v2.0.2] - Deprecation, 2023-01-14

- Changed: Replaced a `${variable}` usage as this is deprecated in PHP 8.2.

## [v2.0.1] - Bugfix, 2022-10-24

- Changed: Made `bootHasTaggedCache` method static as intended.

## [v2.0.0] - Code Overhaul, including breaking changes, 2022-10-24

- Added: New method `flushTaggedCacheOnAttributeUpdate(): array`, optionally overridden by the user.
- Added: New method `getCacheTagIdentifier(): string`, optionally overridden by the user
- Added: Tagged Cache is automatically flushed for model rows that are deleted.
- Changed: Max width of `.md` files is now 120 characters (down from 160) to make them more legible.
- Changed: Refactored code to use inline fully qualified classnames instead of imports.
- Removed: **BREAKING CHANGE** `$cacheTagIdentifier` has been replaced by a method-based implementation.

## [v1.0.0] - Initial Release, 2022-09-17

- Initial functionality in place, releasing as is.
