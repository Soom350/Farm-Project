        </main>
    </div>
</div>

<?php
require __DIR__ . '/partials/media-field.php';
admin_render_media_picker_modal();
?>
<script>
window.ADMIN_MEDIA_LIST_URL = <?= json_encode(admin_url('media.php?action=list'), JSON_UNESCAPED_UNICODE) ?>;
window.ADMIN_MEDIA_UPLOAD_URL = <?= json_encode(admin_url('media.php'), JSON_UNESCAPED_UNICODE) ?>;
window.ADMIN_CSRF = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= h(app_url('admin/admin-media.js')) ?>" defer></script>
</body>
</html>
