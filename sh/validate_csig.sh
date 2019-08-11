#!/bin/sh
set -e
rootpubkey=$1
csigfile=$2

if [ -z $csigfile ] || [ -z $rootpubkey ]; then
  >&2 echo "USAGE: $0 root_public_key.pub combined_siignature_file.csig"
  exit 1
fi

if [ ! -f "$rootpubkey" ]; then
   >&2 echo "FILE NOT FOUND: $rootpubkey"
   exit 1
fi

if [ ! -f "$csigfile" ]; then
   >&2 echo "FILE NOT FOUND: $csigfile"
   exit 1
fi

>&2 echo "Validating csig $2 using trusted root public key $1"


intsig=$(mktemp /tmp/intsigmsg.XXXXXX)
head -n 5 $csigfile > $intsig

message1=$(mktemp /tmp/intsigmsg.XXXXXX)
signify -V -e -x $intsig -p $rootpubkey -m $message1
expiration=$(head -n 1 $message1)

intpubkey=$(mktemp /tmp/intsigmsg.XXXXXX)
tail -n +2 $message1 > $intpubkey


today=$(date -u +%Y-%m-%d)
expiration=$(head -n 1 $message1)
if [[ "$today" > "$expiration" ]] ; then
  >&2 echo "Intermediate key expired on $expiration (today is $today in UTC)"
  exit 1
fi

tail -n +6 $csigfile  | signify -C -p $intpubkey -x -
