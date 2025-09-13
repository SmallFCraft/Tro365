<?php

namespace Tro365\Api;

use Exception;
use Tro365\Core\BaseAPI;
use Tro365\Core\Auth;
use Tro365\Helpers\LoggerHelper;

/**
 * Authentication API Class
 * Tro365 - Standardized authentication API
 */
class AuthAPI extends BaseAPI
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
            case 'refresh-session':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleRefreshSession();
                break;

            case 'check-session':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleCheckSession();
                break;

            case 'logout':
                if ($method !== 'POST') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleLogout();
                break;

            default:
                $this->sendError('Invalid action', 404);
        }
    }

    /**
     * Handle session refresh
     */
    private function handleRefreshSession()
    {
        try {
            // Debug logging for session refresh
            logAPI("Session refresh attempt", [
                'session_id' => session_id(),
                'user_id' => $_SESSION['user_id'] ?? null,
                'session_data' => array_keys($_SESSION),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Check if user is logged in
            if (!$this->auth->isLoggedIn()) {
                logAPI("Session refresh failed - not authenticated", [
                    'session_id' => session_id(),
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);
                
                $this->sendError('Not authenticated', 401);
            }

            // Get current user data
            $userData = $this->auth->getCurrentUser(true); // Force refresh

            if (!$userData) {
                logAPI("Session refresh failed - user not found", [
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                $this->sendError('User not found', 404);
            }

            // Refresh session data
            $_SESSION['user_id'] = $userData['ID'];
            $_SESSION['username'] = $userData['TenDN'] ?? $userData['TenDangNhap'] ?? '';
            $_SESSION['email'] = $userData['Email'];
            $_SESSION['role'] = $userData['VaiTroID'] ?? $userData['VaiTro'] ?? '';
            $_SESSION['last_activity'] = time();

            logAPI("Session refreshed successfully", [
                'user_id' => $userData['ID'],
                'username' => $userData['TenDN'] ?? $userData['TenDangNhap'] ?? '',
                'role' => $userData['VaiTroID'] ?? $userData['VaiTro'] ?? ''
            ]);

            $this->sendSuccess([
                'user' => [
                    'id' => $userData['ID'],
                    'username' => $userData['TenDN'] ?? $userData['TenDangNhap'] ?? '',
                    'email' => $userData['Email'],
                    'role' => $userData['VaiTroID'] ?? $userData['VaiTro'] ?? '',
                    'avatar' => $userData['AnhDaiDien'] ?? null
                ],
                'session_id' => session_id(),
                'expires_at' => time() + SESSION_LIFETIME
            ], 'Session refreshed successfully');

        } catch (Exception $e) {
            logAPI("Session refresh error", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            
            $this->sendError('Session refresh failed', 500, $e->getMessage());
        }
    }

    /**
     * Handle session check
     */
    private function handleCheckSession()
    {
        try {
            if (!$this->auth->isLoggedIn()) {
                $this->sendSuccess([
                    'authenticated' => false,
                    'session_valid' => false
                ], 'Not authenticated');
            }

            $userData = $this->auth->getCurrentUser();

            if (!$userData) {
                $this->sendSuccess([
                    'authenticated' => false,
                    'session_valid' => false
                ], 'User not found');
            }

            $this->sendSuccess([
                'authenticated' => true,
                'session_valid' => true,
                'user' => [
                    'id' => $userData['ID'],
                    'username' => $userData['TenDN'] ?? $userData['TenDangNhap'] ?? '',
                    'email' => $userData['Email'],
                    'role' => $userData['VaiTroID'] ?? $userData['VaiTro'] ?? '',
                    'avatar' => $userData['AnhDaiDien'] ?? null
                ],
                'session_id' => session_id(),
                'expires_at' => time() + SESSION_LIFETIME
            ], 'Session valid');

        } catch (Exception $e) {
            $this->sendError('Session check failed', 500, $e->getMessage());
        }
    }

    /**
     * Handle logout
     */
    private function handleLogout()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            logAPI("User logout", [
                'user_id' => $userId,
                'session_id' => session_id()
            ]);

            // Clear session
            session_destroy();
            
            $this->sendSuccess(null, 'Logged out successfully');

        } catch (Exception $e) {
            $this->sendError('Logout failed', 500, $e->getMessage());
        }
    }
}
