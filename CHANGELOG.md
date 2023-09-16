# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Added
- Add feature test for GET `/token` endpoint
- Add feature test for POST `/token` endpoint
- Add feature test for DELETE `/token` endpoint
- Add new type of feature tests which check that the examples in the documentation are up-to-date.
### Changed
- Increase reference dataset version to 0.0.8.

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
