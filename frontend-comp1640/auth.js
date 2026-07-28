document.addEventListener('DOMContentLoaded', () => {
    
    // 1. KIỂM TRA ĐĂNG NHẬP (Bảo vệ trang)
    const token = localStorage.getItem('userToken');
    const userRole = localStorage.getItem('userRole'); // Kéo thêm Thẻ chức vụ ra kiểm tra
    const currentPage = window.location.pathname;

    // Nếu chưa đăng nhập mà lọt vào trang khác -> Đuổi về login
    if (!token && !currentPage.includes('login.html')) {
        alert("Bạn cần đăng nhập để truy cập hệ thống!");
        window.location.href = 'login.html';
        return; 
    }

    // Nếu đã đăng nhập mà lọt vào lại trang login -> Đẩy thẳng vào trang chủ
    if (token && currentPage.includes('login.html')) {
        window.location.href = 'index.html';
        return;
    }

    // ==========================================
    // 🔥 PHẦN BẢO VỆ RIÊNG CHO TRANG ADMIN
    // ==========================================
    if (currentPage.includes('admin-dashboard.html')) {
        // Đổi thành chữ hoa và có dấu cách
        if (userRole !== 'Admin' && userRole !== 'QA Manager') {
            alert("⛔ CẢNH BÁO: Bạn không có quyền truy cập khu vực Quản trị!");
            window.location.href = 'index.html';
            return;
        }
    }

    // ==========================================
    // 2. LOGIC NÚT ĐĂNG XUẤT 
    // ==========================================
    const btnLogout = document.getElementById('btnLogout');
    
    // Kiểm tra xem trang hiện tại có nút Đăng xuất không thì mới chạy code
    if (btnLogout) {
        btnLogout.addEventListener('click', () => {
            // Hỏi lại cho chắc chắn (Tránh trường hợp user bấm nhầm)
            const confirmLogout = confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?");
            
            if (confirmLogout) {
                localStorage.removeItem('userToken'); // Xóa thẻ Token 
                localStorage.removeItem('userRole');  // Xóa luôn thẻ Chức vụ cho sạch sẽ
                window.location.href = 'login.html';  // Chuyển hướng về trang Đăng nhập
            }
        });
    }
});