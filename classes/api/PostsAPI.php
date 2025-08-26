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

        switch ($action) {
            case 'remove-image':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleRemoveImage();
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
}