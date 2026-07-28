<?php

declare(strict_types=1);

namespace SpaBooking\Validation;

final class CustomerDetailsValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{values: array{name: string, email: string, phone: string, notes: string},
     *     errors: array<string, string>}
     */
    public function validate(array $input): array
    {
        $values = [
            'name' => $this->text($input['name'] ?? null),
            'email' => $this->text($input['email'] ?? null),
            'phone' => $this->text($input['phone'] ?? null),
            'notes' => $this->text($input['notes'] ?? null),
        ];
        $errors = [];

        if ($values['name'] === '') {
            $errors['name'] = 'Enter your full name.';
        } elseif ($this->length($values['name']) < CustomerDetailsRules::NAME_MIN_LENGTH
            || $this->length($values['name']) > CustomerDetailsRules::NAME_MAX_LENGTH
        ) {
            $errors['name'] = 'Full name must be between 2 and 120 characters.';
        }

        if ($values['email'] === '') {
            $errors['email'] = 'Enter your email address.';
        } elseif ($this->length($values['email']) > CustomerDetailsRules::EMAIL_MAX_LENGTH
            || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($values['phone'] === '') {
            $errors['phone'] = 'Enter your phone number.';
        } elseif ($this->length($values['phone']) < CustomerDetailsRules::PHONE_MIN_LENGTH
            || $this->length($values['phone']) > CustomerDetailsRules::PHONE_MAX_LENGTH
        ) {
            $errors['phone'] = 'Phone number must be between 7 and 32 characters.';
        }

        if ($this->length($values['notes']) > CustomerDetailsRules::NOTES_MAX_LENGTH) {
            $errors['notes'] = 'Notes must be 1,000 characters or fewer.';
        }

        return ['values' => $values, 'errors' => $errors];
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
