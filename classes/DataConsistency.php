<?php
/**
 * Data Consistency Helper Class
 * Tro365 - Website thuê trọ
 * 
 * Handles data consistency between KhachHang and DangKySeller tables
 * Prevents data redundancy and ensures single source of truth
 */

namespace Tro365;

use Tro365\Core\Database;

class DataConsistency {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if two values are different (considering empty values)
     */
    public function isDifferent($newValue, $currentValue) {
        // Normalize empty values
        $newValue = trim($newValue ?? '');
        $currentValue = trim($currentValue ?? '');
        
        // If new value is empty, consider it same as current
        if (empty($newValue)) {
            return false;
        }
        
        // If current value is empty, new value is different
        if (empty($currentValue)) {
            return true;
        }
        
        // Compare normalized values
        return $newValue !== $currentValue;
    }
    
    /**
     * Get effective value (seller-specific or fallback to user data)
     */
    public function getEffectiveValue($sellerValue, $userValue) {
        $sellerValue = trim($sellerValue ?? '');
        $userValue = trim($userValue ?? '');
        
        return !empty($sellerValue) ? $sellerValue : $userValue;
    }
    
    /**
     * Prepare seller data with consistency checks
     * Supports both old and new normalized database structure
     */
    public function prepareSellerData($postData, $userData = null) {
        $sellerData = [
            'HoTenChuTro' => cleanInput($postData['owner_name'] ?? $postData['full_name'] ?? ''),
            'LyDoMuonBan' => cleanInput($postData['reason'] ?? ''),
            'TrangThai' => 0 // Pending approval
        ];

        // Check if we're using normalized structure
        $isNormalized = $this->isNormalizedStructure();

        if ($isNormalized) {
            return $this->prepareSellerDataNormalized($sellerData, $postData, $userData);
        } else {
            return $this->prepareSellerDataLegacy($sellerData, $postData, $userData);
        }
    }

    /**
     * Prepare seller data unified method (eliminates duplication)
     */
    private function prepareSellerDataUnified($sellerData, $postData, $userData, $isNormalized) {
        // Define column mappings based on structure
        $columns = $isNormalized ? [
            'cccd' => 'CCCDKinhDoanh',
            'address' => 'DiaChiKinhDoanh',
            'phone' => 'SDTKinhDoanh',
            'email' => 'EmailKinhDoanh'
        ] : [
            'cccd' => 'CCCD',
            'address' => 'DiaChi',
            'phone' => 'SDTLienHe',
            'email' => 'EmailLienHe'
        ];

        // Extract input values
        $inputValues = [
            'cccd' => $postData['cccd'] ?? '',
            'address' => $postData['business_address'] ?? $postData['address'] ?? '',
            'phone' => $postData['contact_phone'] ?? $postData['phone'] ?? '',
            'email' => $postData['contact_email'] ?? $postData['email'] ?? ''
        ];

        if ($userData) {
            // Only store different values
            $userValues = [
                'cccd' => $userData['CCCD'] ?? '',
                'address' => $userData['DiaChi'] ?? '',
                'phone' => $userData['SDT'] ?? '',
                'email' => $userData['Email'] ?? ''
            ];

            foreach ($inputValues as $field => $value) {
                if ($this->isDifferent($value, $userValues[$field])) {
                    $sellerData[$columns[$field]] = cleanInput($value);
                }
            }
        } else {
            // No user data provided, store all seller-specific data
            foreach ($inputValues as $field => $value) {
                $sellerData[$columns[$field]] = cleanInput($value);
            }
        }

        return $sellerData;
    }

    /**
     * Prepare seller data for normalized structure
     */
    private function prepareSellerDataNormalized($sellerData, $postData, $userData) {
        return $this->prepareSellerDataUnified($sellerData, $postData, $userData, true);
    }

    /**
     * Prepare seller data for legacy structure (backward compatibility)
     */
    private function prepareSellerDataLegacy($sellerData, $postData, $userData) {
        return $this->prepareSellerDataUnified($sellerData, $postData, $userData, false);
    }
    
    /**
     * Get complete seller information (merged with user data)
     * Supports both old and new normalized database structure
     */
    public function getCompleteSellerInfo($sellerId) {
        // Check if we're using normalized structure
        $isNormalized = $this->isNormalizedStructure();

        if ($isNormalized) {
            return $this->getCompleteSellerInfoNormalized($sellerId);
        } else {
            return $this->getCompleteSellerInfoLegacy($sellerId);
        }
    }

