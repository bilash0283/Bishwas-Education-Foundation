
    const menuBtn = document.getElementById('menu-btn');
    const closeBtn = document.getElementById('close-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const mobileLinks = document.querySelectorAll('.mobile-link');

    // মেনু খোলার ফাংশন
    function openMenu() {
        mobileMenu.classList.remove('-translate-x-full');
        sidebarOverlay.classList.remove('opacity-0', 'pointer-events-none');
    }

    // মেনু বন্ধ করার ফাংশন
    function closeMenu() {
        mobileMenu.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('opacity-0', 'pointer-events-none');
    }

    // ইভেন্ট লিসেনার
    menuBtn.addEventListener('click', openMenu);
    closeBtn.addEventListener('click', closeMenu);
    sidebarOverlay.addEventListener('click', closeMenu);

    // মোবাইল মেনুর যেকোনো লিঙ্কে ক্লিক করলে মেনুটি স্বয়ংক্রিয়ভাবে বন্ধ হয়ে যাবে
    mobileLinks.forEach(link => {
        link.addEventListener('click', closeMenu);
    });

    // card scrole right to left start *******

    const slider = document.getElementById('slider-container');
    const prevBtn = document.getElementById('slide-prev');
    const nextBtn = document.getElementById('slide-next');

    // কার্ডের উইডথ অনুযায়ী স্ক্রোল অ্যামাউন্ট নির্ধারণ
    const scrollAmount = 390;

    // ম্যানুয়াল অ্যারো বাটন ক্লিক লজিক
    nextBtn.addEventListener('click', () => {
        if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 10) {
            slider.scrollTo({ left: 0, behavior: 'smooth' }); // শেষে পৌঁছালে আবার শুরুতে যাবে
        } else {
            slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    });

    prevBtn.addEventListener('click', () => {
        if (slider.scrollLeft <= 0) {
            slider.scrollTo({ left: slider.scrollWidth, behavior: 'smooth' }); // শুরুতে থাকলে এক ক্লিকে শেষে যাবে
        } else {
            slider.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        }
    });

    // অটো স্ক্রোল লজিক (Auto Left-Right Smooth Loop)
    let autoScrollInterval = setInterval(() => {
        if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 10) {
            slider.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }, 3500); // প্রতি ৩.৫ সেকেন্ড পর পর স্লাইড হবে

    // মাউস হোভার করলে অটো-স্ক্রোল বন্ধ থাকবে, মাউস সরালে আবার চালু হবে
    slider.addEventListener('mouseenter', () => clearInterval(autoScrollInterval));
    slider.addEventListener('mouseleave', () => {
        autoScrollInterval = setInterval(() => {
            if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 10) {
                slider.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
        }, 3500);
    });

    // hero section number counter start ********
    document.addEventListener("DOMContentLoaded", () => {
        const counters = document.querySelectorAll(".counter");

        const toBangla = (num) => {
            return num.toString().replace(/\d/g, d => "০১২৩৪৫৬৭৮৯"[d]);
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;

                const counter = entry.target;
                const target = +counter.dataset.target;
                const suffix = counter.dataset.suffix || "";

                let start = 0;
                const duration = 3000;
                const startTime = performance.now();

                function update(now) {
                    const progress = Math.min((now - startTime) / duration, 1);

                    // Ease Out
                    const ease = 1 - Math.pow(1 - progress, 3);

                    const value = Math.floor(ease * target);

                    counter.textContent = toBangla(value) + suffix;

                    if (progress < 1) {
                        requestAnimationFrame(update);
                    } else {
                        counter.textContent = toBangla(target) + suffix;
                    }
                }

                requestAnimationFrame(update);
                observer.unobserve(counter);
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    });

    // ফটো গ্যালারি জাভাস্ক্রিপ্ট (JavaScript Logic) 
    document.addEventListener('DOMContentLoaded', () => {
        const lightbox = document.getElementById('lightbox');
        const lightboxBox = document.getElementById('lightbox-box');
        const lightboxImg = document.getElementById('lightbox-img');
        const lightboxClose = document.getElementById('lightbox-close');
        const galleryItems = document.querySelectorAll('.gallery-item');

        // ওপেন লাইটবক্স
        galleryItems.forEach(item => {
            item.addEventListener('click', () => {
                const imgSrc = item.getAttribute('data-src');
                lightboxImg.src = imgSrc;

                // ব্যাকড্রপ ও পপআপ ভিজিবল করা
                lightbox.classList.remove('pointer-events-none', 'opacity-0');
                lightbox.classList.add('pointer-events-auto', 'opacity-100');

                // জুম-ইন এনিমেশন
                lightboxBox.classList.remove('scale-90');
                lightboxBox.classList.add('scale-100');

                // স্ক্রোল লক
                document.body.classList.add('overflow-hidden');
            });
        });

        // ক্লোজ ফংশন
        const closeLightbox = () => {
            // জুম-আউট ও ফেড-আউট এনিমেশন
            lightbox.classList.remove('pointer-events-auto', 'opacity-100');
            lightbox.classList.add('pointer-events-none', 'opacity-0');

            lightboxBox.classList.remove('scale-100');
            lightboxBox.classList.add('scale-90');

            // স্ক্রোল রিস্টোর
            document.body.classList.remove('overflow-hidden');

            setTimeout(() => {
                lightboxImg.src = '';
            }, 300);
        };

        lightboxClose.addEventListener('click', closeLightbox);

        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lightbox.classList.contains('opacity-100')) {
                closeLightbox();
            }
        });
    });