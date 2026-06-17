<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_blog.php';
require_once __DIR__ . '/lib_media.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$post = $slug !== '' ? blog_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Article introuvable | Timbuktu Farming';
    require __DIR__ . '/layout_top.php';
    ?>
    <section class="services">
        <article class="service-card">
            <div class="service-card__body">
                <h1 class="service-card__title">Article introuvable</h1>
                <a class="btn btn--primary" href="<?= h(app_url('blog.php')) ?>">Retour au blog</a>
            </div>
        </article>
    </section>
    <?php
    require __DIR__ . '/layout_bottom.php';
    exit;
}

$videoHtml = blog_video_embed_html((string)($post['video_url'] ?? ''));

$pageTitle = (string)$post['title'] . ' | Blog';
$pageDescription = (string)($post['excerpt'] ?? '');
require __DIR__ . '/layout_top.php';
?>

<section class="services" aria-labelledby="blog-post-title">
    <header class="services__header">
        <p class="services__eyebrow">Blog</p>
        <h1 class="services__title" id="blog-post-title"><?= h((string)$post['title']) ?></h1>
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

    <div class="actions mt-3">
        <a class="btn btn--secondary" href="<?= h(app_url('blog.php')) ?>">Retour au blog</a>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>