    /**
     * Check if database is using normalized structure
     */
    public function isNormalizedStructure() {
        try {
            $columns = $this->db->select("SHOW COLUMNS FROM DangKySeller LIKE 'CCCDKinhDoanh'");
            return !empty($columns);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get seller info from any structure (unified method)
     */
    private function getCompleteSellerInfoUnified($sellerId, $isNormalized) {
        $query = "
            SELECT
                ds.*,
                kh.HoTen as UserHoTen,
                kh.Email as UserEmail,
                kh.SDT as UserSDT,
                kh.DiaChi as UserDiaChi,
                kh.CCCD as UserCCCD,
                kh.TenDN,
                kh.VaiTroID,
                kh.AnhDaiDien,
                kh.NgayTao as UserNgayTao
            FROM DangKySeller ds
            INNER JOIN KhachHang kh ON ds.KhachHangID = kh.ID
            WHERE ds.ID = :seller_id
        ";

        $sellerData = $this->db->selectOne($query, ['seller_id' => $sellerId]);

        if (!$sellerData) {
            return null;
        }

        // Get effective values based on structure
        if ($isNormalized) {
            $effectiveCCCD = $this->getEffectiveValue($sellerData['CCCDKinhDoanh'] ?? '', $sellerData['UserCCCD']);
            $effectiveEmail = $this->getEffectiveValue($sellerData['EmailKinhDoanh'] ?? '', $sellerData['UserEmail']);
            $effectiveSDT = $this->getEffectiveValue($sellerData['SDTKinhDoanh'] ?? '', $sellerData['UserSDT']);
            $effectiveDiaChi = $this->getEffectiveValue($sellerData['DiaChiKinhDoanh'] ?? '', $sellerData['UserDiaChi']);

            $businessData = [
                'CCCD' => $sellerData['CCCDKinhDoanh'] ?? '',
                'Email' => $sellerData['EmailKinhDoanh'] ?? '',
                'SDT' => $sellerData['SDTKinhDoanh'] ?? '',
                'DiaChi' => $sellerData['DiaChiKinhDoanh'] ?? ''
            ];
        } else {
            $effectiveCCCD = $this->getEffectiveValue($sellerData['CCCD'] ?? '', $sellerData['UserCCCD']);
            $effectiveEmail = $this->getEffectiveValue($sellerData['EmailLienHe'] ?? '', $sellerData['UserEmail']);
            $effectiveSDT = $this->getEffectiveValue($sellerData['SDTLienHe'] ?? '', $sellerData['UserSDT']);
            $effectiveDiaChi = $this->getEffectiveValue($sellerData['DiaChi'] ?? '', $sellerData['UserDiaChi']);

            $businessData = [
                'CCCD' => $sellerData['CCCD'] ?? '',
                'Email' => $sellerData['EmailLienHe'] ?? '',
                'SDT' => $sellerData['SDTLienHe'] ?? '',
                'DiaChi' => $sellerData['DiaChi'] ?? ''
            ];
        }

        // Return unified structure
        return [
            'ID' => $sellerData['ID'],
            'KhachHangID' => $sellerData['KhachHangID'],
            'HoTenChuTro' => $sellerData['HoTenChuTro'],
            'TenDN' => $sellerData['TenDN'],
            'VaiTroID' => $sellerData['VaiTroID'],
            'AnhDaiDien' => $sellerData['AnhDaiDien'],

            // Effective values
            'CCCD' => $effectiveCCCD,
            'Email' => $effectiveEmail,
            'SDT' => $effectiveSDT,
            'DiaChi' => $effectiveDiaChi,

            // Seller-specific data
            'LyDoMuonBan' => $sellerData['LyDoMuonBan'],
            'TrangThai' => $sellerData['TrangThai'],
            'NguoiDuyet' => $sellerData['NguoiDuyet'],
            'NgayDuyet' => $sellerData['NgayDuyet'],
            'LyDoTuChoi' => $sellerData['LyDoTuChoi'],
            'NgayTao' => $sellerData['NgayTao'],
            'NgayCapNhat' => $sellerData['NgayCapNhat'],

            // Document paths
            'AnhCCCDTruoc' => $sellerData['AnhCCCDTruoc'],
            'AnhCCCDSau' => $sellerData['AnhCCCDSau'],
            'GiayPhepKD' => $sellerData['GiayPhepKD'],

            // Raw data for reference
            'UserData' => [
                'HoTen' => $sellerData['UserHoTen'],
                'Email' => $sellerData['UserEmail'],
                'SDT' => $sellerData['UserSDT'],
                'DiaChi' => $sellerData['UserDiaChi'],
                'CCCD' => $sellerData['UserCCCD'],
                'NgayTao' => $sellerData['UserNgayTao']
            ],
            'BusinessSpecificData' => $businessData
        ];
    }

    /**
     * Get seller info from normalized structure
     */
    private function getCompleteSellerInfoNormalized($sellerId) {
        return $this->getCompleteSellerInfoUnified($sellerId, true);
    }

    /**
     * Get seller info from legacy structure (backward compatibility)
     */
    private function getCompleteSellerInfoLegacy($sellerId) {
        return $this->getCompleteSellerInfoUnified($sellerId, false);
    }

    /**
     * Get effective values for seller display (eliminates admin page duplication)
     */
    public function getEffectiveSellerValues($seller) {
        $isNormalized = $this->isNormalizedStructure();

        if ($isNormalized) {
            return [
                'CCCD' => $this->getEffectiveValue($seller['CCCDKinhDoanh'] ?? '', $seller['UserCCCD'] ?? ''),
                'Phone' => $this->getEffectiveValue($seller['SDTKinhDoanh'] ?? '', $seller['UserSDT'] ?? ''),
                'Email' => $this->getEffectiveValue($seller['EmailKinhDoanh'] ?? '', $seller['Email'] ?? ''),
                'Address' => $this->getEffectiveValue($seller['DiaChiKinhDoanh'] ?? '', $seller['UserDiaChi'] ?? '')
            ];
        } else {
            return [
                'CCCD' => $this->getEffectiveValue($seller['CCCD'] ?? '', $seller['UserCCCD'] ?? ''),
                'Phone' => $this->getEffectiveValue($seller['SDTLienHe'] ?? '', $seller['UserSDT'] ?? ''),
                'Email' => $this->getEffectiveValue($seller['EmailLienHe'] ?? '', $seller['Email'] ?? ''),
                'Address' => $this->getEffectiveValue($seller['DiaChi'] ?? '', $seller['UserDiaChi'] ?? '')
            ];
        }
    }

    /**
     * Validate seller data consistency
     */
    public function validateSellerData($sellerData, $userData) {
        $errors = [];
        
        // Check required fields
        if (empty($sellerData['HoTenChuTro'])) {
            $errors[] = 'Họ tên chủ trọ không được để trống';
        }
        
        // Validate effective CCCD
        $effectiveCCCD = $this->getEffectiveValue($sellerData['CCCD'] ?? '', $userData['CCCD'] ?? '');
        if (empty($effectiveCCCD)) {
            $errors[] = 'Số CCCD/CMND không được để trống';
        } elseif (!preg_match('/^[0-9]{9,12}$/', $effectiveCCCD)) {
            $errors[] = 'Số CCCD/CMND không hợp lệ';
        }
        
        // Validate effective phone
        $effectivePhone = $this->getEffectiveValue($sellerData['SDTLienHe'] ?? '', $userData['SDT'] ?? '');
        if (empty($effectivePhone)) {
            $errors[] = 'Số điện thoại liên hệ không được để trống';
        } elseif (!isValidPhone($effectivePhone)) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        
        // Validate effective address
        $effectiveAddress = $this->getEffectiveValue($sellerData['DiaChi'] ?? '', $userData['DiaChi'] ?? '');
        if (empty($effectiveAddress)) {
            $errors[] = 'Địa chỉ kinh doanh không được để trống';
        }
        
        return $errors;
    }
    
    /**
     * Get seller by user ID with merged data
     */
    public function getSellerByUserId($userId) {
        $query = "
            SELECT
                ds.*,
                kh.HoTen as UserHoTen,
                kh.Email as UserEmail,
                kh.SDT as UserSDT,
                kh.DiaChi as UserDiaChi,
                kh.CCCD as UserCCCD,
                kh.TenDN,
                kh.VaiTroID,
                kh.AnhDaiDien
            FROM DangKySeller ds
            INNER JOIN KhachHang kh ON ds.KhachHangID = kh.ID
            WHERE ds.KhachHangID = :user_id AND ds.TrangThai = 1
            ORDER BY ds.NgayTao DESC
            LIMIT 1
        ";

        $sellerData = $this->db->selectOne($query, ['user_id' => $userId]);

        if (!$sellerData) {
            return null;
        }

        return $this->formatSellerData($sellerData);
    }

    /**
     * Format seller data with effective values (supports both structures)
     */
    private function formatSellerData($sellerData) {
        $isNormalized = $this->isNormalizedStructure();

        if ($isNormalized) {
            $effectiveCCCD = $this->getEffectiveValue($sellerData['CCCDKinhDoanh'] ?? '', $sellerData['UserCCCD']);
            $effectiveEmail = $this->getEffectiveValue($sellerData['EmailKinhDoanh'] ?? '', $sellerData['UserEmail']);
            $effectiveSDT = $this->getEffectiveValue($sellerData['SDTKinhDoanh'] ?? '', $sellerData['UserSDT']);
            $effectiveDiaChi = $this->getEffectiveValue($sellerData['DiaChiKinhDoanh'] ?? '', $sellerData['UserDiaChi']);
        } else {
            $effectiveCCCD = $this->getEffectiveValue($sellerData['CCCD'] ?? '', $sellerData['UserCCCD']);
            $effectiveEmail = $this->getEffectiveValue($sellerData['EmailLienHe'] ?? '', $sellerData['UserEmail']);
            $effectiveSDT = $this->getEffectiveValue($sellerData['SDTLienHe'] ?? '', $sellerData['UserSDT']);
            $effectiveDiaChi = $this->getEffectiveValue($sellerData['DiaChi'] ?? '', $sellerData['UserDiaChi']);
        }

        return [
            'ID' => $sellerData['ID'],
            'KhachHangID' => $sellerData['KhachHangID'],
            'HoTenChuTro' => $sellerData['HoTenChuTro'],
            'TenDN' => $sellerData['TenDN'],
            'VaiTroID' => $sellerData['VaiTroID'],
            'AnhDaiDien' => $sellerData['AnhDaiDien'],

            // Effective values
            'CCCD' => $effectiveCCCD,
            'Email' => $effectiveEmail,
            'SDT' => $effectiveSDT,
            'DiaChi' => $effectiveDiaChi,

            // Seller-specific data
            'LyDoMuonBan' => $sellerData['LyDoMuonBan'],
            'TrangThai' => $sellerData['TrangThai'],
            'NgayTao' => $sellerData['NgayTao'],
            'NgayCapNhat' => $sellerData['NgayCapNhat'],

            // Document paths
            'AnhCCCDTruoc' => $sellerData['AnhCCCDTruoc'],
            'AnhCCCDSau' => $sellerData['AnhCCCDSau'],
            'GiayPhepKD' => $sellerData['GiayPhepKD']
        ];
    }

    /**
     * Check if user has different business info from personal info
     */
    public function hasBusinessSpecificInfo($sellerId) {
        $query = "
            SELECT
                ds.CCCD as SellerCCCD,
                ds.DiaChi as SellerDiaChi,
                ds.SDTLienHe as SellerSDT,
                ds.EmailLienHe as SellerEmail,
                kh.CCCD as UserCCCD,
                kh.DiaChi as UserDiaChi,
                kh.SDT as UserSDT,
                kh.Email as UserEmail
            FROM DangKySeller ds
            INNER JOIN KhachHang kh ON ds.KhachHangID = kh.ID
            WHERE ds.ID = :seller_id
        ";

        $data = $this->db->selectOne($query, ['seller_id' => $sellerId]);

        if (!$data) {
            return false;
        }

        return [
            'has_business_cccd' => !empty($data['SellerCCCD']),
            'has_business_address' => !empty($data['SellerDiaChi']),
            'has_business_phone' => !empty($data['SellerSDT']),
            'has_business_email' => !empty($data['SellerEmail']),
            'any_business_specific' => !empty($data['SellerCCCD']) || !empty($data['SellerDiaChi']) || !empty($data['SellerSDT']) || !empty($data['SellerEmail'])
        ];
    }

    /**
     * Sync user data changes to seller registrations
     */
    public function syncUserDataToSellers($userId, $updatedUserData) {
        try {
            // Get all seller registrations for this user
            $sellers = $this->db->selectAll(
                "SELECT ID, CCCD, DiaChi, SDTLienHe, EmailLienHe FROM DangKySeller WHERE KhachHangID = :user_id",
                ['user_id' => $userId]
            );

            foreach ($sellers as $seller) {
                $updateData = [];

                // If seller has same CCCD as old user data, update it
                if (!empty($updatedUserData['CCCD']) && empty($seller['CCCD'])) {
                    // Seller was using user's CCCD, no need to update
                } elseif (!empty($seller['CCCD']) && $seller['CCCD'] === $updatedUserData['old_CCCD']) {
                    $updateData['CCCD'] = $updatedUserData['CCCD'];
                }

                // Similar logic for other fields...
                if (!empty($updateData)) {
                    $this->db->update('DangKySeller', $updateData, ['ID' => $seller['ID']]);
                }
            }

            return true;
        } catch (Exception $e) {
            writeLog("Error syncing user data to sellers: " . $e->getMessage());
            return false;
        }
    }
}
