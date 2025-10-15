<?php

namespace Tro365\Api;

use Exception;
use Tro365\Core\BaseAPI;
use Tro365\Models\User;
use Tro365\Helpers\ValidationHelper;
use Tro365\Helpers\LoggerHelper;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Check Availability API Class
 * Tro365 - Standardized email/username availability checking
 */
class CheckAvailabilityAPI extends BaseAPI
{
    /**
     * Handle API requests
     */
    public function handle()
    {
        // Ensure we always return JSON
        header('Content-Type: application/json; charset=utf-8');
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method !== 'POST') {
            $this->sendError('Method not allowed', 405);
            return;
        }

        $this->handleCheckAvailability();
    }

    /**
     * Handle availability check
     */
    private function handleCheckAvailability()
    {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->sendError('Invalid JSON input', 400);
            }

            $excludeId = isset($input['exclude_id']) ? (int)$input['exclude_id'] : null;

            // Enhanced validation using rakit/validation
            $validation = ValidationHelper::enhancedValidate($input, [
                'type' => 'required|in:email,username',
                'value' => 'required|min:1',
                'exclude_id' => 'nullable|integer|min:1'
            ], [
                'type.required' => 'Type là bắt buộc',
                'type.in' => 'Type phải là email hoặc username',
                'value.required' => 'Value là bắt buộc',
                'value.min' => 'Value không được để trống',
                'exclude_id.integer' => 'exclude_id không hợp lệ'
            ]);

            if (!$validation['valid']) {
                $errors = [];
                foreach ($validation['errors'] as $field => $fieldErrors) {
                    $errors = array_merge($errors, $fieldErrors);
                }
                $this->sendError(implode(', ', $errors), 400);
            }

            $type = trim($input['type']);
            $value = trim($input['value']);
            
            $user = new User();
            $available = true;
            $message = '';
            
            switch ($type) {
                case 'email':
                    $result = $this->checkEmailAvailability($user, $value, $excludeId);
                    break;
                    
                case 'username':
                    $result = $this->checkUsernameAvailability($user, $value, $excludeId);
                    break;
                    
                default:
                    $this->sendError('Invalid type', 400);
            }

            $this->sendSuccess([
                'available' => $result['available'],
                'type' => $type,
                'value' => $value,
                'exclude_id' => $excludeId
            ], $result['message']);

        } catch (Exception $e) {
            LoggerHelper::logAPI('check-availability', 'POST', [
                'error' => $e->getMessage(),
                'type' => $input['type'] ?? null
            ]);
            
            $this->sendError('Availability check failed', 500, $e->getMessage());
        }
    }

    /**
     * Check email availability
     */
    private function checkEmailAvailability($user, $email, $excludeId = null)
    {
        // Enhanced email validation using rakit/validation
        $emailValidation = ValidationHelper::enhancedValidate(['email' => $email], [
            'email' => 'required|email'
        ], [
            'email.required' => 'Email không được để trống',
            'email.email' => 'Định dạng email không hợp lệ'
        ]);

        if (!$emailValidation['valid']) {
            $errors = [];
            foreach ($emailValidation['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            
            LoggerHelper::logAPI('check-availability', 'POST', [
                'type' => 'email', 
                'valid' => false,
                'errors' => $errors
            ]);
            
            return [
                'available' => false,
                'message' => implode(', ', $errors)
            ];
        }

        if ($excludeId) {
            $existing = $user->emailExists($email, $excludeId) ? ['ID' => $excludeId] : null;
        } else {
            $existing = $user->getByEmail($email);
        }
        
        $available = !$existing;
        $message = $existing ? 'Email đã được sử dụng' : 'Email có thể sử dụng';
        
        LoggerHelper::logAPI('check-availability', 'POST', [
            'type' => 'email', 
            'available' => $available, 
            'excludeId' => $excludeId
        ]);

        return [
            'available' => $available,
            'message' => $message
        ];
    }

    /**
     * Check username availability
     */
    private function checkUsernameAvailability($user, $username, $excludeId = null)
    {
        // Validate username using Symfony Validator
        $validation = ValidationHelper::validateValue($username, [
            new Assert\NotBlank(['message' => 'Tên đăng nhập không được để trống']),
            new Assert\Length([
                'min' => 3,
                'max' => 30,
                'minMessage' => 'Tên đăng nhập phải có ít nhất {{ limit }} ký tự',
                'maxMessage' => 'Tên đăng nhập không được vượt quá {{ limit }} ký tự'
            ]),
            new Assert\Regex([
                'pattern' => '/^[a-zA-Z0-9_]+$/',
                'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới'
            ])
        ]);

        if (!$validation['valid']) {
            LoggerHelper::logAPI('check-availability', 'POST', [
                'type' => 'username', 
                'valid' => false,
                'errors' => $validation['errors']
            ]);
            
            return [
                'available' => false,
                'message' => implode(', ', $validation['errors'])
            ];
        }

        if ($excludeId) {
            $existing = $user->usernameExists($username, $excludeId) ? ['ID' => $excludeId] : null;
        } else {
            $existing = $user->getByUsername($username);
        }
        
        $available = !$existing;
        $message = $existing ? 'Tên đăng nhập đã được sử dụng' : 'Tên đăng nhập có thể sử dụng';
        
        LoggerHelper::logAPI('check-availability', 'POST', [
            'type' => 'username', 
            'available' => $available, 
            'excludeId' => $excludeId
        ]);

        return [
            'available' => $available,
            'message' => $message
        ];
    }
}
