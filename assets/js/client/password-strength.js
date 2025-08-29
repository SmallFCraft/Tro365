/**
 * Enhanced Password Strength Indicator with Glass Morphism
 * Tro365 - Modern Password Validation System
 */

class PasswordStrengthIndicator {
    constructor(options = {}) {
        this.passwordFieldId = options.passwordFieldId || 'password';
        this.strengthBarId = options.strengthBarId || 'passwordStrength';
        this.strengthTextId = options.strengthTextId || 'passwordStrengthText';
        this.confirmFieldId = options.confirmFieldId || null;
        this.confirmFeedbackId = options.confirmFeedbackId || null;
        
        this.init();
    }

    init() {
        const passwordField = document.getElementById(this.passwordFieldId);
        if (passwordField) {
            passwordField.addEventListener('input', () => this.updatePasswordStrength());
            
            // Add confirm password validation if specified
            if (this.confirmFieldId) {
                const confirmField = document.getElementById(this.confirmFieldId);
                if (confirmField) {
                    confirmField.addEventListener('input', () => this.validatePasswordMatch());
                }
            }
        }
    }

    checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];

        if (password.length >= 8) {
            strength += 1;
        } else {
            feedback.push('Ít nhất 8 ký tự');
        }

        if (/[a-z]/.test(password)) {
            strength += 1;
        } else {
            feedback.push('Chữ thường (a-z)');
        }

        if (/[A-Z]/.test(password)) {
            strength += 1;
        } else {
            feedback.push('Chữ hoa (A-Z)');
        }

        if (/[0-9]/.test(password)) {
            strength += 1;
        } else {
            feedback.push('Số (0-9)');
        }

        if (/[^A-Za-z0-9]/.test(password)) {
            strength += 1;
        } else {
            feedback.push('Ký tự đặc biệt (!@#$...)');
        }

        return { strength, feedback };
    }

    updatePasswordStrength() {
        const password = document.getElementById(this.passwordFieldId).value;
        const strengthBar = document.getElementById(this.strengthBarId);
        const strengthText = document.getElementById(this.strengthTextId);

        if (!strengthBar || !strengthText) return;

        if (password.length === 0) {
            strengthBar.innerHTML = '';
            strengthText.innerHTML = '';
            return;
        }

        const result = this.checkPasswordStrength(password);
        const percentage = (result.strength / 5) * 100;

        let color = '#ef4444';
        let bgColor = 'rgba(239, 68, 68, 0.1)';
        let text = 'Yếu';

        if (result.strength >= 4) {
            color = '#22c55e';
            bgColor = 'rgba(34, 197, 94, 0.1)';
            text = 'Mạnh';
        } else if (result.strength >= 3) {
            color = '#f59e0b';
            bgColor = 'rgba(245, 158, 11, 0.1)';
            text = 'Trung bình';
        } else if (result.strength >= 2) {
            color = '#f97316';
            bgColor = 'rgba(249, 115, 22, 0.1)';
            text = 'Yếu';
        }

        // Create glass morphism progress bar
        strengthBar.innerHTML = `
            <div class="glass-container" style="
                background: ${bgColor};
                border: 1px solid ${color}33;
                border-radius: 8px;
                padding: 0;
                overflow: hidden;
                height: 8px;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            ">
                <div style="
                    width: ${percentage}%;
                    height: 100%;
                    background: linear-gradient(90deg, ${color}88, ${color});
                    transition: width 0.3s ease;
                    border-radius: 8px;
                "></div>
            </div>
        `;

        // Create glass morphism text indicator
        strengthText.innerHTML = `
            <div class="glass-container mt-2" style="
                background: ${bgColor};
                border: 1px solid ${color}33;
                border-radius: 8px;
                padding: 0.5rem 0.75rem;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            ">
                <div class="d-flex align-items-center justify-content-between">
                    <span style="color: ${color}; font-weight: 600; font-size: 0.875rem;">
                        <i class="fas fa-shield-alt me-1"></i>
                        Độ mạnh: ${text}
                    </span>
                    <span style="color: ${color}; font-size: 0.75rem;">
                        ${result.strength}/5
                    </span>
                </div>
                ${result.feedback.length > 0 ? `
                    <div class="mt-2" style="font-size: 0.75rem; color: var(--text-secondary);">
                        <div class="mb-1">Cần thêm:</div>
                        ${result.feedback.map(item => `
                            <div style="display: flex; align-items: center; margin-bottom: 0.25rem;">
                                <i class="fas fa-circle" style="font-size: 0.25rem; color: ${color}; margin-right: 0.5rem;"></i>
                                ${item}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    }

    validatePasswordMatch() {
        if (!this.confirmFieldId || !this.confirmFeedbackId) return;

        const password = document.getElementById(this.passwordFieldId).value;
        const confirmPassword = document.getElementById(this.confirmFieldId).value;
        const feedback = document.getElementById(this.confirmFeedbackId);

        if (!feedback) return;

        if (confirmPassword.length === 0) {
            feedback.innerHTML = '';
            return;
        }

        const isMatch = password === confirmPassword;
        const color = isMatch ? '#22c55e' : '#ef4444';
        const bgColor = isMatch ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)';
        const icon = isMatch ? 'fa-check-circle' : 'fa-times-circle';
        const text = isMatch ? 'Mật khẩu khớp' : 'Mật khẩu không khớp';

        feedback.innerHTML = `
            <div class="glass-container mt-2" style="
                background: ${bgColor};
                border: 1px solid ${color}33;
                border-radius: 8px;
                padding: 0.5rem 0.75rem;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            ">
                <div class="d-flex align-items-center">
                    <i class="fas ${icon} me-2" style="color: ${color};"></i>
                    <span style="color: ${color}; font-weight: 600; font-size: 0.875rem;">
                        ${text}
                    </span>
                </div>
            </div>
        `;
    }
}

// Global Password Toggle Function (consistent across all auth pages)
function togglePasswordVisibility(fieldId, iconId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(iconId);
    
    if (passwordField && toggleIcon) {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PasswordStrengthIndicator;
}

// Auto-initialize for common field names
document.addEventListener('DOMContentLoaded', function() {
    // Auto-detect and initialize password strength indicators
    const commonConfigs = [
        { passwordFieldId: 'password', strengthBarId: 'passwordStrength', strengthTextId: 'passwordStrengthText', confirmFieldId: 'confirm_password', confirmFeedbackId: 'confirmPasswordFeedback' },
        { passwordFieldId: 'new_password', strengthBarId: 'newPasswordStrength', strengthTextId: 'newPasswordStrengthText', confirmFieldId: 'confirm_password', confirmFeedbackId: 'confirmPasswordFeedback' }
    ];

    commonConfigs.forEach(config => {
        if (document.getElementById(config.passwordFieldId)) {
            new PasswordStrengthIndicator(config);
        }
    });
});
