-- Cơ sở dữ liệu Trọ 365
-- Tạo database
CREATE DATABASE IF NOT EXISTS Tro365 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Tro365;

-- Bảng vai trò
CREATE TABLE VaiTro (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenVT VARCHAR(50) NOT NULL UNIQUE,
    MoTa TEXT,
    CapDo INT NOT NULL DEFAULT 1, -- 1:user, 2:seller, 3:supporter, 4:moderator, 5:admin
    TrangThai TINYINT DEFAULT 1, -- 1:active, 0:inactive
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng tỉnh thành
CREATE TABLE TinhThanh (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenTT VARCHAR(100) NOT NULL,
    MaTT VARCHAR(10) UNIQUE,
    TrangThai TINYINT DEFAULT 1
);

-- Bảng quận huyện
CREATE TABLE QuanHuyen (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenQH VARCHAR(100) NOT NULL,
    MaQH VARCHAR(10) UNIQUE,
    TinhThanhID INT,
    TrangThai TINYINT DEFAULT 1,
    FOREIGN KEY (TinhThanhID) REFERENCES TinhThanh(ID)
);

-- Bảng xã phường
CREATE TABLE XaPhuong (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenXP VARCHAR(100) NOT NULL,
    MaXP VARCHAR(10) UNIQUE,
    QuanHuyenID INT,
    TrangThai TINYINT DEFAULT 1,
    FOREIGN KEY (QuanHuyenID) REFERENCES QuanHuyen(ID)
);

-- Bảng khách hàng
CREATE TABLE KhachHang (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenDN VARCHAR(50) NOT NULL UNIQUE, -- Tên đăng nhập
    Email VARCHAR(100) NOT NULL UNIQUE,
    MatKhau VARCHAR(255) NOT NULL,
    HoTen VARCHAR(100) NOT NULL,
    SDT VARCHAR(15),
    DiaChi TEXT,
    TinhThanhID INT,
    QuanHuyenID INT,
    XaPhuongID INT,
    VaiTroID INT DEFAULT 1,
    AnhDaiDien VARCHAR(255),
    TrangThai TINYINT DEFAULT 1, -- 1:active, 0:inactive, 2:banned
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    LanDangNhapCuoi TIMESTAMP NULL,
    FOREIGN KEY (VaiTroID) REFERENCES VaiTro(ID),
    FOREIGN KEY (TinhThanhID) REFERENCES TinhThanh(ID),
    FOREIGN KEY (QuanHuyenID) REFERENCES QuanHuyen(ID),
    FOREIGN KEY (XaPhuongID) REFERENCES XaPhuong(ID)
);

-- Bảng đăng ký seller
CREATE TABLE DangKySeller (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    HoTenChuTro VARCHAR(100) NOT NULL,
    CCCD VARCHAR(20) NOT NULL,
    AnhCCCDTruoc VARCHAR(255),
    AnhCCCDSau VARCHAR(255),
    GiayPhepKD VARCHAR(255), -- Giấy phép kinh doanh
    DiaChi TEXT NOT NULL,
    TinhThanhID INT,
    QuanHuyenID INT,
    XaPhuongID INT,
    SDTLienHe VARCHAR(15) NOT NULL,
    EmailLienHe VARCHAR(100),
    LyDoMuonBan TEXT,
    TrangThai TINYINT DEFAULT 0, -- 0:chờ duyệt, 1:đã duyệt, 2:từ chối
    NguoiDuyet INT NULL,
    NgayDuyet TIMESTAMP NULL,
    LyDoTuChoi TEXT,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID),
    FOREIGN KEY (NguoiDuyet) REFERENCES KhachHang(ID),
    FOREIGN KEY (TinhThanhID) REFERENCES TinhThanh(ID),
    FOREIGN KEY (QuanHuyenID) REFERENCES QuanHuyen(ID),
    FOREIGN KEY (XaPhuongID) REFERENCES XaPhuong(ID)
);

