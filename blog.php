<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_blog.php';

$pageTitle = 'Blog | Timbuktu Farming';
$pageDescription = 'News, recipes and events from our farm.';

$activeCategory = (string)($_GET['cat'] ?? '');
if (!array_key_exists($activeCategory, blog_categories())) {
    $activeCategory = '';
}

$posts = blog_posts($activeCategory === '' ? null : $activeCategory);

require __DIR__ . '/layout_top.php';
?>

<div class="blog-index" aria-labelledby="blog-index-title">
    <header class="blog-index__bar">
        <h1 id="blog-index-title" class="sr-only">Blog</h1>
        <nav class="blog-index__nav" aria-label="Filtrer les articles">
            <?php foreach (blog_categories() as $slug => $label): ?>
                <?php
                $href = $slug === '' ? app_url('blog.php') : app_url('blog.php?cat=' . rawurlencode($slug));
                $isActive = $activeCategory === $slug;
                ?>
                <a
                    class="blog-index__tab<?= $isActive ? ' is-active' : '' ?>"
                    href="<?= h($href) ?>"
                    <?= $isActive ? 'aria-current="page"' : '' ?>
                ><?= h($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <button type="button" class="blog-index__search" aria-label="Rechercher dans le blog">
            <i class="fas fa-search" aria-hidden="true"></i>
        </button>
    </header>

    <div class="blog-index__main">
        <div class="blog-index__grid">
            <?php foreach ($posts as $post): ?>
                <?php blog_render_card($post); ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="wix-side-badge wix-side-badge--join" aria-hidden="true">JOIN US</div>
<button class="wix-chat-fab" type="button" aria-label="Ouvrir le chat">💬</button>

<?php require __DIR__ . '/layout_bottom.php'; ?>
