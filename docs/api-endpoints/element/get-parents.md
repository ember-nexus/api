# GET /&lt;uuid&gt;/parents - Get Parents

Returns all parents of the specified node.  
Returned data is paginated, can be filtered/sorted (?) and each page contains all relations between the node and the
returned parents.

Note: Usually a node only has one parent, although the security system allows multiple owners.
