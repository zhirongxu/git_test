<?php
/**
 * Created by PhpStorm.
 * User: lsqi
 * Date: 2017/2/8
 * Time: 10:52
 */

namespace Admin\Action;

//美容院管理
use Admin\Model\AreasModel;
use Checkout\Model\AccountModel;
use Checkout\Model\ShopModel;
use Checkout\Model\WorktimeModel;
use Think\Page;

class BeautyParlorAction extends BaseAction
{
    //美容院列表
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('mdlb_00');

        $areaModel = new AreasModel();
        $page = ShopModel::singleton()->queryByPage();
        foreach ($page['root'] as $k => $v) {
            $page['root'][$k]['address'] = $areaModel->getAddressStr($v['province'], $v['city'], $v['district']) . $v['address'];
        }

        $pager = new Page($page['total'],$page['pageSize'], $_REQUEST);
        $page['pager'] = $pager->show();


        $this->assign('name',I('name'));
        $this->assign('province',I('province'));
        $this->assign('city',I('city'));
        $this->assign('district',I('district'));
        $this->assign('provinces', $areaModel->getList(0));
        I('province') and $this->assign('cities', $areaModel->getList(I('province')));
        I('city') and $this->assign('districts', $areaModel->getList(I('city')));
        $this->assign('Page', $page);
        $this->display("/beautyparlor/list");
    }

    //添加美容院
    public function add()
    {
        $this->isLogin();
        $this->checkPrivelege('mdlb_01');

        $this->assign('parentShops', ShopModel::singleton()->getParentShopList());
        $this->assign('provinces', (new AreasModel())->getList(0));
        $this->display("/beautyparlor/add");
    }

    //添加美容院
    public function create()
    {
        $this->isLogin();
        $this->checkPrivelege('mdlb_01');

        //登陆账号密码
        $account = $_POST['account'];
        $password = $_POST['password'];
        if (!$account || !$password) exit('登录账号或密码不能为空');

        //班次数据
        $wtName = $_POST['wt_name'];
        $wtStart = $_POST['wt_start'];
        $wtEnd = $_POST['wt_end'];
        $worktime = [];
        $len = count($wtName);
        for ($i = 0; $i < $len; $i++) {
            if (!$wtName[$i] || !$wtStart[$i] || !$wtEnd[$i]) exit('班次数据错误');
            $worktime[] = ['name' => $wtName[$i], 'start' => $wtStart[$i], 'end' => $wtEnd[$i]];
        }
        if (!$worktime) exit('班次数据错误');

        isset($_POST['interval']) and $_POST['interval'] *= 60;
        isset($_POST['step']) and $_POST['step'] *= 60;
        $_POST['created_at'] = date('Y-m-d H:i:s');

        $model = ShopModel::singleton();
        $model->startTrans();

        $model->create() or exit($model->getError());
        $shopId = $model->add();
        if ($shopId === false) exit('添加失败');

        $rs = AccountModel::singleton()->saveAccount($shopId, $account, $password);
        if ($rs !== true) exit($rs);

        if (WorktimeModel::singleton()->addWorktime($shopId, $worktime) === false) exit('保存班次失败');

        $model->commit();
        exit('ok');
    }

    //编辑美容院
    public function edit()
    {
        $this->isLogin();
        $this->checkPrivelege('mdlb_02');

        $id = I('id');
        $model = ShopModel::singleton()->findOrFail($id);

        $accountRow = AccountModel::singleton()->getFirstAccount($model['id']);
        if ($accountRow) {
            $model['account'] = $accountRow['account'];
        }

        $areaModel = new AreasModel();

        $this->assign('model', $model);
        $this->assign('parentShops', ShopModel::singleton()->getParentShopList());
        $this->assign('provinces', $areaModel->getList(0));
        $this->assign('cities', $areaModel->getList($model['province']));
        $this->assign('districts', $areaModel->getList($model['city']));
        $this->assign('worktime', ShopModel::singleton()->getWorkTime($id));
        $this->display("/beautyparlor/add");
    }

    //编辑美容院
    public function update()
    {
        $this->isLogin();
        $this->checkPrivelege('mdlb_02');

        $account = $_POST['account'];
        $password = $_POST['password'];
        if (!$account) exit('登录账号不能为空');

        $shopId = $_POST['id'];
        //班次数据
        $wtId = $_POST['wt_id'];
        $wtName = $_POST['wt_name'];
        $wtStart = $_POST['wt_start'];
        $wtEnd = $_POST['wt_end'];
        $worktime = [];
        $len = count($wtName);
        for ($i = 0; $i < $len; $i++) {
            if (!$wtName[$i] || !$wtStart[$i] || !$wtEnd[$i]) exit('班次数据错误');
            $tmp = ['shop_id' => $shopId, 'name' => $wtName[$i], 'start' => $wtStart[$i], 'end' => $wtEnd[$i]];
            $wtId[$i] and $tmp['id'] = $wtId[$i];
            $worktime[] = $tmp;
        }
        if (!$worktime) exit('班次数据错误');

        isset($_POST['interval']) and $_POST['interval'] *= 60;
        isset($_POST['step']) and $_POST['step'] *= 60;

        $model = ShopModel::singleton();
        $model->startTrans();

        $model->create() or exit($model->getError());
        if ($model->save() === false) exit('保存失败');

        $rs = AccountModel::singleton()->updateFirstAccount($_POST['id'], $account, $password);
        if ($rs !== true) exit($rs);

        WorktimeModel::singleton()->updateWorktime($shopId, $worktime);

        $model->commit();
        exit('ok');
    }

    //删除美容院
    public function delete()
    {
        $this->isLogin();
        $this->checkPrivelege('mdlb_03');

        $n = 0;
        $id = I('id') and $n = ShopModel::singleton()->softDelete($id);
        if ($n == 1) exit('ok');
    }

    public function area()
    {
        $this->ajaxReturn((new AreasModel())->getList(I('parent')));
    }
}