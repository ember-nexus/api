curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "elasticsearch-query-dsl-mixin",
            "query": {
              "terms": {
                "_id": "{{someElementIds}}"
              }
            },
            "parameters": {
              "someElementIds": [
                "02IiNr0GOoKiPMLPDlPbKrlR4vnBQsvSBSkU5",
                "02IiNr0GOoKiPMLO9dQSP9Mmvy5E3YmZXDsBP",
                "02IiNr0GOoKiPMMBnI2aIF8Qd4GH33FIAozo7"
              ]
            }
          },
          {
            "type": "element-hydration"
          }
        ]
      }' \
  https://api.example.com/search
