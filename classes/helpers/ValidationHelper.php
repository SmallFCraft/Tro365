<?php
/**
 * Validation Helper - Enhanced with rakit/validation
 * Tro365 - Website thuê trọ
 */

namespace Tro365\Helpers;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Rakit\Validation\Validator as RakitValidator;

class ValidationHelper
{
    private static ?ValidatorInterface $validator = null;
    private static ?RakitValidator $rakitValidator = null;
    private static bool $useEnhancedValidation = true;

    /**
     * Get Symfony validator instance (legacy)
     */
    private static function getValidator(): ValidatorInterface
    {
        if (self::$validator === null) {
            self::$validator = Validation::createValidator();
        }

        return self::$validator;
    }

    /**
     * Get Rakit validator instance (enhanced)
     */
    private static function getRakitValidator(): RakitValidator
    {
        if (self::$rakitValidator === null) {
            self::$rakitValidator = new RakitValidator([
                'required' => 'Trường :attribute là bắt buộc',
                'email' => 'Trường :attribute phải là email hợp lệ',
                'min' => 'Trường :attribute phải có ít nhất :min ký tự',
                'max' => 'Trường :attribute không được vượt quá :max ký tự',
                'numeric' => 'Trường :attribute phải là số',
                'integer' => 'Trường :attribute phải là số nguyên',
                'alpha' => 'Trường :attribute chỉ được chứa chữ cái',
                'alpha_num' => 'Trường :attribute chỉ được chứa chữ cái và số',
                'regex' => 'Trường :attribute không đúng định dạng',
                'confirmed' => 'Trường :attribute xác nhận không khớp',
                'same' => 'Trường :attribute phải giống với :other',
                'different' => 'Trường :attribute phải khác với :other',
                'in' => 'Trường :attribute không hợp lệ',
                'not_in' => 'Trường :attribute không được chọn',
                'uploaded_file' => 'Trường :attribute phải là file được upload',
                'mimes' => 'Trường :attribute phải có định dạng: :values',
                'date' => 'Trường :attribute phải là ngày hợp lệ',
                'before' => 'Trường :attribute phải trước ngày :date',
                'after' => 'Trường :attribute phải sau ngày :date'
            ]);
        }

        return self::$rakitValidator;
    }

