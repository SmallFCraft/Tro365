<?php

namespace Tro365\Models;

use Tro365\Core\BaseModel;
use Tro365\Helpers\ModernValidationHelper;
use Tro365\Helpers\LoggerHelper;
use Tro365\Helpers\StatusHelper;

/**
 * User Class
 * Tro365 - Website thuê trọ
 */
class User extends BaseModel
{
    protected $table = 'KhachHang';

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Hook: Before creating user
     */
    protected function beforeCreate(&$data)
    {
        // Enhanced validation using rakit/validation
        $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate([
            'username' => $data['TenDN'] ?? '',
            'email' => $data['Email'] ?? '',
            'password' => $data['MatKhau'] ?? '',
            'full_name' => $data['HoTen'] ?? '',
            'phone' => $data['SDT'] ?? '',
            'cccd' => $data['CCCD'] ?? '',
            'birth_date' => $data['NgaySinh'] ?? '',
            'gender' => $data['GioiTinh'] ?? ''
        ], [
            'username' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email',
            'password' => 'required|min:8|max:100',
            'full_name' => 'required|min:2|max:100',
            // Phone is optional - validation handled by client-side and database constraints
            // Cannot use regex with rakit/validation due to pipe character conflicts in phone pattern
            'phone' => 'nullable',
            'cccd' => 'nullable|regex:/^[0-9]{9,12}$/',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:Nam,Nữ,Khác'
        ], [
            'username.required' => 'Tên đăng nhập là bắt buộc',
            'username.min' => 'Tên đăng nhập phải có ít nhất 3 ký tự',
            'username.max' => 'Tên đăng nhập không được vượt quá 50 ký tự',
            'username.alpha_num' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.max' => 'Mật khẩu không được vượt quá 100 ký tự',
            'full_name.required' => 'Họ tên là bắt buộc',
            'full_name.min' => 'Họ tên phải có ít nhất 2 ký tự',
            'full_name.max' => 'Họ tên không được vượt quá 100 ký tự',
            'phone.regex' => 'Số điện thoại không hợp lệ',
            'cccd.regex' => 'Số CCCD phải có 9-12 chữ số',
            'birth_date.date' => 'Ngày sinh không hợp lệ',
            'gender.in' => 'Giới tính không hợp lệ'
        ]);

        if (!$validation['valid']) {
            $errors = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            LoggerHelper::error('User registration validation failed', [
                'errors' => $validation['errors'],
                'username' => $data['TenDN'] ?? 'unknown'
            ]);
            throw new \Exception(implode(', ', $errors));
        }

        // Check if username exists
        if ($this->usernameExists($data['TenDN'])) {
            throw new \Exception("Tên đăng nhập đã tồn tại");
        }

        // Check if email exists
        if ($this->emailExists($data['Email'])) {
            throw new \Exception("Email đã tồn tại");
        }

        // Hash password
        $data['MatKhau'] = password_hash($data['MatKhau'], PASSWORD_DEFAULT);

        // Set default values
        $data['VaiTroID'] = $data['VaiTroID'] ?? ROLE_USER;
        $data['TrangThai'] = $data['TrangThai'] ?? StatusHelper::USER_ACTIVE;
    }
    
    /**
     * Get user by ID with role info
     */
    public function getById($id)
    {
        $sql = "SELECT kh.*, vt.TenVT, vt.CapDo
                FROM KhachHang kh
                LEFT JOIN VaiTro vt ON kh.VaiTroID = vt.ID
                WHERE kh.ID = :id";

        return $this->db->selectOne($sql, ['id' => $id]);
    }
    
    /**
     * Get user by username
     */
    public function getByUsername($username)
    {
        $sql = "SELECT kh.*, vt.TenVT, vt.CapDo 
                FROM KhachHang kh 
                LEFT JOIN VaiTro vt ON kh.VaiTroID = vt.ID 
                WHERE kh.TenDN = :username";
        
        return $this->db->selectOne($sql, ['username' => $username]);
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email)
    {
        $sql = "SELECT kh.*, vt.TenVT, vt.CapDo 
                FROM KhachHang kh 
                LEFT JOIN VaiTro vt ON kh.VaiTroID = vt.ID 
                WHERE kh.Email = :email";
        
        return $this->db->selectOne($sql, ['email' => $email]);
    }
    
