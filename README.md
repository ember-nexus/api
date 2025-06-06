# Ember Nexus: Knowledge Graph API

![GitHub License](https://img.shields.io/badge/license-Source%20First%20License%201.1-blue)
[![Docker Pulls](https://img.shields.io/docker/pulls/embernexus/api?logo=docker&label=Docker%20pulls&color=%232496ed)](https://hub.docker.com/r/embernexus/api)
[![Docker Image Version](https://img.shields.io/docker/v/embernexus/api?sort=semver)](https://hub.docker.com/r/embernexus/api)
[![Demo API](https://img.shields.io/website?url=https%3A%2F%2Freference-dataset.ember-nexus.dev%2F&label=Demo%20API)](https://reference-dataset.ember-nexus.dev/)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/ember-nexus/api/ci-test.yml?label=CI)
[![Discord](https://img.shields.io/discord/1135243882360221787?logo=discord&label=Discord&color=%235865f2)](https://discord.gg/qbQFBrJrRC)

Store anything, connect everything, search it all? Sure thing! 😃

Ember Nexus API is a dynamic and versatile REST-API that leverages the power of graphs to provide flexible and secure
data storage and retrieval for data-minded people. It is [source first](https://sourcefirst.com/) and can be hosted on
your own hardware - we currently support AMD and ARM architectures.

## Quick Links

- [Getting Started](https://ember-nexus.github.io/api/#/getting-started/tech-stack)
- [Check out the documentation](https://ember-nexus.github.io/api)
- [Find us on Docker Hub](https://hub.docker.com/r/embernexus/api)
- [Demo API](https://reference-dataset.ember-nexus.dev/)

## Features

Ember Nexus offers a range of cutting-edge features to meet your data storage needs, including:

- **Graph-based data model**: Ember Nexus uses the graph database [Neo4j](https://neo4j.com/) internally, which enables
  you to connect any data element to any other data element and efficiently search long and recursive relations.
- **Access through UUID**: Ember Nexus assigns a UUID to every storable element, making it easy to retrieve and manage
  data. User can provide their own UUIDs as well, although this feature is disabled by default.
- **Near real-time search**: Ember Nexus supports full-text search that is fast and responsive. Most of
  [Elasticsearch](https://www.elastic.co/elasticsearch/)'s features are directly available while not bypassing the
  security model.
- **Secure by default**: User-created data is only visible to the owner by default. Sharing data requires explicit
  rules, which are recursive and optionally limited in access scope and other features.
- **High-quality software**: We invested quite a bit of time to make sure that this software is as stable as possible.
  We employ code linters, unit tests, feature tests, memory leak tests, mutant tests, and more. Most examples in the
  documentation are automatically checked for deprecations.

## Planned Features

See our [milestone](https://github.com/ember-nexus/api/milestones) and
[issue](https://github.com/ember-nexus/api/issues) list on GitHub for planned features.

## When should I use Ember Nexus API?

Ember Nexus is a powerful tool for a range of use cases, including:

- **Private cloud**: Use Ember Nexus to create your own private cloud, similar to Nextcloud.
- **Websites**: Use Ember Nexus as a CMS or blog platform.
- **Data management systems**: Ember Nexus is ideal for building data management systems and archives.
- **Data lakes**: Ember Nexus makes it easy to create data lakes, where you can store and manage large volumes of data.
- **Interface between multiple systems**: Ember Nexus can serve as a bridge between multiple systems, making it easy to
  integrate and manage data across your entire infrastructure.

Experience the power of Ember Nexus for yourself - [try it out today](https://ember-nexus.github.io/api/#/)!
