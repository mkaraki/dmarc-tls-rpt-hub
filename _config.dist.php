<?php

const SENTRY_DSN = null;

const DB_HOST = 'db';
const DB_PORT = 3306;
const DB_USER = 'root';
const DB_PASS = 'dmarc';
const DB_NAME = 'dmarc';

const TLS_RPT_IMAP_URL  = '{mail.server.invalid:993/imap/ssl}INBOX';
const TLS_RPT_IMAP_USER = 'your@inbox.invalid';
const TLS_RPT_IMAP_PASS = 'password';

const DMARC_IMAP_URL  = '{mail.server.invalid:993/imap/ssl}INBOX';
const DMARC_IMAP_USER = 'your@inbox.invalid';
const DMARC_IMAP_PASS = 'password';

const PURGE_AFTER_HARVEST_TLS_RPT = true;
const PURGE_AFTER_HARVEST_DMARC = true;