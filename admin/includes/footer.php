<?php
/**
 * Admin Panel Footer Layout
 * Alokito Gour Clone
 */

declare(strict_types=1);
?>
        </main>
    </div>
</div>

<!-- jQuery (Required for Summernote BS5) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Summernote BS5 JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>

<!-- Admin Custom Scripts -->
<script src="assets/js/admin.js"></script>

<script>
    // Initialize Summernote WYSIWYG
    $(document).ready(function() {
        if ($('#summernote').length) {
            $('#summernote').summernote({
                placeholder: 'এখানে সংবাদের বিস্তারিত বিবরণ লিখুন...',
                tabsize: 2,
                height: 350,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        }
    });
</script>
</body>
</html>
