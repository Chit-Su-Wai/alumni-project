<?php
/**
 * Reusable empty-state component.
 *
 * Set these variables before including:
 *   $es_icon    string  FontAwesome class, e.g. 'fa-regular fa-newspaper'
 *   $es_title   string  Heading text
 *   $es_message string  Supporting text (optional)
 *   $es_action  string  HTML for a call-to-action button/link (optional)
 *   $es_class   string  Extra classes for the wrapper (optional)
 *
 * Example:
 *   $es_icon    = 'fa-regular fa-newspaper';
 *   $es_title   = 'No posts yet';
 *   $es_message = 'Share something with your alumni network.';
 *   $es_action  = '<a href="create_post.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Post</a>';
 *   include '../include/empty_state.php';
 */
$es_icon    = $es_icon    ?? 'fa-regular fa-folder-open';
$es_title   = $es_title   ?? 'Nothing here yet';
$es_message = $es_message ?? '';
$es_action  = $es_action  ?? '';
$es_class   = $es_class   ?? '';
?>
<div class="empty-state animate-in <?= htmlspecialchars($es_class) ?>">
    <div class="es-badge"><i class="<?= htmlspecialchars($es_icon) ?>"></i></div>
    <h3><?= htmlspecialchars($es_title) ?></h3>
    <?php if ($es_message !== ''): ?>
        <p><?= htmlspecialchars($es_message) ?></p>
    <?php endif; ?>
    <?php if ($es_action !== ''): ?>
        <div class="empty-action"><?= $es_action ?></div>
    <?php endif; ?>
</div>
