<?php require_once APP_ROOT.'/app/views/admin/tv3_ui.php';tv3_start('Thông tin sản phẩm');?><form method="post" action="admin_tv3.php?route=admin/products/save"><?php tv3_csrf($csrf)?><input type="hidden" name="id" value="<?=e($item['id']??'')?>"><label>Danh mục con <select name="category_id" required><?php foreach($categories as $c):?><option value="<?=e($c['id'])?>" <?=((int)($item['category_id']??0)===(int)$c['id'])?'selected':''?>><?=e($c['name'])?></option><?php endforeach?></select></label><label>Tên <input name="name" required maxlength="200" value="<?=e($item['name']??'')?>"></label><label>Mô tả <textarea name="description" rows="5"><?=e($item['description']??'')?></textarea></label><button>Lưu</button></form><?php if($item):?>
<h2>Giảm giá và nổi bật (C9)</h2><p>Thời gian nhập theo UTC, dạng YYYY-MM-DD HH:MM. Để 0% để tắt giảm giá.</p>
<form method="post" action="admin_tv3.php?route=admin/products/promotion">
<?php tv3_csrf($csrf)?><input type="hidden" name="product_id" value="<?=e($item['id'])?>">
<label>Giảm giá (%) <input type="number" name="discount_percent" min="0" max="100" step="0.01" required value="<?=e($item['discount_percent']??0)?>"></label>
<label>Bắt đầu UTC <input type="datetime-local" name="discount_start_at" value="<?=e(!empty($item['discount_start_at'])?str_replace(' ','T',substr($item['discount_start_at'],0,16)):'')?>"></label>
<label>Kết thúc UTC <input type="datetime-local" name="discount_end_at" value="<?=e(!empty($item['discount_end_at'])?str_replace(' ','T',substr($item['discount_end_at'],0,16)):'')?>"></label>
<label><input type="checkbox" name="is_featured" value="1" <?=!empty($item['is_featured'])?'checked':''?>> Sản phẩm nổi bật</label>
<button>Lưu giảm giá / nổi bật</button></form>
<p>Ngày mở bán đầu tiên: <?=e($item['published_at']??'Chưa mở bán')?></p>
<?php endif;?>
<?php if($item):?><p><a href="admin_tv3.php?route=admin/variants&product_id=<?=e($item['id'])?>">Quản lý biến thể</a> | <a href="admin_tv3.php?route=admin/products/images&id=<?=e($item['id'])?>">Quản lý ảnh</a></p><?php endif;tv3_end();?>