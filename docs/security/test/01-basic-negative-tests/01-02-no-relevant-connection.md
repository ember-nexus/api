# Scenario 1-02: No Relevant Connection

Users which are connected to nodes with relationships, which are not relevant for the security system, do not have
access. Relevant relations include `OWNS`, `HAS_X_ACCESS` and `CREATED`.

<div id="graph" class="graph-container" style="height:300px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data', ...dataNode },
  ],
  edges: [
    { source: 'user', target: 'data', label: 'RELATION' },
  ]
});
</script>
