curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "element-hydration",
            "query": {
              "elementIds": "{{map(sortBy(filter(elements, #.score > 1.0), .score, \"desc\"), #.id)}}"
            },
            "parameters": {
              "elements": [
                {"id": "02IiNr0GOoKiPMMnmHcecC7H3P3IOipWs3wbB", "score": 0.9},
                {"id": "02IiNr0GOoKiPMLzlyajuxj3r003A91sQN0s8", "score": 4.0},
                {"id": "02IiNr0GOoKiPMMVF5V1KsKN9p11vzC3JU7xE", "score": 1.4},
                {"id": "02IiNr0GOoKiPMLh5LwCdzZMAqqP08VzcXmWl", "score": 3.2}
              ]
            }
          }
        ]
      }' \
  https://api.example.com/search
