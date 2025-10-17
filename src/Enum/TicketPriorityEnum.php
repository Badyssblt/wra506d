<?php

namespace App\Enum;

enum TicketPriorityEnum: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
}
