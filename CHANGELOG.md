# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.0.2] - 2026-10-28

### Changed

- The key screens take the page pattern of dboard: the heading carries the create action, the list and the forms sit on a `dboard-surface`, the way back is a link above the title, the form actions stick to the foot of the screen, the main action is in the accent colour. The edit screen is titled by the key, with its number as subtitle.

### Added

- The `MCP key` translation, in the six languages.
- `AddMissingMcpTranslations`, a migration that adds to the `mcp` translation category the keys of the i18n files a base seeded earlier does not hold yet, with their translations.

## [1.0.1] - 2026-10-27

### Changed

- The key admin handlers use `Blackcube\Handler\OutputType`: dboard no longer ships its own copy; `blackcube/handler` is required.

## [1.0.0] - 2026-10-25

Initial release.
