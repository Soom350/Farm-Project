<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';

/**
 * "Mailer" dev: writes in data/mail.log.
 * In production: replace with SMTP/Sendgrid/etc.
 */
function mail_log_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'mail.log';
}

function send_email(string $to, string $subject, string $body): void
{
    $dir = dirname(mail_log_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $entry = [
        'at' => gmdate('c'),
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
    ];

    file_put_contents(mail_log_path(), json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

