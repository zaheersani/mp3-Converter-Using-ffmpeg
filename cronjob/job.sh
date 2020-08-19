#!/bin/bash
# Remove CoverPhotos extracted from mp3 files which are older than 1 day
find /var/www/re.ifonea.me/htdocs/ -name 'CoverPhoto_*' -mtime +1 -exec rm {} \;
# Remove all files (mp3 and image) from uploads folder which are older than 1 day
find /var/www/re.ifonea.me/htdocs/uploads/ -mtime +1 -exec rm {} \;
# Remove all directories and files which are uploaded from jQuery and are older than 1 day
find /var/www/re.ifonea.me/htdocs/server/php/files/* -mtime +1 -exec rm -r {} \;
