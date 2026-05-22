// assets/js/navbar.js
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('menuToggle');
    const dropdown = document.getElementById('categoryDropdown');

    if (btn && dropdown) {
        // Log để kiểm tra trong Console (F12) xem code có chạy tới đây không
        console.log("Menu JS đã sẵn sàng!");

        btn.addEventListener('click', function(e) {
            dropdown.classList.toggle('active');
            console.log("Đã bấm nút danh mục");
            e.stopPropagation();
        });

        document.addEventListener('click', function(e) {
            // Nếu click ra ngoài thì đóng menu
            if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    } else {
        console.error("Không tìm thấy ID menuToggle hoặc categoryDropdown!");
    }
});