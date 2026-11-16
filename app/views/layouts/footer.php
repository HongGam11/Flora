    </main>

    <footer class="flora-site-footer">
        <div class="flora-footer-grid">
            <div class="flora-footer-brand-block">
                <a class="flora-footer-brand" href="/flora/index.php?route=home">FLORA</a>
                <p>Thời trang nam nữ tinh giản, dễ phối và phù hợp cho phong cách mỗi ngày.</p>
            </div>

            <nav class="flora-footer-column" aria-label="Khám phá Flora">
                <h2>Khám phá</h2>
                <a href="/flora/index.php?route=products">Tất cả sản phẩm</a>
                <a href="/flora/index.php?route=products&amp;category=nam">Thời trang nam</a>
                <a href="/flora/index.php?route=products&amp;category=nu">Thời trang nữ</a>
                <a href="/flora/index.php?route=login">Tài khoản</a>
            </nav>

            <div class="flora-footer-column">
                <h2>Quản trị cửa hàng</h2>
                <a class="flora-footer-admin" href="/flora/index.php?route=admin_dashboard">
                    Mở trang Admin <span aria-hidden="true">↗</span>
                </a>
            </div>
        </div>
        <div class="flora-footer-bottom">
            © <?= date('Y') ?> Flora cửa hàng thời trang.
        </div>
    </footer>

    <script src="/flora/assets/js/common.js" defer></script>
    <script src="/flora/assets/js/storefront.js" defer></script>
</body>
</html>