-- Bảng danh mục
CREATE TABLE DanhMuc (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenDM VARCHAR(100) NOT NULL,
    MoTa TEXT,
    ThuTu INT DEFAULT 0,
    TrangThai TINYINT DEFAULT 1,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng bài đăng
CREATE TABLE BaiDang (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TieuDe VARCHAR(255) NOT NULL,
    MoTa TEXT,
    NoiDung LONGTEXT,
    NguoiDangID INT NOT NULL,
    DanhMucID INT,
    DiaChi TEXT NOT NULL,
    TinhThanhID INT,
    QuanHuyenID INT,
    XaPhuongID INT,
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
    FOREIGN KEY (NguoiDangID) REFERENCES KhachHang(ID),
    FOREIGN KEY (DanhMucID) REFERENCES DanhMuc(ID),
    FOREIGN KEY (NguoiDuyet) REFERENCES KhachHang(ID),
    FOREIGN KEY (TinhThanhID) REFERENCES TinhThanh(ID),
    FOREIGN KEY (QuanHuyenID) REFERENCES QuanHuyen(ID),
    FOREIGN KEY (XaPhuongID) REFERENCES XaPhuong(ID)
);

-- Bảng hình ảnh bài đăng
CREATE TABLE HinhAnhBaiDang (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    BaiDangID INT NOT NULL,
    DuongDan VARCHAR(255) NOT NULL,
    ThuTu INT DEFAULT 0,
    MoTa VARCHAR(255),
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID) ON DELETE CASCADE
);

-- Bảng yêu thích
CREATE TABLE YeuThich (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    BaiDangID INT NOT NULL,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID),
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID),
    UNIQUE KEY unique_favorite (KhachHangID, BaiDangID)
);

-- Bảng liên hệ thuê trọ
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
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID),
    FOREIGN KEY (NguoiThueID) REFERENCES KhachHang(ID),
    FOREIGN KEY (NguoiChoThueID) REFERENCES KhachHang(ID)
);

-- Bảng giao dịch
CREATE TABLE GiaoDich (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    MaGD VARCHAR(50) UNIQUE NOT NULL,
    BaiDangID INT NOT NULL,
    NguoiThueID INT NOT NULL,
    NguoiChoThueID INT NOT NULL,
    SoTien DECIMAL(15,0) NOT NULL,
    PhiHoaHong DECIMAL(15,0) NOT NULL,
    TyLeHoaHong DECIMAL(5,2) DEFAULT 5.00, -- %
    NgayBatDau DATE NOT NULL,
    NgayKetThuc DATE NOT NULL,
    TrangThai TINYINT DEFAULT 0, -- 0:chờ xác nhận, 1:đã xác nhận, 2:đã thanh toán, 3:hoàn thành, 4:hủy
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID),
    FOREIGN KEY (NguoiThueID) REFERENCES KhachHang(ID),
    FOREIGN KEY (NguoiChoThueID) REFERENCES KhachHang(ID)
);

-- Bảng hoa hồng
CREATE TABLE HoaHong (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    GiaoDichID INT NOT NULL,
    NguoiNhanID INT NOT NULL, -- Admin nhận hoa hồng
    SoTien DECIMAL(15,0) NOT NULL,
    TyLe DECIMAL(5,2) NOT NULL,
    TrangThai TINYINT DEFAULT 0, -- 0:chưa thanh toán, 1:đã thanh toán
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayThanhToan TIMESTAMP NULL,
    FOREIGN KEY (GiaoDichID) REFERENCES GiaoDich(ID),
    FOREIGN KEY (NguoiNhanID) REFERENCES KhachHang(ID)
);

