<?php
namespace App\Core;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $ruleString);
            foreach ($rulesList as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $this->errors[$field][] = 'required';
                }
                if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = 'email';
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if (strlen((string)$value) < $min) {
                        $this->errors[$field][] = 'min';
                    }
                }
            }
        }
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
