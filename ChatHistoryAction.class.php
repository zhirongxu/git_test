<?php
/**
 * 聊天记录
 * Created by PhpStorm.
 * User: lsqi
 * Date: 2017/2/15
 * Time: 15:17
 */

namespace Admin\Action;


use Admin\Model\ServiceMsgModel;
use Admin\Model\ServiceUserModel;
use Think\Page;

class ChatHistoryAction extends BaseAction
{
    //列表
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('ltjl_00');

        $serviceUserId = I('serviceUserId');
        $startDate = I('startDate');
        $endDate = I('endDate');
        $customerName = I('customerName');

        $page = (new ServiceMsgModel())->getList($serviceUserId, $startDate, $endDate, $customerName);
        $pager = new Page($page['total'],$page['pageSize'], $_REQUEST);
        $page['pager'] = $pager->show();

        $this->assign('serviceUserId', $serviceUserId);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('customerName', $customerName);
        $this->assign('serviceUsers', (new ServiceUserModel())->getList());
        $this->assign('Page', $page);
        $this->display('/chathistory/list');
    }

    //删除
    public function delete()
    {
        $id = I('id');
        (new ServiceMsgModel())->deleteById($id);
        exit('ok');
    }
}