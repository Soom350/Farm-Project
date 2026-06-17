<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';

admin_require_access('admin/blog.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/blog.php']));
}

$info = flash_get('info');
$errors = [];

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    require_csrf();
    $result = blog_post_delete((string)($_POST['post_id'] ?? ''));
    if ($result['ok'] ?? false) {
        flash_set('info', 'Article supprime.');
        redirect(admin_url('blog.php'));
    }
    $errors = (array)($result['errors'] ?? ['Suppression impossible.']);
}

$posts = blog_posts_all(true);
$categories = blog_categories();
$category = trim((string)($_GET['category'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));

if ($category !== '') {
    $posts = array_values(array_filter($posts, static fn(array $post): bool => ($post['category'] ?? '') === $category));
}

if ($statusFilter !== '') {
    $posts = array_values(array_filter($posts, static fn(array $post): bool => ($post['status'] ?? '') === $statusFilter));
}

if ($q !== '') {
    $posts = array_values(array_filter($posts, static function (array $post) use ($q): bool {
        $hay = mb_strtolower(
            (string)($post['title'] ?? '') . ' ' .
            (string)($post['excerpt'] ?? '') . ' ' .
            (string)($post['slug'] ?? '')
        );
        return str_contains($hay, mb_strtolower($q));
    }));
}

$pageTitle = 'Blog | Admin';
$adminPageTitle = 'Blog';
require __DIR__ . '/layout_top.php';
?>

<?php if ($info): ?>
    <div class="alert"><div class="alert__title">Information</div><div><?= h($info) ?></div></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
        <div class="alert__title">Erreur</div>
        <ul class="alert__list"><?php foreach ($errors as $msg): ?><li><?= h((string)$msg) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-panel">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title">Articles</h2>
        <a class="btn btn--primary btn--sm" href="<?= h(admin_url('blog-edit.php')) ?>">Nouvel article</a>
    </div>

    <form method="get" action="<?= h(admin_url('blog.php')) ?>" class="admin-filters">
        <div class="field">
            <label for="category">Categorie</label>
            <select class="form-control form-control--sm" id="category" name="category">
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= h((string)$key) ?>" <?= $category === (string)$key ? 'selected' : '' ?>><?= h((string)$label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="status">Statut</label>
            <select class="form-control form-control--sm" id="status" name="status">
                <option value="">Tous</option>
                <?php foreach (blog_status_options() as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="q">Rechercher</label>
            <input class="form-control form-control--sm" id="q" name="q" type="search" value="<?= h($q) ?>" placeholder="Titre, slug, extrait">
        </div>
        <div class="admin-actions">
            <button class="btn btn--primary btn--sm" type="submit">Filtrer</button>
            <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('blog.php')) ?>">Reinitialiser</a>
        </div>
    </form>

    <?php if (!count($posts)): ?>
        <p class="admin-empty">Aucun article trouve.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Titre</th>
                        <th scope="col">Categorie</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Publication</th>
                        <th scope="col">Auteur</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <strong><?= h((string)($post['title'] ?? '')) ?></strong>
                                <div class="admin-table__sub"><?= h((string)($post['slug'] ?? '')) ?></div>
                            </td>
                            <td><?= h((string)($categories[$post['category'] ?? ''] ?? ($post['category'] ?? ''))) ?></td>
                            <td><span class="admin-badge"><?= h(admin_blog_status_label((string)($post['status'] ?? 'draft'))) ?></span></td>
                            <td><?= h(blog_format_date((string)($post['published_at'] ?? ''))) ?></td>
                            <td><?= h((string)($post['author'] ?? '')) ?></td>
                            <td>
                                <div class="admin-actions">
                                    <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('blog-edit.php?id=' . rawurlencode((string)$post['id']))) ?>">Modifier</a>
                                    <form method="post" action="<?= h(admin_url('blog.php')) ?>" onsubmit="return confirm('Supprimer cet article ?');">
                                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="post_id" value="<?= h((string)$post['id']) ?>">
                                        <button class="btn btn--secondary btn--sm" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_bottom.php'; ?>
