<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';
require_once dirname(__DIR__) . '/lib_media.php';

admin_require_access('admin/preview-blog.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/preview-blog.php']));
}

if (is_post() && ($_POST['action'] ?? '') === 'preview') {
    require_csrf();
    admin_preview_store('blog', $_POST);
    redirect(admin_url('preview-blog.php'));
}

$preview = admin_preview_get();
if (!$preview || ($preview['type'] ?? '') !== 'blog') {
    http_response_code(400);
    echo 'Aucun apercu article disponible.';
    exit;
}

$post = admin_preview_blog_from_input((array)($preview['data'] ?? []));
$videoHtml = blog_video_embed_html((string)($post['video_url'] ?? ''));

$pageTitle = 'Apercu article | Admin';
require dirname(__DIR__) . '/layout_top.php';
?>

<div class="preview-banner" role="status">
    <strong>Apercu admin</strong> — Statut actuel: <?= h(admin_blog_status_label((string)($post['status'] ?? 'draft'))) ?>.
    Seuls les articles « Publie » sont visibles sur le blog public.
</div>

<section class="services" aria-labelledby="preview-blog-title">
    <header class="services__header">
        <p class="services__eyebrow">Apercu blog</p>
        <h1 class="services__title" id="preview-blog-title"><?= h((string)$post['title']) ?></h1>
        <p class="services__lead"><?= h((string)$post['excerpt']) ?></p>
    </header>

    <article class="admin-panel blog-post-preview">
        <?php if ($videoHtml !== ''): ?>
            <?= $videoHtml ?>
        <?php elseif (!empty($post['image']) && media_is_allowed_path((string)$post['image'])): ?>
            <img class="blog-post-preview__cover" src="<?= h(media_url((string)$post['image'])) ?>" alt="<?= h((string)($post['image_alt'] ?? $post['title'])) ?>">
        <?php endif; ?>

        <div class="blog-post-preview__meta">
            <?= h((string)($post['author'] ?? 'Admin')) ?> · <?= h(blog_format_date((string)($post['published_at'] ?? ''))) ?> · <?= h((string)($post['read_minutes'] ?? 1)) ?> min
        </div>

        <div class="blog-post-preview__content">
            <?= nl2br(h((string)($post['content'] ?? ''))) ?>
        </div>
    </article>

    <div class="blog-index__grid mt-3">
        <?php blog_render_card($post); ?>
    </div>
</section>

<div class="preview-banner preview-banner--footer">
    <a class="btn btn--secondary btn--sm" href="javascript:window.close();">Fermer l'apercu</a>
</div>

<?php require dirname(__DIR__) . '/layout_bottom.php'; ?>
