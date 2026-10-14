# General

Some general information regarding the security tests:

- Tests have identifiers like `1-02-03-04`, which can be read as category 1, scenario 2, element 3, operation 4.
- Categories are broad topics for the security tests.
- Each scenario has a single sub graph, consisting of several nodes and relations.
- Each element has an internal id (usually not shown, but part of the code), a text id and a short numeric test id.
- Each operation has its own numeric id. Not all ids are currently used, and are reserved for future useage.
- Tests which are not idempotent require a special execution order or database resets. Usually handled in code and not
  part of the documentation itself.

The tests are made to ensure that Ember Nexus' security model is working as intended.
