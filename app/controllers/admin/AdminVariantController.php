<?php
require_once __DIR__.'/Tv3AdminBase.php';
final class AdminVariantController extends Tv3AdminBase {
 public function index():void {$this->guard();$id=$this->queryId('product_id');$this->page('variants/index',['product'=>$this->service->product($id),'rows'=>$this->service->variants($id)]);}
 public function form():void {$this->guard();$product=$this->queryId('product_id');$id=(int)($_GET['id']??0);$this->page('variants/form',['product'=>$this->service->product($product),'item'=>$id?$this->service->variant($id):null,'sizes'=>$this->service->sizes(),'colors'=>$this->service->colors()]);}
 public function save():void {$this->guard();$this->handle(function(){$this->post();$p=$this->id('product_id');$this->service->saveVariant(!empty($_POST['id'])?(int)$_POST['id']:null,$p,$this->id('size_id'),$this->id('color_id'),(int)($_POST['original_price']??0),(string)($_POST['status']??''));$this->redirect('admin/variants',['product_id'=>$p]);});}
 public function stock():void {$this->guard();$this->handle(function(){$this->post();$p=$this->id('product_id');$delta=filter_var($_POST['delta']??null,FILTER_VALIDATE_INT);if($delta===false)throw new DomainException('Số lượng phải là số nguyên');$this->service->adjustStock($this->id('variant_id'),$delta);$this->redirect('admin/variants',['product_id'=>$p]);});}
}
