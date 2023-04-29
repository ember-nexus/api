# Graph Database Fundamentals

Ember Nexus API is built upon the property graph database [Neo4j](https://neo4j.com/), which means many of the API's
concepts are also graph-like. By understanding core graph database concepts, you can better comprehend higher-level
concepts within the API.

The core element in graph databases are [Nodes](https://neo4j.com/developer/cypher/intro-cypher/#cypher-nodes), which
can contain zero or more labels that can classify the type of node, and as many properties as required. Nodes can be
directly compared to rows in a table or SQL database.

[Relationships](https://neo4j.com/developer/cypher/intro-cypher/#cypher-relationships) connect two nodes and require
exactly one type, and can also have properties of their own. Relationships can be compared to foreign keys in SQL
databases.

In Ember Nexus API, nodes and relationships are combined into the concept of a "data element." These elements are
required to have at least one type (whether it's a label in the case of a node or not) and have an automatically
provided property, the identifier, set to a random [UUIDv4](https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_(random)).
In addition, these data elements may contain more complex data like JSON, as well as up to one file.
