# Scenario 99-06: Token with two owners

If a token is owned by two users, then the token is considered invalid and can not be used for any requests.

<div id="graph" class="graph-container" style="height:400px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user1', ...userNode, label: 'User 1' },
    { id: 'user2', ...userNode, label: 'User 2' },
    { id: 'data', ...dataNode, label: 'Data' },
    { id: 'token', ...tokenNode, label: 'Token' },
  ],
  edges: [
    { source: 'user1', target: 'data', label: 'OWNS' },
    { source: 'user1', target: 'token', label: 'OWNS' },
    { source: 'user2', target: 'data', label: 'OWNS' },
    { source: 'user2', target: 'token', label: 'OWNS' },
  ]
});
</script>
