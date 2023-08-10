# Configuration

## Environment Variables

Environment variables can be easily set through Docker, Docker Compose and other tools.

**Ember Nexus specific variables**:

- `ANONYMOUS_USER_UUID`: The UUID of a user, which will be used if no token is supplied.
- `CYPHER_AUTH`: Connection string for accessing the OpenCypher database, i.e. Neo4j.
- `MONGO_AUTH`: Connection string for accessing the Mongo database.
- `ELASTIC_AUTH`: Connection string for accessing the Elasticsearch database.
- `REDIS_AUTH`: Connection string for accessing the Redis database.
- `RABBITMQ_AUTH`: Connection string for accessing the RabbitMQ database.
- `VERSION`: Variable containing the version of the currently running API software. Automatically set by the build
  process.
- `REFERENCE_DATASET_VERSION`: Variable containing the version of the
  [reference dataset](https://github.com/ember-nexus/reference-dataset) used for validating features.

**Framework specific variables**:

See [Symfony's documentation](https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables)
for details.

## Application Configuration

As Ember Nexus API is based on Symfony, it uses Symfony's default application configuration.

For easier use, parameters can be overwritten by replacing the file `/var/www/html/config/custom-parameters.yaml` with
one containing your values.

Possible parameters are:

[](../example/default-parameters.yaml ':include :type=code')
