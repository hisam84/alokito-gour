/**
 * Alokito Gour Public Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    // Copy article link to clipboard
    const copyBtn = document.getElementById('copyArticleLink');
    if (copyBtn) {
        copyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function () {
                const originalText = copyBtn.getAttribute('title') || '';
                copyBtn.setAttribute('title', 'লিংক কপি করা হয়েছে!');
                alert('সংবাদের লিংক সফলভাবে কপি করা হয়েছে!');
            });
        });
    }

    // Quick search trigger focus
    const searchModal = document.getElementById('searchModal');
    if (searchModal) {
        searchModal.addEventListener('shown.bs.modal', function () {
            const input = document.getElementById('mainSearchInput');
            if (input) input.focus();
        });
    }

    // Keep video gallery counters in sync and pause the previous YouTube video.
    const bengaliDigits = '০১২৩৪৫৬৭৮৯';
    document.querySelectorAll('[data-video-gallery-carousel]').forEach(function (carousel) {
        carousel.addEventListener('slide.bs.carousel', function () {
            const activeIframe = carousel.querySelector('.carousel-item.active iframe');
            if (activeIframe && activeIframe.contentWindow) {
                activeIframe.contentWindow.postMessage(JSON.stringify({
                    event: 'command',
                    func: 'pauseVideo',
                    args: []
                }), 'https://www.youtube.com');
            }
        });

        carousel.addEventListener('slid.bs.carousel', function (event) {
            const current = carousel.querySelector('[data-video-gallery-current]');
            if (current && Number.isInteger(event.to)) {
                current.textContent = String(event.to + 1).replace(/\d/g, function (digit) {
                    return bengaliDigits[digit];
                });
            }
        });
    });
});
