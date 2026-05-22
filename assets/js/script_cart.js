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
    }, 7000);
}
function formatMoney(value) {
    return Number(value).toLocaleString('vi-VN') + ' vn₫';
}
function updateCartTotal() {
    let total = 0;
    document.querySelectorAll('.cart-item').forEach(function (row) {
        const priceElement = row.querySelector('.item-price');
        const qtyElement = row.querySelector('.qty-input');

        if (!priceElement || !qtyElement) {
            return;
        }
        const price = Number(priceElement.dataset.price || 0);
        const quantity = Number(qtyElement.value || 0);

        total += price * quantity;
    });
    const totalElement = document.querySelector('.price-highlight');

    if (totalElement) {
        totalElement.textContent = formatMoney(total);
    }
}
function updateButtonState(row) {
    const qtyInput = row.querySelector('.qty-input');
    const plusBtn = row.querySelector('.btn-plus');
    const minusBtn = row.querySelector('.btn-minus');
    if (!qtyInput) {
        return;
    }
    const quantity = Number(qtyInput.value || 0);
    const stock = Number(qtyInput.dataset.stock || 0);
    if (plusBtn) {
        plusBtn.disabled = quantity >= stock;
    }
    if (minusBtn) {
        minusBtn.disabled = quantity <= 1;
    }
}
// Xóa sản phẩm khỏi giỏ hàng
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete');
    if (!btn) {
        return;
    }
    const id = btn.dataset.id;
    const row = btn.closest('tr');
    fetch('action_cart/delete_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'id=' + encodeURIComponent(id)
    })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                showNotification(data.message, 'success');
                if (row) {
                    row.remove();
                }
                updateCartTotal();
                const remainingItems = document.querySelectorAll('.cart-item');
                if (remainingItems.length === 0) {
                    setTimeout(function () {
                        window.location.href = 'cart.php';
                    }, 800);
                }
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(function () {
            showNotification('Lỗi kết nối, không thể xóa sản phẩm.', 'error');
        });
});
// Cập nhật số lượng sản phẩm
function updateQty(id, change) {
    const qtyInput = document.getElementById('qty-' + id);
    if (!qtyInput) {
        showNotification('Không tìm thấy sản phẩm trong giỏ hàng.', 'error');
        return;
    }
    const currentQty = Number(qtyInput.value || 0);
    const stock = Number(qtyInput.dataset.stock || 0);
    if (change === 1 && currentQty >= stock) {
        showNotification('Số lượng tối đa chỉ còn ' + stock + ' sản phẩm trong kho.', 'error');
        return;
    }
    if (change === -1 && currentQty <= 1) {
        showNotification('Số lượng tối thiểu là 1 sản phẩm.', 'error');
        return;
    }
    fetch('action_cart/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'id=' + encodeURIComponent(id) + '&change=' + encodeURIComponent(change)
    })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                const row = qtyInput.closest('tr');
                const subtotalElement = row.querySelector('.item-subtotal');
                qtyInput.value = data.quantity;
                qtyInput.dataset.stock = data.stock_quantity;
                if (subtotalElement) {
                    subtotalElement.textContent = formatMoney(data.subtotal);
                }
                updateButtonState(row);
                updateCartTotal();

                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(function () {
            showNotification('Lỗi kết nối, không thể cập nhật số lượng.', 'error');
        });
}
document.addEventListener('click', function (e) {
    const plusBtn = e.target.closest('.btn-plus');
    const minusBtn = e.target.closest('.btn-minus');
    if (plusBtn) {
        updateQty(plusBtn.dataset.id, 1);
    }
    if (minusBtn) {
        updateQty(minusBtn.dataset.id, -1);
    }
});
// Cập nhật trạng thái nút khi tải trang
document.querySelectorAll('.cart-item').forEach(function (row) {
    updateButtonState(row);
});
// Chọn tất cả sản phẩm
const checkAll = document.getElementById('check-all');
if (checkAll) {
    checkAll.addEventListener('change', function () {
        const checked = this.checked;

        document.querySelectorAll('.item-check').forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    });
}
// Submit form thanh toán
const checkoutForm = document.getElementById('checkout-form');
const selectedItemsInput = document.getElementById('selected-items');
if (checkoutForm) {
    checkoutForm.addEventListener('submit', function (e) {
        const selected = [];
        document.querySelectorAll('.item-check:checked').forEach(function (checkbox) {
            selected.push(checkbox.value);
        });
        if (selected.length === 0) {
            e.preventDefault();
            showNotification('Bạn phải chọn ít nhất một sản phẩm để thanh toán!', 'error');
            return;
        }
        selectedItemsInput.value = selected.join(',');
    });
}