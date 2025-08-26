<?php

namespace Tro365\Helpers;

/**
 * String Helper Class
 * Tro365 - Website thuê trọ
 */
class StringHelper
{
    /**
     * Generate slug from string
     */
    public static function slug($string, $separator = '-')
    {
        // Convert to lowercase
        $string = mb_strtolower($string, 'UTF-8');
        
        // Replace Vietnamese characters
        $vietnamese = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
            'đ'
        ];
        
        $latin = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd'
        ];
        
        $string = str_replace($vietnamese, $latin, $string);
        
        // Remove special characters
        $string = preg_replace('/[^a-z0-9\s]/', '', $string);
        
        // Replace spaces with separator
        $string = preg_replace('/\s+/', $separator, trim($string));
        
        return $string;
    }
    
    /**
     * Truncate string with ellipsis
     */
    public static function truncate($string, $length = 100, $ellipsis = '...')
    {
        if (mb_strlen($string, 'UTF-8') <= $length) {
            return $string;
        }
        
        return mb_substr($string, 0, $length, 'UTF-8') . $ellipsis;
    }
    
    /**
     * Truncate string by words
     */
    public static function truncateWords($string, $wordCount = 20, $ellipsis = '...')
    {
        $words = explode(' ', $string);
        
        if (count($words) <= $wordCount) {
            return $string;
        }
        
        return implode(' ', array_slice($words, 0, $wordCount)) . $ellipsis;
    }
    
    /**
     * Clean HTML tags from string
     */
    public static function stripTags($string, $allowedTags = '')
    {
        return strip_tags($string, $allowedTags);
    }
    
    /**
     * Convert string to title case
     */
    public static function titleCase($string)
    {
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }
    
    /**
     * Convert string to sentence case
     */
    public static function sentenceCase($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . 
               mb_strtolower(mb_substr($string, 1, null, 'UTF-8'), 'UTF-8');
    }
    
    /**
     * Generate random string
     */
    public static function random($length = 10, $characters = null)
    {
        $characters = $characters ?: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
    
    /**
     * Check if string contains substring
     */
    public static function contains($haystack, $needle, $caseSensitive = true)
    {
        if (!$caseSensitive) {
            $haystack = mb_strtolower($haystack, 'UTF-8');
            $needle = mb_strtolower($needle, 'UTF-8');
        }
        
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }
    
    /**
     * Check if string starts with substring
     */
    public static function startsWith($haystack, $needle, $caseSensitive = true)
    {
        if (!$caseSensitive) {
            $haystack = mb_strtolower($haystack, 'UTF-8');
            $needle = mb_strtolower($needle, 'UTF-8');
        }
        
        return mb_substr($haystack, 0, mb_strlen($needle, 'UTF-8'), 'UTF-8') === $needle;
    }
    
    /**
     * Check if string ends with substring
     */
    public static function endsWith($haystack, $needle, $caseSensitive = true)
    {
        if (!$caseSensitive) {
            $haystack = mb_strtolower($haystack, 'UTF-8');
            $needle = mb_strtolower($needle, 'UTF-8');
        }
        
        return mb_substr($haystack, -mb_strlen($needle, 'UTF-8'), null, 'UTF-8') === $needle;
    }
    
    /**
     * Replace first occurrence of substring
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        $pos = mb_strpos($subject, $search, 0, 'UTF-8');
        
        if ($pos !== false) {
            return mb_substr($subject, 0, $pos, 'UTF-8') . $replace . 
                   mb_substr($subject, $pos + mb_strlen($search, 'UTF-8'), null, 'UTF-8');
        }
        
        return $subject;
    }
    
    /**
     * Replace last occurrence of substring
     */
    public static function replaceLast($search, $replace, $subject)
    {
        $pos = mb_strrpos($subject, $search, 0, 'UTF-8');
        
        if ($pos !== false) {
            return mb_substr($subject, 0, $pos, 'UTF-8') . $replace . 
                   mb_substr($subject, $pos + mb_strlen($search, 'UTF-8'), null, 'UTF-8');
        }
        
        return $subject;
    }
    
    /**
     * Format phone number
     */
    public static function formatPhone($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format Vietnamese phone number
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
        }
        
        return $phone;
    }
    
    /**
     * Format currency
     */
    public static function formatCurrency($amount, $currency = 'VND')
    {
        return number_format($amount, 0, ',', '.') . ' ' . $currency;
    }
    
    /**
     * Extract numbers from string
     */
    public static function extractNumbers($string)
    {
        preg_match_all('/\d+/', $string, $matches);
        return $matches[0];
    }
    
    /**
     * Mask sensitive information
     */
    public static function mask($string, $start = 2, $end = 2, $mask = '*')
    {
        $length = mb_strlen($string, 'UTF-8');
        
        if ($length <= $start + $end) {
            return str_repeat($mask, $length);
        }
        
        $maskLength = $length - $start - $end;
        
        return mb_substr($string, 0, $start, 'UTF-8') . 
               str_repeat($mask, $maskLength) . 
               mb_substr($string, -$end, null, 'UTF-8');
    }
}
