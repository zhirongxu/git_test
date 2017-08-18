<?php
/**
 * Created by PhpStorm.
 * User: lsqi
 * Date: 2017/2/13
 * Time: 16:02
 */

namespace Admin\Action;

//预约管理
use Admin\Service\Push;
use Checkout\Model\AppointModel;
use Checkout\Model\AppointProjectModel;
use Checkout\Model\ProjectModel;
use Checkout\Model\ShopModel;
use Checkout\Model\StaffModel;
use Checkout\Service\Appoint;
use Think\Page;

class AppointAction extends 
    use Appoint;1212

    //已预约
    public function appointed()
    {
        $this->isLogin();
        $this->checkPrivelege('kyygl_02');

        $query = I('query');
        $startDate = I('startDate');
        $endDate = I('endDate');
        $status = I('status');
        $shopId = I('shopId');
        $technicianId = I('technicianId');
        $scoreOp = I('scoreOp');
        $score = I('score'

        $page = AppointModel::singleton()->adminAppoint($query, $startDate, $endDate, $status, $shopId, $technicianId, $scoreOp, $score);
        $pager = new Page($page['total'], $page['pageSize'], $_REQUEST);
        $page['pager'] = $pager->show();
        $this->assign('Page', $page);

        $this->assign('query', $query);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('status', $status);
        $this->assign('shopId', $shopId);
        $this->assign('technicianId', $technicianId);
        $shopId and $this->assign('technicians', StaffModel::singleton()->getTechnician($shopId));
        $this->assign('scoreOp', $scoreOp);
        $this->assign('score', $score);
        $this->assign('shops', ShopModel::singleton()->getList());
        $this->display('/appoint/appointed');
    }

    //获取技师
    public function technician()
    {
        $shopId = I('shopId');
        $this->ajaxReturn(StaffModel::singleton()->getTechnician($shopId) ?: []);
    }

    //可预约
    public function project()
    {
        $this->isLogin();
        $this->checkPrivelege('kyygl_00');

        $customerName = I('customerName');
        $startDate = I('startDate');
        $endDate = I('endDate');

        $page = ProjectModel::singleton()->getAdminList($customerName, $startDate, $endDate);
        $pager = new Page($page['total'], $page['pageSize'], $_REQUEST);
        $page['pager'] = $pager->show();

        $this->assign('customerName', $customerName);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('Page', $page);
        $this->display('/appoint/project');
    }

    //预约确认
    public function confirm()
    {
        $this->isLogin();
        $this->checkPrivelege('yyqr_00');

        $query = I('query');
        $startDate = I('startDate');
        $endDate = I('endDate');
        $shopId = I('shopId');
        $technicianId = I('technicianId');

        $page = AppointModel::singleton()->adminAppoint($query, $startDate, $endDate, 1, $shopId, $technicianId, '', '');
        $pager = new Page($page['total'], $page['pageSize'], $_REQUEST);
        $page['pager'] = $pager->show();
        $this->assign('Page', $page);

        $this->assign('query', $query);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('shopId', $shopId);
        $this->assign('technicianId', $technicianId);
        $shopId and $this->assign('technicians', StaffModel::singleton()->getTechnician($shopId));
        $this->assign('shops', ShopModel::singleton()->getList());
        $this->display('/appoint/confirm');
    }

    //确认预约
    public function doConfirm()
    {
        $this->isAjaxLogin();
        $this->checkPrivelege('yyqr_00');

        $id = I('id');
        if ($id) {
            $n = AppointModel::singleton()->confirm($id);
            $n == 1 and $this->notify($id);
            exit('ok');
        }
    }

    //新增预约
    public function add()
    {
        $this->isLogin();
        $this->checkPrivelege('yyqr_01');

        $this->assign('shops', ShopModel::singleton()->getList());
        $this->display('appoint/add');
    }

    //新增预约
    public function create()
    {
        $this->isAjaxLogin();
        $this->checkPrivelege('yyqr_01');

        $data = [
            'customer_id' => I('customerId'),
            'customer_name' => I('customerName'),
            'customer_cellphone' => I('cellphone'),
            'technician_id' => I('technicianId'),
            'appoint_at' => strtotime(I('date') . ' ' . I('time')),
            'source' => 'BACKEND',
            'status' => AppointModel::WAIT_SERVE,
            'remark' => I('remark'),
            'shop_id' => I('shopId'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $projectId = I('projectId');
        $err = $this->createAppoint($data, [$projectId], $appointId);
        if ($err !== null)
            exit($err);

        $this->notify($appointId);
        
        exit('ok');
    }

    //获取顾客姓名
    public function customerName()
    {
        $cellphone = I('cellphone');
        $result = AppointModel::singleton()->getCustomerByPhone($cellphone);
        $this->ajaxReturn($result ?: ['error' => '没有查询到客户']);
    }

    //获取技师，顾客预约信息
    public function customerProject()
    {
        $customerId = I('customerId');
        $shopId = I('shopId');
        $this->ajaxReturn(AppointModel::singleton()->customerProject1($customerId, $shopId));
    }

    // 获取技师空闲时间
    public function idleTime()
    {

        $projectId = I('projectId');
        $technicianId = I('technicianId');
        $shopId = I('shopId');

        $idleTime = $this->getIdleTime([$projectId], $technicianId, $shopId);
        $result = [];
        foreach ($idleTime as $v) {
            $result[date('Y-m-d', $v)][] = date("H:i", $v);
        }
        $this->ajaxReturn($result);
    }

    //设置项目失效
    public function invalidProject()
    {
        $this->isAjaxLogin();
        $this->checkPrivelege('kyygl_02');

        $id = (int)I('id');
        $id and ProjectModel::singleton()->setInvalid($id);
        exit('ok');
    }

    //取消项目
    public function cancel()
    {
        $this->isAjaxLogin();
        $this->checkPrivelege('yyqr_03');

        $id = (int)I('id');
        $id and  AppointModel::singleton()->cancel($id, false);
        exit('ok');
    }
}