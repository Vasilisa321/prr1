<h1>Список статей</h1>
<ol>
   <?php if (!empty($posts)): ?>
    <?php foreach ($posts as $post): ?>
    <li><?= htmlspecialchars($post->title ?? "Без названия") ?></li>
    <?php endforeach; ?>
    <?php else: ?>
    <li>Статей пока нет</li>
    <?php endif; ?>
</ol>