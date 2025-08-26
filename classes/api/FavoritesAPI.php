<?php

namespace Tro365\Api;

use Exception;
use Tro365\Core\BaseAPI;

/**
 * Favorites API Class
 * Tro365 - Standardized favorites management API
 */
class FavoritesAPI extends BaseAPI
{
    /**
     * Handle API requests
     */
    public function handle()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Parse the path to get the endpoint
        $pathParts = explode('/', trim($path, '/'));
        $endpoint = end($pathParts);

        switch ($endpoint) {
            case 'toggle-favorite':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleToggleFavorite();
                break;

            case 'toggle':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleToggleFavoriteAlt();
                break;

            case 'favorites':
                if ($method === 'GET') {
                    $this->handleGetFavorites();
                } else {
                    $this->sendError('Method not allowed', 405);
                }
                break;

            case 'check-favorite':
                if ($method === 'POST') {
                    $this->handleCheckFavorite();
                } else {
                    $this->sendError('Method not allowed', 405);
                }
                break;

            default:
                $this->sendError('Endpoint not found', 404);
        }
    }

    /**
     * Toggle favorite status for a post (alternative endpoint for search page)
     */
    private function handleToggleFavoriteAlt()
    {
        $user = $this->requireAuth();
        
        $input = $this->getJsonInput();
        
        // Handle both post_id and postId parameter names
        if (isset($input['post_id'])) {
            $postId = (int)$input['post_id'];
        } else {
            $this->validateRequiredFields($input, ['postId']);
            $postId = (int)$input['postId'];
        }

        $userId = $user['ID'];

        try {
            // Check if post exists and is approved
            $post = $this->db->selectOne(
                "SELECT ID FROM BaiDang WHERE ID = :postId AND TrangThai = :status", 
                ['postId' => $postId, 'status' => POST_STATUS_APPROVED]
            );

            if (!$post) {
                $this->sendError('Post not found', 404);
            }

            // Check if already favorited
            $existing = $this->db->selectOne(
                "SELECT ID FROM YeuThich WHERE KhachHangID = :userId AND BaiDangID = :postId", 
                ['userId' => $userId, 'postId' => $postId]
            );

            if ($existing) {
                // Remove from favorites
                $this->db->delete('YeuThich', 'KhachHangID = :userId AND BaiDangID = :postId', [
                    'userId' => $userId,
                    'postId' => $postId
                ]);

                $favorited = false;
                $message = 'Đã xóa khỏi danh sách yêu thích';
            } else {
                // Add to favorites
                $this->db->insert('YeuThich', [
                    'KhachHangID' => $userId,
                    'BaiDangID' => $postId
                ]);

                $favorited = true;
                $message = 'Đã thêm vào danh sách yêu thích';
            }

            // Get total favorites count for this post
            $totalFavoritesResult = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM YeuThich WHERE BaiDangID = :postId", 
                ['postId' => $postId]
            );
            
            $totalFavorites = (int)($totalFavoritesResult['count'] ?? 0);

            $this->sendSuccess([
                'favorited' => $favorited,
                'totalFavorites' => $totalFavorites
            ], $message);

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'toggle favorite alt');
        }
    }
    private function handleToggleFavorite()
    {
        $user = $this->requireAuth();
        
        // Verify CSRF token
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verify_csrf_token($csrfToken)) {
            $this->sendError('Invalid CSRF token', 403);
        }

        $input = $this->getJsonInput();
        $this->validateRequiredFields($input, ['postId']);

        $postId = (int)$input['postId'];
        $userId = $user['ID'];

        try {
            // Check if post exists and is approved
            $post = $this->db->selectOne(
                "SELECT ID FROM BaiDang WHERE ID = :postId AND TrangThai = :status", 
                ['postId' => $postId, 'status' => POST_STATUS_APPROVED]
            );

            if (!$post) {
                $this->sendError('Post not found', 404);
            }

            // Check if already favorited
            $existing = $this->db->selectOne(
                "SELECT ID FROM YeuThich WHERE KhachHangID = :userId AND BaiDangID = :postId", 
                ['userId' => $userId, 'postId' => $postId]
            );

            if ($existing) {
                // Remove from favorites
                $this->db->delete('YeuThich', 'KhachHangID = :userId AND BaiDangID = :postId', [
                    'userId' => $userId,
                    'postId' => $postId
                ]);

                $favorited = false;
                $message = 'Đã xóa khỏi danh sách yêu thích';
            } else {
                // Add to favorites
                $this->db->insert('YeuThich', [
                    'KhachHangID' => $userId,
                    'BaiDangID' => $postId
                ]);

                $favorited = true;
                $message = 'Đã thêm vào danh sách yêu thích';
            }

            // Get total favorites count for this post
            $totalFavoritesResult = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM YeuThich WHERE BaiDangID = :postId", 
                ['postId' => $postId]
            );
            
            $totalFavorites = (int)($totalFavoritesResult['count'] ?? 0);

            $this->sendSuccess([
                'favorited' => $favorited,
                'totalFavorites' => $totalFavorites
            ], $message);

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'toggle favorite');
        }
    }

    /**
     * Get user's favorite posts
     */
    private function handleGetFavorites()
    {
        $user = $this->requireAuth();
        $pagination = $this->getPaginationParams(12, 50);

        try {
            // Get favorites with post details
            $sql = "SELECT bd.*, dm.TenDM, kh.HoTen as NguoiDang, yt.NgayTao as NgayYeuThich
                    FROM YeuThich yt
                    JOIN BaiDang bd ON yt.BaiDangID = bd.ID
                    LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
                    LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
                    WHERE yt.KhachHangID = :userId
                    AND bd.TrangThai = :status
                    ORDER BY yt.NgayTao DESC
                    LIMIT :limit OFFSET :offset";

            $favorites = $this->db->select($sql, [
                'userId' => $user['ID'],
                'status' => POST_STATUS_APPROVED,
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset']
            ]);

            // Get total count
            $totalResult = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM YeuThich yt
                 JOIN BaiDang bd ON yt.BaiDangID = bd.ID
                 WHERE yt.KhachHangID = :userId AND bd.TrangThai = :status", 
                ['userId' => $user['ID'], 'status' => POST_STATUS_APPROVED]
            );

            $total = (int)($totalResult['total'] ?? 0);
            $totalPages = ceil($total / $pagination['limit']);

            $this->sendSuccess([
                'posts' => $favorites,
                'pagination' => [
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'total' => $total,
                    'totalPages' => $totalPages
                ]
            ]);

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'get favorites');
        }
    }

    /**
     * Check if a post is favorited by current user
     */
    private function handleCheckFavorite()
    {
        // Return false if user not logged in
        if (!$this->auth->isLoggedIn()) {
            $this->sendSuccess(['favorited' => false]);
            return;
        }

        // Verify CSRF token
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verify_csrf_token($csrfToken)) {
            $this->sendError('Invalid CSRF token', 403);
        }

        $user = $this->auth->getCurrentUser();
        $input = $this->getJsonInput();
        $this->validateRequiredFields($input, ['postId']);

        $postId = (int)$input['postId'];

        try {
            $existing = $this->db->selectOne(
                "SELECT ID FROM YeuThich WHERE KhachHangID = :userId AND BaiDangID = :postId", 
                ['userId' => $user['ID'], 'postId' => $postId]
            );

            $this->sendSuccess(['favorited' => (bool)$existing]);

        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'check favorite');
        }
    }
}