curl -X POST \
  -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' \
  -H 'Content-Type: application/json' \
  -d '{
        "debug": true,
        "steps": [
          {
            "type": "cypher-path-subset",
            "query": "MATCH path=((:Plant)-[:IS_MEMBER_OF*..]->(:Taxon {name: \"Liliales\"})) RETURN path LIMIT 5"
          },
          {
            "type": "element-hydration"
          }
        ]
      }' \
  https://api.example.com/search
