#!/bin/sh
set -e
intseckey=$1
introotsig=$2
filetohashandsign=$3

if [ -z $intseckey ] || [ -z $introotsig ] || [ -z $filetohashandsign ]; then
  >&2 echo "USAGE: $0 intermediate_secret_key.sec root_intermediate_signature.sig file_to_hash_and_sign  > final.csig"
  exit 1
fi

if [ ! -f "$intseckey" ]; then
   >&2 echo "FILE NOT FOUND: $intseckey"
   exit 1
fi

if [ ! -f "$introotsig" ]; then
   >&2 echo "FILE NOT FOUND: $introotsig"
   exit 1
fi

if [ ! -f "$filetohashandsign" ]; then
   >&2 echo "FILE NOT FOUND: $filetohashandsign"
   exit 1
fi

>&2 echo "Outputting csig for $filetohashandsign"
cat $introotsig

if which sha512sum 2> /dev/null; then
  hash=$(sha512sum --tag $filetohashandsign)
else
  # shasum is usually on both BSD and linux systems.
  data=$(shasum -a 512 $filetohashandsign)
  part1=$(echo "$data" | cut -d ' ' -f1)
  part2=$(echo "$data" | cut -d ' ' -f3)
  hash="SHA512 ($part2) = $part1"
fi

echo "$hash" | signify -S -e -s $intseckey -m - -x -

