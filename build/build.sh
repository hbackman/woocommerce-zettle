#!/bin/bash

zip -r woocommerce-zettle.zip * \
    -x .editorconfig \
    -x composer.json \
    -x composer.lock \
    -x docker-compose.yml \
    -x phpunit.xml \
    -x "tests/*" \
    -x ".git/*" \
    -x ".idea/*"