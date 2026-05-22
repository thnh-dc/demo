document.addEventListener('DOMContentLoaded', function () {

    //hiện thông báo
    function showNotification(message, type = 'error') {
        const oldNoti = document.getElementById('noti-container');
        if (oldNoti) {
            oldNoti.remove();
        }
        const container = document.createElement('div');
        container.id = 'noti-container';
        container.className = 'noti-container';
        const box = document.createElement('div');
        box.className = 'noti-box ' + type;
        const icon = document.createElement('div');
        icon.className = 'noti-icon';
        if (type === 'success') {
            icon.textContent = '✓';
        } else if (type === 'error') {
            icon.textContent = '✕';
        } else {
            icon.textContent = 'i';
        }
        const content = document.createElement('div');
        content.className = 'noti-content';
        content.textContent = message;
        box.appendChild(icon);
        box.appendChild(content);
        container.appendChild(box);
        document.body.appendChild(container);
        setTimeout(function () {
            container.classList.add('noti-fade-out');

            setTimeout(function () {
                container.remove();
            }, 500);
        }, 10000);
    }
    // sidebar
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            if (submenu) {
                submenu.classList.toggle('show');
            }
        });
    });
    //render biểu đồ
    const ctx = document.getElementById('revenueChart');

    if (
        ctx &&
        typeof Chart !== 'undefined' &&
        typeof revenueLabels !== 'undefined' &&
        typeof revenueData !== 'undefined'
    ) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Doanh thu theo tháng',
                    data: revenueData,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        ticks: {
                            callback: function (value) {
                                return value.toLocaleString('vi-VN') + '₫';
                            }
                        }
                    }
                }
            }
        });
    }
    //open menu status
    document.querySelectorAll('.btn-action').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.action-menu').forEach(function (menu) {
                menu.style.display = 'none';
            });
            const menu = this.nextElementSibling;
            if (menu) {
                menu.style.display = 'block';
            }
        });
    });
    // update status
    document.querySelectorAll('.action-menu button').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const status = this.getAttribute('data-status');
            const actionBox = this.closest('.action-buttons');
            if (!actionBox) {
                showNotification('Không tìm thấy vùng thao tác đơn hàng.', 'error');
                return;
            }
            const actionBtn = actionBox.querySelector('.btn-action');
            if (!actionBtn) {
                showNotification('Không tìm thấy mã đơn hàng.', 'error');
                return;
            }
            const orderId = actionBtn.dataset.id;
            fetch('/FD-Tech/admin/action_list_order/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + encodeURIComponent(orderId) + '&status=' + encodeURIComponent(status)
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 1200);
                    } else {
                        showNotification(data.message || 'Lỗi cập nhật trạng thái đơn hàng.', 'error');
                    }
                })
                .catch(function () {
                    showNotification('Lỗi kết nối, không thể cập nhật đơn hàng.', 'error');
                });
        });
    });
    //click ngoài=> đóng menu
    document.addEventListener('click', function () {
        document.querySelectorAll('.action-menu').forEach(function (menu) {
            menu.style.display = 'none';
        });
    });
    // xem chi tiết đơn hàng
    document.querySelectorAll('.btn-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const orderId = this.dataset.id;
            const row = document.getElementById('detail-' + orderId);
            if (!row) {
                return;
            }
            const content = row.querySelector('.order-detail-content');

            if (!content) {
                return;
            }
            if (row.style.display === 'none' || row.style.display === '') {
                row.style.display = 'table-row';

                if (!row.dataset.loaded) {
                    fetch('/FD-Tech/admin/action_list_order/get_order_detail.php?order_id=' + encodeURIComponent(orderId))
                        .then(function (res) {
                            return res.text();
                        })
                        .then(function (data) {
                            content.innerHTML = data;
                            row.dataset.loaded = true;
                        })
                        .catch(function () {
                            content.innerHTML = 'Không thể tải chi tiết đơn hàng.';
                        });
                }
            } else {
                row.style.display = 'none';
            }
        });
    });
});