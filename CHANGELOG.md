# Changelog

This changelog covers **only the two most recent published GitHub releases** of `mod_topicchoice`. Each entry is based on the actual differences between the published tag and the immediately preceding **published release** tag. Subsequent commits on `master` are not included.

## [1.1.6] - 2026-10-15

Published: 2026-10-15 21:01:21 UTC.

Comparison: [1.1.5...1.1.6](https://github.com/EduardoKrausME/moodle-mod_topicchoice/compare/1.1.5...1.1.6)

### Changed: plugin icon assets

- Added the plugin's PNG icon at `pix/icon.png`.
- Renamed `pix/icon.svg` to `pix/monologo.svg`. The SVG content itself was unchanged by the published-tag comparison.

### Release metadata and scope

- Updated `version.php` from plugin version `2026101500` / release `1.1.5` to `2026101501` / release `1.1.6`.
- The published-tag comparison contains two commits and changes only the icon assets and `version.php`.
- No activity implementation, question/option handling, user interface logic or other runtime source changes are present in this release diff. The minimum Moodle build remains `2024100700`.

## [1.1.5] - 2026-10-15

Published: 2026-10-15 18:47:57 UTC.

Comparison: [1.1.4...1.1.5](https://github.com/EduardoKrausME/moodle-mod_topicchoice/compare/1.1.4...1.1.5)

### Changed: continuous integration services

- Updated the PostgreSQL service image used by the GitHub Actions CI workflow from `postgres:16` to **`postgres:17`**.
- Updated the MariaDB CI service image from `mariadb:10` to **`mariadb:11.4`**.
- Replaced the previous MariaDB empty-root-password settings with `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1`.
- Replaced the MariaDB readiness check `mysqladmin ping` with `healthcheck.sh --connect --innodb_initialized`, changing the health-check retry count from three to five.

### Release metadata and scope

- Updated `version.php` from numeric version `2026101200` / release `1.1.4` to `2026101500` / release `1.1.5`.
- The published-tag comparison contains four commits and changes **only** `.github/workflows/ci.yml` and `version.php`. It does not contain changes to Topic Choice runtime functionality or to the minimum required Moodle build (`2024100700`).
- CI database image updates reflect test-environment configuration and do not independently establish new production database requirements.

---

Entries are grounded exclusively in published Git tag comparisons. They do not include later `master` commits or imply that tests or deployments were executed when this changelog was prepared.
