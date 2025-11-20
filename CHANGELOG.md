# Changelog

All notable changes to this project will be documented in this file

## [1.0.0-alpha.15] - 2025-11-20

### Added

- Tracking lesson progress
- Give access to course on woocommerce purchase.

### Fixes

- Fixed issue where saving a course would overwrite lesson content.
- Lesson filtering.
- Admin table list tags.

### Changes

- Better type safety.
- Refactored the sidebar to split into separate functions.

## [1.0.0-alpha.14] - 2025-11-14

### Added

- Course access structures. Still yet to be applied through a purchase or similar. You can give access throught the `User_Access {}` class.

### Fixes

- Woo product sync.
- Fix general css issues.

## [1.0.0-alpha.13] - 2025-11-11

### Added

- Synchronising course tags with woocommerce tags.

### Fixes

- Add product menu order to JS object, when editing course.

## [1.0.0-alpha.12] - 2025-11-10

### Fixes

- fix: Admin CSS header z-index
- Fix global and local JS types in `global.d.ts` and `settings.svelte.js`
- Create product button at the button, so that it actually creates a new product.

### General

- Moved woo getter and setter into a seperate Woo file `WooCommerce/WC`.

## [1.0.0-alpha.11] - 2025-11-07

### Added

- feat: basic user access
- This change log file

### Fixes

- fix: fetch post admin list
- fix: general templating
- fix: general css

### Changes

- Remove excessive usage of mardown horizontal rule.

## [1.0.0-alpha.10] - 2025-11-05

### Fixes

- fix: course pagination statuses

### Changes

- general code cleanup

## [1.0.0-alpha.9] - 2025-11-05

### Fixes

- fix: course admin pagination
- fix: general course fixes

### Changes

- refactor: Fronted course js

## [1.0.0-alpha.8] - 2025-11-04

### Added

- feat: change course slug

### Changes

- cleanup standard template

## [1.0.0-alpha.7] - 2025-11-03

### Fixes

- fix: update check
- fix: details
- fix: vite config
- fix: datepicker
- fix: general css

## [1.0.0-alpha.6] - 2025-10-22

### Added

- feat: launch modal editor
- feat: modal lesson traversal

## [1.0.0-alpha.5] - 2025-10-20

### Fixes

- fix: update url

## [1.0.0-alpha.4] - 2025-10-20

### Added

- feat: update from remote (GH)

### Fixes

- fix: standard template
- fix: Elementor constant
- fix: css
- fix: template sort order

## [1.0.0-alpha.3] - 2025-10-17

### Fixes

- fix: Elementor compatibility
- fix: async lesson view

## [1.0.0-alpha.2] - 2025-10-16

### Fixes

- fix: API get elementor lesson

## [1.0.0-alpha.1] - 2025-10-16

### Fixes

- fix: moved from short tags to full `<?php...?>`.

## [1.0.0-alpha.0] - 2025-10-16 (tagged "Alpha" on GH)

### Added

- Added core functionality:
    - Autoloader.
    - Plugin instance.
    - Post types.
    - Admin views.
    - Lesson database table.
    - Randflake ID creation.
    - Svelte app.
    - Simple frontend view.
- Added MIT license.
