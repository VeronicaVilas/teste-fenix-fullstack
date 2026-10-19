<?php

namespace App\Enums;

enum StudentExamStatus: string
{
    case Pending = 'pending';
    case Answered = 'answered';
}
