<?php

namespace Tro365\Api;

use Exception;
use Tro365\Core\BaseAPI;

/**
 * Posts API Class
 * Tro365 - Standardized posts management API
 */
class PostsAPI extends BaseAPI
{
    /**
     * Handle API requests
     */
    public function handle()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Parse the path to get the action
        $pathParts = explode('/', trim($path, '/'));
        $action = $pathParts[2] ?? '';

        // Handle case where action might be in different position
        if (empty($action) && count($pathParts) >= 2 && $pathParts[0] === 'api' && $pathParts[1] === 'posts') {
            $action = 'list'; // Default action for /api/posts
        }

        switch ($action) {
            case 'remove-image':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleRemoveImage();
                break;

            case 'list':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleGetPosts();
                break;

            case 'get':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleGetPost();
                break;

            case 'create':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleCreatePost();
                break;

            case 'update':
                if ($method !== 'PUT') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleUpdatePost();
                break;

            case 'delete':
                if ($method !== 'DELETE') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleDeletePost();
                break;

            case 'suggestions':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleSearchSuggestions();
                break;

            default:
                $this->sendError('Endpoint not found', 404);
        }
    }

    /**
     * Remove an image from a post
     */
    private function handleRemoveImage()
    {
        $user = $this->requireAuth();
        
        // Verify CSRF token
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verify_csrf_token($csrfToken)) {
            $this->sendError('Invalid CSRF token', 403);
        }

        $input = $this->getJsonInput();

        // Enhanced validation using rakit/validation
        $this->validateEnhanced($input, [
            'image_id' => 'required|integer|min:1'
        ], [
            'image_id.required' => 'ID hình ảnh là bắt buộc',
            'image_id.integer' => 'ID hình ảnh phải là số nguyên',
            'image_id.min' => 'ID hình ảnh không hợp lệ'
        ]);

        $imageId = (int)$input['image_id'];

        try {
            // Get image info to verify ownership
            $imageInfo = $this->db->selectOne(
                "SELECT * FROM HinhAnhBaiDang WHERE ID = :imageId", 
                ['imageId' => $imageId]
            );
            
            if (!$imageInfo) {
                $this->sendError('Image not found', 404);
            }

            // Get post info to verify ownership
            $postInfo = $this->db->selectOne(
                "SELECT * FROM BaiDang WHERE ID = :postId", 
                ['postId' => $imageInfo['BaiDangID']]
            );
            
            if (!$postInfo) {
                $this->sendError('Post not found', 404);
            }

            // Check if user owns the post or has admin privileges
            if ($postInfo['NguoiDangID'] != $user['ID'] && $user['VaiTroID'] < ROLE_MODERATOR) {
                $this->sendError('Permission denied', 403);
            }

            // Remove the image file from storage
            $imagePath = $imageInfo['DuongDan'];
            if ($imagePath && file_exists($imagePath)) {
                unlink($imagePath);
            }

            // Remove image record from database
            $result = $this->db->delete('HinhAnhBaiDang', 'ID = :imageId', ['imageId' => $imageId]);
            
            if ($result > 0) {
                // Update the order of remaining images
                $this->db->execute(
                    "UPDATE HinhAnhBaiDang SET ThuTu = ThuTu - 1 WHERE BaiDangID = :postId AND ThuTu > :order",
                    ['postId' => $imageInfo['BaiDangID'], 'order' => $imageInfo['ThuTu']]
                );

                $this->sendSuccess(null, 'Image removed successfully');
            } else {
                $this->sendError('Failed to remove image', 500);
            }

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'remove image');
        }
    }

    /**
     * Get posts list with pagination and filters
     */
    private function handleGetPosts()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = min((int)($_GET['limit'] ?? 20), 100); // Max 100 items per page
            $offset = ($page - 1) * $limit;

            $filters = [];
            $params = [];

            // Add filters
            if (!empty($_GET['category'])) {
                $filters[] = "bd.DanhMucID = :category";
                $params['category'] = (int)$_GET['category'];
            }

            if (!empty($_GET['province'])) {
                $filters[] = "bd.TinhThanhID = :province";
                $params['province'] = (int)$_GET['province'];
            }

            if (!empty($_GET['status'])) {
                $filters[] = "bd.TrangThai = :status";
                $params['status'] = (int)$_GET['status'];
            }

            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';

            // Get posts with basic info (fixed column names)
            $sql = "SELECT bd.ID, bd.TieuDe, bd.Gia, bd.DienTich, bd.TrangThai,
                           bd.NgayTao as NgayDang, bd.LuotXem
                    FROM BaiDang bd
                    {$whereClause}
                    ORDER BY bd.NgayTao DESC
                    LIMIT {$limit} OFFSET {$offset}";

            $posts = $this->db->select($sql, $params);

            // Get total count (simplified)
            $countSql = "SELECT COUNT(*) as total FROM BaiDang bd {$whereClause}";
            $totalResult = $this->db->selectOne($countSql, $params);
            $total = $totalResult['total'] ?? 0;

            $this->sendSuccess([
                'posts' => $posts,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'get posts');
        }
    }

    /**
     * Get single post by ID
     */
    private function handleGetPost()
    {
        try {
            $postId = (int)($_GET['id'] ?? 0);

            if ($postId <= 0) {
                $this->sendError('Invalid post ID', 400);
            }

            // Get post with basic details (simplified to avoid JOIN errors)
            $sql = "SELECT bd.*, dm.TenDM as DanhMuc, kh.HoTen as NguoiDang
                    FROM BaiDang bd
                    LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
                    LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
                    WHERE bd.ID = :postId";

            $post = $this->db->selectOne($sql, ['postId' => $postId]);

            if (!$post) {
                $this->sendError('Post not found', 404);
            }

            // Get post images
            $images = $this->db->select(
                "SELECT ID, DuongDan, ThuTu FROM HinhAnhBaiDang WHERE BaiDangID = :postId ORDER BY ThuTu",
                ['postId' => $postId]
            );

            $post['images'] = $images;

            $this->sendSuccess($post);

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'get post');
        }
    }

    /**
     * Create new post (placeholder - complex implementation needed)
     */
    private function handleCreatePost()
    {
        $this->sendError('Create post endpoint not implemented yet. Use web interface.', 501);
    }

    /**
     * Update post (placeholder - complex implementation needed)
     */
    private function handleUpdatePost()
    {
        $this->sendError('Update post endpoint not implemented yet. Use web interface.', 501);
    }

    /**
     * Delete post (placeholder - complex implementation needed)
     */
    private function handleDeletePost()
    {
        $this->sendError('Delete post endpoint not implemented yet. Use web interface.', 501);
    }

    /**
     * Get search suggestions based on query
     */
    private function handleSearchSuggestions()
    {
        try {
            $query = trim($_GET['q'] ?? '');

            if (empty($query) || strlen($query) < 2) {
                $this->sendSuccess([
                    'suggestions' => []
                ]);
                return;
            }

            // Get title suggestions
            $titleSuggestions = $this->db->select(
                "SELECT DISTINCT TieuDe as suggestion, 'title' as type, COUNT(*) as count
                 FROM BaiDang
                 WHERE TrangThai = 1 AND TieuDe LIKE :query
                 GROUP BY TieuDe
                 ORDER BY count DESC, TieuDe ASC
                 LIMIT 5",
                ['query' => '%' . $query . '%']
            );

            // Get location suggestions
            $locationSuggestions = $this->db->select(
                "SELECT DISTINCT DiaChi as suggestion, 'location' as type, COUNT(*) as count
                 FROM BaiDang
                 WHERE TrangThai = 1 AND DiaChi LIKE :query
                 GROUP BY DiaChi
                 ORDER BY count DESC, DiaChi ASC
                 LIMIT 3",
                ['query' => '%' . $query . '%']
            );

            // Get category suggestions
            $categorySuggestions = $this->db->select(
                "SELECT DISTINCT dm.TenDM as suggestion, 'category' as type, COUNT(*) as count
                 FROM BaiDang bd
                 JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
                 WHERE bd.TrangThai = 1 AND dm.TenDM LIKE :query
                 GROUP BY dm.TenDM
                 ORDER BY count DESC, dm.TenDM ASC
                 LIMIT 3",
                ['query' => '%' . $query . '%']
            );

            // Combine and format suggestions
            $suggestions = [];

            foreach ($titleSuggestions as $item) {
                $suggestions[] = [
                    'text' => $item['suggestion'],
                    'type' => 'title',
                    'icon' => 'fas fa-home',
                    'count' => (int)$item['count']
                ];
            }

            foreach ($locationSuggestions as $item) {
                $suggestions[] = [
                    'text' => $item['suggestion'],
                    'type' => 'location',
                    'icon' => 'fas fa-map-marker-alt',
                    'count' => (int)$item['count']
                ];
            }

            foreach ($categorySuggestions as $item) {
                $suggestions[] = [
                    'text' => $item['suggestion'],
                    'type' => 'category',
                    'icon' => 'fas fa-tag',
                    'count' => (int)$item['count']
                ];
            }

            $this->sendSuccess([
                'suggestions' => $suggestions,
                'query' => $query
            ]);

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'get search suggestions');
        }
    }
}