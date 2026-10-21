#!/bin/sh
# Zippe la recette, l'importe dans LaraPaper comme « BookPoster (test) » et rapatrie les rendus dans $1
# LIVE=1 (facultatif) : vérifie aussi les listes réelles de Place des Libraires, et celles de Hardcover si .hardcover_token existe
set -e
DIR=$(cd "$(dirname "$0")" && pwd)
HC=""; [ -n "$LIVE" ] && [ -f "$DIR/.hardcover_token" ] && HC=$(tr -d ' \n\r' < "$DIR/.hardcover_token")
(cd "$DIR/recipe/src" && rm -f ../bookposter.zip && zip -q -r ../bookposter.zip .)
scp -q "$DIR/recipe/bookposter.zip" "$DIR/recipe_test.php" "$DIR/test_payload.json" "$DIR/test_payload_en.json" root@192.168.1.110:/tmp/
ssh root@192.168.1.110 "for f in bookposter.zip recipe_test.php test_payload.json test_payload_en.json; do docker cp /tmp/\$f larapaper-app-1:/tmp/; done && docker exec -u www-data -e LIVE='$LIVE' -e HC_TOKEN='${HC#Bearer}' larapaper-app-1 php artisan tinker /tmp/recipe_test.php < /dev/null 2>&1 | grep -E ' ok$|Exception|Error|url=|live payload|bytes'; rm -rf /tmp/bpr && docker cp larapaper-app-1:/tmp/bpr /tmp/bpr"
mkdir -p "$1"; scp -q 'root@192.168.1.110:/tmp/bpr/*' "$1/"
