# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Changed
- Upgrade to Alpine 3.22, closes #389.

## 0.1.22 - 2025-05-21
### Changed
- Upgrade PHP to 8.4.7, closes [#383].
- Upgrade PHP to 8.4.6, closes [#380].
- Fixed pecl mongodb to version 1.21.0 due to breaking changes in version 2.x.

### Fixed
- Fix bug in CI check upstream dependency NGINX Unit, closes [#384].

## 0.1.21 - 2025-03-22
### Added
- Add `CODEOWNERS` file, closes [#331].

### Changed
- Upgrade PHP to 8.4.5, closes [#377].

### Fixed
- Fix post release task regarding Originstamp certificates, closes [#373]

## 0.1.20 - 2025-03-05
### Added
- Increase general test coverage to 28.2% (lines) and 54.1% (classes), increase mutant score indicator for covered code
  to 91%, closes [#364]. Feature tests do not count towards code coverage.
- Add Docker build cache to CI/CD, closes [#352].
- Docker images used in CI/CD are getting rebuilt weekly, closes [#369].

### Changed
- Health state of production tests are getting printed in CI/CD, closes [#362].
- Mutant tests are now being executed on `buildjet-4vcpu-ubuntu-2204` to increase CI/CD speed.
- Enable Originstamp support, closes [#328].
- Upgrade upstream dependency NGINX Unit to 1.34.2, closes [#371].
- Update dependencies, related to [#371].

## 0.1.19 - 2025-02-22
### Changed
- Upgrade upstream dependency NGINX Unit to 1.34.1, closes [#356].
- Change method declaration from `self` to `static`, general improvement.

### Fixed
- Fix bad release 0.1.18 by upgrading `neo4j-php/Bolt` to version 7.2.3, closes [#361].

## 0.1.18 - 2025-02-16
### Changed
- Upgrade PHP to 8.4.4, closes [#348].
- Update dependencies, related to [#348].
- Update code style, related to [#348].
- Remove dev dependencies `roave/no-leaks` and `boesing/psalm-plugin-stringf` due to conflict with newer PHP versions,
  related to [#348].

## 0.1.17 - 2024-12-10
### Changed
- Upgrade to Alpine 3.21, closes [#350].

## 0.1.16 - 2024-11-06
### Added
- Improve error handling when request contains malformed JSON, closes [#343].

### Changed
- Upgrade PHP to 8.3.13, closes [#341].
- Change license from AGPL 3 to Source First License 1.1, closes [#345].

## 0.1.15 - 2024-10-12
### Changed
- Increase reference dataset version to 0.0.25.

### Fixed
- Fix pagination bug with the get index endpoint, where the total number of nodes was limited to the page size, and
  therefore always showed zero additional pages. Node count is now calculated correctly, closes [#338].

## 0.1.14 - 2024-10-07
### Added
- Add HTTP header `Access-Control-Expose-Headers`, closes [#336].

## 0.1.13 - 2024-10-02
### Changed
- Upgrade PHP to 8.3.12, skipping 8.3.11, closes [#332].
- Upgrade upstream dependency NGINX Unit to 1.33.0, closes [#333].

## 0.1.12 - 2024-08-04
### Changed
- Update versions of Neo4j used inside the CI, closes [#326].
- Upgrade PHP to 8.3.10, closes [#329].

## 0.1.11 - 2024-07-08
### Changed
- Upgrade PHP to 8.3.9, closes [#324].
- Upgrade Xdebug to 3.3.2.
- Remove deprecated php stan configuration.

## 0.1.10 - 2024-06-23
### Changed
- Upgrade Symfony and other PHP dependencies, closes [#322].

## 0.1.9 - 2024-06-09
### Changed
- Rework README.md, add shield labels.
- Fix LICENSE file.
- Upgrade PHP to 8.3.8, closes [#321].

## 0.1.8 - 2024-05-25
### Changed
- Upgrade to Alpine 3.20, closes [#315].
- Fix GetInstanceConfigurationTest and documentation for GetInstanceConfigurationController.
- Fix labels used in automatic GitHub issues, closes [#314].
- Fix CI version replace command, closes [#318].

## 0.1.7 - 2024-05-17
### Added
- Add `requestId` property to all logs to enable finding all logs made during a single request, closes [#306].

### Changed
- Change naming of internal variables to replace `Uuid` with `Id`, because the variables are already type hinted and
  `Id` emerged as a better convention in the ecosystem. Closes [#292].
- Server exceptions with the HTTP status code 500 will now always log their message.
- Upgrade PHP to 8.3.7, closes [#310].
- Update PHP dependencies, related to [#310].
- Feature tests use production docker build in CI, closes [#297].

### Fixed
- Fix wrong license identifier in Dockerfile, closes [#302].

## 0.1.6 - 2024-04-22
### Added
- Introduce simpler request bodies for endpoints `POST /change-password`, `POST /token` and `POST /register`. Old
  behavior can be disabled with the feature flag `featureFlag.280_OldUniqueUserIdentifierDisabled`. Closes [#280].
- Add first unit tests for controller, related to [#280].
- Enable PHP's strict type, closes [#284].
- Enable PHP Opcache extension, closes [#294].
- Add `brianium/paratest` as a parallel PHP Unit runtime, closes [#63].

### Changed
- In the case of server errors during requests in production environments, i.e. HTTP status code 500, the error message
  will be printed to the log.
- Update PHP dependencies.
- Upgrade remaining GitHub actions, required due to deprecation of Node 16, closes [#240].
- Upgrade PHP Unit to major version 10, related to [#63].

### Deprecated
- Original request body scheme of endpoints `POST /change-password`, `POST /token` and `POST /register` is deprecated,
  will be removed in version 0.2.0, part of [#280].

### Removed
- Remove leak test due to incompatibility with PHP Unit 10+, related to [#63].

## 0.1.5 - 2024-04-12
### Changed
- Upgrade upstream dependency NGINX Unit to 1.32.1, closes [#274].
- Upgrade PHP to 8.3.6, closes [#278] and [#272].

## 0.1.4 - 2024-03-10
### Added
- Add support for the `If-Match`-header, closes [#232].
- Add support for the `If-None-Match`-header, closes [#233].
- Add links in error endpoints to http.dev explaining the current error status code, closes [#267].
- Add documentation for the GET `error/500/internal-server-error` endpoint, closes [#251].

### Changed
- Increase reference dataset version to 0.0.24.
- Refactor parts of the Etag generation in order to make implementation of [#232] and [#233] easier.

### Fixed
- Fix controller example tests for put element and patch element endpoints, uncovered in [#233].

## 0.1.3 - 2024-03-04
### Changed
- Update NGINX Unit build steps in Dockerfile, replace Mercurial repo with new GitHub repo. Closes [#261].
- Streamline documentation, closes [#263].

### Removed
- Remove supervisord and its dependencies like Python. Logs are directly sent to stderr. Closes [#244].

## 0.1.2 - 2024-02-28
### Changed
- Upgrade most GitHub actions, required due to deprecation of Node 16, relates to [#240].
- Increase feature test timeout from 10 to 15 minutes.
- Upgrade PHP to 8.3.3, closes [#253].
- Upgrade upstream dependency NGINX Unit to 1.32.0, closes [#254].
- Upgrade dependencies, closes [#257].
- Add environment variable `COMPOSER_ALLOW_SUPERUSER=1` in release action to fix image build, fixes [#259].

## 0.1.1 - 2024-01-27
### Added
- Add `/.well-known/security.txt` endpoint, documentation for it and (disabled) file check on container startup which
  will crash the container intentionally if the file is missing. Check will be enabled with the release of version
  0.2.0, see also [#225]. Closes issue [#131].
- Add HTTP header `Etag` to GET responses of element and collection endpoints, closes [#88].
- Add `beste/psr-testlogger` for creating easier unit tests, related to [#88].

### Changed
- Constants are changed to contain type declarations, closes [#211].
- Remove timeout from PHP-tasks, closes [#220]. CI timeouts still apply.
- Upgrade PHP to 8.3.2, closes [#236]. Skips interim PHP version 8.3.1, which was tracked in [#223].
- Enable `composer mess` in CI due to fixed upstream issue, closes [#203].
- Enable PHP CS Fixer rule `self_accessor`, which prefers `self` over the element's class name.
- Change value of HTTP header `X-Powered-By` to `Ember Nexus API`.
- Move configuration files and scripts out of root folder, closes [#237].
- Replace flysystem AWS with `async-aws/s3`, closes [#235].
- Upgrade dependencies.

### Fixed
- Fix monospace font in documentation (graph renderings), closes [#152].
- Fix bug where updated timestamps where not updated when they already where set to a value.

## 0.1.0 - 2023-12-16
### Added
- Add documentation and automatic documentation tests for endpoint DELETE `/token`, closes [#208].
- Add command `token:revoke`, closes [#59].

### Changed
- **Switch license to AGPL-3.0-only, closes [#215].**
- Remove commented and unused code, configuration etc., closes [#168].
- Remove test CI triggers "pull_request" and "fork", closes [#216].
- Rename command `user:token:create` to `token:create`, related to [#59].
- Tokens cached by Redis now automatically expire within 30 minutes or as their expiration date is reached, part of [#59].

### Fixed
- Deleting tokens now deletes the correct token from Redis, fixes [#186].
- Add missing feature tests, related to [#168].
- Fix bug where datetime-similar properties could not be correctly returned to the user, uncovered during [#59].

## 0.0.38 - 2023-12-08
### Added
- Add documentation and automatic example generation for healthcheck command, closes [#184].
- Add 401 error case for the GET /me endpoint, closes [#190].
- Add parameters `page` and `pageSize` to documentation of collection endpoints, closes [#189].

### Changed
- Rename `_PartialUnifiedCollection` to `_PartialElementCollection`, closes [#187].
- All CI tasks are configured to have timeouts, closes [#201].
- Upgrade dependencies, e.g. Symfony to 7.0 and intermediate Neo4j PHP dependencies as far as possible.
- Deactivate `composer mess` in CI due to upstream issue, tracked in [#203].
- Upgrade to Alpine 3.19, closes [#205].

### Fixed
- Fix logic to detect readable relations between user and group elements, fixes [#188].
- Uncommented and fixed assertions in feature tests, related to [#188].

## 0.0.37 - 2023-11-24
### Changed
- Move code to expire deleted tokens from Redis into separate event listener, closes [#111].
- Upgrade PHP to 8.3.0, closes [#191].
- Add parameter `--ignore-platform-req=php` to CI jobs.
- Add env variable `PHP_CS_FIXER_IGNORE_ENV=1` in cs:list CI job.

## 0.0.36 - 2023-10-29
### Added
- Add explicit feature tests for parents, children and related endpoints to (not) include non-OWNS-relations, related
  to [#163].

### Changed
- Update readme and docker image labels.
- Increase reference dataset version to 0.0.16, skipped 0.0.15 and 0.0.14 due to erroneous releases.
- Upgrade PHP to 8.2.12, closes [#176].
- Upgrade PHP dependencies and upgrade feature tests to use Neo4j 5.13.

### Fixed
- Fix index endpoint to include elements which are directly owned or are accessible. Fixes [#163].

## 0.0.35 - 2023-10-26

## 0.0.34 - 2023-10-26
### Added
- New releases include two new labels, 'major.minor' and 'major'. Closes [#151].
- Release action will now update version string in documentation automatically.

## 0.0.33 - 2023-10-25
### Added
- Add diverse unit tests.
- Add timeout to some GitHub Action jobs.
- Command examples are now automatically checked.
- More commands are documented.
- Add git and ssh to development Docker image.
- Add GET /me endpoint and associated documentation, examples and tests. Closes [#142].
- Add GET /token endpoint (again), this time finished. Closes [#72].

### Changed
- Rename term "session" to "token", closes [#145].
- Finalise documentation for the POST /token endpoint, closes [#49].
- Implement changes from [#161], closes it.
- Improve documentation, closes [#162].
- Elements in endpoint get index are now sorted via the element's id.
- Relations in collection endpoints are sorted by their id as well.
- Upgrade upstream dependency NGINX Unit to 1.31.1, closes [#171].

### Fixed
- Property normalization is now standardized across endpoints, and uses events. Closes [#166].
- Implement changes from [#161].
- Fix id and link properties in parents, children and related endpoints are wrong, closes [#164].

## 0.0.32 - 2023-09-30
### Added
- Add POST /change-password endpoint, including tests and documentation. Closes [#121].

### Changed
- Increase reference dataset version to 0.0.11, skipped 0.0.10.
- Change default anonymous user to `2d376349-c5e2-42c8-8ce0-d6f525256cf7`.
- Upgrade PHP to 8.2.11.

### Removed
- Test command, closes [#138].

### Fixed
- Backup will only load JSON files, before that every file was tried to be loaded.

## 0.0.31 - 2023-09-26
### Added
- Add healthcheck command.
- Add healthcheck command in Docker image, closes [#106].
- Add feature test to test that tokens not owned by any user are invalid, closes [#118].
- Add feature test to test that tokens owned by two or more users are invalid as well, closes [#118].

### Changed
- Increase reference dataset version to 0.0.9.
- Upgrade MongoDB version from 6.x to 7.x in Docker Compose examples.
- Move `guzzlehttp/guzzle` to production dependency, as it is required by healthcheck.
- Change test CI runner of feature test jobs to ubuntu-latest in order to reduce CI cost.
- Feature test CI is back to using 4 vCPUs, although one of the 4 test scenarios is removed to save costs.

## 0.0.30 - 2023-09-20

## 0.0.29 - 2023-09-17
### Added
- Add new type of feature tests which check that the examples in the documentation are up-to-date, closes [#116].
- Add WebDAV HTTP methods to the allowed HTTP method lists.
- Add 'Example Generation Test' to CI.
- Enable phpstan rule for enforcing [safe functions](https://github.com/thecodingmachine/safe), closes [#55].
- Add PHP Mess Detector, closes [#114].
- Set configuration framework.disallow_search_engine_index explicitly to false to disable the HTTP tag X-Robots-Tag,
  closes [#123].

### Changed
- NGINX Unit strips all cookie headers from responses, closes [#124].

### Fixed
- Software version is now correctly set in final Docker images, fixes [#115].

## 0.0.28 - 2023-09-14
### Added
- Add feature test for GET `/token` endpoint
- Add feature test for POST `/token` endpoint
- Add feature test for DELETE `/token` endpoint

### Changed
- Increase reference dataset version to 0.0.8.
- **Switch license to GPL-3.0-only.**

## 0.0.27 - 2023-09-02
### Changed
- Upgrade upstream dependencies PHP to 8.2.10 and NGINX Unit to 1.31.
- Unknown routes are handled by a custom problem handler (404).

## 0.0.26 - 2023-09-02
### Added
- Add assertions to test identical headers in HEAD and GET requests automatically.

### Changed
- Hotfix.

## 0.0.25 - 2023-08-31
### Added
- Add CI workflow to check for upstream Alpine updated.
- Add supervisord to combine all relevant logs and publish to docker logs.
- Add endpoint DELETE `/token`.

### Changed
- **Note**: This release is broken.
- Improve documentation.
- Change PHP CS ruleset such that global objects are always imported.
- Rework exception and logging.

## 0.0.24 - 2023-08-21
### Changed
- Improve CI/CD.
- Upgrade PHP to 8.2.9.

## 0.0.23 - 2023-08-16
### Changed
- Change CI to support multi arch Docker image builds.

## 0.0.22 - 2023-08-15
### Fixed
- Fix labels in new compressed production Docker image.

## 0.0.21 - 2023-08-15
### Changed
- Optimize Docker image layer count.

## 0.0.20 - 2023-08-15
### Added
- Add POST '/token' endpoint.
- Add expiration date to tokens.

### Changed
- Reword application configuration, via internal Symfony bundle.
- Switch datetime format from temporary string type to datetime.
- Change default JSON encoding options.
- Replaced configuration, documentation WIP.
- Rework event system.

### Fixed
- Fix [#22].

## 0.0.19 - 2023-08-10
### Added
- Add `/instance-configuration` endpoint for retrieving instance specific configurations.
- Add `charset=utf-8` property in response headers.

### Changed
- Change theme colors in documentation, update favicon.
- Update dependencies.
- Enhance API endpoint documentation.
    - Get element endpoint.
    - Get index endpoint.
- Update docker-compose.yml for local deployment.
- Disable NGINX Unit version in response header, see
  [commit](https://github.com/nginx/unit/commit/1a485fed6a8353ecc09e6c0f050e44c0a2d30419).
- Disabled PHP version in response header.
- Split services.yaml file into smaller ones for easier customization.

## 0.0.18 - 2023-07-20
### Changed
- Upgrade PHP to 8.2.8.

## 0.0.17 - 2023-07-09
### Changed
- Test.

## 0.0.16 - 2023-07-02
### Added
- Add command descriptions.

### Fixed
- Fix phpstan, psalm and code style problems.
- Fix pagination problem in GetChildrenController, GetParentsController, GetRelatedController.

## 0.0.15 - 2023-07-01
### Added
- Add PHP ZIP extension.
- Add backup:fetch command.

### Changed
- Feature tests are now loaded from ember-nexus/reference-dataset directly.

### Fixed
- Fixed NGINX Unit upstream dependency check action, will no longer return empty strings as version numbers.

## 0.0.14 - 2023-06-12
### Added
- Upgrade PHP to version 8.2.7, fixes issue [#8].
- Upgrade NGINX Unit to version 1.30.
- Update "Check upstream dependency - Nginx Unit" action from nginx/unit to unit.

## 0.0.13 - 2023-05-30
### Added
- Add header `Access-Control-Allow-Headers: Authorization` to all responses.

## 0.0.12 - 2023-05-28
### Added
- Improve post search endpoint, documentation.

## 0.0.11 - 2023-05-28
### Added
- Add PHP sockets extension, upgrade PhpAmqpLib.
- Improve post search endpoint, add documentation.

## 0.0.10 - 2023-05-27
### Fixed
- Fix create relation elements.

## 0.0.9 - 2023-05-27
### Added
- Fix ElementPostCreateEventListeners.

## 0.0.8 - 2023-05-26
### Added
- Add PHP intl extension.
- Add support for RabbitMQ.
- Partly refactor of the event system.
- WIP calculate users and groups with search access, store result in Elasticsearch itself.

## 0.0.7 - 2023-05-18
### Added
- Rework PostIndexController, DeleteElementController, PatchElementController and PutElementController.
- Update PHP dependencies.
- Add endpoint feature tests, prefixed with `e`.

## 0.0.6 - 2023-05-15
### Added
- Refactor security tests.
- Add placeholder controllers for WebDAV HTTP methods.
- Add placeholder controllers for file controllers.
- Add PostElementController.
- Refactor security tests of scenario 1.
- Fix table overflow issue in documentation.
- Rework GetChildrenController, GetParentsController, GetRelatedController and PostElementController.

## 0.0.5 - 2023-05-13
### Added
- Database connection strings are now configurable via environment variables.
- Redis is now used.
- Add `user:create` command.
- Add `user:session:create` command.
- Update dependencies.
- Rework several commands.
- Token management now uses Redis.
- Remove Twig from production requirements, as it is not used.
- Add security test cases, part of feature tests.
- Upgrade to PHP 8.2.6.
- Upgrade to Alpine 3.18.
- Remove templates from Dockerfile.

## 0.0.4 - 2023-04-22

## 0.0.3 - 2023-04-21

## 0.0.2 - 2023-04-21

## 0.0.1 - 2023-04-21
### Added
- CI/CD
- Dockerfile for [Nginx Unit](https://unit.nginx.org/)
