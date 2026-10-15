<?php

namespace App\Exceptions;

final class ExamHasAttemptsException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Prova já respondida por alunos não pode ser excluída.');
    }
}
