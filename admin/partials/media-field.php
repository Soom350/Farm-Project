<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib_media.php';

function admin_render_media_field(string $inputId, string $label, string $value = ''): void
{
    $previewUrl = $value !== '' && media_is_allowed_path($value) ? media_url($value) : '';
    ?>
    <div class="field field--full" data-media-field>
        <label for="<?= h($inputId) ?>"><?= h($label) ?></label>
        <div class="media-field">
            <input type="hidden" id="<?= h($inputId) ?>" name="<?= h($inputId) ?>" value="<?= h($value) ?>" data-media-input>
            <div class="media-field__preview-wrap">
                <img class="media-field__preview" src="<?= h($previewUrl) ?>" alt="" data-media-preview <?= $previewUrl === '' ? 'hidden' : '' ?>>
                <div class="media-field__placeholder" data-media-placeholder <?= $previewUrl !== '' ? 'hidden' : '' ?>>Aucune image selectionnee</div>
            </div>
            <div class="admin-actions">
                <button class="btn btn--secondary btn--sm" type="button" data-media-open>Choisir une image</button>
                <button class="btn btn--secondary btn--sm" type="button" data-media-clear>Retirer</button>
            </div>
        </div>
    </div>
    <?php
}

function admin_render_media_picker_modal(): void
{
    ?>
    <div class="media-picker" id="media-picker" role="dialog" aria-modal="true" aria-labelledby="media-picker-title" hidden>
        <div class="media-picker__backdrop" data-media-close></div>
        <div class="media-picker__panel">
            <header class="media-picker__header">
                <h2 id="media-picker-title">Mediatheque</h2>
                <button class="icon-btn" type="button" data-media-close aria-label="Fermer">&times;</button>
            </header>
            <div class="media-picker__toolbar">
                <label class="btn btn--secondary btn--sm">
                    Importer une image
                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-media-upload hidden>
                </label>
            </div>
            <div class="media-picker__grid" data-media-grid></div>
        </div>
    </div>
    <?php
}
