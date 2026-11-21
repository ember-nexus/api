curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "cypher-path-subset",
            "query": "MATCH path=((:Plant)-[:HAS_TAG]->(:Tag {name: \"blue\"})) RETURN path"
          },
          {
            "type": "element-hydration"
          }
        ]
      }' \
  https://api.example.com/search
