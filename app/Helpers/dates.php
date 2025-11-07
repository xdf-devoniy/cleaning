<?php
namespace App\Helpers;

use DateTime;
use DateTimeZone;

function tz_now(string $tz = 'Asia/Tashkent'): DateTime
{
    return new DateTime('now', new DateTimeZone($tz));
}
