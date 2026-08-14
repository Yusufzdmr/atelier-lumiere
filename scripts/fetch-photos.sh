#!/usr/bin/env bash
# Lädt kuratierte Platzhalter-Bilder (Unsplash-Suche) nach scripts/.raw/.
# Danach: node scripts/build-photos.mjs  ->  lib/photos.json
set -u
OUT="scripts/.raw"
mkdir -p "$OUT"

fetch() { # $1 = bucket, $2 = query
  local file="$OUT/$1__$(echo "$2" | tr ' ' '-').json"
  if [ -s "$file" ] && grep -q '"results"' "$file"; then
    echo "cached  $1 / $2"
    return
  fi
  local q
  q=$(echo "$2" | sed 's/ /%20/g')
  curl -s --max-time 25 -H "Accept: application/json" \
    "https://unsplash.com/napi/search/photos?query=$q&per_page=24" > "$file"
  echo "$1 / $2 -> $(grep -o '"id":"' "$file" | wc -l)"
  sleep 3
}

fetch couple "wedding couple portrait"
fetch couple "bride and groom"
fetch couple "wedding couple sunset"
fetch ceremony "wedding ceremony"
fetch ceremony "wedding vows"
fetch ceremony "wedding aisle"
fetch party "wedding reception party"
fetch party "wedding first dance"
fetch party "wedding celebration guests"
fetch details "wedding rings detail"
fetch details "bridal bouquet"
fetch details "wedding table decoration"
fetch venue "castle wedding venue"
fetch venue "wedding hall interior"
fetch venue "wedding venue garden"
fetch prep "bride getting ready"
fetch prep "wedding dress hanging"
fetch prep "groom suit detail"
fetch portrait "photographer portrait"
fetch portrait "wedding photographer working"
