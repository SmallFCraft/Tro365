<?php

namespace Tro365\Models;

use Exception;
use Tro365\Core\BaseModel;
use Tro365\Helpers\LoggerHelper;
use Symfony\Component\Validator\Constraints as Assert;
use Tro365\Helpers\StatusHelper;

/**
 * Contact Model - Quản lý liên hệ
 * Tro365 - Website thuê trọ
 */
class Contact extends BaseModel
{
    protected $table = 'LienHe';
    protected $primaryKey = 'ID';

    // Contact status constants
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_RESOLVED = 2;
    const STATUS_CLOSED = 3;

    // Contact types
    const TYPE_GENERAL = 'general';
    const TYPE_SUPPORT = 'support';
    const TYPE_COMPLAINT = 'complaint';
    const TYPE_SUGGESTION = 'suggestion';
    const TYPE_BUSINESS = 'business';

    /**
     * Create a new contact
     */
    public function create($data)
    {
        try {
            // Validate required fields
            if (empty($data['HoTen']) || empty($data['Email']) || empty($data['NoiDung'])) {
                throw new Exception('Vui lòng nhập đầy đủ thông tin bắt buộc');
            }

            // Prepare contact data
            $contactData = [
                'HoTen' => cleanInput($data['HoTen']),
                'Email' => cleanInput($data['Email']),
                'SDT' => cleanInput($data['SDT'] ?? ''),
                'ChuDe' => cleanInput($data['ChuDe'] ?? ''),
                'NoiDung' => cleanInput($data['NoiDung']),
                'LoaiLienHe' => $data['LoaiLienHe'] ?? self::TYPE_GENERAL,
                'TrangThai' => self::STATUS_PENDING,
                'ThoiGianTao' => date('Y-m-d H:i:s'),
                'DiaChi_IP' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];

            // Insert contact
            $contactId = $this->db->insert($this->table, $contactData);

            // Log the contact creation
            LoggerHelper::info('New contact created', [
                'contact_id' => $contactId,
                'email' => $contactData['Email'],
                'type' => $contactData['LoaiLienHe']
            ]);

            return $contactId;

        } catch (Exception $e) {
            LoggerHelper::error('Failed to create contact', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Get contact by ID
     */
    public function getById($id)
    {
        return $this->db->selectOne("SELECT * FROM {$this->table} WHERE ID = :id", ['id' => $id]);
    }

    /**
     * Get all contacts with pagination
     */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $whereClause = '1=1';
        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $whereClause .= ' AND TrangThai = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $whereClause .= ' AND LoaiLienHe = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $whereClause .= ' AND (HoTen LIKE :search OR Email LIKE :search OR ChuDe LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE {$whereClause}
                ORDER BY ThoiGianTao DESC
                LIMIT :limit OFFSET :offset";

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->db->select($sql, $params);
    }

    /**
     * Get contacts by user (for seller dashboard)
     */
    public function getByUser($userId, $type = 'received', $page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $whereClause = '1=1';
        $params = [];

        // Filter by user type
        if ($type === 'received') {
            $whereClause .= ' AND ChuNhaID = :userId';
        } else {
            $whereClause .= ' AND NguoiLienHeID = :userId';
        }
        $params['userId'] = $userId;

        // Apply additional filters
        if (!empty($filters['status'])) {
            $whereClause .= ' AND TrangThai = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $whereClause .= ' AND (HoTen LIKE :search OR Email LIKE :search OR ChuDe LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE {$whereClause}
                ORDER BY NgayTao DESC
                LIMIT :limit OFFSET :offset";

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->db->select($sql, $params);
    }

    /**
     * Count contacts
     */
    public function count($filters = [])
    {
        $whereClause = '1=1';
        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $whereClause .= ' AND TrangThai = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $whereClause .= ' AND LoaiLienHe = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $whereClause .= ' AND (HoTen LIKE :search OR Email LIKE :search OR ChuDe LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        return $this->db->count($this->table, $whereClause, $params);
    }

    /**
     * Update contact status
     */
    public function updateStatus($id, $status, $note = null)
    {
        try {
            $updateData = [
                'TrangThai' => $status,
                'ThoiGianCapNhat' => date('Y-m-d H:i:s')
            ];

            if ($note) {
                $updateData['GhiChu'] = $note;
            }

            $result = $this->db->update($this->table, $updateData, 'ID = :id', ['id' => $id]);

            LoggerHelper::info('Contact status updated', [
                'contact_id' => $id,
                'status' => $status,
                'note' => $note
            ]);

            return $result;

        } catch (Exception $e) {
            LoggerHelper::error('Failed to update contact status', [
                'contact_id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete contact
     */
    public function delete($id)
    {
        try {
            $result = $this->db->delete($this->table, 'ID = :id', ['id' => $id]);

            LoggerHelper::info('Contact deleted', ['contact_id' => $id]);

            return $result;

        } catch (Exception $e) {
            LoggerHelper::error('Failed to delete contact', [
                'contact_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get contact statistics
     */
    public function getStats()
    {
        $stats = [];

        // Count by status
        $statusStats = $this->db->select(
            "SELECT TrangThai, COUNT(*) as count FROM {$this->table} GROUP BY TrangThai"
        );

        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['TrangThai']] = $stat['count'];
        }

        // Count by type
        $typeStats = $this->db->select(
            "SELECT LoaiLienHe, COUNT(*) as count FROM {$this->table} GROUP BY LoaiLienHe"
        );

        foreach ($typeStats as $stat) {
            $stats['by_type'][$stat['LoaiLienHe']] = $stat['count'];
        }

        // Total count
        $stats['total'] = $this->db->count($this->table);

        // Recent count (last 7 days)
        $recentDate = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stats['recent'] = $this->db->count($this->table, 'ThoiGianTao >= :date', ['date' => $recentDate]);

        return $stats;
    }

    /**
     * Get status name
     */
    public static function getStatusName($status)
    {
        $statuses = [
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_RESOLVED => 'Đã giải quyết',
            self::STATUS_CLOSED => 'Đã đóng'
        ];

        return $statuses[$status] ?? 'Không xác định';
    }

    /**
     * Get type name
     */
    public static function getTypeName($type)
    {
        $types = [
            self::TYPE_GENERAL => 'Liên hệ chung',
            self::TYPE_SUPPORT => 'Hỗ trợ kỹ thuật',
            self::TYPE_COMPLAINT => 'Khiếu nại',
            self::TYPE_SUGGESTION => 'Góp ý',
            self::TYPE_BUSINESS => 'Hợp tác kinh doanh'
        ];

        return $types[$type] ?? 'Khác';
    }
}

