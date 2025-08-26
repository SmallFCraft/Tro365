---
trigger: always_on
alwaysApply: true
---
# Project Description
- Website thuê trọ PHP 8.2 + MySQL với hệ thống vai trò 5 cấp (user>seller>supporter>moderator>admin), seller cần đăng ký với admin để đăng bài, có hệ thống hoa hồng 5% cho admin khi giao dịch thành công.
- Client-side development preferences: modern 2025-2026 design trends, mobile-first responsive approach (320px-768px priority), smooth animations/transitions, comprehensive search/filter functionality, wishlist and comparison features, map integration, and maintain PHP backend structure with existing authentication system and API endpoints.
- Thiết kế giao diện glass morphism UI
- Project uses mobile-first responsive design (320px-768px priority), glassmorphism UI design system, CSS Grid/Flexbox for layouts, and requires reusable components that maintain consistency across all client pages.
- Post detail pages should use glassmorphism UI design system, mobile-first responsive approach (320px-768px priority), reusable CSS classes across client pages, and enhanced image gallery with lightbox/zoom features while maintaining dark/light mode compatibility.
- Tro365 website development should prioritize mobile-first responsive design (320px-768px priority), consistently use glass morphism UI design system, create reusable CSS components for pages in /pages/client/, and follow modern 2025-2026 design trends with Light/Dark mode compatibility.
- Tro365 project requires Glass Morphism UI/UX design system with mobile-first responsive design (320px-768px priority), reusable CSS components in /assets/css/client/, and full Light/Dark mode compatibility using CSS variables for all client pages.
- Luôn luôn đảm bảo Glass Morphism cards, settings-item và settings-card có border rõ ràng trong Light Mode với opacity cao (0.7-0.95) để người dùng có thể nhìn thấy và phân biệt các thẻ một cách rõ ràng, không được sử dụng opacity thấp.

# Development Practices
- Luôn sử dụng MCP có sẵn (Playwright, Thinking và Exa Search) để kiểm tra liên tục trong quá trình phát triển và xác minh.
- Luôn luôn kiểm tra toàn bộ thư mục codebase, function, code, biến giá trị, hàm,... sẽ có cái nào dư thừa không sử dụng và trùng lặp không? Đảm bảo mọi thứ đều có sự liên kết đồng bộ với nhau chặt chẽ.
- Luôn luôn đọc @docs.md để hiểu rõ cấu trúc thư mục. Tài khoản admin (user: phamhuy1710, pass: Gunny123456@). Website chạy bằng localhost:8000
`