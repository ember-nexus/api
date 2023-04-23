# Hardware Requirements

## Local and Development Setups

For local and development purposes, the whole stack can be hosted on a single machine with at least 12 GB of RAM,
6+ vCPUs, and 20 GB of SSD storage. However, it's important to note that this setup is not recommended for production
use as certain services like ElasticSearch can consume so much RAM that other services are starved. Additionally, the
lack of service distribution means that if one storage layer service crashes, the entire stack becomes unavailable.

A Docker Compose configuration is available [here](/getting-started/local-deployment).

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
