#!/usr/bin/env bash

mkdir -p /tmp/geolite
rm -r /tmp/geolite/*
cd /tmp/geolite

wget http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz
EXPECTED=$(wget -O - http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz.md5)
ACTUAL=$(md5sum GeoLite2-Country.tar.gz | cut -f 1 -d " ")

if [ "$EXPECTED" != "$ACTUAL" ]
then
    >&2 echo "ERROR: Invalid checksum."
    >&2 echo "Expected: $EXPECTED"
    >&2 echo "Actual: $ACTUAL"
    rm GeoLite2-Country.tar.gz
    exit 1
fi

mkdir geolite2

tar zxf GeoLite2-Country.tar.gz -C geolite2 --strip-components=1

mv geolite2 /var/local/geolite2

rm -r /tmp/geolite
