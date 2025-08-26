<?php

namespace Tro365\Helpers;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Date Helper Class
 * Tro365 - Website thuê trọ
 */
class DateHelper
{
    /**
     * Format date to Vietnamese format
     */
    public static function formatVietnamese($date, $format = 'd/m/Y')
    {
        if (empty($date)) {
            return '';
        }
        
        try {
            if (is_string($date)) {
                $date = new DateTime($date);
            }
            
            return $date->format($format);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Format datetime to Vietnamese format
     */
    public static function formatDateTimeVietnamese($datetime, $format = 'd/m/Y H:i')
    {
        return self::formatVietnamese($datetime, $format);
    }
    
    /**
     * Get time ago in Vietnamese
     */
    public static function timeAgo($datetime)
    {
        if (empty($datetime)) {
            return '';
        }
        
        try {
            if (is_string($datetime)) {
                $datetime = new DateTime($datetime);
            }
            
            $now = new DateTime();
            $diff = $now->diff($datetime);
            
            if ($diff->y > 0) {
                return $diff->y . ' năm trước';
            } elseif ($diff->m > 0) {
                return $diff->m . ' tháng trước';
            } elseif ($diff->d > 0) {
                return $diff->d . ' ngày trước';
            } elseif ($diff->h > 0) {
                return $diff->h . ' giờ trước';
            } elseif ($diff->i > 0) {
                return $diff->i . ' phút trước';
            } else {
                return 'Vừa xong';
            }
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get current date in Vietnamese format
     */
    public static function now($format = 'd/m/Y H:i:s')
    {
        return (new DateTime())->format($format);
    }
    
    /**
     * Get current date for database
     */
    public static function nowForDatabase()
    {
        return (new DateTime())->format('Y-m-d H:i:s');
    }
    
    /**
     * Convert date format
     */
    public static function convertFormat($date, $fromFormat, $toFormat)
    {
        try {
            $datetime = DateTime::createFromFormat($fromFormat, $date);
            if (!$datetime) {
                throw new Exception('Invalid date format');
            }
            
            return $datetime->format($toFormat);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Check if date is valid
     */
    public static function isValid($date, $format = 'Y-m-d')
    {
        try {
            $datetime = DateTime::createFromFormat($format, $date);
            return $datetime && $datetime->format($format) === $date;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get age from birth date
     */
    public static function getAge($birthDate)
    {
        try {
            if (is_string($birthDate)) {
                $birthDate = new DateTime($birthDate);
            }
            
            $now = new DateTime();
            $age = $now->diff($birthDate);
            
            return $age->y;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get days between two dates
     */
    public static function daysBetween($date1, $date2)
    {
        try {
            if (is_string($date1)) {
                $date1 = new DateTime($date1);
            }
            if (is_string($date2)) {
                $date2 = new DateTime($date2);
            }
            
            $diff = $date1->diff($date2);
            return $diff->days;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Add days to date
     */
    public static function addDays($date, $days, $format = 'Y-m-d')
    {
        try {
            if (is_string($date)) {
                $date = new DateTime($date);
            }
            
            $date->modify("+{$days} days");
            return $date->format($format);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Subtract days from date
     */
    public static function subtractDays($date, $days, $format = 'Y-m-d')
    {
        try {
            if (is_string($date)) {
                $date = new DateTime($date);
            }
            
            $date->modify("-{$days} days");
            return $date->format($format);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get start of month
     */
    public static function startOfMonth($date = null, $format = 'Y-m-d')
    {
        try {
            if ($date) {
                if (is_string($date)) {
                    $date = new DateTime($date);
                }
            } else {
                $date = new DateTime();
            }
            
            $date->modify('first day of this month');
            return $date->format($format);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get end of month
     */
    public static function endOfMonth($date = null, $format = 'Y-m-d')
    {
        try {
            if ($date) {
                if (is_string($date)) {
                    $date = new DateTime($date);
                }
            } else {
                $date = new DateTime();
            }
            
            $date->modify('last day of this month');
            return $date->format($format);
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get Vietnamese day name
     */
    public static function getVietnameseDayName($date)
    {
        try {
            if (is_string($date)) {
                $date = new DateTime($date);
            }
            
            $dayNames = [
                'Sunday' => 'Chủ nhật',
                'Monday' => 'Thứ hai',
                'Tuesday' => 'Thứ ba',
                'Wednesday' => 'Thứ tư',
                'Thursday' => 'Thứ năm',
                'Friday' => 'Thứ sáu',
                'Saturday' => 'Thứ bảy'
            ];
            
            $englishDay = $date->format('l');
            return $dayNames[$englishDay] ?? '';
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get Vietnamese month name
     */
    public static function getVietnameseMonthName($date)
    {
        try {
            if (is_string($date)) {
                $date = new DateTime($date);
            }
            
            $monthNames = [
                'January' => 'Tháng một',
                'February' => 'Tháng hai',
                'March' => 'Tháng ba',
                'April' => 'Tháng tư',
                'May' => 'Tháng năm',
                'June' => 'Tháng sáu',
                'July' => 'Tháng bảy',
                'August' => 'Tháng tám',
                'September' => 'Tháng chín',
                'October' => 'Tháng mười',
                'November' => 'Tháng mười một',
                'December' => 'Tháng mười hai'
            ];
            
            $englishMonth = $date->format('F');
            return $monthNames[$englishMonth] ?? '';
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Check if date is weekend
     */
    public static function isWeekend($date)
    {
        try {
            if (is_string($date)) {
                $date = new DateTime($date);
            }
            
            $dayOfWeek = $date->format('N'); // 1 = Monday, 7 = Sunday
            return $dayOfWeek >= 6; // Saturday or Sunday
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get quarter of year
     */
    public static function getQuarter($date)
    {
        try {
            if (is_string($date)) {
                $date = new DateTime($date);
            }
            
            $month = (int)$date->format('n');
            return ceil($month / 3);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Format date range
     */
    public static function formatDateRange($startDate, $endDate, $format = 'd/m/Y')
    {
        try {
            $start = self::formatVietnamese($startDate, $format);
            $end = self::formatVietnamese($endDate, $format);
            
            if ($start === $end) {
                return $start;
            }
            
            return $start . ' - ' . $end;
        } catch (Exception $e) {
            return '';
        }
    }
}
