# Changelog
All notable changes to BG Web Dynamics Tag Manager will be documented in this file.

## [2.1.6] - 2026-01-12

### Fixed
- added special character entities for less than and greater than signs in plugin description

## [2.1.2 - 2.1.5] - 2026-01-12

### Fixed
- Changed how auto updates work. Should see new releases immediatly

## [2.1.1] - 2026-01-12

### Changed
- worked on setting up auto updates

## [2.1.0] - 2026-01-12

### Added

-Automatic updates via GitHub releases
-Plugin Update Checker library integration
- Version constants for better version management
- Input validation for GTM Container ID format
- Admin notice when GTM ID is not configured
- Dismissible admin notices with AJAX handler
- Activation, deactivation, and uninstall hooks
- Enhanced settings page with version info
- Text domain for internationalization support

### Changed

-Improved settings page layout with information panels
-Version number format to semantic versioning (2.1.0)

### Fixed

- Typo in Salient theme hook (was nectar_hook_after_body_ts, corrected to nectar_hook_after_body_open)