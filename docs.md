# Cấu trúc thư mục dự án Trọ 365

## Cấu trúc thư mục hiện tại và đề xuất

```
Tro365/
├── index.php                          # File chính, điều hướng request
├── .htaccess                          # Cấu hình Apache rewrite rules
├── .env                               # File cấu hình môi trường (không commit)
├── .env.example                       # File mẫu cấu hình môi trường
├── composer.json                      # Quản lý dependencies PHP
├── composer.lock                      # Lock file dependencies
├── README.md                          # Hướng dẫn dự án
│
├── assets/                            # Tài nguyên tĩnh
│   ├── css/                          # File CSS
│   │   ├── bootstrap.min.css         # Bootstrap CSS
│   │   ├── client/                   # CSS chính của website
│   │   │   ├── main.css              # CSS chính
│   │   │   └── profile.css           # CSS trang cá nhân
│   │   ├── admin                     # CSS cho admin panel
│   │   │   └── main.css              # CSS Chính
│   │   ├── responsive.css            # CSS responsive
│   │   └── components/               # CSS cho từng component
│   │       ├── header.css
│   │       ├── footer.css
│   │       ├── card.css
│   │       └── form.css
│   │
│   ├── js/                           # File JavaScript
│   │   ├── jquery.min.js             # jQuery library
│   │   ├── bootstrap.min.js          # Bootstrap JS
│   │   ├── client/                   # JS chính
│   │   │   ├── main.js               # JS chính
│   │   │   └── custom.js             # JS tùy chỉnh
│   │   ├── admin                     # JS cho admin
│   │   │   └── main.js               # JS chính
│   │   ├── validation.js             # Validation form
│   │   ├── ajax.js                   # AJAX functions
│   │   └── components/               # JS cho từng component
│   │       ├── search.js
│   │       ├── filter.js
│   │       ├── map.js
│   │       └── upload.js
│   │
│   ├── images/                       # Hình ảnh tĩnh
│   │   ├── logo/                     # Logo website
│   │   ├── icons/                    # Icons
│   │   ├── banners/                  # Banner quảng cáo
│   │   ├── default/                  # Ảnh mặc định
│   │   └── system/                   # Ảnh hệ thống
│   │
│   ├── uploads/                      # File upload từ user
│   │   ├── avatars/                  # Avatar người dùng
│   │   ├── posts/                    # Ảnh bài đăng
│   │   │   ├── 2024/                 # Phân theo năm
│   │   │   │   ├── 01/               # Phân theo tháng
│   │   │   │   └── 02/
│   │   │   └── thumbs/               # Ảnh thumbnail
│   │   ├── documents/                # Giấy tờ đăng ký seller
│   │   └── temp/                     # File tạm thời
│   │
│   └── vendors/                      # Thư viện bên thứ 3
│       ├── fontawesome/              # Font Awesome
│       ├── sweetalert2/              # SweetAlert2
│       ├── datatables/               # DataTables
│       └── tinymce/                  # TinyMCE editor
│
├── config/                           # Cấu hình hệ thống
│   ├── database/         
│   │   ├── tro365.sql                # SQL database
│   │   └── connection.php            # Kết nối database
│   │   
│   ├── app.php                       # Cấu hình ứng dụng
│   ├── mail.php                      # Cấu hình email
│   ├── upload.php                    # Cấu hình upload
│   └── constants.php                 # Hằng số hệ thống
│
├── includes/                         # File include chung
│   ├── layouts/                      # Layout templates
│   │   ├── header.php                # Header chung
│   │   ├── footer.php                # Footer chung
│   │   ├── sidebar.php               # Sidebar
│   │   ├── navigation.php            # Menu navigation
│   │   └── admin/                    # Layout admin
│   │       ├── header.php
│   │       ├── footer.php
│   │       └── sidebar.php
│   │
│   ├── functions/                    # Các function chung
│   │   ├── auth.php                  # Xác thực người dùng
│   │   ├── database.php              # Function database
│   │   ├── upload.php                # Function upload
│   │   ├── validation.php            # Validation
│   │   ├── mail.php                  # Gửi email
│   │   ├── pagination.php            # Phân trang
│   │   ├── security.php              # Bảo mật
│   │   └── helpers.php               # Helper functions
│   │
│   └── components/                   # Components tái sử dụng
│       ├── breadcrumb.php            # Breadcrumb
│       ├── pagination.php            # Pagination component
│       ├── search-form.php           # Form tìm kiếm
│       ├── post-card.php             # Card bài đăng
│       └── user-menu.php             # Menu người dùng
│
├── pages/                            # Các trang chính
│   ├── client/                       # Trang người dùng
│   │   ├── home.php                  # Trang chủ
│   │   ├── search.php                # Tìm kiếm
│   │   ├── post-detail.php           # Chi tiết bài đăng
│   │   ├── contact.php               # Liên hệ
│   │   ├── about.php                 # Giới thiệu
│   │   ├── news.php                  # Tin tức
│   │   └── profile/                  # Trang cá nhân
│   │       ├── index.php             # Thông tin cá nhân
│   │       ├── edit.php              # Sửa thông tin
│   │       ├── favorites.php         # Bài đăng yêu thích
│   │       ├── my-posts.php          # Bài đăng của tôi
│   │       └── change-password.php   # Đổi mật khẩu
│   │
│   ├── auth/                         # Xác thực
│   │   ├── login.php                 # Đăng nhập
│   │   ├── register.php              # Đăng ký
│   │   ├── logout.php                # Đăng xuất
│   │   ├── forgot-password.php       # Quên mật khẩu
│   │   └── reset-password.php        # Reset mật khẩu
│   │
│   ├── seller/                       # Trang seller
│   │   ├── dashboard.php             # Dashboard seller
│   │   ├── posts/                    # Quản lý bài đăng
│   │   │   ├── index.php             # Danh sách bài đăng
│   │   │   ├── create.php            # Tạo bài đăng
│   │   │   ├── edit.php              # Sửa bài đăng
│   │   │   └── delete.php            # Xóa bài đăng
│   │   ├── contacts.php              # Liên hệ từ khách
│   │   ├── transactions.php          # Giao dịch
│   │   ├── stats.php                 # Thống kê
│   │   └── register-seller.php       # Đăng ký seller
│   │
│   └── admin/                        # Trang admin
│       ├── dashboard.php             # Dashboard admin
│       ├── users/                    # Quản lý người dùng
│       │   ├── index.php             # Danh sách user
│       │   ├── create.php            # Tạo user
│       │   ├── edit.php              # Sửa user
│       │   └── delete.php            # Xóa user
│       ├── posts/                    # Quản lý bài đăng
│       │   ├── index.php             # Danh sách bài đăng
│       │   ├── approve.php           # Duyệt bài đăng
│       │   └── reports.php           # Báo cáo bài đăng
│       ├── sellers/                  # Quản lý seller
│       │   ├── index.php             # Danh sách seller
│       │   ├── approve.php           # Duyệt đăng ký seller
│       │   └── commissions.php       # Hoa hồng
│       ├── categories.php            # Quản lý danh mục
│       ├── locations.php             # Quản lý địa điểm
│       ├── settings.php              # Cài đặt hệ thống
│       ├── statistics.php            # Thống kê tổng quan
│       └── logs.php                  # Nhật ký hệ thống
│
├── router/                           # Routing system
│   ├── web.php                       # Routes cho web
│   ├── api/                          # API routes
│   │   ├── auth.php                  # API xác thực
│   │   ├── posts.php                 # API bài đăng
│   │   ├── users.php                 # API người dùng
│   │   ├── locations.php             # API địa điểm
│   │   └── upload.php                # API upload
│   └── middleware/                   # Middleware
│       ├── auth.php                  # Middleware xác thực
│       ├── admin.php                 # Middleware admin
│       ├── seller.php                # Middleware seller
│       └── cors.php                  # Middleware CORS
│
├── classes/                          # Classes PHP
│   ├── Database.php                  # Class database
│   ├── User.php                      # Class người dùng
│   ├── Post.php                      # Class bài đăng
│   ├── Auth.php                      # Class xác thực
│   ├── Upload.php                    # Class upload
│   ├── Mail.php                      # Class email
│   ├── Pagination.php                # Class phân trang
│   ├── Validation.php                # Class validation
│   └── helpers/                      # Helper classes
│       ├── ImageHelper.php           # Xử lý ảnh
│       ├── StringHelper.php          # Xử lý chuỗi
│       └── DateHelper.php            # Xử lý ngày tháng
│
├── logs/                             # File log
│   ├── error.log                     # Log lỗi
│   ├── access.log                    # Log truy cập
│   └── app.log                       # Log ứng dụng
│
├── docs/                             # Tài liệu
│   ├── api.md                        # Tài liệu API
│   ├── database.md                   # Tài liệu database
│   ├── installation.md               # Hướng dẫn cài đặt
│   └── cau-truc-thu-muc.md          # File này
│
└── vendor/                           # Composer dependencies
    └── autoload.php                  # Autoload file
```

Đầu tiên hãy chia ra từng phần làm cho dễ, hãy làm thật kỹ để tránh bị xao nhãn. Lên tiến trình trong file plan.md, phần nào xong thì đánh dấu tick xanh, còn chưa thì đánh dấu X đỏ để theo dõi quá trình thực hiện. 