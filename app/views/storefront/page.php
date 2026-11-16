 <?php require APP_ROOT . '/app/views/layouts/header.php'; ?>
        <?php if ($detail !== null): ?>
            <section id="product-detail">
                <div class="c">
                    <a href="index.php?route=products#products">← Quay lại danh sách</a>
                    <div class="det">
                        <div class="flora-gallery">
                            <div class="ph detail-image">
                                <img id="detail-image" src="<?= flora_e($detail['main_image'] ?: 'assets/images/no-image.svg') ?>"
                                     alt="<?= flora_e($detail['name']) ?>">
                            </div>
                            <?php if (count($images) > 1): ?>
                                <div class="flora-thumbnails" aria-label="Ảnh sản phẩm">
                                    <?php foreach ($images as $image): ?>
                                        <button type="button" class="flora-thumbnail"
                                            data-gallery-src="<?= flora_e($image['image_path']) ?>"
                                            aria-label="Xem ảnh <?= (int) $image['id'] ?>">
                                            <img src="<?= flora_e($image['image_path']) ?>" alt="" loading="lazy">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="detail-copy">
                            <p class="product-category"><?= flora_e($detail['root_name']) ?> · <?= flora_e($detail['category_name']) ?></p>
                            <h1><?= flora_e($detail['name']) ?></h1>
                            <div class="pr detail-price" id="variant-price"><?= flora_money($detail['price']) ?></div>
                            <?php if (!empty($detail['description'])): ?>
                                <p><?= nl2br(flora_e($detail['description'])) ?></p>
                            <?php endif; ?>
                            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                                <a class="btn" href="/flora/index.php?route=admin_products">Quản lý sản phẩm</a>
                            <?php elseif ($variants): ?>
                            
                            <form
                                id="add-to-cart-form"
                                action="/flora/index.php?route=cart_add"
                                method="post"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= flora_e(Csrf::token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="return_url"
                                    value="<?= flora_e(
                                        'index.php?route=product-detail&id=' . (int) $detail['id']
                                    ) ?>"
                                >

                                <label class="variant-label" for="variant">
                                    Màu và kích thước
                                </label>

                                <select
                                    class="fc variant-select"
                                    id="variant"
                                    name="variant_id"
                                    required
                                >
                                    <option value="">Chọn màu và kích thước</option>

                                    <?php foreach ($variants as $variant): ?>
                                        <option
                                            value="<?= (int) $variant['id'] ?>"
                                            data-stock="<?= (int) $variant['stock'] ?>"
                                            data-price="<?= (int) $variant['price'] ?>"
                                            data-original-price="<?= (int) $variant['original_price'] ?>"
                                            data-image="<?= flora_e(
                                                $variant['image_path'] ?: $detail['main_image']
                                            ) ?>"
                                            <?= (int) $variant['stock'] <= 0 ? 'disabled' : '' ?>
                                        >
                                            <?= flora_e($variant['color_name']) ?>
                                            · <?= flora_e($variant['size_name']) ?>
                                            <?= (int) $variant['stock'] <= 0 ? ' — Hết hàng' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <p class="note" id="variant-stock" aria-live="polite">
                                    Chọn màu và kích thước để xem tồn kho.
                                </p>

                                <label class="variant-label" for="cart-quantity">
                                    Số lượng
                                </label>

                                <input
                                    class="fc"
                                    id="cart-quantity"
                                    name="quantity"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value="1"
                                    required
                                >

                                <div class="product-actions detail-actions">
                                    <button
                                        class="product-action-btn product-action-cart"
                                        id="add-to-cart-button"
                                        type="submit"
                                        disabled
                                    >
                                        Thêm vào giỏ
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="note out">
                                Sản phẩm hiện chưa có biến thể đang bán.
                            </p>
                        <?php endif; ?>

                        <?php if ($currentUser === null): ?>
                            <p class="note"><a href="index.php?route=login">Đăng nhập</a> để thêm sản phẩm vào giỏ hàng.</p>
                        <?php endif; ?>
                        <a class="btn" href="index.php?route=products#products">Khám phá thêm sản phẩm</a>
                        </div>
                    </div>
                </div>
            </section>
        <?php elseif ($productId !== null): ?>
            <section><div class="c empty"><h1>Không tìm thấy sản phẩm</h1><a class="btn" href="index.php?route=products#products">Quay lại cửa hàng</a></div></section>
        <?php elseif ($view !== 'shop'): ?>
            <section class="hero" id="home">
                <div class="c hero-layout">
                    <div class="hero-copy">
                        <p class="eyebrow">FLORA · BỘ SƯU TẬP MỚI</p>
                        <h1>Một mùa hoa<br>trăm cách phối</h1>
                        <p>Những thiết kế tinh giản cho phong cách riêng của bạn thanh lịch mỗi ngày, tự tin trong mọi khoảnh khắc</p>
                        <a class="btn pri" href="index.php?route=products#products">
                            <span>Xem tất cả sản phẩm</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                    <div class="hero-visual hero-visual-single">
                        <div class="hero-image hero-image-primary">
                            <img src="assets/images/flora-hero-model.png"
                                alt="Người mẫu trong áo choàng đen trắng và quần ống rộng màu đỏ rượu" fetchpriority="high">
                        </div>
                    </div>
                </div>
            </section>

            <section class="reveal-on-scroll">
                <div class="c">
                    <div class="hd"><h2>Sản phẩm nổi bật</h2><a href="index.php?route=products#products">Xem tất cả →</a></div>
                    <?php if ($featured): ?>
                        <div class="grid"><?php foreach ($featured as $product) { flora_render_card($product); } ?></div>
                    <?php else: ?>
                        <p class="empty">Chưa có sản phẩm nổi bật.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="reveal-on-scroll">
                <div class="c">
                    <div class="hd"><h2>Sản phẩm mới</h2><a href="index.php?route=products#products">Khám phá cửa hàng -></a></div>
                    <?php if ($newest): ?>
                        <div class="grid"><?php foreach ($newest as $product) { flora_render_card($product); } ?></div>
                    <?php else: ?>
                        <p class="empty">Chưa có sản phẩm mới</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($detail === null && $view === 'shop'): ?>
            <section class="reveal-on-scroll" id="products">
                <div class="c">
                    <div class="hd">
                        <h2><?= $search !== '' ? 'Kết quả tìm kiếm' : 'Tất cả sản phẩm' ?></h2>
                        <span class="result-count"><?= $catalog['total'] ?> sản phẩm</span>
                    </div>
                    <?php if ($search !== ''): ?>
                        <p class="search-summary">Từ khóa: “<?= flora_e($search) ?>”</p>
                    <?php endif; ?>
                    <div class="pills">
                        <a class="<?= $category === '' ? 'on' : '' ?>" href="index.php?route=products#products">Tất cả</a>
                        <a class="<?= $category === 'nam' ? 'on' : '' ?>" href="index.php?route=products&amp;category=nam#products">Nam</a>
                        <a class="<?= $category === 'nu' ? 'on' : '' ?>" href="index.php?route=products&amp;category=nu#products">Nữ</a>
                    </div>
                    <nav class="pills" aria-label="Danh mục con">
