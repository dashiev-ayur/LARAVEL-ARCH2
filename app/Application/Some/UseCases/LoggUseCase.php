<?php

declare(strict_types=1);

namespace App\Application\Some\UseCases;

final class LoggUseCase
{
    public private(set) string $message {
        set(string $value) {
            if ($value === '') {
                throw new \Exception('Message must be not empty');
            }
            $this->message = $value;
        }
        get {
            return $this->message;
        }
    }

    public string $message2 {
        get => '['.$this->message.']';
    }

    public function setMessage(string $value): void
    {
        $this->message = $value;
    }

    public function execute(): void
    {
        echo $this->message;
    }
}
