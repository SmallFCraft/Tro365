<?php
/**
 * Email Service Class
 * Tro365 - Website thuê trọ
 */

namespace Tro365\Services;

require_once __DIR__ . '/../../includes/functions/helpers.php';

class EmailService
{
    private $config;
    private $errors = [];
    private $debugMode = false;
    private $lastConnectionTest = null;

    public function __construct($config = null)
    {
        $this->config = $config ?: getEmailConfig();
        $this->debugMode = defined('APP_DEBUG') && APP_DEBUG;
    }

    /**
     * Test SMTP connection
     */
    public function testConnection($config = null)
    {
        $testConfig = $config ?: $this->config;

        // Validate configuration first
        if (!$this->validateConfig($testConfig)) {
            return [
                'success' => false,
                'message' => 'Cấu hình không hợp lệ: ' . implode(', ', $this->errors),
                'errors' => $this->errors
            ];
        }

        // Only test SMTP connections
        if ($testConfig['driver'] !== 'smtp') {
            return [
                'success' => true,
                'message' => 'Driver ' . $testConfig['driver'] . ' không cần test connection',
                'driver' => $testConfig['driver']
            ];
        }

        try {
            // Try to load PHPMailer
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }

            // Check if PHPMailer class exists
            if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            } else if (class_exists('\Tro365\PHPMailer\PHPMailer\PHPMailer')) {
                $mail = new \Tro365\PHPMailer\PHPMailer\PHPMailer(true);
            } else {
                return [
                    'success' => false,
                    'message' => 'PHPMailer không có sẵn. Cần cài đặt PHPMailer để test SMTP connection.',
                    'errors' => ['PHPMailer not found']
                ];
            }

            // Configure SMTP settings
            $mail->isSMTP();
            $mail->Host = $testConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $testConfig['username'];
            $mail->Password = $testConfig['password'];
            $mail->SMTPSecure = $testConfig['encryption'];
            $mail->Port = $testConfig['port'];
            $mail->Timeout = 10; // 10 seconds timeout

