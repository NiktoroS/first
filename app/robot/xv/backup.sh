#!/bin/sh

mysqldump -e -u sirotkin -ppLum4ha xv | gzip > `date +/home/sirotkin-aa/backup/xv.%w.sql.gz`