    /**
     * Hook: Before updating user
     */
    protected function beforeUpdate($id, &$data)
    {
        // Remove sensitive fields that shouldn't be updated directly
        unset($data['ID'], $data['MatKhau'], $data['NgayTao']);

        if (empty($data)) {
            throw new \Exception("Không có dữ liệu để cập nhật");
        }

        // Validate email if provided
        if (isset($data['Email'])) {
            $emailValidation = ValidationHelper::validateEmail($data['Email']);
            if (!$emailValidation['valid']) {
                throw new \Exception(implode(', ', $emailValidation['errors']));
            }

            // Check if email exists for other users
            $existing = $this->getByEmail($data['Email']);
            if ($existing && $existing['ID'] != $id) {
                throw new \Exception("Email đã tồn tại");
            }
        }

        // Validate birth date
        if (isset($data['NgaySinh']) && !empty($data['NgaySinh'])) {
            $birthDate = \DateTime::createFromFormat('Y-m-d', $data['NgaySinh']);
            if (!$birthDate || $birthDate->format('Y-m-d') !== $data['NgaySinh']) {
                throw new \Exception('Ngày sinh không hợp lệ');
            }

            // Check if birth date is not in the future
            if ($birthDate > new \DateTime()) {
                throw new \Exception('Ngày sinh không thể là ngày trong tương lai');
            }

            // Check minimum age (13 years old)
            $minAge = new \DateTime('-13 years');
            if ($birthDate > $minAge) {
                throw new \Exception('Bạn phải ít nhất 13 tuổi');
            }
        }

        // Validate CCCD
        if (isset($data['CCCD']) && !empty($data['CCCD'])) {
            if (!preg_match('/^[0-9]{9,12}$/', $data['CCCD'])) {
                throw new \Exception('CCCD phải có từ 9-12 chữ số');
            }
        }

        // Validate gender
        if (isset($data['GioiTinh']) && !empty($data['GioiTinh'])) {
            $validGenders = ['Nam', 'Nữ', 'Khác'];
            if (!in_array($data['GioiTinh'], $validGenders)) {
                throw new \Exception('Giới tính không hợp lệ');
            }
        }
    }
    
