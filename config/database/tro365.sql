-- Cơ sở dữ liệu Trọ 365 (Tên Tro365)
-- Last Updated: 2025-08-26

-- Tạo database
USE tro365;

-- Thiết lập charset và collation cho database
ALTER DATABASE tro365 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Thiết lập charset cho session hiện tại
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;
SET character_set_connection=utf8mb4;

-- Disable foreign key checks để có thể drop tables
SET FOREIGN_KEY_CHECKS = 0;

-- Bảng vai trò
DROP TABLE IF EXISTS VaiTro;
CREATE TABLE VaiTro (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenVT VARCHAR(50) NOT NULL UNIQUE,
    MoTa TEXT,
    CapDo INT NOT NULL DEFAULT 1, -- 1:user, 2:seller, 3:supporter, 4:moderator, 5:admin
    TrangThai TINYINT DEFAULT 1, -- 1:active, 0:inactive
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng khách hàng (Optimized for Enhanced Validation)
DROP TABLE IF EXISTS KhachHang;
CREATE TABLE KhachHang (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenDN VARCHAR(50) NOT NULL UNIQUE, -- Tên đăng nhập
    Email VARCHAR(100) NOT NULL UNIQUE,
    MatKhau VARCHAR(255) NOT NULL,
    HoTen VARCHAR(100) NOT NULL,
    NgaySinh DATE NULL, -- Ngày sinh
    GioiTinh ENUM('Nam', 'Nữ', 'Khác') NULL, -- Giới tính
    CCCD VARCHAR(20) NULL, -- Căn cước công dân
    SDT VARCHAR(15),
    DiaChi TEXT,
    TinhThanhID VARCHAR(10) NULL COMMENT 'Province code from API',
    QuanHuyenID VARCHAR(10) NULL COMMENT 'District code from API',
    XaPhuongID VARCHAR(10) NULL COMMENT 'Ward code from API',
    VaiTroID INT DEFAULT 1,
    AnhDaiDien VARCHAR(255),
    TrangThai TINYINT DEFAULT 1, -- 1:active, 0:inactive, 2:banned
    reset_token VARCHAR(255) NULL, -- Token reset password
    reset_expiry TIMESTAMP NULL, -- Thời gian hết hạn token
    email_verified_at TIMESTAMP NULL COMMENT 'Thời gian xác thực email',
    email_verification_token VARCHAR(255) NULL COMMENT 'Token xác thực email',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    LanDangNhapCuoi TIMESTAMP NULL,
    FOREIGN KEY (VaiTroID) REFERENCES VaiTro(ID),

    -- Enhanced Validation Indexes
    INDEX idx_khachhang_tendn (TenDN),
    INDEX idx_khachhang_email (Email),
    INDEX idx_khachhang_hoten (HoTen),
    INDEX idx_khachhang_cccd (CCCD),
    INDEX idx_khachhang_sdt (SDT),
    INDEX idx_email_verification_token (email_verification_token),
    INDEX idx_reset_token (reset_token)
);

-- Bảng đăng ký seller
DROP TABLE IF EXISTS DangKySeller;
-- DangKySeller Table - Normalized Structure (Phase 2)
-- Only stores business-specific information that differs from personal data
CREATE TABLE DangKySeller (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    HoTenChuTro VARCHAR(100) NOT NULL,

    -- Document uploads
    AnhCCCDTruoc VARCHAR(255),
    AnhCCCDSau VARCHAR(255),
    GiayPhepKD VARCHAR(255), -- Giấy phép kinh doanh

    -- Location data
    TinhThanhID VARCHAR(10) NULL COMMENT 'Province code from API',
    QuanHuyenID VARCHAR(10) NULL COMMENT 'District code from API',
    XaPhuongID VARCHAR(10) NULL COMMENT 'Ward code from API',

    -- Business-specific contact info (only if different from personal)
    CCCDKinhDoanh VARCHAR(20) NULL COMMENT 'Business CCCD if different from personal',
    SDTKinhDoanh VARCHAR(15) NULL COMMENT 'Business phone if different from personal',
    EmailKinhDoanh VARCHAR(100) NULL COMMENT 'Business email if different from personal',
    DiaChiKinhDoanh TEXT NULL COMMENT 'Business address if different from personal',

    -- Seller application data
    LyDoMuonBan TEXT,
    TrangThai TINYINT DEFAULT 0, -- 0:chờ duyệt, 1:đã duyệt, 2:từ chối
    NguoiDuyet INT NULL,
    NgayDuyet TIMESTAMP NULL,
    LyDoTuChoi TEXT,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiDuyet) REFERENCES KhachHang(ID) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Legacy DangKySeller Table (Phase 1 - Backward Compatibility)
-- Uncomment this if you need to rollback to legacy structure
/*
CREATE TABLE DangKySeller (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    HoTenChuTro VARCHAR(100) NOT NULL,
    CCCD VARCHAR(20) NOT NULL,
    AnhCCCDTruoc VARCHAR(255),
    AnhCCCDSau VARCHAR(255),
    GiayPhepKD VARCHAR(255), -- Giấy phép kinh doanh
    DiaChi TEXT NOT NULL,
    TinhThanhID VARCHAR(10) NULL COMMENT 'Province code from API',
    QuanHuyenID VARCHAR(10) NULL COMMENT 'District code from API',
    XaPhuongID VARCHAR(10) NULL COMMENT 'Ward code from API',
    SDTLienHe VARCHAR(15) NOT NULL,
    EmailLienHe VARCHAR(100),
    LyDoMuonBan TEXT,
    TrangThai TINYINT DEFAULT 0, -- 0:chờ duyệt, 1:đã duyệt, 2:từ chối
    NguoiDuyet INT NULL,
    NgayDuyet TIMESTAMP NULL,
    LyDoTuChoi TEXT,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiDuyet) REFERENCES KhachHang(ID) ON DELETE SET NULL ON UPDATE CASCADE
);
*/

-- Bảng danh mục (Optimized for Enhanced Validation)
DROP TABLE IF EXISTS DanhMuc;
CREATE TABLE DanhMuc (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenDM VARCHAR(100) NOT NULL,
    MoTa TEXT,
    ThuTu INT DEFAULT 0,
    TrangThai TINYINT DEFAULT 1,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Enhanced Validation Indexes
    INDEX idx_danhmuc_tendm (TenDM),
    INDEX idx_danhmuc_thutu (ThuTu),
    INDEX idx_danhmuc_trangthai (TrangThai)
);

-- Bảng bài đăng (Optimized for Enhanced Validation)
DROP TABLE IF EXISTS BaiDang;
CREATE TABLE BaiDang (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TieuDe VARCHAR(255) NOT NULL,
    -- MoTa TEXT, -- REMOVED: Not used anymore, only NoiDung is used
    NoiDung LONGTEXT NOT NULL COMMENT 'Post content (replaces MoTa)',
    NguoiDangID INT NOT NULL,
    DanhMucID INT,
    DiaChi TEXT NOT NULL,
    TinhThanhID VARCHAR(10) NULL COMMENT 'Province code from API',
    QuanHuyenID VARCHAR(10) NULL COMMENT 'District code from API',
    XaPhuongID VARCHAR(10) NULL COMMENT 'Ward code from API',
    Gia DECIMAL(15,0) NOT NULL, -- Giá thuê/tháng
    DienTich DECIMAL(8,2), -- m2
    SoPhong INT DEFAULT 1,
    AnhDaiDien VARCHAR(255),
    LuotXem INT DEFAULT 0,
    TrangThai TINYINT DEFAULT 0, -- 0:chờ duyệt, 1:đã duyệt, 2:từ chối, 3:đã thuê, 4:tạm ẩn
    NguoiDuyet INT NULL,
    NgayDuyet TIMESTAMP NULL,
    NgayHetHan TIMESTAMP NULL,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (NguoiDangID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (DanhMucID) REFERENCES DanhMuc(ID) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (NguoiDuyet) REFERENCES KhachHang(ID) ON DELETE SET NULL ON UPDATE CASCADE,

    -- Enhanced Validation Indexes
    INDEX idx_baidang_tieude (TieuDe),
    INDEX idx_baidang_gia (Gia),
    INDEX idx_baidang_dientich (DienTich),
    INDEX idx_baidang_sophong (SoPhong),
    INDEX idx_baidang_trangthai (TrangThai)
);

-- Sample data for BaiDang (MoTa data migrated to NoiDung)
INSERT INTO `BaiDang` (`ID`, `TieuDe`, `NoiDung`, `NguoiDangID`, `DanhMucID`, `DiaChi`, `TinhThanhID`, `QuanHuyenID`, `XaPhuongID`, `Gia`, `DienTich`, `SoPhong`, `AnhDaiDien`, `LuotXem`, `TrangThai`, `NguoiDuyet`, `NgayDuyet`, `NgayHetHan`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 'Nhà Trọ 70 Kênh Nước Đen, Bình Hưng Hòa A, Quận Bình Tân, Thành Phố Hồ Chí Minh',
'<h3>CHÍNH CHỦ CHO THUÊ PHÒNG TẠI QUẬN BÌNH TÂN</h3><div class=\"box-header\">&nbsp;</div>\r\n<div class=\"hostel__detail--content\">\r\n<div class=\"frame expanded\">\r\n<div class=\"content-detail\">\r\n<p><strong>Địa chỉ:</strong>&nbsp;70 K&ecirc;nh Nước Đen, B&igrave;nh Hưng H&ograve;a A, B&igrave;nh T&acirc;n</p>\r\n<p><strong>-Li&ecirc;n Hệ Xem Ph&ograve;ng:&nbsp;</strong></p>\r\n<ul>\r\n<li><strong>Anh B&igrave;nh: 0909275350</strong></li>\r\n<li><strong>Chị Nhiệm:&nbsp;0909472585</strong></li>\r\n</ul>\r\n<p><strong>-Gi&aacute; Ph&ograve;ng:</strong>&nbsp;Từ 4tr-4tr2</p>\r\n<p><strong>-Diện t&iacute;ch:</strong>&nbsp;Từ&nbsp;25-35m2</p>\r\n<p><strong>-Điện:</strong>&nbsp;3k8,&nbsp;<strong>Nước:</strong>100k/Người,&nbsp;<strong>Xe:</strong>&nbsp;100k/Chiếc,&nbsp;<strong>Dịch vụ:&nbsp;</strong>120k</p>\r\n<p><strong>-Nội thất:</strong>&nbsp;Kệ bếp, M&aacute;y lạnh, Tủ quần &aacute;o, Ghế sofa, Cửa sổ, Quạt m&aacute;y, M&aacute;y lạnh, Tủ lạnh, Giường nệm, Nh&agrave; vệ sinh ri&ecirc;ng, ban c&ocirc;ng, thang m&aacute;y</p>\r\n<p>-C&oacute; b&atilde;i để xe rộng r&atilde;i, camera an ninh, kh&oacute;a v&acirc;n tay, PCCC, c&oacute; s&acirc;n thượng rộng r&atilde;i tho&aacute;ng m&aacute;t</p>\r\n<p>-Nằm ngo&agrave;i đường lớn rộng r&atilde;i, đối diện c&ocirc;ng vi&ecirc;n, Gần TTTM AEON T&acirc;n Ph&uacute;, Trường ĐH C&ocirc;ng Thương TPHCM, Trường ĐH Văn Hiến, Bệnh Viện B&igrave;nh T&acirc;n</p>\r\n<p>-Thuận tiện di chuyển: Qu&acirc;n T&acirc;n Ph&uacute;, Quận B&igrave;nh T&acirc;n, Quận 10, Quận 5</p>\r\n</div>\r\n</div>\r\n</div>',
1, 1, '70 Kênh Nước Đen', '79', '777', '27439', 4000000, 35.00, 29, '/assets/uploads/posts/2025/08/70_kenhnuocden_hinhk_1755233996_TGYwH91a.png', 4, 1, 1, '2025-08-15 05:00:04', NULL, '2025-08-15 04:59:56', '2025-08-15 06:12:02'),
(2, 'Nhà trọ số 73 Phạm Sư Mạnh, Phường Khuê Trung, Quận Cẩm Lệ, Đà Nẵng',
'<h3>Khu vực an ninh tốt, yên tĩnh,đường đi riêng, giờ giấc tự do đến 22 giờ, trên lầu 1 có máy lạnh, máy nước nóng, tủ lạnh, máy giặt chung.</h3><div class=\"box-header\">\r\n<h2 class=\"box-subtitle\">Giới thiệu</h2>\r\n</div>\r\n<div class=\"hostel__detail--content\">\r\n<div class=\"frame\">\r\n<div class=\"content-detail\">\r\n<p>C&ograve;n ph&ograve;ng cho thu&ecirc;</p>\r\n<p>Ph&ograve;ng trọ 73 Phạm sư Mạnh - Khu&ecirc; trung - Cẩm Lệ - Đ&agrave; Nẵng</p>\r\n<p>Rộng: 20m2</p>\r\n<p>C&oacute; chổ nấu ăn đặt ngo&agrave;i ph&ograve;ng ngủ, kh&ocirc;ng bị &aacute;m m&ugrave;i, Toilet ri&ecirc;ng, ph&ograve;ng mới x&acirc;y, sạch sẽ, full nội thất, kh&eacute;p k&iacute;n.</p>\r\n<p>Li&ecirc;n hệ: 0905094473</p>\r\n</div>\r\n</div>\r\n</div>',
1, 1, '73 Phạm Sư Mạnh', '48', '495', '20260', 3150000, 20.00, 20, '/assets/uploads/posts/2025/08/395400237_693986446018327_5398445775215642801_n_1755234406_mAZvwDO8.jpg', 8, 1, 1, '2025-08-15 05:06:53', NULL, '2025-08-15 05:06:46', '2025-08-15 06:40:01');

-- Bảng hình ảnh bài đăng
DROP TABLE IF EXISTS HinhAnhBaiDang;
CREATE TABLE HinhAnhBaiDang (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    BaiDangID INT NOT NULL,
    DuongDan VARCHAR(255) NOT NULL,
    ThuTu INT DEFAULT 0,
    MoTa VARCHAR(255),
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO `HinhAnhBaiDang` (`ID`, `BaiDangID`, `DuongDan`, `ThuTu`, `MoTa`, `NgayTao`) VALUES
(1, 1, '/assets/uploads/posts/2025/08/70_kenhnuocden_hinhk_1755233996_TGYwH91a.png', 0, NULL, '2025-08-15 04:59:56'),
(2, 1, '/assets/uploads/posts/2025/08/70_kenhnuocden_hinhc_1755233996_lGOi5lG8.png', 1, NULL, '2025-08-15 04:59:56'),
(3, 1, '/assets/uploads/posts/2025/08/70_kenhnuocden_hinhb_1755233996_vdtWmuEU.png', 2, NULL, '2025-08-15 04:59:56'),
(4, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh13_1755233996_SNTuOje3.png', 3, NULL, '2025-08-15 04:59:56'),
(5, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh19_1755233996_UT9rZGj3.png', 4, NULL, '2025-08-15 04:59:56'),
(6, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh12_1755233996_w8w3u9JN.png', 5, NULL, '2025-08-15 04:59:56'),
(7, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh115_1755233996_JysJ0VYk.png', 6, NULL, '2025-08-15 04:59:56'),
(8, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh114_1755233996_beitci5a.png', 7, NULL, '2025-08-15 04:59:56'),
(9, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh11_1755233996_n3V77Dud.png', 8, NULL, '2025-08-15 04:59:56'),
(10, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh9_1755233996_WlNYzTA1.png', 9, NULL, '2025-08-15 04:59:56'),
(11, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh8_1755233996_lUW3MqoM.png', 10, NULL, '2025-08-15 04:59:56'),
(12, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh7_1755233996_DXz4CBnX.png', 11, NULL, '2025-08-15 04:59:56'),
(13, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh4_1755233996_Dr65azzd.png', 12, NULL, '2025-08-15 04:59:56'),
(14, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh3_1755233996_95pMGrKe.png', 13, NULL, '2025-08-15 04:59:56'),
(15, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh2_1755233996_1HGOdZWC.png', 14, NULL, '2025-08-15 04:59:56'),
(16, 1, '/assets/uploads/posts/2025/08/70kenhnuocdenhinh1_1755233996_ApxgU2JU.png', 15, NULL, '2025-08-15 04:59:56'),
(17, 2, '/assets/uploads/posts/2025/08/395400237_693986446018327_5398445775215642801_n_1755234406_mAZvwDO8.jpg', 0, NULL, '2025-08-15 05:06:46');

-- Bảng yêu thích
DROP TABLE IF EXISTS YeuThich;
CREATE TABLE YeuThich (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    BaiDangID INT NOT NULL,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_favorite (KhachHangID, BaiDangID),

    -- Enhanced Validation Indexes
    INDEX idx_yeuthich_khachhang (KhachHangID),
    INDEX idx_yeuthich_baidang (BaiDangID),
    INDEX idx_yeuthich_ngaytao (NgayTao)
);

-- Bảng liên hệ thuê trọ
DROP TABLE IF EXISTS LienHeThueTro;
CREATE TABLE LienHeThueTro (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    BaiDangID INT NOT NULL,
    NguoiThueID INT NOT NULL,
    NguoiChoThueID INT NOT NULL,
    TenNguoiThue VARCHAR(100) NOT NULL,
    SDTNguoiThue VARCHAR(15) NOT NULL,
    EmailNguoiThue VARCHAR(100),
    TinNhan TEXT,
    TrangThai TINYINT DEFAULT 0, -- 0:mới, 1:đã xem, 2:đã liên hệ, 3:đã thuê, 4:hủy
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiThueID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiChoThueID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE
);



-- Bảng cấu hình hệ thống (Optimized for Enhanced Validation)
DROP TABLE IF EXISTS CauHinh;
CREATE TABLE CauHinh (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenCH VARCHAR(100) NOT NULL UNIQUE,
    GiaTri VARCHAR(1000) COMMENT 'Optimized from TEXT for better performance',
    MoTa TEXT,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Enhanced Validation Index
    INDEX idx_cauhinh_tench (TenCH)
);

-- Bảng thông báo
DROP TABLE IF EXISTS ThongBao;
CREATE TABLE ThongBao (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    NguoiNhanID INT NOT NULL,
    TieuDe VARCHAR(255) NOT NULL,
    NoiDung TEXT,
    LoaiTB TINYINT DEFAULT 1, -- 1:thông thường, 2:quan trọng, 3:khẩn cấp
    DaDoc TINYINT DEFAULT 0,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (NguoiNhanID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,

    -- Enhanced Validation Indexes
    INDEX idx_thongbao_nguoinhan (NguoiNhanID),
    INDEX idx_thongbao_dadoc (DaDoc),
    INDEX idx_thongbao_loaitb (LoaiTB),
    INDEX idx_thongbao_ngaytao (NgayTao)
);

-- Insert dữ liệu mẫu vai trò
INSERT INTO VaiTro (TenVT, MoTa, CapDo) VALUES
('User', 'khách hàng thông thường', 1),
('Seller', 'Người cho thuê trọ', 2),
('Supporter', 'Nhân viên hỗ trợ', 3),
('Moderator', 'Kiểm duyệt viên', 4),
('Administrator', 'Quản trị viên', 5);

-- Insert user mẫu
INSERT INTO KhachHang (TenDN, Email, MatKhau, HoTen, VaiTroID, TrangThai, email_verified_at) VALUES
('phamhuy1710', 'phamhuy1710@example.com', '$2y$10$OTvSKBKwh6GsBsk5hH0.Qu7tTqSus1c/aZm3tVFlrgzqoPjccM3.i', 'Phạm Huy', 5, 1, NOW());

-- Insert dữ liệu mẫu danh mục
INSERT INTO DanhMuc (TenDM, MoTa, ThuTu) VALUES
('Phòng trọ', 'Phòng trọ sinh viên, công nhân', 1),
('Căn hộ mini', 'Căn hộ mini, studio', 2),
('Nhà nguyên căn', 'Nhà nguyên căn cho thuê', 3),
('Ký túc xá', 'Ký túc xá, nhà trọ tập thể', 4),
('Homestay', 'Homestay, nhà nghỉ ngắn hạn', 5);

-- Bảng báo cáo
DROP TABLE IF EXISTS BaoCao;
CREATE TABLE BaoCao (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    BaiDangID INT NOT NULL,
    NguoiBaoCaoID INT NOT NULL,
    LoaiBC TINYINT NOT NULL, -- 1:spam, 2:lừa đảo, 3:nội dung không phù hợp, 4:khác
    NoiDung TEXT,
    TrangThai TINYINT DEFAULT 0, -- 0:chờ xử lý, 1:đã xử lý, 2:bỏ qua
    NguoiXuLy INT NULL,
    NgayXuLy TIMESTAMP NULL,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiBaoCaoID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiXuLy) REFERENCES KhachHang(ID) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Bảng đánh giá
DROP TABLE IF EXISTS DanhGia;
CREATE TABLE DanhGia (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    BaiDangID INT NOT NULL,
    NguoiDanhGiaID INT NOT NULL,
    DiemSo TINYINT NOT NULL CHECK (DiemSo >= 1 AND DiemSo <= 5),
    BinhLuan TEXT,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiDanhGiaID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_rating (BaiDangID, NguoiDanhGiaID)
);

-- Bảng lịch sử đăng nhập
DROP TABLE IF EXISTS LichSuDangNhap;
CREATE TABLE LichSuDangNhap (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    DiaChiIP VARCHAR(45),
    UserAgent TEXT,
    TrangThai TINYINT DEFAULT 1, -- 1:thành công, 0:thất bại
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng hoạt động người dùng
DROP TABLE IF EXISTS HoatDong;
CREATE TABLE HoatDong (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    LoaiHoatDong VARCHAR(50) NOT NULL, -- login, logout, create_post, edit_post, etc.
    MoTa TEXT,
    DuLieu JSON, -- Dữ liệu bổ sung (post_id, etc.)
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID) ON DELETE CASCADE,

    -- Enhanced Validation Indexes
    INDEX idx_hoatdong_khachhang (KhachHangID),
    INDEX idx_hoatdong_loai (LoaiHoatDong),
    INDEX idx_hoatdong_ngaytao (NgayTao)
);

-- Bảng liên hệ thuê trọ
DROP TABLE IF EXISTS LienHe;
CREATE TABLE LienHe (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    BaiDangID INT NOT NULL,
    NguoiLienHeID INT NOT NULL, -- Người muốn thuê
    ChuNhaID INT NOT NULL, -- Chủ nhà (người đăng bài)
    HoTen VARCHAR(100) NOT NULL,
    SDT VARCHAR(15) NOT NULL,
    Email VARCHAR(100),
    TinNhan TEXT,
    TrangThai ENUM('pending', 'contacted', 'interested', 'deal', 'cancelled') DEFAULT 'pending',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiLienHeID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ChuNhaID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng giao dịch
DROP TABLE IF EXISTS GiaoDich;
CREATE TABLE GiaoDich (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    LienHeID INT NOT NULL,
    BaiDangID INT NOT NULL,
    NguoiThueID INT NOT NULL,
    ChuNhaID INT NOT NULL,
    GiaThue DECIMAL(15,2) NOT NULL,
    TienCoc DECIMAL(15,2),
    ThoiHanThue INT, -- Số tháng
    NgayBatDau DATE,
    NgayKetThuc DATE,
    TrangThai ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    GhiChu TEXT,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (LienHeID) REFERENCES LienHe(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (NguoiThueID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ChuNhaID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE,

    -- Enhanced Validation Indexes
    INDEX idx_giaodich_trangthai (TrangThai),
    INDEX idx_giaodich_baidang (BaiDangID),
    INDEX idx_giaodich_nguoithue (NguoiThueID),
    INDEX idx_giaodich_chunha (ChuNhaID),
    INDEX idx_giaodich_ngaytao (NgayTao)
);

-- Bảng hoa hồng
DROP TABLE IF EXISTS HoaHong;
CREATE TABLE HoaHong (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    GiaoDichID INT NOT NULL,
    AdminID INT NULL,
    SoTien DECIMAL(15,2) NOT NULL, -- 5% của giá thuê
    TrangThai ENUM('pending', 'paid') DEFAULT 'pending',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayThanhToan TIMESTAMP NULL,
    FOREIGN KEY (GiaoDichID) REFERENCES GiaoDich(ID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (AdminID) REFERENCES KhachHang(ID) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Bảng token reset password
DROP TABLE IF EXISTS TokenResetPassword;
CREATE TABLE TokenResetPassword (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    Token VARCHAR(255) NOT NULL,
    NgayHetHan TIMESTAMP NOT NULL,
    DaSuDung TINYINT DEFAULT 0,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng thống kê
DROP TABLE IF EXISTS ThongKe;
CREATE TABLE ThongKe (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    Ngay DATE NOT NULL,
    SoBaiDangMoi INT DEFAULT 0,
    SoKhachHangMoi INT DEFAULT 0,
    SoGiaoDichMoi INT DEFAULT 0,
    DoanhThuHoaHong DECIMAL(15,0) DEFAULT 0,
    LuotTruyCap INT DEFAULT 0,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (Ngay)
);

-- Insert cấu hình hệ thống
INSERT INTO CauHinh (TenCH, GiaTri, MoTa) VALUES
('ty_le_hoa_hong', '5.0', 'Tỷ lệ hoa hồng mặc định (%)'),
('so_bai_dang_moi_trang', '20', 'Số bài đăng hiển thị mỗi trang'),
('thoi_gian_hieu_luc_bai_dang', '30', 'Thời gian hiệu lực bài đăng (ngày)'),
('email_admin', 'admin@tro.loading99.site', 'Email quản trị viên'),
('sdt_hotline', '0387368890', 'Số điện thoại hotline'),
('app_version', '1.1.4', 'Phiên bản ứng dụng hiện tại'),
('version_history', '[{"version":"1.1.4","date":"2025-08-13 09:32:00","previous_version":null,"description":"Phiên bản hiện tại","is_custom_description":false},{"version":"1.0.0","date":"2025-01-01 00:00:00","previous_version":null,"description":"Phiên bản khởi tạo","is_custom_description":false}]', 'Lịch sử phiên bản (JSON)'),
('ten_website', 'Trọ 365', 'Tên website'),
('mo_ta_website', 'Website đăng tin cho thuê nhà trọ, phòng trọ giá rẻ, phòng trọ sinh viên, nhà nguyên căn, căn hộ, ở ghép nhanh và hiệu quả trên toàn quốc', 'Mô tả website'),
('dia_chi_cong_ty', 'Đà Nẵng, Việt Nam', 'Địa chỉ công ty'),
('email_lien_he', 'contact@tro.loading99.site', 'Email liên hệ'),
('facebook_url', 'https://facebook.com/tro365', 'Link Facebook'),
('zalo_url', 'https://zalo.me/tro365', 'Link Zalo'),
-- Email SMTP Configuration
('mail_driver', 'smtp', 'Driver gửi email (smtp/mail)'),
('mail_host', 'smtp.gmail.com', 'SMTP Host'),
('mail_port', '587', 'SMTP Port'),
('mail_encryption', 'tls', 'SMTP Encryption (tls/ssl)'),
('mail_username', 'zunamc40@gmail.com', 'SMTP Username'),
('mail_password', 'sszbuwnfjnfqvkay', 'SMTP Password'),
('mail_from_address', 'noreply@tro.loading99.site', 'Email gửi đi'),
('mail_from_name', 'Trọ 365', 'Tên người gửi email'),
-- System Settings
('max_upload_size', '5', 'Kích thước upload tối đa (MB)'),
('allowed_file_types', 'jpg,jpeg,png', 'Loại file được phép upload'),
('enable_registration', '1', 'Cho phép đăng ký người dùng (1=có, 0=không)'),
('enable_seller_registration', '1', 'Cho phép đăng ký seller (1=có, 0=không)'),
('require_email_verification', '1', 'Yêu cầu xác thực email (1=có, 0=không)'),
('enable_maintenance_mode', '0', 'Bật chế độ bảo trì (1=có, 0=không)'),
('app_debug', '1', 'Bật chế độ debug (1=có, 0=không)'),
-- SEO Settings
('meta_keywords', 'thuê trọ, phòng trọ, nhà trọ, tìm trọ, cho thuê phòng', 'Meta keywords cho SEO'),
('meta_description', 'Website đăng tin cho thuê nhà trọ, phòng trọ giá rẻ, phòng trọ sinh viên, nhà nguyên căn, căn hộ, ở ghép nhanh và hiệu quả trên toàn quốc', 'Meta description cho SEO'),
('google_analytics_id', '', 'Google Analytics ID (G-XXXXXXXXXX)'),
('google_search_console', '', 'Google Search Console verification code'),
('facebook_pixel_id', '', 'Facebook Pixel ID để tracking'),
('enable_sitemap', '1', 'Bật sitemap tự động (1=có, 0=không)'),
('enable_robots_txt', '1', 'Bật robots.txt tự động (1=có, 0=không)'),
-- TinyMCE Configuration
('tinymce_api_key', 'wzm4vulhgy0dogieo76nsf1uaseax2w42kr7sk6ff7c78avm', 'TinyMCE API Key cho Rich Text Editor'),
-- Room Limit Configuration
('max_rooms_per_post', '50', 'Số phòng tối đa có thể đăng trong một bài đăng');

-- Note: Indexes are now defined within table definitions for better organization
-- All Enhanced Validation indexes are included in CREATE TABLE statements



-- Insert dữ liệu mẫu thông báo
INSERT INTO ThongBao (NguoiNhanID, TieuDe, NoiDung, LoaiTB, DaDoc, NgayTao) VALUES
(1, 'Chào mừng bạn đến với Trọ 365!', 'Cảm ơn bạn đã đăng ký tài khoản. Hãy khám phá các tính năng tuyệt vời của chúng tôi.', 1, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'Cập nhật bảo mật quan trọng', 'Chúng tôi đã cập nhật chính sách bảo mật. Vui lòng xem lại để đảm bảo tài khoản của bạn được bảo vệ tốt nhất.', 2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Khuyến mãi đặc biệt cho thành viên mới', 'Giảm 20% phí đăng tin trong tháng đầu tiên. Sử dụng mã: WELCOME20', 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'Bảo trì hệ thống', 'Hệ thống sẽ được bảo trì vào 2:00 AM ngày mai. Thời gian dự kiến: 30 phút.', 3, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(1, 'Tính năng mới: Tìm kiếm bằng giọng nói', 'Bạn có thể sử dụng tính năng tìm kiếm bằng giọng nói trên ứng dụng di động của chúng tôi.', 1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
