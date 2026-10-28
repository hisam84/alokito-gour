/**
 * Admin Panel Client Utilities
 */

document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle (Desktop Collapse + Mobile Drawer)
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function isDesktop() {
        return window.innerWidth >= 992;
    }

    // Sync state on load
    if (isDesktop() && localStorage.getItem('admin_sidebar_collapsed') === 'true') {
        document.documentElement.classList.add('sidebar-collapsed');
        document.body.classList.add('sidebar-collapsed');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (isDesktop()) {
                const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
                document.body.classList.toggle('sidebar-collapsed', isCollapsed);
                localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
            } else {
                if (sidebar) sidebar.classList.toggle('show');
                if (backdrop) backdrop.classList.toggle('show');
            }
        });
    }

    if (backdrop && sidebar) {
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }

    // Auto dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Confirmation for Delete actions
    const deleteButtons = document.querySelectorAll('.btn-confirm-delete');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Image preview for file uploads
    const imageInputs = document.querySelectorAll('.image-preview-input');
    imageInputs.forEach(function (input) {
        input.addEventListener('change', function (e) {
            const targetPreviewId = input.getAttribute('data-preview');
            const previewEl = document.getElementById(targetPreviewId);
            if (previewEl && input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    previewEl.src = event.target.result;
                    previewEl.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    });
});
