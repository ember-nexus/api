curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "elasticsearch-query-dsl-mixin",
            "query": {
              "prefix": {
                "name": {
                  "value": "blu"
                }
              }
            }
          },
          {
            "type": "element-hydration"
          }
        ]
      }' \
  https://api.example.com/search
