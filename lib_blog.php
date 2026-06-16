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
function blog_posts_all(): array
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

/** @return list<array<string, mixed>> */
function blog_posts(?string $category = null): array
{
    $posts = blog_posts_all();
    if ($category === null || $category === '') {
        return $posts;
    }

    return array_values(array_filter(
        $posts,
        static fn(array $post): bool => ($post['category'] ?? '') === $category
    ));
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
    return app_url('blog.php') . '#post-' . rawurlencode((string)$post['slug']);
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
    ?>
    <article class="blog-card--wix" id="post-<?= h($slug) ?>">
        <a class="blog-card__cover-link" href="<?= h($url) ?>">
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
