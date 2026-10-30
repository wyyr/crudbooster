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

- Require PHP 8.2 or later and update Composer constraints for Laravel 12 and 13.
- Move package controllers into a dedicated namespace and update route registration.
- Update image handling to Intervention Image 3 and remove the Doctrine DBAL dependency.
- Publish the package seeder to `database/seeders`.
- Simplify the installation guide and document the default admin credentials.
- **Breaking:** Controller namespaces and names have changed. Applications that reference or extend the previous package controller classes may need to update those references.

### Fixed

- Prevent ambiguous class resolution by excluding template files from Composer's classmap.
- Align 404 and data-modal asset URLs with the published `public/crudbooster` directory.
- Fix the Select2 form component and the published `CBHook` controller.
- Use parameterized SQL strings for column metadata queries to avoid PDO type errors.
- Provide a default form command so the profile page can render when older published controllers omit it.
- Configure CRUDBooster email settings through Laravel's named mailer configuration.
- Avoid running package seeders automatically during unrelated Artisan `db:seed` commands.

### Security

- Stop creating `public/vendor` with world-writable permissions during installation and updates.

This unreleased set includes breaking changes and should be released under a new major version according to SemVer. Do not treat it as a release until a version and date are assigned and the corresponding tag is published.
