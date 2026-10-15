#/bin/sh

rm -rf lib/Antlr/dist/*
(
  echo "cleaning old PHP files"
  cd lib/Antlr/src
  echo "building PHP files from grammar definitions"
  java org.antlr.v4.Tool -Dlanguage=PHP -o ../dist Cypher25Lexer.g4
  java org.antlr.v4.Tool -Dlanguage=PHP -o ../dist CypherPathSubset.g4
  # Fix 'Undefined constant "paramType"' type error in PHP.
  # Might be related to https://github.com/antlr/antlr4/issues/2709 and https://github.com/antlr/antlr4/issues/3604 .
  # Todo check for better solution.
  sed -i 's/\$this->parameterName(paramType);/\$this->parameterName(\$paramType);/' ../dist/CypherPathSubset.php
  echo "PHP files built and fixed"
)
(
  echo "generating documentation pages from grammar definitions"
  mkdir -p /tmp/tmp-generate-antlr-docs
  cd /tmp/tmp-generate-antlr-docs
  java -jar /usr/local/lib/rrd-antlr4-0.1.2.jar /var/www/html/lib/Antlr/src/CypherPathSubset.g4
  java -jar /usr/local/lib/rrd-antlr4-0.1.2.jar /var/www/html/lib/Antlr/src/Cypher25Parser.g4
  java -jar /usr/local/lib/rrd-antlr4-0.1.2.jar /var/www/html/lib/Antlr/src/openCypher9.g4
  rm -f /var/www/html/docs/public/antlr/*
  mv output/Cypher25Parser/index.html /var/www/html/docs/public/antlr/cypher-25.html
  mv output/CypherPathSubset/index.html /var/www/html/docs/public/antlr/cypher-path-subset.html
  mv output/openCypher9/index.html /var/www/html/docs/public/antlr/open-cypher-9.html
  cd ../
  rm -rf /tmp/tmp-generate-antlr-docs
  echo "updated documentation pages"
)
echo "updating composer, clean cache and run composer cs:fix"
composer dump-autoload
php bin/console cache:clear
composer cs:fix
echo "done"
