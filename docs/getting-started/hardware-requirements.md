# Hardware Requirements

Computer Architectures

The Ember Nexus API stack currently supports the following CPU architectures:

| Software        | amd64 | arm64 | riscv64 | ppc64le | s390x | mips64le |
| --------------- | ----- | ----- | ------- | ------- | ----- | -------- |
| **Ember Nexus API** | ✅ <sup>1</sup> | ✅ <sup>2</sup> | 🚧 <sup>3</sup>    | ⛔ <sup>4</sup> | ⛔ <sup>4</sup> | ⛔ <sup>4</sup> |
| Neo4j | ✅ <sup>5</sup> | ✅ <sup>5</sup> | 🚧 <sup>6</sup> | 🚧 <sup>6</sup> | 🚧 <sup>6</sup> | ⛔ <sup>7</sup> |
| MongoDB | ✅ <sup>8</sup> | ✅ <sup>8</sup> | ⛔ <sup>7</sup> | ✅ <sup>9</sup> | ✅ <sup>9</sup> | ⛔ <sup>7</sup> |
| Elasticsearch | ✅ <sup>10</sup> | ✅ <sup>10</sup> | 🚧 <sup>6</sup> | 🚧 <sup>6</sup> | 🚧 <sup>6</sup> | ⛔ <sup>7</sup> |
| Redis | ✅ <sup>11</sup> | ✅ <sup>11</sup> | 🚧 <sup>12</sup> | ✅ <sup>11</sup> | ✅ <sup>11</sup> | ✅ <sup>11</sup> |
| RabbitMQ | ✅ <sup>13</sup> | ✅ <sup>13</sup> | ⛔ <sup>14</sup> | ✅ <sup>13</sup> | ✅ <sup>13</sup> | ⛔ <sup>7</sup> |
| MinIO | ✅ <sup>15</sup> | ✅ <sup>15</sup> | ⛔ <sup>16</sup> | ✅ <sup>15</sup> | ✅ <sup>15</sup> | ✅ <sup>17</sup> |

1: The architecture amd64 is supported by default.  
2: The architecture arm64 is supported since 0.0.23.  
3: The architecture riscv64 will likely be supported once a) computers and CI/CD infrastructure get more available and
b) ecosystem get more stable; see also [PHP](https://github.com/docker-library/php/issues/1279).  
4: No support is planned, although you can ask for it by opening a [GitHub issue](https://github.com/ember-nexus/api/issues).
It likely requires hardware donation or general collaboration.  
5: Neo4j [officially supports](https://neo4j.com/docs/operations-manual/current/installation/requirements/)
amd64 and arm64.  
6: No official support, although Java applications should be able to run on these architectures.  
7: Software is not runnable on this architecture.  
8: MongoDB [officially supports](https://www.mongodb.com/docs/manual/installation/#supported-platforms) amd64 and arm64
in their community and enterprise versions.  
9: MongoDB supports these architectures in
[enterprise-only versions](https://www.mongodb.com/docs/manual/installation/#supported-platforms).  
10: Elasticsearch [officially supports](https://www.elastic.co/support/matrix) amd64 and arm64.  
11: Redis [supports most architectures](https://hub.docker.com/_/redis).  
12: Redis seems to be [experimentally runable](https://github.com/redis/redis/pull/12349) on RISC V.  
13: RabbitMQ supports [most platforms Erlang covers](https://www.rabbitmq.com/platforms.html).  
14: RabbitMQ [might support](https://www.rabbitmq.com/platforms.html) RISC V once it is
[supported by Erlang](https://github.com/erlang/otp/issues/7498).  
15: MinIO [supports](https://min.io/docs/minio/linux/operations/install-deploy-manage/deploy-minio-single-node-multi-drive.html#download-the-minio-server)
amd64, arm64, ppc64le as well as s390x.  
16: MinIO [does not support](https://github.com/minio/minio/pull/17161) RISC V yet, although there is some community
work done.  
17: MinIO [seems to support](https://github.com/minio/minio/blob/adb8be069ee18f5360c2a9dcd22054b113493fec/buildscripts/cross-compile.sh#L12C34-L12C44)
mips64, although it is not available through Docker.

If this information needs to be corrected, please open a [GitHub issue](https://github.com/ember-nexus/api/issues).

## Local and Development Setups

For local and development purposes, the whole stack can be hosted on a single machine with at least 8 GB of RAM,
4+ vCPUs, and 10 GB of SSD storage. However, it is important to note that this setup is not recommended for production
use, as certain services like ElasticSearch can consume so much RAM, that other services are starved. Additionally, the
lack of service distribution means that if one storage layer service crashes, the entire stack becomes unavailable.

A Docker Compose configuration is available [in the local deployment guide](/getting-started/local-deployment).

## Production Setups

For production setups, it's recommended that each service is run in its own compute instance, ideally in a cluster
setup. While using SaaS products is possible, increased network delays can result in increased API response delays as
well. For fast response times, it's recommended to keep all services as close to each other as possible.

The following setups are possible:

### Neo4j

- Self-hosted community edition: At least 1 GB RAM, 1+ vCPU, and 10+ GB storage ([source](https://neo4j.com/docs/operations-manual/current/installation/requirements/#deployment-requirements-hardware)).
- Self-hosted clusters: At least 3 compute instances with 1+ GB RAM, 1+ vCPU, and 10+ GB storage each
  ([requires the enterprise edition](https://neo4j.com/docs/operations-manual/current/clustering/setup/deploy/)).
- SaaS: [Neo4j's AuraDB](https://neo4j.com/cloud/platform/aura-graph-database/) can be used.

Alternatively, other graph databases which support OpenCypher and Bolt 4.4+ can be used, e.g., Memgraph.

### MongoDB

- Self-hosted: At least 4 GB RAM, 1+ vCPU, and 10+ GB storage ([source](https://www.mongodb.com/docs/cloud-manager/tutorial/provisioning-prep/#hardware-and-software)).
- Clusters and SaaS: [MongoDB Atlas](https://www.mongodb.com/atlas/database).

### MinIO

- Self-hosted: At least 1 GB RAM, 1+ vCPU, and 10+ GB storage.
- Clusters: [documentation](https://min.io/docs/minio/linux/operations/install-deploy-manage/deploy-minio-multi-node-multi-drive.html).

Alternatively, other S3-compatible object storage APIs can and should be used if available.

### Elastic Search

- Self-hosted: At least 4 GB RAM, 1+ vCPU, and 10+ GB storage.
- Clusters: multiple options, see Google.
- SaaS: [Elastic Cloud](https://www.elastic.co/cloud/).

### Redis

- Self-hosted: At least 4 GB RAM, 1+ vCPU, and 10+ GB storage.
- Clusters: [documentation](https://redis.io/docs/management/scaling/).
- SaaS: [Redis Enterprise Cloud](https://redis.com/redis-enterprise-cloud/overview/).

### RabbitMQ

- Self-hosted: At least 1 GB RAM, 1+ vCPU, and 1+ GB storage ([source](https://cloud.ibm.com/docs/messages-for-rabbitmq?topic=messages-for-rabbitmq-resources-scaling&interface=ui)).
- Cluster: [documentation](https://www.rabbitmq.com/clustering.html).
- SaaS: There is no official SaaS available, but [other companies provide RabbitMQ SaaS](https://www.cloudamqp.com/).
