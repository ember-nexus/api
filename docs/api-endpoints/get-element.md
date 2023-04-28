# GET /&lt;uuid&gt;

<div id="graph-container-1" class="graph-container" style="height:1000px"></div>

```mermaid
%%{init: {'flowchart' : {'curve' : 'monotone'}}}%%

graph TB
  init([server receives GET-request])
  checkToken{{does request contain token?}}
  noTokenAction([use default anonymous user for auth])
  checkTokenValidity{{is token valid?}}
  checkElementExistence{{does element exist?}}
  checkElementAccess{{does user have access to element?}}
  
  error401([return error 401])
  error404([return error 404])
  success200([return success 200])
  
  class init init
  class error401 status4xx
  class error404 status4xx
  class success200 status2xx
  
  init --> checkToken
  checkToken -- no --> noTokenAction
  checkToken -- yes --> checkTokenValidity
  noTokenAction --> checkElementExistence
  checkTokenValidity -- no --> error401
  checkTokenValidity -- yes --> checkElementExistence
  checkElementExistence -- yes --> checkElementAccess
  checkElementAccess -- yes --> success200
  checkElementAccess -- no --> error404
  checkElementExistence -- no --> error404
```


<script>
renderGraph(document.getElementById('graph-container-1'), {
  nodes: [
    { id: 'init', type: 'autosize-rect', label: 'server receives GET-request' },
    { id: 'checkToken', type: 'autosize-flat-hexagon', label: 'does request contain token?' },
    { id: 'noTokenAction', type: 'autosize-rect', label: 'use default anonymous user for auth' },
    { id: 'checkTokenValidity', type: 'autosize-flat-hexagon', label: 'is token valid?' },
    { id: 'checkElementExistence', type: 'autosize-flat-hexagon', label: 'does element exist?' },
    { id: 'checkElementAccess', type: 'autosize-flat-hexagon', label: 'does user have access to element?' },
    { id: 'error401', type: 'autosize-rect', label: 'return 401' },
    { id: 'error404', style: {fill: '#d8c527' }, labelCfg: {style: {fill: '#000' } }, type: 'autosize-rect', label: 'return 404' },
    {
      id: 'success200',
      label: 'return 200',
      type: 'rect'
    },
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkTokenValidity', target: 'checkElementExistence', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkElementExistence', target: 'checkElementAccess', label: 'yes' },
    { source: 'checkElementExistence', target: 'error404', label: 'no' },
    { source: 'checkElementAccess', target: 'success200', label: 'yes' },
    { source: 'checkElementAccess', target: 'error404', label: 'no' },
    { source: 'noTokenAction', target: 'checkElementExistence', label: '' }
  ],
}, 'TB');
</script>
