<?php
/**
 * Validation Functions - Essential Only
 * Tro365 - Website thuê trọ
 *
 * Note: Deprecated functions have been removed.
 * Use Tro365\Helpers\ValidationHelper class for validation.
 * This file contains only essential utility functions.
 */

/**
 * Check if string is valid JSON
 */
function isValidJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Validate URL format
 */
function validateUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception("URL không hợp lệ");
    }
    return true;
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        throw new Exception("Định dạng ngày không hợp lệ");
    }
    return true;
}

/**
 * Validate time format
 */
function validateTime($time, $format = 'H:i') {
    $t = DateTime::createFromFormat($format, $time);
    if (!$t || $t->format($format) !== $time) {
        throw new Exception("Định dạng thời gian không hợp lệ");
    }
    return true;
}

/**
 * Validate numeric range
 */
function validateRange($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        throw new Exception("Giá trị phải là số");
    }
    
    $value = (float)$value;
    
    if ($min !== null && $value < $min) {
        throw new Exception("Giá trị phải lớn hơn hoặc bằng {$min}");
    }
    
    if ($max !== null && $value > $max) {
        throw new Exception("Giá trị phải nhỏ hơn hoặc bằng {$max}");
    }
    
    return true;
}

/**
 * Validate string length
 */
function validateLength($string, $min = null, $max = null) {
    $length = strlen($string);
    
    if ($min !== null && $length < $min) {
        throw new Exception("Độ dài tối thiểu là {$min} ký tự");
    }
    
    if ($max !== null && $length > $max) {
        throw new Exception("Độ dài tối đa là {$max} ký tự");
    }
    
    return true;
}

/**
 * Validate array contains specific values
 */
function validateInArray($value, $array, $message = null) {
    if (!in_array($value, $array)) {
        $message = $message ?: "Giá trị không hợp lệ. Chỉ chấp nhận: " . implode(', ', $array);
        throw new Exception($message);
    }
    return true;
}
