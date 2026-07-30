document.addEventListener('DOMContentLoaded', () => {
    
    // 1. KIỂM TRA ĐĂNG NHẬP (Bảo vệ trang)
    const token = localStorage.getItem('userToken');
    const userRole = localStorage.getItem('userRole'); 
    const currentPage = window.location.pathname;

    if (!token && !currentPage.includes('login.html')) {
        alert("Bạn cần đăng nhập để truy cập hệ thống!");
        window.location.href = 'login.html';
        return; 
    }

    if (token && currentPage.includes('login.html')) {
        window.location.href = 'index.html';
        return;
    }

    // ==========================================
    //  PHẦN BẢO VỆ RIÊNG CHO TRANG ADMIN
    // ==========================================
    if (currentPage.includes('admin-dashboard.html')) {
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
    
    if (btnLogout) {
        btnLogout.addEventListener('click', () => {
            const confirmLogout = confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?");
            
            if (confirmLogout) {
                localStorage.removeItem('userToken');
                localStorage.removeItem('userRole'); 
                window.location.href = 'login.html'; 
            }
        });
    }
});