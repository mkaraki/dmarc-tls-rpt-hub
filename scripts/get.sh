#!/bin/bash

cd "$(dirname "$0")"

php85 dmarc.php
php85 tls-rpt.php

