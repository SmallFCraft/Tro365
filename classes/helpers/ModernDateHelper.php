<?php

namespace Tro365\Helpers;

use Carbon\Carbon;

/**
 * Modern Date Helper - Thay thế date manipulation cũ
 * Sử dụng Carbon library với timezone support
 */
class ModernDateHelper
{
    private static string $defaultTimezone = 'Asia/Ho_Chi_Minh';
    private static string $defaultLocale = 'vi';

    /**
     * Set default timezone
     */
    public static function setDefaultTimezone(string $timezone): void
    {
        self::$defaultTimezone = $timezone;
        // Carbon 3.x uses different method
        date_default_timezone_set($timezone);
    }

    /**
     * Set default locale
     */
    public static function setDefaultLocale(string $locale): void
    {
        self::$defaultLocale = $locale;
        Carbon::setLocale($locale);
    }

    /**
     * Create Carbon instance from various inputs
     */
    public static function make($date = null): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }
        
        if ($date === null) {
            return Carbon::now(self::$defaultTimezone);
        }
        
        return Carbon::parse($date, self::$defaultTimezone);
    }

    /**
     * Get current date/time
     */
    public static function now(): Carbon
    {
        return Carbon::now(self::$defaultTimezone);
    }

    /**
     * Get today's date
     */
    public static function today(): Carbon
    {
        return Carbon::today(self::$defaultTimezone);
    }

    /**
     * Format date for display
     */
    public static function format($date, string $format = 'd/m/Y'): string
    {
        return self::make($date)->format($format);
    }

    /**
     * Format date for database
     */
    public static function forDatabase($date): string
    {
        return self::make($date)->format('Y-m-d H:i:s');
    }

    /**
     * Format date for humans (relative time)
     */
    public static function forHumans($date): string
    {
        return self::make($date)->diffForHumans();
    }

    /**
     * Vietnamese date format
     */
    public static function vietnamese($date, bool $includeTime = false): string
    {
        $carbon = self::make($date);
        $format = $includeTime ? 'd/m/Y H:i' : 'd/m/Y';
        return $carbon->format($format);
    }

    /**
     * Vietnamese relative time
     */
    public static function vietnameseRelative($date): string
    {
        $carbon = self::make($date);
        $now = self::now();
        
        $diffInSeconds = $now->diffInSeconds($carbon);
        $diffInMinutes = $now->diffInMinutes($carbon);
        $diffInHours = $now->diffInHours($carbon);
        $diffInDays = $now->diffInDays($carbon);
        
        if ($carbon->isFuture()) {
            if ($diffInSeconds < 60) {
                return 'trong vài giây nữa';
            } elseif ($diffInMinutes < 60) {
                return "trong {$diffInMinutes} phút nữa";
            } elseif ($diffInHours < 24) {
                return "trong {$diffInHours} giờ nữa";
            } elseif ($diffInDays < 7) {
                return "trong {$diffInDays} ngày nữa";
            } else {
                return $carbon->format('d/m/Y');
            }
        } else {
            if ($diffInSeconds < 60) {
                return 'vừa xong';
            } elseif ($diffInMinutes < 60) {
                return "{$diffInMinutes} phút trước";
            } elseif ($diffInHours < 24) {
                return "{$diffInHours} giờ trước";
            } elseif ($diffInDays < 7) {
                return "{$diffInDays} ngày trước";
            } else {
                return $carbon->format('d/m/Y');
            }
        }
    }

    /**
     * Check if date is today
     */
    public static function isToday($date): bool
    {
        return self::make($date)->isToday();
    }

    /**
     * Check if date is yesterday
     */
    public static function isYesterday($date): bool
    {
        return self::make($date)->isYesterday();
    }

    /**
     * Check if date is this week
     */
    public static function isThisWeek($date): bool
    {
        return self::make($date)->isCurrentWeek();
    }

    /**
     * Check if date is this month
     */
    public static function isThisMonth($date): bool
    {
        return self::make($date)->isCurrentMonth();
    }

    /**
     * Get age from birthdate
     */
    public static function age($birthdate): int
    {
        return self::make($birthdate)->age;
    }

    /**
     * Add time to date
     */
    public static function add($date, int $value, string $unit = 'days'): Carbon
    {
        $carbon = self::make($date);
        
        switch ($unit) {
            case 'seconds':
                return $carbon->addSeconds($value);
            case 'minutes':
                return $carbon->addMinutes($value);
            case 'hours':
                return $carbon->addHours($value);
            case 'days':
                return $carbon->addDays($value);
            case 'weeks':
                return $carbon->addWeeks($value);
            case 'months':
                return $carbon->addMonths($value);
            case 'years':
                return $carbon->addYears($value);
            default:
                return $carbon->addDays($value);
        }
    }

    /**
     * Subtract time from date
     */
    public static function subtract($date, int $value, string $unit = 'days'): Carbon
    {
        return self::add($date, -$value, $unit);
    }

    /**
     * Get start of day
     */
    public static function startOfDay($date): Carbon
    {
        return self::make($date)->startOfDay();
    }

    /**
     * Get end of day
     */
    public static function endOfDay($date): Carbon
    {
        return self::make($date)->endOfDay();
    }

    /**
     * Parse Vietnamese date input
     */
    public static function parseVietnamese(string $input): ?Carbon
    {
        // Common Vietnamese date formats
        $formats = [
            'd/m/Y',
            'd-m-Y',
            'd/m/Y H:i',
            'd-m-Y H:i',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
        ];
        
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $input, self::$defaultTimezone);
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Try parsing with Carbon's flexible parser
        try {
            return Carbon::parse($input, self::$defaultTimezone);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get business days between two dates
     */
    public static function businessDaysBetween($start, $end): int
    {
        $startDate = self::make($start);
        $endDate = self::make($end);
        
        $businessDays = 0;
        while ($startDate->lte($endDate)) {
            if ($startDate->isWeekday()) {
                $businessDays++;
            }
            $startDate->addDay();
        }
        
        return $businessDays;
    }

    /**
     * Check if date is weekend
     */
    public static function isWeekend($date): bool
    {
        return self::make($date)->isWeekend();
    }

    /**
     * Check if date is weekday
     */
    public static function isWeekday($date): bool
    {
        return self::make($date)->isWeekday();
    }

    /**
     * Get date range
     */
    public static function range($start, $end, string $step = '1 day'): array
    {
        $startDate = self::make($start);
        $endDate = self::make($end);
        $dates = [];
        
        while ($startDate->lte($endDate)) {
            $dates[] = $startDate->copy();
            $startDate->add(\DateInterval::createFromDateString($step));
        }
        
        return $dates;
    }
}

// Initialize with Vietnamese settings
// ModernDateHelper::setDefaultTimezone('Asia/Ho_Chi_Minh');
// ModernDateHelper::setDefaultLocale('vi');
