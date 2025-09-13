<?php
/**
 * Upload API
 * Tro365 - File upload management
 */

namespace Tro365\Api;

use Exception;
use Tro365\Core\Database;
use Tro365\Core\Auth;

class UploadAPI
{
    private $db;
    private $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }

    public function handle()
    {
        // Set JSON response header
        header('Content-Type: application/json; charset=utf-8');
        
        // Handle CORS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        try {
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($method) {
                case 'POST':
                    $this->handleUpload();
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function handleUpload()
    {
        // Require authentication
        if (!$this->auth->isLoggedIn()) {
            throw new Exception('Unauthorized', 401);
        }

        if (empty($_FILES)) {
            throw new Exception('No files uploaded', 400);
        }

        $uploadedFiles = [];
        
        foreach ($_FILES as $fileKey => $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Upload error: ' . $this->getUploadErrorMessage($file['error']), 400);
            }

            // Validate file
            $this->validateFile($file);
            
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid() . '_' . time() . '.' . $extension;
            
            // Determine upload directory
            $uploadDir = $this->getUploadDirectory($fileKey);
            $uploadPath = $uploadDir . '/' . $filename;
            
            // Create directory if not exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to save file', 500);
            }
            
            $uploadedFiles[] = [
                'filename' => $filename,
                'original_name' => $file['name'],
                'path' => $uploadPath,
                'url' => $this->getFileUrl($uploadPath),
                'size' => $file['size'],
                'type' => $file['type']
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles
        ], JSON_UNESCAPED_UNICODE);
    }

    private function validateFile($file)
    {
        // Check file size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds 5MB limit', 400);
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, WebP allowed', 400);
        }

        // Additional security check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file content', 400);
        }
    }

    private function getUploadDirectory($fileKey)
    {
        $baseDir = __DIR__ . '/../../assets/uploads';
        
        switch ($fileKey) {
            case 'post_images':
                return $baseDir . '/posts';
            case 'avatar':
                return $baseDir . '/avatars';
            case 'documents':
                return $baseDir . '/documents';
            default:
                return $baseDir . '/general';
        }
    }

    private function getFileUrl($filePath)
    {
        $relativePath = str_replace(__DIR__ . '/../../', '', $filePath);
        return '/' . str_replace('\\', '/', $relativePath);
    }

    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
}
