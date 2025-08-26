<?php

namespace Tro365\Helpers;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

/**
 * Modern Validator - Thay thế validation cũ rườm rà
 * Sử dụng Respect/Validation với fluent interface
 */
class ModernValidator
{
    private array $rules = [];
    private array $errors = [];
    private array $data = [];

    /**
     * Create new validator instance
     */
    public static function make(array $data): self
    {
        $instance = new self();
        $instance->data = $data;
        return $instance;
    }

    /**
     * Add validation rule
     */
    public function rule(string $field, $validator, string $message = null): self
    {
        $this->rules[$field] = [
            'validator' => $validator,
            'message' => $message
        ];
        return $this;
    }

    /**
     * Required field
     */
    public function required(string $field, string $message = null): self
    {
        return $this->rule($field, v::notEmpty(), $message ?? "Trường {$field} là bắt buộc");
    }

    /**
     * Email validation
     */
    public function email(string $field, string $message = null): self
    {
        return $this->rule($field, v::email(), $message ?? "Email không hợp lệ");
    }

    /**
     * Phone validation
     */
    public function phone(string $field, string $message = null): self
    {
        $phoneValidator = v::regex('/^[0-9+\-\s()]+$/')->length(10, 15);
        return $this->rule($field, $phoneValidator, $message ?? "Số điện thoại không hợp lệ");
    }

    /**
     * Length validation
     */
    public function length(string $field, int $min, int $max = null, string $message = null): self
    {
        $validator = $max ? v::length($min, $max) : v::length($min, null);
        $defaultMessage = $max 
            ? "Trường {$field} phải có từ {$min} đến {$max} ký tự"
            : "Trường {$field} phải có ít nhất {$min} ký tự";
        return $this->rule($field, $validator, $message ?? $defaultMessage);
    }

    /**
     * Numeric validation
     */
    public function numeric(string $field, string $message = null): self
    {
        return $this->rule($field, v::numeric(), $message ?? "Trường {$field} phải là số");
    }

    /**
     * URL validation
     */
    public function url(string $field, string $message = null): self
    {
        return $this->rule($field, v::url(), $message ?? "URL không hợp lệ");
    }

    /**
     * Date validation
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = null): self
    {
        return $this->rule($field, v::date($format), $message ?? "Ngày không hợp lệ");
    }

    /**
     * In array validation
     */
    public function in(string $field, array $values, string $message = null): self
    {
        return $this->rule($field, v::in($values), $message ?? "Giá trị không hợp lệ");
    }

    /**
     * Confirm field validation (password confirmation)
     */
    public function confirm(string $field, string $confirmField, string $message = null): self
    {
        $validator = v::callback(function($value) use ($confirmField) {
            return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
        });
        return $this->rule($field, $validator, $message ?? "Xác nhận không khớp");
    }

    /**
     * Unique validation (database check)
     */
    public function unique(string $field, string $table, string $column = null, $except = null, string $message = null): self
    {
        $column = $column ?? $field;
        $validator = v::callback(function($value) use ($table, $column, $except) {
            $db = \Tro365\Core\Database::getInstance();
            $query = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            
            if ($except) {
                $query .= " AND id != ?";
                $params[] = $except;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn() == 0;
        });
        
        return $this->rule($field, $validator, $message ?? "Giá trị đã tồn tại");
    }

    /**
     * File validation
     */
    public function file(string $field, array $allowedTypes = [], int $maxSize = null, string $message = null): self
    {
        $validator = v::callback(function($file) use ($allowedTypes, $maxSize) {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return false;
            }
            
            // Check file size
            if ($maxSize && $file['size'] > $maxSize) {
                return false;
            }
            
            // Check file type
            if (!empty($allowedTypes)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                return in_array($mimeType, $allowedTypes);
            }
            
            return true;
        });
        
        return $this->rule($field, $validator, $message ?? "File không hợp lệ");
    }

    /**
     * Image validation
     */
    public function image(string $field, int $maxSize = null, string $message = null): self
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return $this->file($field, $allowedTypes, $maxSize, $message ?? "File phải là hình ảnh hợp lệ");
    }

    /**
     * Custom validation rule
     */
    public function custom(string $field, callable $callback, string $message): self
    {
        $validator = v::callback($callback);
        return $this->rule($field, $validator, $message);
    }

    /**
     * Validate all rules
     */
    public function validate(): bool
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rule) {
            $value = $this->data[$field] ?? null;
            
            try {
                $rule['validator']->assert($value);
            } catch (ValidationException $e) {
                $this->errors[$field] = $rule['message'] ?? $e->getMessage();
            }
        }
        
        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error
     */
    public function firstError(): ?string
    {
        return !empty($this->errors) ? array_values($this->errors)[0] : null;
    }

    /**
     * Check if field has error
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get error for specific field
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Get validated data (only fields that passed validation)
     */
    public function validated(): array
    {
        if (!empty($this->errors)) {
            return [];
        }
        
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        
        return $validated;
    }

    /**
     * Quick validation method
     */
    public static function quick(array $data, array $rules): array
    {
        $validator = self::make($data);
        
        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                // Simple rule like 'required|email'
                $ruleParts = explode('|', $rule);
                foreach ($ruleParts as $rulePart) {
                    if (method_exists($validator, $rulePart)) {
                        $validator->$rulePart($field);
                    }
                }
            } elseif (is_array($rule)) {
                // Complex rule with parameters
                foreach ($rule as $ruleName => $params) {
                    if (method_exists($validator, $ruleName)) {
                        if (is_array($params)) {
                            $validator->$ruleName($field, ...$params);
                        } else {
                            $validator->$ruleName($field, $params);
                        }
                    }
                }
            }
        }
        
        return [
            'valid' => $validator->validate(),
            'errors' => $validator->errors(),
            'data' => $validator->validated()
        ];
    }
}
