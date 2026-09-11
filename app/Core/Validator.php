<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    public function required(string $field, ?string $value, string $label): self
    {
        if ($value === null || trim($value) === '') {
            $this->errors[$field] = $label . ' es obligatorio.';
        }
        return $this;
    }

    public function min(string $field, ?string $value, int $min, string $label): self
    {
        if (!isset($this->errors[$field]) && $value !== null && mb_strlen(trim($value)) < $min) {
            $this->errors[$field] = $label . ' debe tener al menos ' . $min . ' caracteres.';
        }
        return $this;
    }

    public function max(string $field, ?string $value, int $max, string $label): self
    {
        if (!isset($this->errors[$field]) && $value !== null && mb_strlen(trim($value)) > $max) {
            $this->errors[$field] = $label . ' no puede superar ' . $max . ' caracteres.';
        }
        return $this;
    }

    public function email(string $field, ?string $value, string $label = 'El correo'): self
    {
        if (!isset($this->errors[$field]) && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $label . ' no tiene un formato válido.';
        }
        return $this;
    }

    public function in(string $field, ?string $value, array $allowed, string $label): self
    {
        if (!isset($this->errors[$field]) && !in_array((string) $value, $allowed, true)) {
            $this->errors[$field] = $label . ' no es una opción válida.';
        }
        return $this;
    }

    public function match(string $field, ?string $a, ?string $b, string $message): self
    {
        if (!isset($this->errors[$field]) && $a !== $b) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function regex(string $field, ?string $value, string $pattern, string $message): self
    {
        if (!isset($this->errors[$field]) && !preg_match($pattern, (string) $value)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function condition(string $field, bool $ok, string $message): self
    {
        if (!isset($this->errors[$field]) && !$ok) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /** Contraseña: longitud mínima y mezcla de letras y números. */
    public function password(string $field, ?string $value): self
    {
        $v = (string) $value;
        if (mb_strlen($v) < 10) {
            $this->errors[$field] = 'La contraseña debe tener al menos 10 caracteres.';
        } elseif (!preg_match('/[A-Za-zÁÉÍÓÚáéíóúÑñ]/', $v) || !preg_match('/\d/', $v)) {
            $this->errors[$field] = 'La contraseña debe combinar letras y números.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        foreach ($this->errors as $e) {
            return $e;
        }
        return null;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }
}
