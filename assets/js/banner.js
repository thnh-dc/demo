// assets/js/banner.js
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('bannerTrack');
    const slides = document.querySelectorAll('.banner-slide');
    if (!track || slides.length <= 1) return; 

    let index = 0;

    function moveSlide() {
        index++;
        if (index >= slides.length) index = 0;
        // BẮT BUỘC dùng % để tự động thu nhỏ theo trình duyệt
        track.style.transform = `translateX(-${index * 100}%)`;
    }

    setInterval(moveSlide, 4000);
});