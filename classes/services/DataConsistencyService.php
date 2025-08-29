<?php

namespace Tro365\Services;

use Exception;
use Tro365\Core\Database;

/**
 * Data Consistency Service
 * Tro365 - Website thuê trọ
 *
 * Handles data consistency between KhachHang and DangKySeller tables
 * Prevents data redundancy and ensures single source of truth
 */
class DataConsistencyService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get complete seller information by merging KhachHang and DangKySeller data
     * This ensures consistent data display across the system
     */
    public function getCompleteSellerInfo($sellerId)
    {
        try {
            $sql = "SELECT
                        ds.*,
                        kh.HoTen as KhachHang_HoTen,
                        kh.Email as KhachHang_Email,
                        kh.SDT as KhachHang_SDT,
                        kh.DiaChi as KhachHang_DiaChi,
                        kh.NgayTao as KhachHang_NgayTao,
                        kh.TrangThai as KhachHang_TrangThai
                    FROM DangKySeller ds
                    LEFT JOIN KhachHang kh ON ds.KhachHangID = kh.ID
                    WHERE ds.ID = :seller_id";

            $seller = $this->db->selectOne($sql, ['seller_id' => $sellerId]);

            if (!$seller) {
                return null;
            }

            // Merge data with priority: DangKySeller > KhachHang
            $mergedData = [
                'ID' => $seller['ID'],
                'KhachHangID' => $seller['KhachHangID'],
                'HoTen' => $seller['HoTenChuTro'] ?: $seller['KhachHang_HoTen'],
                'Email' => $seller['EmailLienHe'] ?: $seller['KhachHang_Email'],
                'SDT' => $seller['SDTLienHe'] ?: $seller['KhachHang_SDT'],
                'DiaChi' => $seller['DiaChiKinhDoanh'] ?: $seller['KhachHang_DiaChi'],
                'CCCD' => $seller['SoCCCD'],
                'LyDoMuonBan' => $seller['LyDoMuonBan'],
                'TrangThai' => $seller['TrangThai'],
                'NgayDangKy' => $seller['NgayDangKy'],
                'NgayDuyet' => $seller['NgayDuyet'],
                'NguoiDuyet' => $seller['NguoiDuyet'],
                'GhiChu' => $seller['GhiChu'],
                'KhachHang_TrangThai' => $seller['KhachHang_TrangThai'],
                'KhachHang_NgayTao' => $seller['KhachHang_NgayTao']
            ];

            return $mergedData;

        } catch (Exception $e) {
            error_log("DataConsistency Error in getCompleteSellerInfo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get seller by user ID with consistent data
     */
    public function getSellerByUserId($userId)
    {
        try {
            $sql = "SELECT
                        ds.*,
                        kh.HoTen as KhachHang_HoTen,
                        kh.Email as KhachHang_Email,
                        kh.SDT as KhachHang_SDT,
                        kh.DiaChi as KhachHang_DiaChi
                    FROM DangKySeller ds
                    LEFT JOIN KhachHang kh ON ds.KhachHangID = kh.ID
                    WHERE ds.KhachHangID = :user_id";

            $seller = $this->db->selectOne($sql, ['user_id' => $userId]);

            if (!$seller) {
                return null;
            }

            // Return merged data
            return [
                'ID' => $seller['ID'],
                'KhachHangID' => $seller['KhachHangID'],
                'HoTen' => $seller['HoTenChuTro'] ?: $seller['KhachHang_HoTen'],
                'Email' => $seller['EmailLienHe'] ?: $seller['KhachHang_Email'],
                'SDT' => $seller['SDTLienHe'] ?: $seller['KhachHang_SDT'],
                'DiaChi' => $seller['DiaChiKinhDoanh'] ?: $seller['KhachHang_DiaChi'],
                'CCCD' => $seller['SoCCCD'],
                'TrangThai' => $seller['TrangThai'],
                'NgayDangKy' => $seller['NgayDangKy']
            ];

        } catch (Exception $e) {
            error_log("DataConsistency Error in getSellerByUserId: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Prepare seller data for insertion with consistency checks
     */
    public function prepareSellerData($postData, $userData = null)
    {
        $sellerData = [
            'HoTenChuTro' => cleanInput($postData['landlord_name'] ?? ''),
            'CCCDKinhDoanh' => cleanInput($postData['cccd'] ?? ''),
            'SDTKinhDoanh' => cleanInput($postData['contact_phone'] ?? ''),
            'EmailKinhDoanh' => cleanInput($postData['contact_email'] ?? ''),
            'DiaChiKinhDoanh' => cleanInput($postData['business_address'] ?? ''),
            'LyDoMuonBan' => cleanInput($postData['reason'] ?? ''),
            'TrangThai' => 0, // Pending approval
        ];

        // If user data is available, use it as fallback for empty fields
        if ($userData) {
            $sellerData['HoTenChuTro'] = $sellerData['HoTenChuTro'] ?: $userData['HoTen'];
            $sellerData['EmailKinhDoanh'] = $sellerData['EmailKinhDoanh'] ?: $userData['Email'];
            $sellerData['SDTKinhDoanh'] = $sellerData['SDTKinhDoanh'] ?: $userData['SDT'];
            $sellerData['DiaChiKinhDoanh'] = $sellerData['DiaChiKinhDoanh'] ?: $userData['DiaChi'];
            $sellerData['CCCDKinhDoanh'] = $sellerData['CCCDKinhDoanh'] ?: $userData['CCCD'];
        }

        return $sellerData;
    }

    /**
     * Validate seller data with consistency checks
     */
    public function validateSellerData($sellerData, $userData = null)
    {
        $errors = [];

        // Required fields validation
        if (empty($sellerData['HoTenChuTro'])) {
            $errors[] = 'Vui lòng nhập họ tên chủ trọ';
        }

        if (empty($sellerData['CCCDKinhDoanh'])) {
            $errors[] = 'Vui lòng nhập số CCCD/CMND';
        }

        if (empty($sellerData['SDTKinhDoanh'])) {
            $errors[] = 'Vui lòng nhập số điện thoại liên hệ';
        }

        if (empty($sellerData['DiaChiKinhDoanh'])) {
            $errors[] = 'Vui lòng nhập địa chỉ kinh doanh';
        }

        // Format validation
        if (!empty($sellerData['CCCDKinhDoanh']) && !preg_match('/^\d{9,12}$/', $sellerData['CCCDKinhDoanh'])) {
            $errors[] = 'Số CCCD/CMND không hợp lệ (9-12 chữ số)';
        }

        if (!empty($sellerData['SDTKinhDoanh']) && !isValidPhone($sellerData['SDTKinhDoanh'])) {
            $errors[] = 'Số điện thoại liên hệ không hợp lệ';
        }

        if (!empty($sellerData['EmailKinhDoanh']) && !isValidEmail($sellerData['EmailKinhDoanh'])) {
            $errors[] = 'Email liên hệ không hợp lệ';
        }

        // Check for duplicate CCCD
        if (!empty($sellerData['CCCDKinhDoanh'])) {
            $existingCCCD = $this->db->selectOne(
                "SELECT ID FROM DangKySeller WHERE CCCDKinhDoanh = :cccd AND KhachHangID != :user_id",
                [
                    'cccd' => $sellerData['CCCDKinhDoanh'],
                    'user_id' => $sellerData['KhachHangID'] ?? 0
                ]
            );

            if ($existingCCCD) {
                $errors[] = 'Số CCCD/CMND đã được sử dụng bởi seller khác';
            }
        }

        return $errors;
    }

    /**
     * Update seller status with consistency
     */
    public function updateSellerStatus($sellerId, $status, $note = null, $approverId = null)
    {
        try {
            $updateData = [
                'TrangThai' => $status,
                'NgayCapNhat' => date('Y-m-d H:i:s')
            ];

            if ($status == 1) { // Approved
                $updateData['NgayDuyet'] = date('Y-m-d H:i:s');
                $updateData['NguoiDuyet'] = $approverId;
            }

            if ($note) {
                $updateData['GhiChu'] = $note;
            }

            $result = $this->db->update('DangKySeller', $updateData, 'ID = :id', ['id' => $sellerId]);

            // If approved, update user role to seller
            if ($status == 1) {
                $seller = $this->db->selectOne("SELECT KhachHangID FROM DangKySeller WHERE ID = :id", ['id' => $sellerId]);
                if ($seller) {
                    $this->db->update('KhachHang', ['VaiTroID' => ROLE_SELLER], 'ID = :id', ['id' => $seller['KhachHangID']]);
                }
            }

            return $result;

        } catch (Exception $e) {
            error_log("DataConsistency Error in updateSellerStatus: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all sellers with consistent data
     */
    public function getAllSellers($filters = [], $page = 1, $limit = 20)
    {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = '1=1';
            $params = [];

            // Apply filters
            if (!empty($filters['status'])) {
                $whereClause .= ' AND ds.TrangThai = :status';
                $params['status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $whereClause .= ' AND (ds.HoTenChuTro LIKE :search OR kh.HoTen LIKE :search OR kh.Email LIKE :search)';
                $params['search'] = '%' . $filters['search'] . '%';
            }

            $sql = "SELECT
                        ds.*,
                        kh.HoTen as KhachHang_HoTen,
                        kh.Email as KhachHang_Email,
                        kh.SDT as KhachHang_SDT,
                        kh.VaiTroID as KhachHang_VaiTroID
                    FROM DangKySeller ds
                    LEFT JOIN KhachHang kh ON ds.KhachHangID = kh.ID
                    WHERE {$whereClause}
                    ORDER BY ds.NgayDangKy DESC
                    LIMIT :limit OFFSET :offset";

            $params['limit'] = $limit;
            $params['offset'] = $offset;

            $sellers = $this->db->select($sql, $params);

            // Merge data for each seller
            foreach ($sellers as &$seller) {
                $seller['HoTen_Display'] = $seller['HoTenChuTro'] ?: $seller['KhachHang_HoTen'];
                $seller['Email_Display'] = $seller['EmailLienHe'] ?: $seller['KhachHang_Email'];
                $seller['SDT_Display'] = $seller['SDTLienHe'] ?: $seller['KhachHang_SDT'];
            }

            return $sellers;

        } catch (Exception $e) {
            error_log("DataConsistency Error in getAllSellers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count sellers with filters
     */
    public function countSellers($filters = [])
    {
        try {
            $whereClause = '1=1';
            $params = [];

            // Apply filters
            if (!empty($filters['status'])) {
                $whereClause .= ' AND ds.TrangThai = :status';
                $params['status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $whereClause .= ' AND (ds.HoTenChuTro LIKE :search OR kh.HoTen LIKE :search OR kh.Email LIKE :search)';
                $params['search'] = '%' . $filters['search'] . '%';
            }

            $sql = "SELECT COUNT(*) as total
                    FROM DangKySeller ds
                    LEFT JOIN KhachHang kh ON ds.KhachHangID = kh.ID
                    WHERE {$whereClause}";

            $result = $this->db->selectOne($sql, $params);
            return (int)$result['total'];

        } catch (Exception $e) {
            error_log("DataConsistency Error in countSellers: " . $e->getMessage());
            return 0;
        }
    }
}

