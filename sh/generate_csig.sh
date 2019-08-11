#!/bin/sh
set -e
intseckey=$1
introotsig=$2
filetohashandsign=$3
>&2 echo Outputting csig for $filetohashandsign
cat $introotsig
sha512sum --tag $3 | signify -S -e -s $intseckey -m - -x -

