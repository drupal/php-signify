#!/bin/sh
set -e
rootseckey=$1
intpubkey=$2
>&2 echo Outputting signature of intermediate public key $2 with root secret $1
msgfile=$(mktemp /tmp/intsigmsg.XXXXXX)
date --utc --iso-8601 --date="+30 days" > $msgfile
cat $intpubkey >> "$msgfile"
signify -S -e -s $rootseckey -m $msgfile -x -
rm "$msgfile"

