<?php

namespace Tro365\Models;

use Tro365\Core\BaseModel;
use Tro365\Helpers\ValidationHelper;
use Tro365\Helpers\LoggerHelper;
use Tro365\Helpers\MarkdownHelper;
use Tro365\Helpers\StatusHelper;
use Tro365\Services\LocationService;

/**
 * Post Class
 * Tro365 - Website thuê trọ
 */
class Post extends BaseModel
{
    protected $table = 'BaiDang';

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Hook: Before creating post
     */
    protected function beforeCreate(&$data)
    {
        // Validate post creation data using Symfony Validator
        $validation = \Tro365\Helpers\ValidationHelper::validatePostCreation([
            'title' => $data['TieuDe'] ?? '',
            'content' => $data['NoiDung'] ?? '',  // Changed from MoTa to NoiDung
            'price' => $data['Gia'] ?? 0,
            'area' => $data['DienTich'] ?? 0,
            'address' => $data['DiaChi'] ?? '',
            'category_id' => $data['DanhMucID'] ?? 0
        ]);

        if (!$validation['valid']) {
            LoggerHelper::error('Post creation validation failed', [
                'errors' => $validation['errors'],
                'user_id' => $data['NguoiDangID'] ?? 'unknown'
            ]);
            throw new Exception(implode(', ', $validation['errors']));
        }

        // Process content with Markdown (NoiDung only)
        if (!empty($data['NoiDung'])) {
            // Validate markdown content
            $markdownValidation = MarkdownHelper::validate($data['NoiDung']);
            if (!$markdownValidation['valid']) {
                throw new Exception(implode(', ', $markdownValidation['errors']));
            }

            // Store processed HTML (NoiDung already contains markdown)
            // No need for separate HTML field as NoiDung will be processed on display
        }

        // Set default values
        $data['TrangThai'] = $data['TrangThai'] ?? StatusHelper::POST_PENDING;
        $data['LuotXem'] = 0;
    }
    
    /**
     * Get post by ID
     */
    public function getById($id)
    {
        $sql = "SELECT bd.*, dm.TenDM, kh.HoTen as NguoiDang, kh.SDT as SDTNguoiDang, kh.Email as EmailNguoiDang
                FROM BaiDang bd
                LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
                LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
                WHERE bd.ID = :id";

        $post = $this->db->selectOne($sql, ['id' => $id]);

        if ($post) {
            // Get location names from API
            $locationService = new LocationService();
            $post['TenTT'] = $post['TinhThanhID'] ? $locationService->getProvinceName($post['TinhThanhID']) : '';
            $post['TenQH'] = $post['QuanHuyenID'] ? $locationService->getDistrictName($post['QuanHuyenID']) : '';
            $post['TenXP'] = $post['XaPhuongID'] ? $locationService->getWardName($post['XaPhuongID']) : '';
        }

        return $post;
    }
    
    /**
     * Hook: Before updating post
     */
    protected function beforeUpdate($id, &$data)
    {
        // Remove sensitive fields that shouldn't be updated directly
        unset($data['ID'], $data['NgayTao'], $data['LuotXem']);

        if (empty($data)) {
            throw new Exception("Không có dữ liệu để cập nhật");
        }

        // Validate price if provided using Symfony Validator
        if (isset($data['Gia'])) {
            $validation = \Tro365\Helpers\ValidationHelper::validateValue($data['Gia'], [
                new \Symfony\Component\Validator\Constraints\NotBlank(['message' => 'Giá không được để trống']),
                new \Symfony\Component\Validator\Constraints\Type(['type' => 'numeric', 'message' => 'Giá phải là số']),
                new \Symfony\Component\Validator\Constraints\Range([
                    'min' => 0,
                    'max' => 999999999,
                    'notInRangeMessage' => 'Giá phải từ {{ min }} đến {{ max }}'
                ])
            ]);

            if (!$validation['valid']) {
                throw new Exception(implode(', ', $validation['errors']));
            }
        }

        // Validate title length if provided
        if (isset($data['TieuDe']) && strlen($data['TieuDe']) > MAX_TITLE_LENGTH) {
            throw new Exception("Tiêu đề không được quá " . MAX_TITLE_LENGTH . " ký tự");
        }

        // MoTa field removed - no longer needed
        // Content validation is handled in beforeCreate() method
    }
    
    /**
     * Hook: Before deleting post
     */
    protected function beforeDelete($id)
    {
        // Delete related images first
        $this->deletePostImages($id);
    }
    
