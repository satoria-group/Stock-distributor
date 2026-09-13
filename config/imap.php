<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IMAP Mail Server Configuration (Read-Only)
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the company's mail server to view
    | incoming distributor stock report emails and download Excel attachments.
    |
    */

    'host' => env('IMAP_HOST', ''),
    'port' => (int) env('IMAP_PORT', 993),
    'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    'validate_cert' => (bool) env('IMAP_VALIDATE_CERT', true),

    'username' => env('IMAP_USERNAME', ''),
    'password' => env('IMAP_PASSWORD', ''),

    'mailbox' => env('IMAP_MAILBOX', 'INBOX'),

    /*
    | Connection timeout in seconds.
    */
    'timeout' => (int) env('IMAP_TIMEOUT', 10),

    /*
    | Cache duration for inbox listings in seconds (default: 30s).
    */
    'cache_ttl' => (int) env('IMAP_CACHE_TTL', 30),

    /*
    | Maximum allowed attachment download size in megabytes.
    */
    'max_attachment_mb' => (int) env('MAIL_ATTACHMENT_MAX_MB', 25),

    /*
    | Subject patterns/keywords to identify distributor Daily Stock report emails.
    | "Satoria Daily Stock" is the primary required keyword. Additional patterns
    | can be configured via the IMAP_STOCK_KEYWORDS env variable (comma-separated).
    */
    'stock_subject_keywords' => array_values(array_unique(array_filter(array_merge(
        ['Satoria Daily Stock'],
        array_map('trim', explode(',', env('IMAP_STOCK_KEYWORDS', '')))
    )))),
];