            if ($this->debugMode) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    $this->errors[] = "Debug: $str";
                };
            }

            // Test connection
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                $this->lastConnectionTest = [
                    'success' => true,
                    'timestamp' => time(),
                    'config' => $testConfig
                ];

                return [
                    'success' => true,
                    'message' => 'Kết nối SMTP thành công!',
                    'host' => $testConfig['host'],
                    'port' => $testConfig['port'],
                    'encryption' => $testConfig['encryption']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể kết nối đến SMTP server',
                    'errors' => $this->errors
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi kết nối SMTP: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Send email
     */
    public function send($to, $subject, $body, $isHtml = true, $attachments = [])
    {
        try {
            // Clear previous errors
            $this->errors = [];

            // Validate email configuration
            if (!$this->validateConfig()) {
                $this->logError('Email configuration validation failed', [
                    'errors' => $this->errors,
                    'config' => $this->sanitizeConfigForLog()
                ]);
                return false;
            }

            // Log email attempt
            $this->logInfo('Attempting to send email', [
                'to' => is_array($to) ? implode(', ', array_keys($to)) : $to,
                'subject' => $subject,
                'driver' => $this->config['driver']
            ]);

            // Choose sending method based on driver and availability
            if ($this->config['driver'] === 'smtp' && $this->isPHPMailerAvailable()) {
                $result = $this->sendWithPHPMailer($to, $subject, $body, $isHtml, $attachments);
            } else {
                $result = $this->sendWithBasicMail($to, $subject, $body, $isHtml);
            }

            if ($result) {
                $this->logInfo('Email sent successfully', [
                    'to' => is_array($to) ? implode(', ', array_keys($to)) : $to,
                    'subject' => $subject
                ]);
            } else {
                $this->logError('Email sending failed', [
                    'to' => is_array($to) ? implode(', ', array_keys($to)) : $to,
                    'subject' => $subject,
                    'errors' => $this->errors
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->logError('Email sending exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check if PHPMailer is available
     */
    private function isPHPMailerAvailable()
    {
        // Try to load PHPMailer
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        return class_exists('\PHPMailer\PHPMailer\PHPMailer') || class_exists('\Tro365\PHPMailer\PHPMailer\PHPMailer');
    }

    /**
     * Send email using PHPMailer
     */
    private function sendWithPHPMailer($to, $subject, $body, $isHtml, $attachments)
    {
        // Try to load PHPMailer
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        // Check if PHPMailer class exists with correct namespace
        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        } else if (class_exists('\Tro365\PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new \Tro365\PHPMailer\PHPMailer\PHPMailer(true);
        } else {
            $this->errors[] = 'PHPMailer not available';
            return false;
        }

        try {
            // Server settings
            if ($this->config['driver'] === 'smtp') {
                $mail->isSMTP();
                $mail->Host = $this->config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
                $mail->SMTPSecure = $this->config['encryption'];
                $mail->Port = $this->config['port'];
                $mail->Timeout = 30; // 30 seconds timeout

                if ($this->debugMode) {
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = function($str, $level) {
                        $this->errors[] = "SMTP Debug: $str";
                    };
                }
            }

            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom($this->config['from_address'], $this->config['from_name']);

            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $mail->addAddress($name);
                    } else {
                        $mail->addAddress($email, $name);
                    }
                }
            } else {
                $mail->addAddress($to);
            }

            // Attachments
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } else {
                        $this->errors[] = 'Attachment file not found: ' . ($attachment['path'] ?? 'unknown');
                    }
                } else {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    } else {
                        $this->errors[] = 'Attachment file not found: ' . $attachment;
                    }
                }
            }

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if ($isHtml) {
                $mail->AltBody = strip_tags($body);
            }

            $mail->send();
            return true;
        } catch (\Exception $e) {
            $this->errors[] = "PHPMailer Error: " . $e->getMessage();
            if (isset($mail) && $mail->ErrorInfo) {
                $this->errors[] = "SMTP Error Info: " . $mail->ErrorInfo;
            }
            return false;
        }
    }

    /**
     * Send email using basic mail() function
     */
    private function sendWithBasicMail($to, $subject, $body, $isHtml)
    {
        // Validate recipient
        if (is_array($to)) {
            $to = implode(', ', array_keys($to));
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid recipient email address';
            return false;
        }

        $headers = [];
        $headers[] = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_address'] . '>';
        $headers[] = 'Reply-To: ' . $this->config['from_address'];
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'MIME-Version: 1.0';

        if ($isHtml) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }

        $headerString = implode("\r\n", $headers);

        $result = mail($to, $subject, $body, $headerString);

        if (!$result) {
            $this->errors[] = 'PHP mail() function failed';
        }

        return $result;
    }

    /**
     * Validate email configuration
     */
    private function validateConfig($config = null)
    {
        $configToValidate = $config ?: $this->config;

        if (empty($configToValidate['from_address'])) {
            $this->errors[] = 'From address is required';
            return false;
        }

        if (!filter_var($configToValidate['from_address'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid from address format';
            return false;
        }

        if ($configToValidate['driver'] === 'smtp') {
            if (empty($configToValidate['host'])) {
                $this->errors[] = 'SMTP host is required';
                return false;
            }

            if (empty($configToValidate['port']) || !is_numeric($configToValidate['port'])) {
                $this->errors[] = 'Valid SMTP port is required';
                return false;
            }

            if (empty($configToValidate['username']) || empty($configToValidate['password'])) {
                $this->errors[] = 'SMTP username and password are required';
                return false;
            }

            if (!in_array($configToValidate['encryption'], ['tls', 'ssl', ''])) {
                $this->errors[] = 'Invalid encryption type. Use tls, ssl, or leave empty';
                return false;
            }
        }

        return true;
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail($userEmail, $userName)
    {
        $websiteName = getWebsiteName();
        $subject = 'Chào mừng bạn đến với ' . $websiteName;
        // Get contact email from database settings
        $contactEmail = getCompanyInfo('email_lien_he', 'contact@tro.loading99.site');
        $websiteUrl = app_url();

        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='margin: 0; font-size: 28px;'>🎉 Chào mừng bạn!</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Cảm ơn bạn đã tham gia cộng đồng {$websiteName}</p>
            </div>

            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);'>
                <h2 style='color: #333; margin-top: 0;'>Xin chào {$userName}! 👋</h2>

                <p style='color: #666; line-height: 1.6;'>
                    Cảm ơn bạn đã đăng ký tài khoản tại <strong>{$websiteName}</strong>.
                    Chúng tôi rất vui mừng chào đón bạn tham gia cộng đồng của chúng tôi!
                </p>

                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;'>
                    <h3 style='color: #495057; margin-top: 0; font-size: 18px;'>🏠 Bạn có thể bắt đầu:</h3>
                    <ul style='color: #6c757d; margin: 15px 0; padding-left: 20px;'>
                        <li style='margin: 8px 0;'>Tìm kiếm phòng trọ phù hợp với nhu cầu</li>
                        <li style='margin: 8px 0;'>Xem thông tin chi tiết các bài đăng</li>
                        <li style='margin: 8px 0;'>Liên hệ trực tiếp với chủ nhà</li>
                        <li style='margin: 8px 0;'>Lưu các bài đăng yêu thích</li>
                    </ul>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$websiteUrl}'
                       style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px;'>
                        🚀 Khám phá ngay
                    </a>
                </div>

                <div style='background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;'>
                    <p style='margin: 0; color: #1565c0;'>
                        <strong>💡 Mẹo:</strong> Hãy cập nhật đầy đủ thông tin cá nhân để có trải nghiệm tốt nhất!
                    </p>
                </div>

                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>
                    <h4 style='color: #495057; margin-top: 0; font-size: 16px;'>📞 Cần hỗ trợ?</h4>
                    <p style='color: #6c757d; margin: 10px 0;'>
                        Nếu có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi:
                    </p>
                    <p style='color: #6c757d; margin: 5px 0;'>
                        📧 Email: <a href='mailto:{$contactEmail}' style='color: #007bff; text-decoration: none;'>{$contactEmail}</a>
                    </p>
                    <p style='color: #6c757d; margin: 5px 0;'>
                        🌐 Website: <a href='{$websiteUrl}' style='color: #007bff; text-decoration: none;'>{$websiteUrl}</a>
                    </p>
                </div>

                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #999; font-size: 14px; margin: 0;'>
                        Trân trọng,<br>
                        <strong style='color: #667eea;'>Đội ngũ {$websiteName}</strong>
                    </p>
                </div>
            </div>

            <div style='text-align: center; margin-top: 20px;'>
                <p style='color: #999; font-size: 12px; margin: 0;'>
                    Email này được gửi tự động từ hệ thống {$websiteName}
                </p>
            </div>
        </div>
        ";

        return $this->send($userEmail, $subject, $body, true);
    }

    /**
     * Send seller approval email
     */
    public function sendSellerApprovalEmail($userEmail, $userName, $isApproved)
    {
        if ($isApproved) {
            $subject = 'Tài khoản seller đã được phê duyệt - ' . getWebsiteName();
            $body = "
            <h2>Chúc mừng {$userName}!</h2>
            <p>Tài khoản seller của bạn đã được phê duyệt.</p>
            <p>Bạn có thể bắt đầu đăng bài cho thuê ngay bây giờ.</p>
            <p>Trân trọng,<br>" . getWebsiteName() . "</p>
            ";
        } else {
            $subject = 'Tài khoản seller không được phê duyệt - ' . getWebsiteName();
            $body = "
            <h2>Xin chào {$userName},</h2>
            <p>Rất tiếc, tài khoản seller của bạn không được phê duyệt.</p>
            <p>Vui lòng liên hệ với chúng tôi để biết thêm chi tiết.</p>
            <p>Trân trọng,<br>" . getWebsiteName() . "</p>
            ";
        }

        return $this->send($userEmail, $subject, $body, true);
    }

    /**
     * Send contact form email
     */
    public function sendContactEmail($name, $email, $subject, $message)
    {
        // Get admin email from database settings - prioritize email_lien_he for contact forms
        $adminEmail = getCompanyInfo('email_lien_he');
        if (empty($adminEmail) || strpos($adminEmail, '@tro.loading99.site') !== false) {
            $adminEmail = getCompanyInfo('email_admin');
        }

        // Final fallback to a proper default
        if (empty($adminEmail) || strpos($adminEmail, '@tro.loading99.site') !== false) {
            $adminEmail = 'admin@tro365.com';
        }
        $websiteName = getWebsiteName();

        $emailSubject = 'Liên hệ từ website: ' . $subject;
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px;'>
                📧 Liên hệ mới từ website
            </h2>

            <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 10px 0;'><strong>👤 Tên:</strong> {$name}</p>
                <p style='margin: 10px 0;'><strong>📧 Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>
                <p style='margin: 10px 0;'><strong>📋 Chủ đề:</strong> {$subject}</p>
                <p style='margin: 10px 0;'><strong>🕒 Thời gian:</strong> " . date('d/m/Y H:i:s') . "</p>
            </div>

            <div style='background: white; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;'>
                <h4 style='color: #495057; margin-top: 0;'>💬 Nội dung tin nhắn:</h4>
                <p style='line-height: 1.6; color: #6c757d;'>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>

            <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
            <p style='font-size: 12px; color: #666; text-align: center;'>
                <em>Email này được gửi từ form liên hệ của {$websiteName}</em>
            </p>
        </div>
        ";

        return $this->send($adminEmail, $emailSubject, $body, true);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($userEmail, $resetLink, $token)
    {
        $websiteName = getWebsiteName();
        $subject = 'Đặt lại mật khẩu - ' . $websiteName;

        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333; text-align: center;'>🔐 Đặt lại mật khẩu</h2>

            <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                <p>Xin chào,</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>{$websiteName}</strong>.</p>
            </div>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$resetLink}'
                   style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>
                    🔄 Đặt lại mật khẩu
                </a>
            </div>

            <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 0; color: #856404;'>
                    <strong>⚠️ Lưu ý quan trọng:</strong><br>
                    • Liên kết này chỉ có hiệu lực trong 1 giờ<br>
                    • Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này<br>
                    • Không chia sẻ liên kết này với bất kỳ ai
                </p>
            </div>

            <div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 0; font-size: 12px; color: #6c757d;'>
                    <strong>Mã token:</strong> {$token}<br>
                    <strong>Thời gian yêu cầu:</strong> " . date('d/m/Y H:i:s') . "<br>
                    <strong>IP Address:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "
                </p>
            </div>

            <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
            <p style='font-size: 12px; color: #666; text-align: center;'>
                <em>Email này được gửi tự động từ hệ thống {$websiteName}. Vui lòng không trả lời email này.</em>
            </p>
        </div>
        ";

        return $this->send($userEmail, $subject, $body, true);
    }

    /**
     * Send notification email
     */
    public function sendNotificationEmail($to, $subject, $message, $type = 'info')
    {
        $websiteName = getWebsiteName();

        // Define colors and icons based on notification type
        $typeConfig = [
            'success' => ['color' => '#28a745', 'icon' => '✅', 'bg' => '#d4edda'],
            'warning' => ['color' => '#ffc107', 'icon' => '⚠️', 'bg' => '#fff3cd'],
            'error' => ['color' => '#dc3545', 'icon' => '❌', 'bg' => '#f8d7da'],
            'info' => ['color' => '#17a2b8', 'icon' => 'ℹ️', 'bg' => '#d1ecf1']
        ];

        $config = $typeConfig[$type] ?? $typeConfig['info'];

        $emailSubject = '[' . $websiteName . '] ' . $subject;
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: {$config['color']}; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h2 style='margin: 0; font-size: 24px;'>{$config['icon']} Thông báo</h2>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>{$subject}</p>
            </div>

            <div style='background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='background: {$config['bg']}; padding: 20px; border-radius: 5px; border-left: 4px solid {$config['color']};'>
                    <div style='color: #333; line-height: 1.6;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                </div>

                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;'>
                    <p style='color: #666; font-size: 14px; margin: 0;'>
                        <strong>Thời gian:</strong> " . date('d/m/Y H:i:s') . "<br>
                        <strong>Từ:</strong> {$websiteName}
                    </p>
                </div>
            </div>

            <div style='text-align: center; margin-top: 20px;'>
                <p style='color: #999; font-size: 12px; margin: 0;'>
                    Email thông báo từ hệ thống {$websiteName}
                </p>
            </div>
        </div>
        ";

        return $this->send($to, $emailSubject, $body, true);
    }

    /**
     * Send test email
     */
    public function sendTestEmail($testEmail = null, $customConfig = null)
    {
        // Use custom config if provided
        if ($customConfig) {
            $originalConfig = $this->config;
            $this->config = $customConfig;
        }

        $adminEmail = $testEmail ?: getCompanyInfo('email_admin', 'admin@tro.loading99.site');
        $websiteName = getWebsiteName();

        $subject = 'Test Email từ ' . $websiteName;
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333;'>🧪 Test Email Configuration</h2>
            <p>Đây là email test để kiểm tra cấu hình email của hệ thống.</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>📧 Thông tin gửi:</strong></p>
                <ul style='margin: 10px 0; padding-left: 20px;'>
                    <li><strong>Thời gian:</strong> " . date('d/m/Y H:i:s') . "</li>
                    <li><strong>Từ:</strong> " . $websiteName . "</li>
                    <li><strong>Gửi đến:</strong> " . $adminEmail . "</li>
                    <li><strong>Driver:</strong> " . $this->config['driver'] . "</li>
                    " . ($this->config['driver'] === 'smtp' ? "<li><strong>SMTP Host:</strong> " . $this->config['host'] . ":" . $this->config['port'] . "</li>" : "") . "
                </ul>
            </div>

            <p style='color: #28a745;'>✅ Nếu bạn nhận được email này, cấu hình email đã hoạt động chính xác.</p>

            <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
            <p style='font-size: 12px; color: #666;'>
                <em>Email này được gửi từ hệ thống test email của " . $websiteName . ".</em>
            </p>
        </div>
        ";

        $result = $this->send($adminEmail, $subject, $body, true);

        // Restore original config if custom config was used
        if ($customConfig) {
            $this->config = $originalConfig;
        }

        return $result;
    }

    /**
     * Send email verification email
     */
    public function sendEmailVerification($userEmail, $userName, $verificationLink, $token)
    {
        $websiteName = getWebsiteName();
        $subject = 'Xác thực email - ' . $websiteName;
        $contactEmail = getCompanyInfo('email_lien_he', 'contact@tro.loading99.site');
        $websiteUrl = app_url();

        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa;'>
            <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='margin: 0; font-size: 28px;'>📧 Xác thực email</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Vui lòng xác thực email để hoàn tất đăng ký</p>
            </div>

            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #495057; margin-top: 0;'>Xin chào {$userName}!</h2>

                <p style='line-height: 1.6; color: #6c757d; font-size: 16px;'>
                    Cảm ơn bạn đã đăng ký tài khoản tại <strong>{$websiteName}</strong>.
                    Để hoàn tất quá trình đăng ký và bảo mật tài khoản, vui lòng xác thực địa chỉ email của bạn.
                </p>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$verificationLink}'
                       style='display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                              color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px;
                              font-weight: bold; font-size: 16px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);'>
                        ✅ Xác thực email ngay
                    </a>
                </div>

                <div style='background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #495057; margin-top: 0;'>🔒 Tại sao cần xác thực email?</h4>
                    <ul style='color: #6c757d; margin: 0; padding-left: 20px;'>
                        <li>Bảo mật tài khoản của bạn</li>
                        <li>Nhận thông báo quan trọng từ hệ thống</li>
                        <li>Khôi phục mật khẩu khi cần thiết</li>
                        <li>Trải nghiệm đầy đủ các tính năng</li>
                    </ul>
                </div>

                <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404; font-size: 14px;'>
                        <strong>⚠️ Lưu ý:</strong> Link xác thực này sẽ hết hạn sau 24 giờ.
                        Nếu bạn không xác thực email, tài khoản sẽ bị hạn chế một số tính năng.
                    </p>
                </div>

                <p style='color: #6c757d; font-size: 14px; line-height: 1.6;'>
                    Nếu nút không hoạt động, bạn có thể copy và paste link sau vào trình duyệt:<br>
                    <a href='{$verificationLink}' style='color: #007bff; word-break: break-all;'>{$verificationLink}</a>
                </p>

                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>

                <div style='text-align: center;'>
                    <p style='color: #6c757d; font-size: 14px; margin: 0;'>
                        Cần hỗ trợ? Liên hệ với chúng tôi tại:
                        <a href='mailto:{$contactEmail}' style='color: #007bff;'>{$contactEmail}</a>
                    </p>
                    <p style='color: #6c757d; font-size: 14px; margin: 10px 0 0 0;'>
                        Hoặc truy cập: <a href='{$websiteUrl}' style='color: #007bff;'>{$websiteUrl}</a>
                    </p>
                </div>
            </div>

            <div style='text-align: center; margin-top: 20px;'>
                <p style='color: #999; font-size: 12px; margin: 0;'>
                    Email xác thực từ hệ thống {$websiteName}
                </p>
            </div>
        </div>
        ";

        return $this->send($userEmail, $subject, $body, true);
    }

    /**
     * Get detailed email configuration info
     */
    public function getConfigInfo()
    {
        return [
            'driver' => $this->config['driver'],
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'encryption' => $this->config['encryption'],
            'from_address' => $this->config['from_address'],
            'from_name' => $this->config['from_name'],
            'phpmailer_available' => $this->isPHPMailerAvailable(),
            'last_connection_test' => $this->lastConnectionTest
        ];
    }

    /**
     * Sanitize config for logging (remove sensitive data)
     */
    private function sanitizeConfigForLog()
    {
        $config = $this->config;
        if (isset($config['password'])) {
            $config['password'] = '***';
        }
        return $config;
    }

    /**
     * Log info message
     */
    private function logInfo($message, $context = [])
    {
        if (function_exists('writeLog')) {
            writeLog($message, 'info', 'email', $context);
        }
    }

    /**
     * Log error message
     */
    private function logError($message, $context = [])
    {
        if (function_exists('writeLog')) {
            writeLog($message, 'error', 'email', $context);
        }
    }

    /**
     * Get errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get last error
     */
    public function getLastError()
    {
        return end($this->errors) ?: '';
    }

    /**
     * Clear errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
}
?>