    /**
     * Get posts with pagination and filters
     */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        
        $where = "1=1";
        $params = [];
        
        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $where .= " AND bd.TrangThai = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $where .= " AND bd.NguoiDangID = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['category'])) {
            $where .= " AND bd.DanhMucID = :category";
            $params['category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (bd.TieuDe LIKE :search1 OR bd.NoiDung LIKE :search2 OR bd.DiaChi LIKE :search3)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search1'] = $searchTerm;
            $params['search2'] = $searchTerm;
            $params['search3'] = $searchTerm;
        }
        
        $sql = "SELECT bd.*, dm.TenDM, kh.HoTen as NguoiDang
                FROM BaiDang bd
                LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
                LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
                WHERE {$where}
                ORDER BY bd.NgayTao DESC
                LIMIT :limit OFFSET :offset";
        
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $posts = $this->db->select($sql, $params);

        // Add location names from API
        if (!empty($posts)) {
            $locationService = new LocationService();
            foreach ($posts as &$post) {
                $post['TenTT'] = $post['TinhThanhID'] ? $locationService->getProvinceName($post['TinhThanhID']) : '';
                $post['TenQH'] = $post['QuanHuyenID'] ? $locationService->getDistrictName($post['QuanHuyenID']) : '';
                $post['TenXP'] = $post['XaPhuongID'] ? $locationService->getWardName($post['XaPhuongID']) : '';
            }
        }

        return $posts;
    }
    
    /**
     * Count posts
     */
    public function count($filters = [])
    {
        $where = "1=1";
        $params = [];
        
        // Apply same filters as getAll
        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $where .= " AND TrangThai = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $where .= " AND NguoiDangID = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['category'])) {
            $where .= " AND DanhMucID = :category";
            $params['category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (TieuDe LIKE :search1 OR MoTa LIKE :search2 OR DiaChi LIKE :search3)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search1'] = $searchTerm;
            $params['search2'] = $searchTerm;
            $params['search3'] = $searchTerm;
        }
        
        return $this->db->count('BaiDang', $where, $params);
    }
    
    /**
     * Approve post
     */
    public function approve($id, $approverId)
    {
        return $this->update($id, [
            'TrangThai' => StatusHelper::POST_APPROVED,
            'NguoiDuyet' => $approverId,
            'NgayDuyet' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Reject post
     */
    public function reject($id, $approverId, $reason = null)
    {
        $data = [
            'TrangThai' => StatusHelper::POST_REJECTED,
            'NguoiDuyet' => $approverId,
            'NgayDuyet' => date('Y-m-d H:i:s')
        ];

        if ($reason) {
            $data['LyDoTuChoi'] = $reason;
        }

        return $this->update($id, $data);
    }

    /**
     * Hide post
     */
    public function hide($id, $userId)
    {
        return $this->update($id, [
            'TrangThai' => StatusHelper::POST_HIDDEN,
            'NguoiDuyet' => $userId,
            'NgayDuyet' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Show post (unhide)
     */
    public function show($id, $userId)
    {
        return $this->update($id, [
            'TrangThai' => StatusHelper::POST_APPROVED,
            'NguoiDuyet' => $userId,
            'NgayDuyet' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Toggle post visibility (hide/show)
     */
    public function toggleVisibility($id, $userId)
    {
        $post = $this->getById($id);
        if (!$post) {
            throw new Exception("Không tìm thấy bài đăng");
        }

        // Only toggle between APPROVED and HIDDEN status
        if ($post['TrangThai'] == StatusHelper::POST_APPROVED) {
            return $this->hide($id, $userId);
        } elseif ($post['TrangThai'] == StatusHelper::POST_HIDDEN) {
            return $this->show($id, $userId);
        } else {
            throw new Exception("Chỉ có thể ẩn/hiện bài đăng đã được duyệt");
        }
    }
    
    /**
     * Add image to post
     */
    public function addImage($postId, $imagePath, $order = 0)
    {
        try {
            return $this->db->insert('HinhAnhBaiDang', [
                'BaiDangID' => $postId,
                'DuongDan' => $imagePath,
                'ThuTu' => $order
            ]);
            
        } catch (Exception $e) {
            throw new Exception("Lỗi thêm hình ảnh: " . $e->getMessage());
        }
    }
    
    /**
     * Get post images
     */
    public function getImages($postId)
    {
        return $this->db->select(
            "SELECT * FROM HinhAnhBaiDang WHERE BaiDangID = :postId ORDER BY ThuTu",
            ['postId' => $postId]
        );
    }
    
    /**
     * Delete post images
     */
    public function deletePostImages($postId)
    {
        try {
            // Get image paths to delete files
            $images = $this->getImages($postId);

            // Delete from database
            $this->db->delete('HinhAnhBaiDang', 'BaiDangID = :postId', ['postId' => $postId]);

            // Delete physical files
            foreach ($images as $image) {
                $filePath = __DIR__ . '/../' . ltrim($image['DuongDan'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            return true;

        } catch (Exception $e) {
            throw new Exception("Lỗi xóa hình ảnh: " . $e->getMessage());
        }
    }

    /**
     * Get image by ID
     */
    public function getImageById($imageId)
    {
        return $this->db->selectOne(
            "SELECT * FROM HinhAnhBaiDang WHERE ID = :imageId",
            ['imageId' => $imageId]
        );
    }

    /**
     * Remove single image
     */
    public function removeImage($imageId)
    {
        try {
            // Get image info
            $image = $this->getImageById($imageId);
            if (!$image) {
                throw new Exception("Hình ảnh không tồn tại");
            }

            // Delete from database
            $result = $this->db->delete('HinhAnhBaiDang', 'ID = :imageId', ['imageId' => $imageId]);

            if ($result) {
                // Delete physical file
                $filePath = __DIR__ . '/../' . ltrim($image['DuongDan'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // Update main image if this was the main image
                $postData = $this->getById($image['BaiDangID']);
                if ($postData && $postData['AnhDaiDien'] === $image['DuongDan']) {
                    // Get remaining images
                    $remainingImages = $this->getImages($image['BaiDangID']);
                    $newMainImage = !empty($remainingImages) ? $remainingImages[0]['DuongDan'] : null;

                    $this->update($image['BaiDangID'], ['AnhDaiDien' => $newMainImage]);
                }
            }

            return $result;

        } catch (Exception $e) {
            throw new Exception("Lỗi xóa hình ảnh: " . $e->getMessage());
        }
    }
    
    /**
     * Update view count
     */
    public function incrementViewCount($id)
    {
        try {
            $this->db->execute("UPDATE BaiDang SET LuotXem = LuotXem + 1 WHERE ID = :id", ['id' => $id]);
        } catch (Exception $e) {
            // Log error but don't throw exception
            error_log("Failed to increment view count: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user can edit post
     */
    public function canEdit($postId, $userId, $userRole)
    {
        $post = $this->getById($postId);

        if (!$post) {
            return false;
        }

        // Admin and moderator can edit any post
        if ($userRole >= ROLE_MODERATOR) {
            return true;
        }

        // Owner can edit their own post
        return $post['NguoiDangID'] == $userId;
    }

    /**
     * Generate excerpt from NoiDung for display purposes
     * Replaces the old MoTa field functionality
     */
    public function generateExcerpt($noiDung, $maxLength = 200)
    {
        if (empty($noiDung)) {
            return '';
        }

        // Use MarkdownHelper to create clean excerpt
        return MarkdownHelper::createExcerpt($noiDung, $maxLength);
    }

    /**
     * Get post with auto-generated excerpt
     */
    public function getWithExcerpt($id, $excerptLength = 200)
    {
        $post = $this->getById($id);

        if ($post && !empty($post['NoiDung'])) {
            $post['excerpt'] = $this->generateExcerpt($post['NoiDung'], $excerptLength);
        } else {
            $post['excerpt'] = '';
        }

        return $post;
    }
    
    /**
     * Get categories
     */
    public function getCategories()
    {
        $category = new Category();
        return $category->getAllActive();
    }
    
    /**
     * Get provinces using API
     */
    public function getProvinces()
    {
        $locationService = new LocationService();
        return $locationService->getProvinces();
    }

    /**
     * Get districts by province using API
     */
    public function getDistricts($provinceId)
    {
        $locationService = new LocationService();
        return $locationService->getDistricts($provinceId);
    }

    /**
     * Get wards by district using API
     */
    public function getWards($districtId)
    {
        $locationService = new LocationService();
        return $locationService->getWards($districtId);
    }

    /**
     * Get province name by code
     */
    public function getProvinceName($code)
    {
        $locationService = new LocationService();
        return $locationService->getProvinceName($code);
    }

    /**
     * Get district name by code
     */
    public function getDistrictName($code)
    {
        $locationService = new LocationService();
        return $locationService->getDistrictName($code);
    }

    /**
     * Get ward name by code
     */
    public function getWardName($code)
    {
        $locationService = new LocationService();
        return $locationService->getWardName($code);
    }
}
