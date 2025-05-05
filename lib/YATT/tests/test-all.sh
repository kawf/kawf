#!/bin/bash
set -e
for i in *.php; do
    echo $i
    php $i
done
