<?php

namespace App;

enum TaskType: string
{
    case OneTime = 'one_time';
    case Daily = 'daily';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'Satu kali',
            self::Daily => 'Harian',
        };
    }
}
