#!/bin/sh
set -e
rootpubkey=$1
csigfile=$2

>&2 echo Validating csig $2 using trusted root public key $1
sigofint=$(mktemp /tmp/intsigmsg.XXXXXX)
head --lines=5 $csigfile | signify -V -p $rootpubkey -m - > $intsig
today=$(date --utc --iso-8601)
expiration=$(head --lines=1 $intsig)
if [[ "$today" > "$expiration" ]] ; then
  >&2 echo Intermediate key expired on $expiration (today is $today in UTC)
  exit 1
fi

intsig=$(mktemp /tmp/intsigmsg.XXXXXX)


tail --lines=+6 $csigfile | 