-- Bảng cấu hình hệ thống
CREATE TABLE CauHinh (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TenCH VARCHAR(100) NOT NULL UNIQUE,
    GiaTri TEXT,
    MoTa TEXT,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng thông báo
CREATE TABLE ThongBao (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    NguoiNhanID INT NOT NULL,
    TieuDe VARCHAR(255) NOT NULL,
    NoiDung TEXT,
    LoaiTB TINYINT DEFAULT 1, -- 1:thông thường, 2:quan trọng, 3:khẩn cấp
    DaDoc TINYINT DEFAULT 0,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (NguoiNhanID) REFERENCES KhachHang(ID)
);

-- Insert dữ liệu mẫu vai trò
INSERT INTO VaiTro (TenVT, MoTa, CapDo) VALUES
('User', 'khách hàng thông thường', 1),
('Seller', 'Người cho thuê trọ', 2),
('Supporter', 'Nhân viên hỗ trợ', 3),
('Moderator', 'Kiểm duyệt viên', 4),
('Administrator', 'Quản trị viên', 5);

-- Insert dữ liệu mẫu danh mục
INSERT INTO DanhMuc (TenDM, MoTa, ThuTu) VALUES
('Phòng trọ', 'Phòng trọ sinh viên, công nhân', 1),
('Căn hộ mini', 'Căn hộ mini, studio', 2),
('Nhà nguyên căn', 'Nhà nguyên căn cho thuê', 3),
('Ký túc xá', 'Ký túc xá, nhà trọ tập thể', 4),
('Homestay', 'Homestay, nhà nghỉ ngắn hạn', 5);

-- Bảng báo cáo
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
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID),
    FOREIGN KEY (NguoiBaoCaoID) REFERENCES KhachHang(ID),
    FOREIGN KEY (NguoiXuLy) REFERENCES KhachHang(ID)
);

-- Bảng đánh giá
CREATE TABLE DanhGia (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    BaiDangID INT NOT NULL,
    NguoiDanhGiaID INT NOT NULL,
    DiemSo TINYINT NOT NULL CHECK (DiemSo >= 1 AND DiemSo <= 5),
    BinhLuan TEXT,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BaiDangID) REFERENCES BaiDang(ID),
    FOREIGN KEY (NguoiDanhGiaID) REFERENCES KhachHang(ID),
    UNIQUE KEY unique_rating (BaiDangID, NguoiDanhGiaID)
);

-- Bảng lịch sử đăng nhập
CREATE TABLE LichSuDangNhap (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    DiaChiIP VARCHAR(45),
    UserAgent TEXT,
    TrangThai TINYINT DEFAULT 1, -- 1:thành công, 0:thất bại
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID)
);

-- Bảng token reset password
CREATE TABLE TokenResetPassword (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    KhachHangID INT NOT NULL,
    Token VARCHAR(255) NOT NULL,
    NgayHetHan TIMESTAMP NOT NULL,
    DaSuDung TINYINT DEFAULT 0,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KhachHangID) REFERENCES KhachHang(ID)
);

-- Bảng thống kê
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
('email_admin', 'admin@tro365.com', 'Email quản trị viên'),
('sdt_hotline', '1900xxxx', 'Số điện thoại hotline'),
('ten_website', 'Trọ 365', 'Tên website'),
('mo_ta_website', 'Website thuê trọ uy tín số 1 Việt Nam', 'Mô tả website'),
('dia_chi_cong_ty', 'Hà Nội, Việt Nam', 'Địa chỉ công ty'),
('email_lien_he', 'contact@tro365.com', 'Email liên hệ'),
('facebook_url', 'https://facebook.com/tro365', 'Link Facebook'),
('zalo_url', 'https://zalo.me/tro365', 'Link Zalo');

-- Tạo index để tối ưu hiệu suất
CREATE INDEX idx_KhachHang_email ON KhachHang(Email);
CREATE INDEX idx_KhachHang_TenDN ON KhachHang(TenDN);
CREATE INDEX idx_baidang_trangthai ON BaiDang(TrangThai);
CREATE INDEX idx_baidang_tinhthanh ON BaiDang(TinhThanhID);
CREATE INDEX idx_baidang_ngaytao ON BaiDang(NgayTao);
CREATE INDEX idx_baidang_gia ON BaiDang(Gia);
CREATE INDEX idx_giaodich_trangthai ON GiaoDich(TrangThai);
CREATE INDEX idx_thongbao_nguoinhan ON ThongBao(NguoiNhanID, DaDoc);
