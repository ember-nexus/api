# Ember Nexus: Knowledge Graph API

![GitHub License](https://img.shields.io/badge/license-Source%20First%20License%201.1-blue)
[![Docker Pulls](https://img.shields.io/docker/pulls/embernexus/api?logo=docker&label=Docker%20pulls&color=%232496ed)](https://hub.docker.com/r/embernexus/api)
[![Docker Image Version](https://img.shields.io/docker/v/embernexus/api?sort=semver)](https://hub.docker.com/r/embernexus/api)
[![Demo API](https://img.shields.io/website?url=https%3A%2F%2Freference-dataset.ember-nexus.dev%2F&label=Demo%20API)](https://reference-dataset.ember-nexus.dev/)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/ember-nexus/api/ci-test.yml?label=CI)
[![Discord](https://img.shields.io/discord/1135243882360221787?logo=discord&label=Discord&color=%235865f2)](https://discord.gg/qbQFBrJrRC)

Ember Nexus is a knowledge graph API, which means that it is specialized in storing and retrieving your data.  
It can be used as an alternative to Obsidian, Notion, your companies ERP or content management systems or as a blank
database for your own projects. As long as you need to store data and are interested in easy to use cutting edge
features - Ember Nexus will be useful for you! :D

## How is Ember Nexus different to other solutions?

Ember Nexus is built on three principles:

- Be easy to use.
- Be secure.
- Expose the advantages and modern features of its internal databases.

The "secret sauce" is the combination of databases Ember Nexus uses:

- **Neo4j**: A graph database, which stores relationships and core properties of your data elements.
- **MongoDB**: A document database, stores more complex properties which Neo4j can not handle itself.
- **Elasticsearch**: A search database, used for full text search, vector search and more.
- **S3 compatible object store**: Used to store binary files, up to 1 TB by default.

Instead of using multiple specialized databases yourself, simply use Ember Nexus, and benefit of the exposed features
and without having to worry about data consistency, security aspects and more.

It just works :D

## What can you find where?

- [GitHub Repository](https://github.com/ember-nexus/api): Keep up to date with new releases, post issues and more.
- [API Documentation](https://ember-nexus.github.io/api/#/): For everyone who wants to develop own apps on top of
  Ember Nexus.
- [Docker Hub](https://hub.docker.com/r/embernexus/api): Ready to use container images for AMD and ARM architectures.
- [Demo API](https://reference-dataset.ember-nexus.dev/): Instance which can be used for quick testing. Is being reset
  every day, and uses the [reference dataset](https://github.com/ember-nexus/reference-dataset) as its data, which
  includes eternal tokens.

## Is there a graphical user interface (GUI)?

It is currently being developed, but not yet ready to be used.
