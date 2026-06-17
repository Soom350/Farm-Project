<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_bootstrap.php';

function blog_categories(): array
{
    return [
        '' => 'All Posts',
        'news' => 'News',
        'recipes' => 'Recipes',
        'events' => 'Events',
    ];
}

/** @return list<array<string, mixed>> */
function blog_posts_seed(): array
{
    return [
        [
            'id' => 'avocado-season',
            'slug' => 'avocado-season-is-here',
            'title' => 'Avocado Season is Here',
            'excerpt' => 'Create a blog post subtitle that summarizes your post in a few short, punchy sentences and entices readers to continue.',
            'category' => 'news',
            'author' => 'Admin',
            'published_at' => '2024-11-13',
            'read_minutes' => 1,
            'views' => 0,
            'comments' => 0,
            'image' => 'logo_slide_img/megan-thomas-xMh_ww8HN_Q-unsplash.jpg',
            'image_alt' => 'Fresh avocados on a wooden table',
        ],
        [
            'id' => 'herb-garden',
            'slug' => 'tips-for-your-herb-garden',
            'title' => 'Tips For Your Herb Garden',
            'excerpt' => 'Create a blog post subtitle that summarizes your post in a few short, punchy sentences and entices readers to continue.',
            'category' => 'news',
            'author' => 'Admin',
            'published_at' => '2024-11-13',
            'read_minutes' => 1,
            'views' => 0,
            'comments' => 0,
            'image' => 'image/divaris-shirichena-xZqfw-VXnYE-unsplash.jpg',
            'image_alt' => 'Potted herbs in a sunny garden',
        ],
        [
            'id' => 'pumpkin-soup',
            'slug' => 'creamy-pumpkin-soup',
            'title' => 'Creamy Pumpkin Soup',
            'excerpt' => 'Create a blog post subtitle that summarizes your post in a few short, punchy sentences and entices readers to continue.',
            'category' => 'recipes',
            'author' => 'Admin',
            'published_at' => '2024-11-13',
            'read_minutes' => 1,
            'views' => 0,
            'comments' => 0,
            'image' => 'logo_slide_img/pexels-pixabay-54082.jpg',
            'image_alt' => 'Creamy pumpkin soup with fresh ingredients',
        ],
        [
            'id' => 'harvest-tips',
            'slug' => 'farm-fresh-harvest-tips',
            'title' => 'Farm Fresh Harvest Tips',
            'excerpt' => 'Create a blog post subtitle that summarizes your post in a few short, punchy sentences and entices readers to continue.',
            'category' => 'news',
            'author' => 'Admin',
            'published_at' => '2024-11-10',
            'read_minutes' => 2,
            'views' => 0,
            'comments' => 0,
            'image' => 'logo_slide_img/okra-raw.jpg',
            'image_alt' => 'Fresh okra harvest from the farm',
        ],
        [
            'id' => 'community-market',
            'slug' => 'community-market-day',
            'title' => 'Community Market Day',
            'excerpt' => 'Create a blog post subtitle that summarizes your post in a few short, punchy sentences and entices readers to continue.',
            'category' => 'events',
            'author' => 'Admin',
            'published_at' => '2024-11-08',
            'read_minutes' => 1,
            'views' => 0,
            'comments' => 0,
            'image' => 'logo_slide_img/steven-weeks-DUPFowqI6oI-unsplash.jpg',
            'image_alt' => 'Farmers at a community market',
        ],
        [
            'id' => 'seasonal-recipes',
            'slug' => 'seasonal-recipes-to-try',
            'title' => 'Seasonal Recipes to Try',
            'excerpt' => 'Create a blog post subtitle that summarizes your post in a few short, punchy sentences and entices readers to continue.',
            'category' => 'recipes',
            'author' => 'Admin',
            'published_at' => '2024-11-05',
            'read_minutes' => 3,
            'views' => 0,
            'comments' => 0,
            'image' => 'logo_slide_img/pexels-ivan-torres-594557-1374651.jpg',
            'image_alt' => 'Seasonal vegetables ready to cook',
        ],
    ];
}

function blog_row_to_post(array $row): array
{
    return [
        'id' => (string)($row['post_id'] ?? ''),
        'slug' => (string)($row['slug'] ?? ''),
        'title' => (string)($row['title'] ?? ''),
        'excerpt' => (string)($row['excerpt'] ?? ''),
        'content' => (string)($row['content'] ?? ''),
        'category' => (string)($row['category'] ?? ''),
        'author' => (string)($row['author'] ?? 'Admin'),
        'published_at' => (string)($row['published_at'] ?? ''),
        'read_minutes' => (int)($row['read_minutes'] ?? 1),
        'views' => (int)($row['views'] ?? 0),
        'comments' => (int)($row['comments'] ?? 0),
        'image' => (string)($row['image'] ?? ''),
        'image_alt' => (string)($row['image_alt'] ?? ''),
        'video_url' => (string)($row['video_url'] ?? ''),
        'status' => (string)($row['status'] ?? 'draft'),
        'db_id' => (int)($row['id'] ?? 0),
    ];
}