    /**
     * Generate and save password reset token
     */
    public function generateResetToken($email)
    {
        $user = $this->getByEmail($email);
        if (!$user) {
            // For security reasons, don't reveal if email exists or not
            // Always return success to prevent email enumeration attacks
            // But don't actually send email for non-existent accounts
            return [
                'token' => null,
                'user' => null,
                'email_exists' => false
            ];
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to database
        $this->update($user['ID'], [
            'reset_token' => $token,
            'reset_expiry' => $expiry
        ]);

        return [
            'token' => $token,
            'user' => $user,
            'email_exists' => true
        ];
    }

    /**
     * Get user by reset token
     */
    public function getByResetToken($token)
    {
        $sql = "SELECT * FROM KhachHang
                WHERE reset_token = :token
                AND reset_expiry > NOW()
                AND reset_token IS NOT NULL";

        return $this->db->selectOne($sql, ['token' => $token]);
    }

    /**
     * Reset password using token
     */
    public function resetPassword($token, $newPassword)
    {
        $user = $this->getByResetToken($token);
        if (!$user) {
            throw new \Exception("Token không hợp lệ hoặc đã hết hạn");
        }

        // Validate new password
        $passwordValidation = \Tro365\Helpers\ValidationHelper::validatePassword($newPassword);
        if (!$passwordValidation['valid']) {
            throw new \Exception(implode(', ', $passwordValidation['errors']));
        }

        // Update password and clear token - bypass beforeUpdate hook
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('KhachHang', [
            'MatKhau' => $hashedPassword,
            'reset_token' => null,
            'reset_expiry' => null
        ], 'ID = :id', ['id' => $user['ID']]);

        return $user;
    }
    
    /**
     * Verify login credentials
     */
    public function verifyLogin($username, $password)
    {
        try {
            $user = $this->getByUsername($username);
            
            if (!$user) {
                // Try login with email
                $user = $this->getByEmail($username);
            }
            
            if (!$user) {
                throw new \Exception("Tên đăng nhập hoặc email không tồn tại");
            }
            
            if ($user['TrangThai'] != USER_STATUS_ACTIVE) {
                throw new \Exception("Tài khoản đã bị khóa hoặc chưa được kích hoạt");
            }
            
            if (!password_verify($password, $user['MatKhau'])) {
                throw new \Exception("Mật khẩu không đúng");
            }
            
            // Update last login time
            $this->updateLastLogin($user['ID']);
            
            return $user;
            
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Get user data
            $user = $this->getById($userId);
            if (!$user) {
                throw new \Exception("Người dùng không tồn tại");
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['MatKhau'])) {
                throw new \Exception("Mật khẩu hiện tại không đúng");
            }

            // Validate new password
            $passwordValidation = \Tro365\Helpers\ValidationHelper::validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                throw new \Exception(implode(', ', $passwordValidation['errors']));
            }

            // Check if new password is different from current
            if (password_verify($newPassword, $user['MatKhau'])) {
                throw new \Exception("Mật khẩu mới phải khác mật khẩu hiện tại");
            }

            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password directly (bypass beforeUpdate hook)
            $sql = "UPDATE KhachHang SET MatKhau = :password WHERE ID = :id";
            $this->db->execute($sql, [
                'password' => $hashedPassword,
                'id' => $userId
            ]);

            return true;

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Update last login time
     */
    public function updateLastLogin($id)
    {
        $this->db->update('KhachHang',
            ['LanDangNhapCuoi' => date('Y-m-d H:i:s')],
            'ID = :id',
            ['id' => $id]
        );
    }

    /**
     * Update user profile with data consistency sync
     */
    public function updateProfile($id, $data)
    {
        try {
            // Get current user data for comparison
            $currentUser = $this->getById($id);
            if (!$currentUser) {
                throw new \Exception('User not found');
            }

            // Update user data
            $result = $this->db->update('KhachHang', $data, 'ID = :id', ['id' => $id]);

            if ($result) {
                // Sync changes to seller registrations if needed
                $dataConsistency = new DataConsistency();
                $updatedData = array_merge($data, ['old_CCCD' => $currentUser['CCCD']]);
                $dataConsistency->syncUserDataToSellers($id, $updatedData);

                return true;
            }

            return false;
        } catch (Exception $e) {
            writeLog("Error updating user profile: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM KhachHang WHERE TenDN = :username";
        $params = ['username' => $username];

        if ($excludeId) {
            $sql .= " AND ID != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $result = $this->db->selectOne($sql, $params);

        return $result['count'] > 0;
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM KhachHang WHERE Email = :email";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $sql .= " AND ID != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        
        $where = "1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['role'])) {
            $where .= " AND kh.VaiTroID = :role";
            $params['role'] = $filters['role'];
        }
        
        if (!empty($filters['status'])) {
            $where .= " AND kh.TrangThai = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (kh.HoTen LIKE :search OR kh.Email LIKE :search OR kh.TenDN LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql = "SELECT kh.*, vt.TenVT, vt.CapDo 
                FROM KhachHang kh 
                LEFT JOIN VaiTro vt ON kh.VaiTroID = vt.ID 
                WHERE {$where}
                ORDER BY kh.NgayTao DESC 
                LIMIT :limit OFFSET :offset";
        
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Count users
     */
    public function count($filters = [])
    {
        $where = "1=1";
        $params = [];
        
        // Apply same filters as getAll
        if (!empty($filters['role'])) {
            $where .= " AND VaiTroID = :role";
            $params['role'] = $filters['role'];
        }
        
        if (!empty($filters['status'])) {
            $where .= " AND TrangThai = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (HoTen LIKE :search OR Email LIKE :search OR TenDN LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        return $this->db->count('KhachHang', $where, $params);
    }
    
    /**
     * Delete user
     */
    public function delete($id)
    {
        try {
            return $this->db->delete('KhachHang', 'ID = :id', ['id' => $id]);
        } catch (Exception $e) {
            throw new \Exception("Lỗi xóa người dùng: " . $e->getMessage());
        }
    }

    /**
     * Generate email verification token
     */
    public function generateEmailVerificationToken($userId)
    {
        try {
            $token = bin2hex(random_bytes(32));

            $result = $this->db->update('KhachHang', [
                'email_verification_token' => $token
            ], 'ID = :id', ['id' => $userId]);

            if ($result) {
                return $token;
            }

            throw new \Exception("Không thể tạo token xác thực");

        } catch (Exception $e) {
            throw new \Exception("Lỗi tạo token xác thực: " . $e->getMessage());
        }
    }

    /**
     * Verify email with token
     */
    public function verifyEmail($token)
    {
        try {
            // Find user by verification token
            $user = $this->db->selectOne(
                "SELECT ID, Email, HoTen FROM KhachHang WHERE email_verification_token = :token AND email_verified_at IS NULL",
                ['token' => $token]
            );

            if (!$user) {
                throw new \Exception("Token xác thực không hợp lệ hoặc đã được sử dụng");
            }

            // Update user as verified
            $result = $this->db->update('KhachHang', [
                'email_verified_at' => date('Y-m-d H:i:s'),
                'email_verification_token' => null
            ], 'ID = :id', ['id' => $user['ID']]);

            if ($result) {
                return $user;
            }

            throw new \Exception("Không thể xác thực email");

        } catch (Exception $e) {
            throw new \Exception("Lỗi xác thực email: " . $e->getMessage());
        }
    }

    /**
     * Check if user email is verified
     */
    public function isEmailVerified($userId)
    {
        try {
            $user = $this->db->selectOne(
                "SELECT email_verified_at FROM KhachHang WHERE ID = :id",
                ['id' => $userId]
            );

            return $user && !is_null($user['email_verified_at']);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get user by verification token
     */
    public function getByVerificationToken($token)
    {
        try {
            return $this->db->selectOne(
                "SELECT * FROM KhachHang WHERE email_verification_token = :token",
                ['token' => $token]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Resend email verification
     */
    public function resendEmailVerification($email)
    {
        try {
            $user = $this->getByEmail($email);

            if (!$user) {
                throw new \Exception("Email không tồn tại trong hệ thống");
            }

            if ($this->isEmailVerified($user['ID'])) {
                throw new \Exception("Email đã được xác thực");
            }

            // Generate new token
            $token = $this->generateEmailVerificationToken($user['ID']);

            return [
                'user' => $user,
                'token' => $token
            ];

        } catch (Exception $e) {
            throw new \Exception("Lỗi gửi lại email xác thực: " . $e->getMessage());
        }
    }
}
