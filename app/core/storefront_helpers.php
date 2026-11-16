<?php

function flora_render_card(array $product): void
{
    $isNew = !empty($product['published_at'])
        && strtotime($product['published_at']) >= time() - 7 * 86400;
    $hasDiscount = (float) $product['price'] < (float) $product['original_price'];
    $image = $product['main_image'] ?: 'assets/images/no-image.svg';
    ?>
    <article class="card product-card">
        <a class="product-link" href="index.php?route=product-detail&amp;id=<?= (int) $product['id'] ?>">
            <div class="ph">
                <img src="<?= flora_e($image) ?>" alt="<?= flora_e($product['name']) ?>" loading="lazy">
                <?php if ((int) $product['is_featured'] === 1): ?>
                    <span class="tag">Nổi bật</span>
                <?php elseif ($isNew): ?>
                    <span class="tag">Mới</span>
                <?php elseif ($hasDiscount): ?>
                    <span class="tag">Ưu đãi</span>
                <?php endif; ?>
            </div>
            <div class="nm"><?= flora_e($product['name']) ?></div>
            <div class="pr">
                <?= flora_money($product['price']) ?>
                <?php if ($hasDiscount): ?>
                    <del class="old-price"><?= flora_money($product['original_price']) ?></del>
                <?php endif; ?>
            </div>
            <div class="product-category"><?= flora_e($product['root_name']) ?> · <?= flora_e($product['category_name']) ?></div>
        </a>
    </article>
    <?php
}

