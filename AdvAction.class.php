<?php
/**

 * 
 * 
 * by xu
 */

namespace Admin\Action;

//门店广告

use Checkout\Model\ShopModel;
use Think\Page;
use Admin\Model\AdvModel;

class AdvAction extends BaseAction
{


    /**
     * 分页查询
     */
    public function index(){
        $this->isLogin();
        $this->checkPrivelege('syjlb_00');
        $m = D('Admin/Adv');
        $page = $m->queryByPage();
        foreach ($page['root'] as $key => $value) {
            $page['root'][$key]['shopId']=ShopModel::singleton()->getShopInfo($value['shopId'])['name'];
            $page['root'][$key]['createTime']=date('Y-m-d H:i:s',$value['createTime']);
        }
        $pager = new \Think\Page($page['total'],$page['pageSize'],I());
        $page['pager'] = $pager->show();   
        $this->assign('advName',I('advName'));
        $this->assign('startDate',I('startDate'));
        $this->assign('endDate',I('endDate'));

        $this->assign('Page',$page);
        $this->display("/adv/list");
    }
    /**
     * 列表查询
     */
    public function queryByList(){
        $this->isAjaxLogin();
        $m = D('Admin/Adv');
        $list = $m->queryByList();
        $rs = array();
        $rs['status'] = 1;
        $rs['list'] = $list;
        $this->ajaxReturn($rs);
    }



    /**
     * 跳到新增/编辑页面
     */
    public function toEdit(){
        $this->isLogin();
        $m = D('Admin/Adv');
        $object = array();
        if(I('id',0)>0){
            $this->checkPrivelege('syjlb_02');
            $object = $m->get();
        }else{
            $this->checkPrivelege('syjlb_01');
            $object = $m->getModel();
        }
        $this->assign('shopList', ShopModel::singleton()->getList());
        $this->assign('object',$object);
        $this->view->display('/adv/edit');
    }
    /**
     * 新增/修改操作
     */
    public function edit(){
        $this->isAjaxLogin();
        $m = D('Admin/Adv');
        $rs = array();
        if(I('id',0)>0){
            $this->checkAjaxPrivelege('syjlb_02');
            $rs = $m->edit();
        }else{
            $this->checkAjaxPrivelege('syjlb_01');
            $rs = $m->insert();
        }
        $this->ajaxReturn($rs);
    }
    /**
     * 删除操作
     */
    public function del(){
        $this->isAjaxLogin();
        $this->checkAjaxPrivelege('syjlb_03');
        $m = D('Admin/Adv');
        $rs = $m->del();
        $this->ajaxReturn($rs);
    }

    /**
     * 显示广告是否显示/隐藏
     */
     public function editiIsShow(){
        $this->isAjaxLogin();
        $this->checkAjaxPrivelege('syjlb_02');
        $m = D('Admin/Adv');
        $rs = $m->editiIsShow();
        $this->ajaxReturn($rs);
     }


}