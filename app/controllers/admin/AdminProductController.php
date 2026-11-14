<?php
require_once __DIR__.'/Tv3AdminBase.php';
final class AdminProductController extends Tv3AdminBase {
 public function index():void {$this->guard();$this->page('products/index',['rows'=>$this->service->products()]);}
 public function form():void {$this->guard();$id=(int)($_GET['id']??0);$this->page('products/form',['item'=>$id?$this->service->product($id):null,'categories'=>$this->service->childCategories()]);}
 public function save():void {$this->guard();$this->handle(function(){$this->post();$id=$this->service->saveProduct(!empty($_POST['id'])?(int)$_POST['id']:null,(int)($_POST['category_id']??0),(string)($_POST['name']??''),(string)($_POST['description']??''));$this->redirect('admin/products/form',['id'=>$id]);});}
 public function publish():void {$this->guard();$this->handle(function(){$this->post();$this->service->publish($this->id('id'),($_POST['status']??'')==='active');$this->redirect('admin/products');});}
 public function images():void {$this->guard();$id=$this->queryId('id');$this->page('products/images',['product'=>$this->service->product($id),'images'=>$this->service->images($id),'colors'=>$this->service->colors()]);}
 public function addImage():void {$this->guard();$this->handle(function(){$this->post();$id=$this->id('product_id');$this->service->addImage($id,!empty($_POST['color_id'])?(int)$_POST['color_id']:null,(string)($_POST['image_path']??''),isset($_POST['is_main']));$this->redirect('admin/products/images',['id'=>$id]);});}
 public function promotion():void {$this->guard();$this->handle(function(){$this->post();$id=$this->id('product_id');$this->service->savePromotion($id,(string)($_POST['discount_percent']??''),$_POST['discount_start_at']??null,$_POST['discount_end_at']??null,isset($_POST['is_featured']));$this->redirect('admin/products/form',['id'=>$id]);});}
 public function updateImage():void {$this->guard();$this->handle(function(){$this->post();$id=$this->id('product_id');$color=($_POST['color_id']??'')!==''?(int)$_POST['color_id']:null;$this->service->updateImage($id,$this->id('image_id'),$color,(string)($_POST['image_path']??''),(int)($_POST['sort_order']??0));$this->redirect('admin/products/images',['id'=>$id]);});}
 public function deleteImage():void {$this->guard();$this->handle(function(){$this->post();$id=$this->id('product_id');$this->service->deleteImage($id,$this->id('image_id'));$this->redirect('admin/products/images',['id'=>$id]);});}
 public function mainImage():void {$this->guard();$this->handle(function(){$this->post();$id=$this->id('product_id');$this->service->setMainImage($id,$this->id('image_id'));$this->redirect('admin/products/images',['id'=>$id]);});}
}
