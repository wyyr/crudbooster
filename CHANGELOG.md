# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Interactive installation seeding prompt and improved seeder detection.
- A title field for admin log entries.
- A Pest integration-test baseline for provider boot, routes, mail settings, and explicit seeding.

### Changed

- **Breaking:** Require PHP `^8.2` and Laravel `^12.69` or `^13.30`; applications on older framework versions must upgrade their platform before updating CRUDBooster.
- **Breaking:** Move package controllers into a dedicated namespace. Update custom references and extensions to use the new controller namespace.
- Update image handling to Intervention Image 3 and remove the Doctrine DBAL dependency.
- Publish the package seeder to `database/seeders`.
- Simplify the installation guide and document the default admin credentials.

### Fixed

- Prevent ambiguous class resolution by excluding template files from Composer's classmap.
- Align 404 and data-modal asset URLs with the published `public/crudbooster` directory.
- Fix the Select2 form component and the published `CBHook` controller.
- Use parameterized SQL strings for column metadata queries to avoid PDO type errors.
- Provide a default form command so the profile page can render when older published controllers omit it.
- Configure CRUDBooster email settings through Laravel's named mailer configuration.
- Avoid running package seeders automatically during unrelated Artisan `db:seed` commands.
- Provide defaults for a new API Generator form, use the SweetAlert2 API for API confirmations, and fix the API documentation row title.

### Security

- Stop creating `public/vendor` with world-writable permissions during installation and updates.
- **Breaking:** Remove the dynamically registered `data-query` and `update-single` routes. Replace integrations that called them with narrowly scoped, authorized application actions.
- **Breaking:** Restrict `data-table` and `modal-data` to fields, display metadata, and conditions configured in `cbInit()`; move custom table/column and filter definitions into the controller configuration.
- Restrict API key management, API generator actions, and Postman export to superadmins.
- **Breaking:** Change API key status, generation/deletion, and generated API deletion from GET to CSRF-protected POST requests. Update custom callers to send POST requests with a valid web CSRF token.
- **Breaking:** Change CRUD, upload-image, settings-file, statistic-component, and notification-read mutations from GET to POST. Rename custom `getDelete*` actions to `postDelete*`, update named routes from `Get...` to `Post...`, and submit valid web CSRF tokens; URL slugs remain unchanged.
- Require superadmin access to delete setting files, restrict upload deletion to controller-configured upload fields, and limit notification reads to global or current-user records.

This unreleased set includes breaking changes and should be released under a new major version according to SemVer. Do not treat it as a release until a version and date are assigned and the corresponding tag is published.
