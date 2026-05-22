document.addEventListener("DOMContentLoaded", () => {
    function showToast(message, type = 'success') {
        let container = document.getElementById('noti-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'noti-container';
            container.className = 'noti-container';
            document.body.appendChild(container);
        }
        let icon = 'i';
        if (type === 'success') {
            icon = '✓';
        } else if (type === 'error') {
            icon = '✕';
        }
        const notiBox = document.createElement('div');
        notiBox.className = `noti-box ${type}`;
        notiBox.innerHTML = `
            <div class="noti-icon">${icon}</div>
            <div class="noti-content">${message}</div>
        `;
        container.appendChild(notiBox);
        setTimeout(() => {
            notiBox.classList.add('noti-fade-out');
            setTimeout(() => {
                notiBox.remove();
                if (container.childNodes.length === 0) {
                    container.remove();
                }
            }, 500);
        }, 5000);
    }
    function redirectToLoginWithDelay() {
        setTimeout(() => {
            window.location.href = loginUrl;
        }, 1200);
    }
    function requireLoginBeforeAction() {
        if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
            showToast('Oppss, bạn chưa đăng nhập rồi!', 'error');
            redirectToLoginWithDelay();
            return false;
        }
        return true;
    }
    const btnAddToCart = document.getElementById('btnAddToCart');
    const productForm = document.getElementById('addToCartForm');
    if (btnAddToCart && productForm) {
        btnAddToCart.addEventListener("click", function(e) {
            e.preventDefault();
            if (!requireLoginBeforeAction()) {
                return;
            }
            const formData = new FormData(productForm);
            formData.append('action_type', 'add_to_cart');
            fetch('../user/action_product_detail/action_product.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.redirect) {
                    showToast(data.message || 'Bạn cần đăng nhập để tiếp tục.', 'error');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200);
                    return;
                }
                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'Có lỗi xảy ra.', 'error');
                }
            })
            .catch(() => {
                showToast('Lỗi kết nối, không thể thêm vào giỏ hàng.', 'error');
            });
        });
    }
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            const submitter = e.submitter;
            if (submitter && submitter.value === 'buy_now') {
                if (!requireLoginBeforeAction()) {
                    e.preventDefault();
                }
            }
        });
    }
    const qtyInput = document.getElementById('qty-input');
    const btnMinus = document.querySelector('.qty-btn.minus');
    const btnPlus = document.querySelector('.qty-btn.plus');
    if (qtyInput && btnMinus && btnPlus) {
        btnMinus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            if (val > 1) {
                qtyInput.value = val - 1;
            }
        });
        btnPlus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            let max = parseInt(qtyInput.getAttribute('max')) || 999;
            if (val < max) {
                qtyInput.value = val + 1;
            }
        });
    }
    let currentImageIndex = 0;
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.thumb-item');
    const btnPrev = document.getElementById('prev-img-btn');
    const btnNext = document.getElementById('next-img-btn');
    function updateGallery(index) {
        if (!mainImage || typeof productImages === 'undefined') {
            return;
        }
        if (index < 0) {
            index = productImages.length - 1;
        }
        if (index >= productImages.length) {
            index = 0;
        }
        currentImageIndex = index;
        mainImage.src = productImages[currentImageIndex];
        thumbnails.forEach(t => t.classList.remove('active'));
        if (thumbnails[currentImageIndex]) {
            thumbnails[currentImageIndex].classList.add('active');
        }
    }
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            updateGallery(index);
        });
    });
    if (btnPrev) {
        btnPrev.addEventListener('click', () => updateGallery(currentImageIndex - 1));
    }
    if (btnNext) {
        btnNext.addEventListener('click', () => updateGallery(currentImageIndex + 1));
    }
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            const targetPane = document.getElementById(target);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
    const btnSubmitReview = document.getElementById('btnSubmitReview');
    const reviewForm = document.getElementById('submitReviewForm');
    if (btnSubmitReview && reviewForm) {
        btnSubmitReview.addEventListener('click', function() {
            if (!requireLoginBeforeAction()) {
                return;
            }
            const comment = document.getElementById('comment').value.trim();
            if (!comment) {
                showToast('Vui lòng nhập nội dung đánh giá!', 'error');
                return;
            }
            const formData = new FormData(reviewForm);
            fetch('../user/action_product_detail/action_review.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.redirect) {
                    showToast(data.message || 'Bạn cần đăng nhập để đánh giá.', 'error');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200);
                    return;
                }
                if (data.success) {
                    showToast('Cảm ơn bạn đã gửi đánh giá!', 'success');
                    setTimeout(() => {
                        window.location.reload(); 
                    }, 1500);
                } else {
                    showToast(data.message || 'Lỗi khi gửi đánh giá.', 'error');
                }
            })
            .catch(() => {
                showToast('Lỗi kết nối.', 'error');
            });
        });
    }
});