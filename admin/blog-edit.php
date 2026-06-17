<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib_admin.php';
require_once dirname(__DIR__) . '/admin/partials/media-field.php';

admin_require_access('admin/blog-edit.php');
if (!admin_user()) {
    redirect(url_with_params(app_url('auth/login.php'), ['next' => 'admin/blog-edit.php']));
}

$postId = trim((string)($_GET['id'] ?? ''));
$post = $postId !== '' ? blog_post_by_id($postId) : null;
$isNew = !$post;

$values = [
    'post_id' => $postId,
    'title' => (string)($post['title'] ?? ''),
    'slug' => (string)($post['slug'] ?? ''),
    'excerpt' => (string)($post['excerpt'] ?? ''),
    'content' => (string)($post['content'] ?? ''),
    'category' => (string)($post['category'] ?? 'news'),
    'author' => (string)($post['author'] ?? 'Admin'),
    'published_at' => (string)($post['published_at'] ?? gmdate('Y-m-d')),
    'read_minutes' => (string)($post['read_minutes'] ?? '1'),
    'image' => (string)($post['image'] ?? ''),
    'image_alt' => (string)($post['image_alt'] ?? ''),
    'video_url' => (string)($post['video_url'] ?? ''),
    'status' => (string)($post['status'] ?? 'draft'),
];

$errors = [];
$categories = blog_categories();

if (is_post()) {
    require_csrf();

    foreach ($values as $key => $default) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $values['post_id'] = $postId;

    if (($_POST['action'] ?? '') === 'preview') {
        require_csrf();
        admin_preview_store('blog', $_POST);
        redirect(admin_url('preview-blog.php'));
    }

    $result = blog_post_save($values);
    if ($result['ok'] ?? false) {
        flash_set('info', $isNew ? 'Article cree.' : 'Article mis a jour.');
        redirect(admin_url('blog.php'));
    }

    $errors = (array)($result['errors'] ?? ['Enregistrement impossible.']);
}

$pageTitle = ($isNew ? 'Nouvel article' : 'Modifier article') . ' | Admin';
$adminPageTitle = $isNew ? 'Nouvel article' : 'Modifier article';
require __DIR__ . '/layout_top.php';
?>

<div class="admin-actions">
    <a class="btn btn--secondary btn--sm" href="<?= h(admin_url('blog.php')) ?>">Retour a la liste</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
        <div class="alert__title">Merci de corriger les champs</div>
        <ul class="alert__list">
            <?php foreach ($errors as $msg): ?>
                <li><?= h((string)$msg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-panel">
    <form method="post" action="<?= h(admin_url('blog-edit.php' . ($postId !== '' ? '?id=' . rawurlencode($postId) : ''))) ?>" class="admin-form">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

        <div class="grid grid--2">
            <div class="field field--full">
                <label for="title">Titre</label>
                <input class="form-control" id="title" name="title" type="text" value="<?= h($values['title']) ?>" required>
            </div>
            <div class="field">
                <label for="slug">Slug (URL)</label>
                <input class="form-control" id="slug" name="slug" type="text" value="<?= h($values['slug']) ?>" placeholder="auto si vide">
            </div>
            <div class="field">
                <label for="status">Statut</label>
                <select class="form-control" id="status" name="status">
                    <?php foreach (blog_status_options() as $key => $label): ?>
                        <option value="<?= h($key) ?>" <?= $values['status'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="category">Categorie</label>
                <select class="form-control" id="category" name="category">
                    <?php foreach ($categories as $key => $label): ?>
                        <?php if ($key === '') continue; ?>
                        <option value="<?= h((string)$key) ?>" <?= $values['category'] === (string)$key ? 'selected' : '' ?>><?= h((string)$label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="author">Auteur</label>
                <input class="form-control" id="author" name="author" type="text" value="<?= h($values['author']) ?>">
            </div>
            <div class="field">
                <label for="published_at">Date de publication</label>
                <input class="form-control" id="published_at" name="published_at" type="date" value="<?= h($values['published_at']) ?>">
            </div>
            <div class="field">
                <label for="read_minutes">Duree de lecture (min)</label>
                <input class="form-control" id="read_minutes" name="read_minutes" type="number" min="1" value="<?= h($values['read_minutes']) ?>">
            </div>
            <?php admin_render_media_field('image', 'Image de couverture', $values['image']); ?>
            <div class="field field--full">
                <label for="video_url">Video (YouTube, Vimeo ou MP4)</label>
                <input class="form-control" id="video_url" name="video_url" type="url" value="<?= h($values['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=...">
                <p class="form-help">Si une video est renseignee, elle sera affichee en priorite sur l'article.</p>
            </div>
            <div class="field field--full">
                <label for="image_alt">Texte alternatif image</label>
                <input class="form-control" id="image_alt" name="image_alt" type="text" value="<?= h($values['image_alt']) ?>">
            </div>
            <div class="field field--full">
                <label for="excerpt">Extrait</label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= h($values['excerpt']) ?></textarea>
            </div>
            <div class="field field--full">
                <label for="content">Contenu</label>
                <textarea class="form-control" id="content" name="content" rows="8"><?= h($values['content']) ?></textarea>
            </div>
        </div>

        <div class="admin-actions mt-3">
            <button class="btn btn--primary" type="submit" name="action" value="save"><?= $isNew ? 'Creer l\'article' : 'Enregistrer' ?></button>
            <button class="btn btn--secondary" type="submit" name="action" value="preview" formtarget="_blank">Apercu avant publication</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_bottom.php'; ?>
