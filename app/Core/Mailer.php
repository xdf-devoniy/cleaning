<?php
namespace App\Core;

class Mailer
{
    public function send(string $to, string $subject, string $body): bool
    {
        // Stub for SMTP integration; log to file for now
        $log = sprintf("[%s] MAIL to:%s subject:%s\n", date('c'), $to, $subject);
        file_put_contents(__DIR__ . '/../../storage/mail.log', $log, FILE_APPEND);
        return true;
    }
}