<?php foreach ($categories as $c): if ($category !== '' && $c['root_name'] !== ($category === 'nam' ? 'Thời trang nam' : 'Thời trang nữ')) continue; ?>
<a href="index.php?<?= flora_e(http_build_query(['route'=>'products','category'=>$category,'category_id'=>$c['id']])) ?>#products" class="<?= $categoryId === (int)$c['id'] ? 'on' : '' ?>"><?= flora_e($c['name']) ?> · <?= $c['root_name']==='Thời trang nam'?'Nam':'Nữ' ?></a>
<?php endforeach; ?>
</nav>
                    <form class="bar" method="get" action="index.php#products">
                        <input type="hidden" name="route" value="products">
                        <?php if ($categoryId !== null): ?><input type="hidden" name="category_id" value="<?= $categoryId ?>"><?php endif; ?>
                        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= flora_e($category) ?>"><?php endif; ?>
                        <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= flora_e($search) ?>"><?php endif; ?>
                        <div><label for="min-price">Giá từ</label><input class="fc" id="min-price" name="min_price" type="number" min="0" step="10000" value="<?= $minPrice === null ? '' : $minPrice ?>"></div>
                        <div><label for="max-price">Đến</label><input class="fc" id="max-price" name="max_price" type="number" min="0" step="10000" value="<?= $maxPrice === null ? '' : $maxPrice ?>"></div>
                        <div><label for="sort">Sắp xếp</label>
                            <select class="fc" id="sort" name="sort">
                                <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Mới nhất</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                            </select>
                        </div>
                        <button class="btn" type="submit">Lọc sản phẩm</button>
                        <a class="btn reset-link" href="index.php?route=products#products">Xóa bộ lọc</a>
                    </form>
                    <?php if ($catalog['products']): ?>
                        <div class="grid">
                            <?php foreach ($catalog['products'] as $product) { flora_render_card($product); } ?>
                        </div>
                        <?php if ($pageCount > 1): ?>
                            <nav class="pg" aria-label="Phân trang sản phẩm">
                                <?php for ($number = 1; $number <= $pageCount; $number++): ?>
                                    <?php $pageUrl = 'index.php?' . http_build_query($queryParams + ['page' => $number]) . '#products'; ?>
                                    <a class="btn <?= $number === $page ? 'on' : '' ?>" href="<?= flora_e($pageUrl) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty"><h3>Không tìm thấy sản phẩm</h3><p>Thử từ khóa khác hoặc xóa bộ lọc giá.</p></div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
<style>
.flora-thumbnails { display:flex; flex-wrap:wrap; gap:10px; margin-top:12px; }
.flora-thumbnail { width:68px; height:85px; padding:2px; background:#fff; border:1px solid #ddd; cursor:pointer; }
.flora-thumbnail img { width:100%; height:100%; object-fit:cover; }
.flora-thumbnail:hover, .flora-thumbnail:focus-visible { border-color:#583b25; }
</style>
<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
