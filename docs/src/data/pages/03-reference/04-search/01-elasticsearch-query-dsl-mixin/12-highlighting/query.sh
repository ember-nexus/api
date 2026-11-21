curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "elasticsearch-query-dsl-mixin",
            "query": {
              "query": {
                "match": {
                  "description": "lily"
                }
              },
              "highlight": {
                "encoder": "html",
                "pre_tags": [
                  "<mark>"
                ],
                "post_tags": [
                  "</mark>"
                ],
                "fields": {
                  "description": {
                    "number_of_fragments": 1
                  }
                }
              }
            },
            "parameters": {
              "nodeTypes": [
                "Plant"
              ],
              "pageSize": 3
            }
          },
          {
            "type": "element-hydration"
          }
        ]
      }' \
  https://api.example.com/search
