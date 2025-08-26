<?php

namespace Tro365\Api;

use Exception;
use Tro365\Core\BaseAPI;

/**
 * Notifications API Handler
 * Tro365 - Standardized notifications management
 */
class NotificationsAPI extends BaseAPI
{
    /**
     * Handle notifications API requests
     */
    public function handle()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $pathParts = explode('/', trim($path, '/'));
            
            switch ($method) {
                case 'GET':
                    $this->handleGetNotifications();
                    break;
                    
                case 'POST':
                    $this->handleCreateNotification();
                    break;
                    
                case 'PUT':
                    if (isset($pathParts[3])) {
                        $this->handleUpdateNotification($pathParts[3]);
                    } else {
                        $this->handleMarkAllAsRead();
                    }
                    break;
                    
                case 'DELETE':
                    if (isset($pathParts[3])) {
                        $this->handleDeleteNotification($pathParts[3]);
                    } else {
                        $this->sendError('Notification ID required for deletion', 400);
                    }
                    break;
                    
                default:
                    $this->sendError('Method not allowed', 405);
            }
            
        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'notifications API operation');
        }
    }
    
    /**
     * Get notifications for current user
     */
    private function handleGetNotifications()
    {
        $user = $this->requireAuth();
        $userId = $user['ID'];
        
        try {
            $pagination = $this->getPaginationParams();
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            // Build where clause
            $whereConditions = ['NguoiNhanID = :userId'];
            $params = ['userId' => $userId];
            
            if ($unreadOnly) {
                $whereConditions[] = 'DaDoc = 0';
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Main query with time formatting
            $sql = "SELECT 
                        ID,
                        TieuDe as title,
                        NoiDung as message,
                        LoaiTB as type,
                        DaDoc as is_read,
                        NgayTao as created_at,
                        CASE 
                            WHEN TIMESTAMPDIFF(MINUTE, NgayTao, NOW()) < 60 
                            THEN CONCAT(TIMESTAMPDIFF(MINUTE, NgayTao, NOW()), ' phút trước')
                            WHEN TIMESTAMPDIFF(HOUR, NgayTao, NOW()) < 24 
                            THEN CONCAT(TIMESTAMPDIFF(HOUR, NgayTao, NOW()), ' giờ trước')
                            WHEN TIMESTAMPDIFF(DAY, NgayTao, NOW()) < 7 
                            THEN CONCAT(TIMESTAMPDIFF(DAY, NgayTao, NOW()), ' ngày trước')
                            ELSE DATE_FORMAT(NgayTao, '%d/%m/%Y')
                        END as time_ago
                    FROM ThongBao 
                    {$whereClause}
                    ORDER BY NgayTao DESC 
                    LIMIT :limit OFFSET :offset";
            
            $params = array_merge($params, [
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset']
            ]);
            
            $notifications = $this->db->select($sql, $params);
            
            // Get unread count
            $unreadResult = $this->db->selectOne(
                "SELECT COUNT(*) as unread_count FROM ThongBao WHERE NguoiNhanID = :userId AND DaDoc = 0",
                ['userId' => $userId]
            );
            $unreadCount = $unreadResult['unread_count'] ?? 0;
            
            // Format notifications
            foreach ($notifications as &$notification) {
                $notification['read'] = (bool)$notification['is_read'];
                $notification['time'] = $notification['time_ago'];
                unset($notification['is_read'], $notification['time_ago']);
            }
            
            $this->sendSuccess([
                'notifications' => $notifications,
                'unread_count' => (int)$unreadCount,
                'pagination' => [
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'total' => count($notifications)
                ]
            ], 'Notifications retrieved successfully');
            
        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'get notifications');
        }
    }
    
    /**
     * Create new notification (admin only)
     */
    private function handleCreateNotification()
    {
        $user = $this->requireRole(ROLE_ADMIN);
        
        try {
            $input = $this->getJsonInput();
            $this->validateRequiredFields($input, ['title', 'message', 'recipient_id']);
            
            $data = [
                'NguoiNhanID' => (int)$input['recipient_id'],
                'TieuDe' => $this->sanitize($input['title']),
                'NoiDung' => $this->sanitize($input['message']),
                'LoaiTB' => (int)($input['type'] ?? 1)
            ];
            
            $notificationId = $this->db->insert('ThongBao', $data);
            
            $this->sendSuccess([
                'id' => $notificationId
            ], 'Notification created successfully', 201);
            
        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'create notification');
        }
    }
    
    /**
     * Mark notification as read
     */
    private function handleUpdateNotification($notificationId)
    {
        $user = $this->requireAuth();
        $userId = $user['ID'];
        
        try {
            $updated = $this->db->update(
                'ThongBao',
                ['DaDoc' => 1],
                'ID = :id AND NguoiNhanID = :userId',
                ['id' => (int)$notificationId, 'userId' => $userId]
            );
            
            if ($updated > 0) {
                $this->sendSuccess(null, 'Notification marked as read');
            } else {
                $this->sendError('Notification not found', 404);
            }
            
        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'update notification');
        }
    }
    
    /**
     * Mark all notifications as read
     */
    private function handleMarkAllAsRead()
    {
        $user = $this->requireAuth();
        $userId = $user['ID'];
        
        try {
            $updated = $this->db->update(
                'ThongBao',
                ['DaDoc' => 1],
                'NguoiNhanID = :userId AND DaDoc = 0',
                ['userId' => $userId]
            );
            
            $this->sendSuccess([
                'updated_count' => $updated
            ], 'All notifications marked as read');
            
        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'mark all notifications as read');
        }
    }
    
    /**
     * Delete notification
     */
    private function handleDeleteNotification($notificationId)
    {
        $user = $this->requireAuth();
        $userId = $user['ID'];
        
        try {
            $deleted = $this->db->delete(
                'ThongBao',
                'ID = :id AND NguoiNhanID = :userId',
                ['id' => (int)$notificationId, 'userId' => $userId]
            );
            
            if ($deleted > 0) {
                $this->sendSuccess(null, 'Notification deleted successfully');
            } else {
                $this->sendError('Notification not found', 404);
            }
            
        } catch (Exception $e) {
            $this->handleDatabaseError($e, 'delete notification');
        }
    }
}