    /**
     * Validate user registration data
     */
    public static function validateUserRegistration(array $data): array
    {
        $constraints = new Assert\Collection([
            'username' => [
                new Assert\NotBlank(['message' => 'Tên đăng nhập không được để trống']),
                new Assert\Length([
                    'min' => 3,
                    'max' => 50,
                    'minMessage' => 'Tên đăng nhập phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Tên đăng nhập không được vượt quá {{ limit }} ký tự'
                ]),
                new Assert\Regex([
                    'pattern' => '/^[a-zA-Z0-9_]+$/',
                    'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới'
                ])
            ],
            'email' => [
                new Assert\NotBlank(['message' => 'Email không được để trống']),
                new Assert\Email(['message' => 'Email không hợp lệ'])
            ],
            'password' => [
                new Assert\NotBlank(['message' => 'Mật khẩu không được để trống']),
                new Assert\Length([
                    'min' => 8,
                    'minMessage' => 'Mật khẩu phải có ít nhất {{ limit }} ký tự'
                ]),
                new Assert\Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                    'message' => 'Mật khẩu phải chứa ít nhất 1 chữ thường, 1 chữ hoa, 1 số và 1 ký tự đặc biệt'
                ])
            ],
            'full_name' => [
                new Assert\NotBlank(['message' => 'Họ tên không được để trống']),
                new Assert\Length([
                    'min' => 2,
                    'max' => 100,
                    'minMessage' => 'Họ tên phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Họ tên không được vượt quá {{ limit }} ký tự'
                ])
            ],
            'phone' => [
                new Assert\NotBlank(['message' => 'Số điện thoại không được để trống']),
                new Assert\Regex([
                    'pattern' => '/^(84|0)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-6|8|9]|9[0-4|6-9])[0-9]{7}$/',
                    'message' => 'Số điện thoại không hợp lệ'
                ])
            ]
        ]);

        return self::validateData($data, $constraints);
    }

    /**
     * Validate post creation data (MoTa field removed)
     */
    public static function validatePostCreation(array $data): array
    {
        $constraints = new Assert\Collection([
            'title' => [
                new Assert\NotBlank(['message' => 'Tiêu đề không được để trống']),
                new Assert\Length([
                    'min' => 10,
                    'max' => 200,
                    'minMessage' => 'Tiêu đề phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Tiêu đề không được vượt quá {{ limit }} ký tự'
                ])
            ],
            'content' => [
                new Assert\NotBlank(['message' => 'Nội dung không được để trống']),
                new Assert\Length([
                    'min' => 100,
                    'minMessage' => 'Nội dung phải có ít nhất {{ limit }} ký tự (vì không còn mô tả ngắn riêng)'
                ])
            ],
            'price' => [
                new Assert\NotBlank(['message' => 'Giá không được để trống']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Giá phải là số']),
                new Assert\Range([
                    'min' => 0,
                    'max' => 999999999,
                    'notInRangeMessage' => 'Giá phải từ {{ min }} đến {{ max }}'
                ])
            ],
            'area' => [
                new Assert\NotBlank(['message' => 'Diện tích không được để trống']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Diện tích phải là số']),
                new Assert\Range([
                    'min' => 1,
                    'max' => 10000,
                    'notInRangeMessage' => 'Diện tích phải từ {{ min }} đến {{ max }} m²'
                ])
            ],
            'address' => [
                new Assert\NotBlank(['message' => 'Địa chỉ không được để trống']),
                new Assert\Length([
                    'min' => 10,
                    'max' => 500,
                    'minMessage' => 'Địa chỉ phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Địa chỉ không được vượt quá {{ limit }} ký tự'
                ])
            ],
            'category_id' => [
                new Assert\NotBlank(['message' => 'Danh mục không được để trống']),
                new Assert\Type(['type' => 'integer', 'message' => 'Danh mục không hợp lệ'])
            ]
        ]);

        return self::validateData($data, $constraints);
    }

    /**
     * Validate contact form data
     */
    public static function validateContactForm(array $data): array
    {
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Họ tên không được để trống']),
                new Assert\Length([
                    'min' => 2,
                    'max' => 100,
                    'minMessage' => 'Họ tên phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Họ tên không được vượt quá {{ limit }} ký tự'
                ])
            ],
            'email' => [
                new Assert\NotBlank(['message' => 'Email không được để trống']),
                new Assert\Email(['message' => 'Email không hợp lệ'])
            ],
            'subject' => [
                new Assert\NotBlank(['message' => 'Chủ đề không được để trống']),
                new Assert\Length([
                    'min' => 5,
                    'max' => 200,
                    'minMessage' => 'Chủ đề phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Chủ đề không được vượt quá {{ limit }} ký tự'
                ])
            ],
            'message' => [
                new Assert\NotBlank(['message' => 'Tin nhắn không được để trống']),
                new Assert\Length([
                    'min' => 20,
                    'max' => 2000,
                    'minMessage' => 'Tin nhắn phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Tin nhắn không được vượt quá {{ limit }} ký tự'
                ])
            ]
        ]);

        return self::validateData($data, $constraints);
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'], int $maxSize = 5242880): array
    {
        $constraints = new Assert\Collection([
            'name' => [new Assert\NotBlank(['message' => 'Tên file không được để trống'])],
            'type' => [
                new Assert\NotBlank(['message' => 'Loại file không được để trống']),
                new Assert\Choice([
                    'choices' => array_map(fn($type) => "image/{$type}", $allowedTypes),
                    'message' => 'Loại file không được hỗ trợ'
                ])
            ],
            'size' => [
                new Assert\NotBlank(['message' => 'Kích thước file không hợp lệ']),
                new Assert\Range([
                    'min' => 1,
                    'max' => $maxSize,
                    'notInRangeMessage' => 'Kích thước file phải từ {{ min }} đến {{ max }} bytes'
                ])
            ],
            'error' => [
                new Assert\EqualTo([
                    'value' => UPLOAD_ERR_OK,
                    'message' => 'Có lỗi xảy ra khi upload file'
                ])
            ]
        ]);

        return self::validateData($file, $constraints);
    }

    /**
     * Validate data against constraints
     */
    private static function validateData(array $data, Assert\Collection $constraints): array
    {
        $validator = self::getValidator();
        $violations = $validator->validate($data, $constraints);

        $errors = [];
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $propertyPath = trim($violation->getPropertyPath(), '[]');
                $errors[$propertyPath] = $violation->getMessage();
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }

    /**
     * Validate single value
     */
    public static function validateValue($value, $constraints): array
    {
        $validator = self::getValidator();
        $violations = $validator->validate($value, $constraints);

        $errors = [];
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }

    /**
     * Sanitize input data
     * Removes HTML tags and trims whitespace
     */
    public static function sanitize($input): string
    {
        if ($input === null) {
            return '';
        }

        return trim(strip_tags((string)$input));
    }

    /**
     * Validate password strength
     */
    public static function validatePassword($password): array
    {
        $constraints = [
            new Assert\NotBlank(['message' => 'Mật khẩu không được để trống']),
            new Assert\Length([
                'min' => 6,
                'minMessage' => 'Mật khẩu phải có ít nhất {{ limit }} ký tự'
            ])
        ];

        return self::validateValue($password, $constraints);
    }

    /**
     * Validate email address
     */
    public static function validateEmail($email): array
    {
        $constraints = [
            new Assert\NotBlank(['message' => 'Email không được để trống']),
            new Assert\Email(['message' => 'Email không hợp lệ'])
        ];

        return self::validateValue($email, $constraints);
    }

    // ==================== ENHANCED VALIDATION METHODS ====================

    /**
     * Enhanced validation using rakit/validation
     */
    public static function enhancedValidate(array $data, array $rules, array $messages = []): array
    {
        try {
            if (!self::$useEnhancedValidation) {
                // Fallback to legacy validation
                return self::legacyValidate($data, $rules);
            }

            $validator = self::getRakitValidator();

            // Add custom messages if provided
            if (!empty($messages)) {
                $validator->setMessages($messages);
            }

            $validation = $validator->validate($data, $rules);

            if ($validation->fails()) {
                return [
                    'valid' => false,
                    'errors' => $validation->errors()->toArray()
                ];
            }

            return [
                'valid' => true,
                'errors' => []
            ];

        } catch (\Exception $e) {
            writeLog("ValidationHelper: Enhanced validation exception - " . $e->getMessage());
            return [
                'valid' => false,
                'errors' => ['general' => ['Có lỗi xảy ra khi validate dữ liệu']]
            ];
        }
    }

    /**
     * Enhanced login form validation
     */
    public static function validateLoginForm(array $data): array
    {
        $rules = [
            'username' => 'required|min:3',
            'password' => 'required|min:6'
        ];

        $messages = [
            'username.required' => 'Tên đăng nhập là bắt buộc',
            'username.min' => 'Tên đăng nhập phải có ít nhất 3 ký tự',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự'
        ];

        return self::enhancedValidate($data, $rules, $messages);
    }

    /**
     * Enhanced registration form validation
     */
    public static function validateRegistrationForm(array $data): array
    {
        $rules = [
            'fullname' => 'required|min:2|max:100',
            'username' => 'required|min:3|max:50|alpha_num',
            'email' => 'required|email',
            'password' => 'required|min:6|max:100',
            'password_confirmation' => 'required|same:password',
            'phone' => 'required|regex:/^(84|0[3|5|7|8|9])+([0-9]{8})$/'
        ];

        $messages = [
            'fullname.required' => 'Họ tên là bắt buộc',
            'fullname.min' => 'Họ tên phải có ít nhất 2 ký tự',
            'fullname.max' => 'Họ tên không được vượt quá 100 ký tự',
            'username.required' => 'Tên đăng nhập là bắt buộc',
            'username.min' => 'Tên đăng nhập phải có ít nhất 3 ký tự',
            'username.max' => 'Tên đăng nhập không được vượt quá 50 ký tự',
            'username.alpha_num' => 'Tên đăng nhập chỉ được chứa chữ cái và số',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.max' => 'Mật khẩu không được vượt quá 100 ký tự',
            'password_confirmation.required' => 'Xác nhận mật khẩu là bắt buộc',
            'password_confirmation.same' => 'Xác nhận mật khẩu không khớp',
            'phone.required' => 'Số điện thoại là bắt buộc',
            'phone.regex' => 'Số điện thoại không hợp lệ'
        ];

        return self::enhancedValidate($data, $rules, $messages);
    }

    /**
     * Enhanced post form validation
     */
    public static function validatePostForm(array $data): array
    {
        $rules = [
            'title' => 'required|min:10|max:255',
            'description' => 'required|min:50',
            'category_id' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'area' => 'required|numeric|min:1',
            'rooms' => 'required|integer|min:1|max:50',
            'address' => 'required|min:10|max:500',
            'province_id' => 'required|integer|min:1',
            'district_id' => 'required|integer|min:1',
            'ward_id' => 'required|integer|min:1'
        ];

        $messages = [
            'title.required' => 'Tiêu đề là bắt buộc',
            'title.min' => 'Tiêu đề phải có ít nhất 10 ký tự',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự',
            'description.required' => 'Mô tả là bắt buộc',
            'description.min' => 'Mô tả phải có ít nhất 50 ký tự',
            'category_id.required' => 'Danh mục là bắt buộc',
            'category_id.integer' => 'Danh mục không hợp lệ',
            'price.required' => 'Giá thuê là bắt buộc',
            'price.numeric' => 'Giá thuê phải là số',
            'price.min' => 'Giá thuê phải lớn hơn 0',
            'area.required' => 'Diện tích là bắt buộc',
            'area.numeric' => 'Diện tích phải là số',
            'area.min' => 'Diện tích phải lớn hơn 0',
            'rooms.required' => 'Số phòng là bắt buộc',
            'rooms.integer' => 'Số phòng phải là số nguyên',
            'rooms.min' => 'Số phòng phải ít nhất là 1',
            'rooms.max' => 'Số phòng không được vượt quá 50',
            'address.required' => 'Địa chỉ là bắt buộc',
            'address.min' => 'Địa chỉ phải có ít nhất 10 ký tự',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự',
            'province_id.required' => 'Tỉnh/Thành phố là bắt buộc',
            'district_id.required' => 'Quận/Huyện là bắt buộc',
            'ward_id.required' => 'Phường/Xã là bắt buộc'
        ];

        return self::enhancedValidate($data, $rules, $messages);
    }

    /**
     * Enhanced file upload validation
     */
    public static function validateFileUploadEnhanced(array $file, array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'], int $maxSizeMB = 5): array
    {
        $rules = [
            'name' => 'required',
            'size' => 'required|integer|max:' . ($maxSizeMB * 1024 * 1024),
            'error' => 'required|in:0'
        ];

        $messages = [
            'name.required' => 'Tên file là bắt buộc',
            'size.required' => 'Kích thước file không hợp lệ',
            'size.integer' => 'Kích thước file phải là số',
            'size.max' => "Kích thước file không được vượt quá {$maxSizeMB}MB",
            'error.required' => 'File upload có lỗi',
            'error.in' => 'File upload không thành công'
        ];

        $validation = self::enhancedValidate($file, $rules, $messages);

        // Additional file type validation
        if ($validation['valid']) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes)) {
                $validation['valid'] = false;
                $validation['errors']['type'] = ['Định dạng file không được hỗ trợ. Chỉ chấp nhận: ' . implode(', ', $allowedTypes)];
            }
        }

        return $validation;
    }

    /**
     * Legacy validation fallback
     */
    private static function legacyValidate(array $data, array $rules): array
    {
        // Simple fallback validation
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';

            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = ["Trường {$field} là bắt buộc"];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if enhanced validation is available
     */
    public static function isEnhancedValidationAvailable(): bool
    {
        return self::$useEnhancedValidation && class_exists('Rakit\Validation\Validator');
    }
}
