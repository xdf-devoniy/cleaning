<?php
namespace App\Core;

class Sms
{
    public function send(string $phone, string $message): bool
    {
        $log = sprintf("[%s] SMS to:%s message:%s\n", date('c'), $phone, $message);
        file_put_contents(__DIR__ . '/../../storage/sms.log', $log, FILE_APPEND);
        return true;
    }
}
