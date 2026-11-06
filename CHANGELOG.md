# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.1.0 — 2026-11-06

First public release, extracted from the iHRMIS application.

### Added

- `FormRenderer` for seven documents: Service Record, leave certification, leave
  application, CTO application, money value, retirement schedule and statement
  of leave, drawn on the bundled templates in `resources/templates`.
- `Config\FormLayout` (templates and page geometry per document),
  `Config\FormOptions`, and one `Config\Layout\*Layout` class per document with
  every legacy coordinate and font size.
- `Document\*` models that carry the data **and** the computations (balances,
  period rows, monetisation) with one repository contract per document and
  array adapters for tests and one-off scripts.
- `Support\Money`, `Support\Dates` and `Support\Text` for the legacy formatting
  rules, including the `days × 0.0478087 × salary` monetisation.
- Leave management: `LeaveApplicationService` (file, approve, disapprove,
  cancel, pending, balances), `LeaveCardService` (earnings, spending,
  forwardages, deletions) and `CtoService` (filing and approval with DTR
  markers), with `Support\LeaveDays`, `Support\LeaveBalanceCalculator` and
  `Config\LeaveTables` plus `Adapter\PdoLeaveStore` / `Adapter\ArrayLeaveStore`.
- Runnable examples: `examples/generate-sample.php` (no database) and
  `examples/write-sample.php` (SQLite schema included).

### Notes

- The `version` field in `composer.json` is intentional: the iHRMIS project
  installs this package through a path repository that needs a `0.1.x` version.
  It must be bumped together with the git tag on every release.
