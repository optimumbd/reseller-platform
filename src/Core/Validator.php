<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny validator. Rules supported:
 *   required, email, url, min:N, max:N, between:N,M,
 *   numeric, integer, alpha, alphanum, regex:pattern,
 *   in:a,b,c, same:field, confirmed, unique:table,column[,exceptId]
 */
final class Validator
{
    /** @var array<string,string[]> */
    private array $errors = [];

    /**
     * @param array<string,mixed> $data
     * @param array<string,string|array> $rules
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly ?Database $db = null,
    ) {}

    public static function make(array $data, array $rules, ?Database $db = null): self
    {
        return new self($data, $rules, $db);
    }

    public function passes(): bool
    {
        $this->errors = [];
        foreach ($this->rules as $field => $ruleset) {
            $rules = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;
            $value = $this->data[$field] ?? null;
            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                if (!$this->check($name, $field, $value, $param)) {
                    break;
                }
            }
        }
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * @return array<string,string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function check(string $rule, string $field, mixed $value, ?string $param): bool
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || $value === []) {
                    $this->addError($field, ucfirst($field) . ' is required.');
                    return false;
                }
                return true;
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, ucfirst($field) . ' must be a valid email.');
                    return false;
                }
                return true;
            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, ucfirst($field) . ' must be a valid URL.');
                    return false;
                }
                return true;
            case 'min':
                if ($value !== null && mb_strlen((string) $value) < (int) $param) {
                    $this->addError($field, ucfirst($field) . " must be at least {$param} characters.");
                    return false;
                }
                return true;
            case 'max':
                if ($value !== null && mb_strlen((string) $value) > (int) $param) {
                    $this->addError($field, ucfirst($field) . " must be at most {$param} characters.");
                    return false;
                }
                return true;
            case 'between':
                [$min, $max] = array_pad(explode(',', (string) $param), 2, '0');
                $len = mb_strlen((string) $value);
                if ($len < (int) $min || $len > (int) $max) {
                    $this->addError($field, ucfirst($field) . " must be between {$min} and {$max} characters.");
                    return false;
                }
                return true;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, ucfirst($field) . ' must be numeric.');
                    return false;
                }
                return true;
            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, ucfirst($field) . ' must be an integer.');
                    return false;
                }
                return true;
            case 'alpha':
                if ($value && !preg_match('/^[\pL]+$/u', (string) $value)) {
                    $this->addError($field, ucfirst($field) . ' must contain only letters.');
                    return false;
                }
                return true;
            case 'alphanum':
                if ($value && !preg_match('/^[\pL\pN]+$/u', (string) $value)) {
                    $this->addError($field, ucfirst($field) . ' must contain only letters and numbers.');
                    return false;
                }
                return true;
            case 'regex':
                if ($value && !@preg_match($param ?? '//', (string) $value)) {
                    $this->addError($field, ucfirst($field) . ' format is invalid.');
                    return false;
                }
                return true;
            case 'in':
                $allowed = array_map('trim', explode(',', (string) $param));
                if ($value !== null && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, ucfirst($field) . ' is invalid.');
                    return false;
                }
                return true;
            case 'same':
                if ((string) $value !== (string) ($this->data[$param] ?? '')) {
                    $this->addError($field, ucfirst($field) . " must match {$param}.");
                    return false;
                }
                return true;
            case 'confirmed':
                if ((string) $value !== (string) ($this->data[$field . '_confirmation'] ?? '')) {
                    $this->addError($field, ucfirst($field) . ' confirmation does not match.');
                    return false;
                }
                return true;
            case 'unique':
                if ($this->db === null || $value === null || $value === '') {
                    return true;
                }
                [$table, $column, $exceptId] = array_pad(explode(',', (string) $param), 3, null);
                $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :v";
                $bind = ['v' => $value];
                if ($exceptId !== null) {
                    $sql .= ' AND `id` <> :id';
                    $bind['id'] = $exceptId;
                }
                if ((int) $this->db->scalar($sql, $bind) > 0) {
                    $this->addError($field, ucfirst($field) . ' is already taken.');
                    return false;
                }
                return true;
        }
        return true;
    }
}
