<?php
 namespace Admin\Action;;
/**
 * ============================================================================
 * UNI测试版
 * @version 1.0 
 * @since 20160407
 * ============================================================================
 * 文章控制器
 */
class ArticlesAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function toEdit(){
		$this->isLogin();
	    $m = D('Admin/Articles');
    	$object = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('wzlb_02');
    		$object = $m->get();
    	}else{
    		$this->checkPrivelege('wzlb_01');
    		$object = $m->getModel();
    	}
    	$m = D('Admin/ArticleCats');
    	$this->assign('catList',$m->getCatLists());
    	$this->assign('object',$object);
		$this->view->display('/articles/edit');
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isAjaxLogin();
		$m = D('Admin/Articles');
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkAjaxPrivelege('wzlb_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkAjaxPrivelege('wzlb_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isAjaxLogin();
		$this->checkAjaxPrivelege('wzlb_03');
		$m = D('Admin/Articles');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
   /**
	 * 查看
	 */
	public function toView(){
		$this->isLogin();
		$this->checkPrivelege('wzlb_00');
		$m = D('Admin/Articles');
		if(I('id')>0){
			$object = $m->get();
			$this->assign('object',$object);
		}
		$this->view->display('/articles/view');
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('wzlb_00');
		$m = D('Admin/Articles');
    	$page = $m->queryByPage();
    	$pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
    	$page['pager'] = $pager->show();
    	foreach ($page['root'] as $key => $value) {
    			$m = D('Admin/ArticleContent');
    			if($m->getCount($value['articleId'])>0){
    				$page['root'][$key]['isEdit']=1;
    			}else{    				
    				$page['root'][$key]['isEdit']=0;
    			}
    		
    	}
		$this->assign('Page',$page);    	
    	$this->assign('articleTitle',I('articleTitle'));
        $this->display("/articles/list");
	}


	public function getABC(){
		$m = D('Admin/ArticleContent');
		$aid = 14;
		$m->getCount($aid);
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isAjaxLogin();
		$m = D('Admin/Articles');
		$list = $m->queryByList();
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
    /**
	 * 文章是否显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isAjaxLogin();
	 	$this->checkAjaxPrivelege('wzlb_02');
	 	$m = D('Admin/Articles');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }

    /**
     * 跳转到新增/修改文章细节
	 * 添加文章细节（插入articles_jump  artticles_contents）;
	 */


	 public function toEditDetails(){


	 	$id = (int)I('id',0);
	 	$this->isLogin();
	    $m = D('Admin/Articles');
    	$object = array();
    	//修改细节
    	if(I('isEdit',0)>0){   		
    		$this->checkPrivelege('wzlb_05');
    		$object = $m->getDetail($id);
    	}else{
    	//新增细节	    		
    		$this->checkPrivelege('wzlb_04');
    		$object = $m->getModel();
    	}
    	$this->assign('aid',$id);
    	$this->assign('isEdit',I('isEdit',0));
    	//print_r($object);

    	$this->assign('object',$object);
		$this->view->display('/articles/detail');	 	

	 }	

	/**
	 * 新增/修改修改文章细节操作
	 */
	public function editDetails(){
		//增加的数据格式
/*	    $data = array(
	    			array('type'=>1,'content'=>'hello1'),
	    			array('type'=>2,'content'=>'hello2'),
	    			array('type'=>3,'content'=>'hello3'),
	    			array('type'=>4,'content'=>7),
	    			array('type'=>1,'content'=>'hello4'),
	    			array('type'=>2,'content'=>'hello5'),
	    			array('type'=>2,'content'=>'hello6'),
	    	);*/
	   
	    $data =I('data');	   
	    $id = (int)I('id',0);
	    $isEdit = I('isEdit',0);
		$this->isAjaxLogin();
		$m = D('Admin/Articles');
    	$rs = array();
    	//修改细节
    	if(I('isEdit',0)>0){
    		$this->checkAjaxPrivelege('wzlb_05');
    		$rs = $m->editDetail($data,$id);
    	}else{
    	//新增细节	
    		$this->checkAjaxPrivelege('wzlb_04');
    		$rs = $m->addDetail($data,$id);

    	}
    	$this->ajaxReturn($rs);
	}	 	


	public function articlesGoods(){
				$this->isLogin();
				$this->checkPrivelege('splb_00');
				//获取地区信息
				$m = D('Admin/Areas');
				$this->assign('areaList',$m->queryShowByList(0));
				//获取商品分类信息
				$m = D('Admin/GoodsCats');
				$this->assign('goodsCatsList',$m->queryByList());
				$m = D('Admin/Goods');
		    	$page = $m->queryByPageArticles();
		    	$pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
		    	$page['pager'] = $pager->show();
		    	$this->assign('Page',$page);
		    	$this->assign('shopName',I('shopName'));
		    	$this->assign('goodsName',I('goodsName'));
		    	$this->assign('areaId1',I('areaId1',0));
		    	$this->assign('areaId2',I('areaId2',0));
		    	$this->assign('goodsCatId1',I('goodsCatId1',0));
		    	$this->assign('goodsCatId2',I('goodsCatId2',0));
		    	$this->assign('goodsCatId3',I('goodsCatId3',0));
		    	$this->assign('isAdminBest',I('isAdminBest',-1));
		    	$this->assign('isAdminRecom',I('isAdminRecom',-1));
		        $this->display("/articles/goodsList");

	}





 
};
?>