#!/bin/sh
set -e
rootseckey=$1
intpubkey=$2
if [ -z $rootseckey ] || [ -z $intpubkey ]; then
  >&2 echo "USAGE: $0 root_secret_key.sec intermediate_public_key.pub > signed.sig"
  exit 1
fi

if [ ! -f "$rootseckey" ]; then
   >&2 echo "FILE NOT FOUND: $rootseckey"
   exit 1
fi

if [ ! -f "$intpubkey" ]; then
   >&2 echo "FILE NOT FOUND: $intpubkey"
   exit 1
fi

>&2 echo "Outputting signature of intermediate public key $2 with root secret $1"

msgfile=$(mktemp /tmp/intsigmsg.XXXXXX)

# Options have to switch on BSD vs. linux
# -u is UTC
# Specify iso8601 format directly.
if date -u --date=+30days 2>/dev/null; then
  offset="--date=+30days"
else
  offset="-v +30d"
fi
date -u $offset +%Y-%m-%d > $msgfile
cat $intpubkey >> "$msgfile"
signify -S -e -s $rootseckey -m $msgfile -x -
rm -f "$msgfile"

