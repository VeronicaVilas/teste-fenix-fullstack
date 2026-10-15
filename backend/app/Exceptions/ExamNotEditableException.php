<?php

namespace App\Exceptions;

final class ExamNotEditableException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Prova publicada não pode ser alterada.');
    }
}
