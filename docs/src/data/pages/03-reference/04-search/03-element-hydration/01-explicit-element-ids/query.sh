curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "element-hydration",
            "query": {
              "elementIds": [
                "02IiNr0GOoKiPMLPDlPbKrlR4vnBQsvSBSkU5",
                "02IiNr0GOoKiPMLVYC87gam9dbmx8E6khf0g8",
                "02IiNr0GOoKiPMMBnI2aIF8Qd4GH33FIAozo7"
              ]
            }
          }
        ]
      }' \
  https://api.example.com/search
