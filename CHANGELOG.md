# Changelog

## Unreleased

- `composer mutation` now runs `infection --with-uncovered`, so mutation testing
  counts code that tests execute without naming it in `#[Covers]`;
  `infection.json5` gains the template `PublicVisibility`/`ProtectedVisibility`
  switches and default-parameter signature ignore, and runs only the `Unit`
  suite (`testFrameworkOptions`) so the timing-sensitive benchmark cannot fail
  Infection's initial test run. `minMsi` stays 100. No `src/` or test changes.
- **Changed:** PHP 8.6 is supported: the `php` constraint is widened to
  `8.3 - 8.6` (#14). CI runs PHP 8.6 as a regular matrix entry; the
  backward-compatibility job runs on PHP 8.5.
- **Changed:** dev tooling: `vimeo/psalm` `^6.16` → `^7.0` (#13).

## 1.0.1 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

## 1.0.0 — 2026-06-27

- Initial release with `Result`, `Option`, and `UnwrapException`.
