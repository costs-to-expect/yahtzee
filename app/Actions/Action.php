<?php
declare(strict_types=1);

namespace App\Actions;

abstract class Action
{
    protected string $message;

    protected array $validation_errors = [];

    public function getValidationErrors(): array
    {
        return $this->validation_errors;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
