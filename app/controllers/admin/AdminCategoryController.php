<?php
require_once __DIR__.'/Tv3AdminBase.php';
final class AdminCategoryController extends Tv3AdminBase {
 public function index():void {$this->guard();$this->page('categories/index',['rows'=>$this->service->categories()]);}
 public function form():void {$this->guard();$id=(int)($_GET['id']??0);$this->page('categories/form',['item'=>$id?$this->service->category($id):null,'roots'=>array_filter($this->service->categories(),fn($c)=>$c['parent_id']===null)]);}
 public function save():void {$this->guard();$this->handle(function(){$this->post();$this->service->saveCategory(!empty($_POST['id'])?(int)$_POST['id']:null,(int)($_POST['parent_id']??0),(string)($_POST['name']??''),(string)($_POST['status']??''));$this->redirect('admin/categories');});}
}