function blog_posts_from_db(bool $includeAllStatuses = false): array
{
    require_once __DIR__ . '/lib_db.php';

    $sql = 'SELECT * FROM blog_posts';
    if (!$includeAllStatuses) {
        $sql .= " WHERE status = 'published'";
    }
    $sql .= ' ORDER BY published_at DESC, id DESC';

    $rows = db()->query($sql)->fetchAll();
    if (!is_array($rows)) return [];

    return array_map('blog_row_to_post', $rows);
}

/** @return list<array<string, mixed>> */
function blog_posts_all(bool $includeAllStatuses = false): array
{
    if (app_frontend_only()) {
        return blog_posts_seed();
    }

    return blog_posts_from_db($includeAllStatuses);
}

/** @return list<array<string, mixed>> */
function blog_posts(?string $category = null): array
{
    $posts = blog_posts_all(false);
    if ($category === null || $category === '') {
        return $posts;
    }

    return array_values(array_filter(
        $posts,
        static fn(array $post): bool => ($post['category'] ?? '') === $category
    ));
}

function blog_post_by_slug(string $slug): ?array
{
    $slug = trim($slug);
    if ($slug === '') return null;

    if (!app_frontend_only()) {
        require_once __DIR__ . '/lib_db.php';
        $stmt = db()->prepare("SELECT * FROM blog_posts WHERE slug = :slug AND status = 'published' LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return blog_row_to_post($row);
        }
    }

    foreach (blog_posts_seed() as $post) {
        if (($post['slug'] ?? '') === $slug) return $post;
    }

    return null;
}

function blog_post_by_id(string $postId): ?array
{
    $postId = trim($postId);
    if ($postId === '') return null;

    if (!app_frontend_only()) {
        require_once __DIR__ . '/lib_db.php';
        $stmt = db()->prepare('SELECT * FROM blog_posts WHERE post_id = :id LIMIT 1');
        $stmt->execute([':id' => $postId]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return blog_row_to_post($row);
        }
    }

    foreach (blog_posts_seed() as $post) {
        if (($post['id'] ?? '') === $postId) return $post;
    }

    return null;
}

function blog_status_options(): array
{
    return [
        'draft' => 'Brouillon',
        'published' => 'Publie',
        'archived' => 'Archive',
    ];
}

function blog_slugify(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = (string)preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-') ?: 'article';
}

function blog_post_save(array $input): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: enregistrement article desactive.']];
    }

    require_once __DIR__ . '/lib_db.php';

    $postId = trim((string)($input['post_id'] ?? ''));
    $title = trim((string)($input['title'] ?? ''));
    $slug = trim((string)($input['slug'] ?? ''));
    $excerpt = trim((string)($input['excerpt'] ?? ''));
    $content = trim((string)($input['content'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $author = trim((string)($input['author'] ?? 'Admin'));
    $publishedAt = trim((string)($input['published_at'] ?? gmdate('Y-m-d')));
    $readMinutes = max(1, (int)($input['read_minutes'] ?? 1));
    $image = trim((string)($input['image'] ?? ''));
    $imageAlt = trim((string)($input['image_alt'] ?? ''));
    $videoUrl = trim((string)($input['video_url'] ?? ''));
    $status = trim((string)($input['status'] ?? 'draft'));

    if ($slug === '' && $title !== '') {
        $slug = blog_slugify($title);
    }

    $errors = [];
    if ($title === '') $errors['title'] = 'Titre requis.';
    if ($slug === '') $errors['slug'] = 'Slug requis.';
    if (!isset(blog_status_options()[$status])) $errors['status'] = 'Statut invalide.';

    if ($errors) return ['ok' => false, 'errors' => $errors];

    $pdo = db();
    $existing = $postId !== '' ? blog_post_by_id($postId) : null;
    if (!$existing && $postId === '') {
        $postId = 'post-' . bin2hex(random_bytes(4));
    }

    $stmtCheck = $pdo->prepare('SELECT post_id FROM blog_posts WHERE slug = :slug AND post_id != :pid LIMIT 1');
    $stmtCheck->execute([':slug' => $slug, ':pid' => $postId]);
    if ($stmtCheck->fetch()) {
        return ['ok' => false, 'errors' => ['slug' => 'Ce slug existe deja.']];
    }

    $now = gmdate('c');
    $params = [
        ':slug' => $slug,
        ':title' => $title,
        ':excerpt' => $excerpt,
        ':content' => $content !== '' ? $content : $excerpt,
        ':category' => $category,
        ':author' => $author !== '' ? $author : 'Admin',
        ':published' => $publishedAt,
        ':read' => $readMinutes,
        ':image' => $image,
        ':alt' => $imageAlt !== '' ? $imageAlt : $title,
        ':video' => $videoUrl !== '' ? $videoUrl : null,
        ':status' => $status,
        ':updated' => $now,
    ];

    if ($existing) {
        $pdo->prepare("
            UPDATE blog_posts SET
                slug = :slug, title = :title, excerpt = :excerpt, content = :content,
                category = :category, author = :author, published_at = :published,
                read_minutes = :read, image = :image, image_alt = :alt, video_url = :video,
                status = :status, updated_at = :updated
            WHERE post_id = :pid
        ")->execute($params + [':pid' => $postId]);
    } else {
        $pdo->prepare("
            INSERT INTO blog_posts(
                post_id, slug, title, excerpt, content, category, author,
                published_at, read_minutes, views, comments, image, image_alt, video_url,
                status, created_at, updated_at
            ) VALUES (
                :pid, :slug, :title, :excerpt, :content, :category, :author,
                :published, :read, 0, 0, :image, :alt, :video,
                :status, :created, :updated
            )
        ")->execute($params + [
            ':pid' => $postId,
            ':created' => $now,
        ]);
    }

    return ['ok' => true, 'post_id' => $postId];
}

function blog_post_delete(string $postId): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: suppression desactivee.']];
    }

    $postId = trim($postId);
    if ($postId === '') {
        return ['ok' => false, 'errors' => ['Article introuvable.']];
    }

    require_once __DIR__ . '/lib_db.php';
    $stmt = db()->prepare('DELETE FROM blog_posts WHERE post_id = :pid');
    $stmt->execute([':pid' => $postId]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'errors' => ['Article introuvable.']];
    }

    return ['ok' => true];
}

