# Scenario 99-05: Token with no owner

If a token is owned by no user, then the token is invalid and can not be used for any requests.

<div id="graph" class="graph-container" style="height:400px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'data', ...dataNode, label: 'Data' },
    { id: 'token', ...tokenNode, label: 'Token' }
  ],
  edges: [
    { source: 'token', target: 'data', label: 'OWNS' }
  ]
});
</script>
