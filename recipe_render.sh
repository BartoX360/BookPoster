#!/bin/sh
# Zippe la recette, l'importe dans LaraPaper comme « BookPoster (test) » et rapatrie les rendus dans $1
# LIVE=1 (facultatif) : vérifie aussi les listes réelles de Place des Libraires
set -e
DIR=$(cd "$(dirname "$0")" && pwd)
(cd "$DIR/recipe/src" && rm -f ../bookposter.zip && zip -q -r ../bookposter.zip .)
scp -q "$DIR/recipe/bookposter.zip" "$DIR/recipe_test.php" "$DIR/test_payload.json" root@192.168.1.110:/tmp/
ssh root@192.168.1.110 "for f in bookposter.zip recipe_test.php test_payload.json; do docker cp /tmp/\$f larapaper-app-1:/tmp/; done && docker exec -u www-data -e LIVE='$LIVE' larapaper-app-1 php artisan tinker /tmp/recipe_test.php < /dev/null 2>&1 | grep -E ' ok$|Exception|Error|url=|live payload|bytes'; rm -rf /tmp/bpr && docker cp larapaper-app-1:/tmp/bpr /tmp/bpr"
mkdir -p "$1"; scp -q 'root@192.168.1.110:/tmp/bpr/*' "$1/"
