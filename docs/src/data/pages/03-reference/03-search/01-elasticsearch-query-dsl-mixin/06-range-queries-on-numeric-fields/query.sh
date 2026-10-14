curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "elasticsearch-query-dsl-mixin",
            "query": {
              "range": {
                "created": {
                  "gte": "2025-09-25T18:35:27+00:00"
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