function blog_format_date(string $isoDate): string
{
    $timestamp = strtotime($isoDate);
    if ($timestamp === false) {
        return $isoDate;
    }

    return date('M j, Y', $timestamp);
}

function blog_post_url(array $post): string
{
    return app_url('blog-post.php?slug=' . rawurlencode((string)($post['slug'] ?? '')));
}

function blog_video_embed_html(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';

    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
        $id = $m[1];
        return '<div class="blog-video"><iframe src="https://www.youtube.com/embed/' . h($id) . '" title="Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe></div>';
    }

    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        $id = $m[1];
        return '<div class="blog-video"><iframe src="https://player.vimeo.com/video/' . h($id) . '" title="Video" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    }

    if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url)) {
        return '<div class="blog-video"><video controls preload="metadata" src="' . h($url) . '"></video></div>';
    }

    return '';
}

function blog_render_card(array $post): void
{
    $title = (string)($post['title'] ?? '');
    $excerpt = (string)($post['excerpt'] ?? '');
    $author = (string)($post['author'] ?? 'Admin');
    $date = blog_format_date((string)($post['published_at'] ?? ''));
    $readMinutes = (int)($post['read_minutes'] ?? 1);
    $views = (int)($post['views'] ?? 0);
    $comments = (int)($post['comments'] ?? 0);
    $image = app_url((string)($post['image'] ?? 'image/divaris-shirichena-xZqfw-VXnYE-unsplash.jpg'));
    $imageAlt = (string)($post['image_alt'] ?? $title);
    $slug = (string)($post['slug'] ?? 'post');
    $url = blog_post_url($post);
    $videoUrl = (string)($post['video_url'] ?? '');
    $hasVideo = $videoUrl !== '';
    ?>
    <article class="blog-card--wix<?= $hasVideo ? ' blog-card--has-video' : '' ?>" id="post-<?= h($slug) ?>">
        <a class="blog-card__cover-link" href="<?= h($url) ?>">
            <?php if ($hasVideo && blog_video_embed_html($videoUrl) !== ''): ?>
                <div class="blog-card__video-badge" aria-hidden="true"><i class="fas fa-play"></i> Video</div>
            <?php endif; ?>
            <img class="blog-card__cover" src="<?= h($image) ?>" alt="<?= h($imageAlt) ?>" loading="lazy" width="640" height="420">
        </a>
        <div class="blog-card__body">
            <div class="blog-card__meta">
                <span class="blog-card__avatar" aria-hidden="true"></span>
                <div class="blog-card__meta-text">
                    <span class="blog-card__author"><?= h($author) ?></span>
                    <span class="blog-card__details">
                        <time datetime="<?= h((string)($post['published_at'] ?? '')) ?>"><?= h($date) ?></time>
                        · <?= h((string)$readMinutes) ?> min read
                    </span>
                </div>
                <button type="button" class="blog-card__menu" aria-label="Options pour <?= h($title) ?>">
                    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                </button>
            </div>
            <h2 class="blog-card__title">
                <a href="<?= h($url) ?>"><?= h($title) ?></a>
            </h2>
            <p class="blog-card__excerpt"><?= h($excerpt) ?></p>
            <div class="blog-card__stats">
                <span class="blog-card__stat"><i class="far fa-eye" aria-hidden="true"></i> <?= h((string)$views) ?></span>
                <span class="blog-card__stat"><i class="far fa-comment" aria-hidden="true"></i> <?= h((string)$comments) ?></span>
                <button type="button" class="blog-card__like" aria-label="Aimer l'article <?= h($title) ?>">
                    <i class="far fa-heart" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </article>
    <?php
}
