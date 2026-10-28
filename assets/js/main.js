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
});
