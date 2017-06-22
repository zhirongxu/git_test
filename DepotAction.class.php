<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ I CAN DO IT WITH MY BEST ]
// +----------------------------------------------------------------------
// | Copyright (c) 2012-2016  由你商城
// +----------------------------------------------------------------------
// | Author: 龙衡 <zuiaisansi@163.com>
// +----------------------------------------------------------------------
// | DATE: 2017年3月2日下午4:28:09
// +----------------------------------------------------------------------
// $id: 仓储控制器
// +----------------------------------------------------------------------
namespace Admin\Action;
use Shop\Model\StockUpModel;
use Admin\Model\OrdersModel;
use Api\Model\OrderGoodsModel;
use Admin\Model\StockGoodsModel;
use Admin\Model\DownGoodsModel;
use Shop\Model\StockDownModel;
use Api\Model\LogOrdersModel;
use Api\Model\GoodsModel;
use Api\Model\GoodsAttributesModel;
use Api\Model\GoodsCatsModel;
use Shop\Action\GoodsCatsAction;
/**
+------------------------------------------------------------------------------
*  ClassName 仓储控制器
+------------------------------------------------------------------------------
* @category  Think
* @author    龙衡
* @date      2017年3月2日下午4:28:51
* @version   1.0
+------------------------------------------------------------------------------
*/
class DepotAction extends BaseAction {
        
    /**
    +----------------------------------------------------------
    * 功能：备货单号列表
    +----------------------------------------------------------
    *日期：2017年3月2日下午4:33:57
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function stock() {
        $this->isLogin();
        $this->checkPrivelege('bhgl_00');
        $status     =   I('post.status',-9);
        $startTime  =   I('post.startTime','');
        $endTime    =   I('post.endTime','');
        $billNo     =   I('post.billNo','');
        
        $this->assign('status',$status);
        $this->assign('startTime',$startTime);
        $this->assign('endTime',$endTime);
        $this->assign('billNo',$billNo);
        
        $startTime  &&  $startTime=strtotime($startTime);
        $endTime    &&  $endTime=strtotime($endTime);
        $condition=[
            'status'        =>  $status,
        ];
        $billNo     &&  $condition['billNo']=['LIKE','%'.$billNo.'%'];
        $startTime  &&  $condition['createTime'][]=['EGT',$startTime];
        $endTime    &&  $condition['createTime'][]=['LT',$endTime]; 
        if($condition['status']==-9){
            //unset($condition['status']);
            $condition['status']=['ELT',2];
        }
        $stockUp= new StockUpModel();
        $data= $stockUp->getList($condition);
        $this->assign('stock',$data);

        $this->display('/depot/stock');
    }
    /**
    +----------------------------------------------------------
    * 功能：生成备货单
    +----------------------------------------------------------
    *日期：2017年3月2日下午5:06:35
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function createPick() {
        $stockUp= new StockUpModel();
        //$orderArr   =   I('post.orderId',[]);
        $starTime   =   I('post.startTime',0);
        $endTime    =   I('post.endTime',0);
        $shopId     =   I('post.shopId',11);
        $starTime || $starTime=strtotime(date('Y-m-d'));
        $endTime  || $endTime=time();
        $sid=0;
        $flag=0;
        $orders     =   new OrdersModel();
        $condition=[
            
            'shopId'        =>  $shopId,
            'orderStatus'   =>  0,
            'isStockUp'     =>  0,
            'orderFlag'     =>  1,
            'source'        =>  0,
        ];
        $orderArr   = $orders->getAllOrderIds($condition);
        if(!empty($orderArr)){
            /*===============备货表*================================*/
            $orderGoods= new OrderGoodsModel();
            $ordersCount=count($orderArr); //订单数量
            $count=$stockUp->getNum(date('Y-m-d'));
            $count+=1;
            $goodsCount= $orderGoods->getGoodsCount($orderArr);//商品数量          
            $var=[
                'billNo'        =>  'BH'.date('YmdHi').mt_rand(1000,9999).$count,    
                'startTime'     =>  $starTime,
                'endTime'       =>  $endTime,
                'ordersCount'   =>  $ordersCount,
                'goodsCount'    =>  $goodsCount,
                'staffId'       =>  session('UNI_STAFF'),
                'shopId'        =>  $shopId,
                'createTime'    =>  time(),               
            ];
            $stockUp= new StockUpModel();
            $sid=$stockUp->add($var);             
            /*===============END备货表*=============================*/
            
            /*===============备货商品详情表*========================*/           
            $rows= $orderGoods->getGoodsByOrderIdNew($orderArr);
            $temp=[];
            foreach($rows as $v){
                $key=$v['goodsId'].'_'.$v['goodsAttrId'];
                if(!array_key_exists($key, $temp)){
                    $temp[$key]=[
                        'sid'           =>  $sid,
                        'billNo'        =>  $var['billNo'],
                        'goodsId'       =>  $v['goodsId'],
                        'goodsNums'     =>  $v['goodsNums'],
                        'goodsPrice'    =>  $v['goodsPrice'],
                        'goodsAttrId'   =>  $v['goodsAttrId'],
                        'goodsAttrName' =>  $v['goodsAttrName']?$v['goodsAttrName']:'',
                        'goodsName'     =>  $v['goodsName'],
                    ];
                }
                else{
                    $temp[$key]['goodsNums']+=$v['goodsNums']; 
                }                
            }
            $list= array_values($temp);
            $flag= (new StockGoodsModel())->addAll($list);
            
            /*===============END备货商品详情表*=====================*/
            
            /*==============更新订单表订单状态,备货状态==============*/
            $map=['orderId'=>['IN',$orderArr]];
            $orders->where($map)->setField(['orderStatus'=>1,'isStockUp'=>1]);
            
            /*==================更新订单商品表备货单号============================*/
            $orderGoods->where($map)->setField('stockUpId',$sid);
        }
        $flag || error('没有订单啦');
        ok(['flag'=>$flag]);
    }
    
    /**
    +----------------------------------------------------------
    * 功能：查看备货单号的商品详情
    +----------------------------------------------------------
    *日期：2017年3月7日下午3:19:18
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function queryStock() {

        $stockGoods= new StockGoodsModel();
        $billNo= I('post.billNo','');
        $sid= I('post.sid',0);
        $map=[];
        $sid    &&  $map['sid']=$sid;
        $billNo &&  $map['billNo']=$billNo;
        $data=$stockGoods->getSku($map);
        ok($data);
    }
    /**
    +----------------------------------------------------------
    * 功能：发货详情
    +----------------------------------------------------------
    *日期：2017年3月10日上午10:08:42
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function sendDetail() {
        $this->isLogin();
        $this->checkPrivelege('fhgl_00');
        $id         =   I('get.id',0);
        $orderNo    =   I('post.orderNo','');
        $userName   =   I('post.userName','');
        $userPhone  =   I('post.userPhone','');
        $startTime  =   I('post.startTime','');
        $endTime    =   I('post.endTime','');
        $uName      =   I('post.uName','');
        $orderStatus=   I('post.orderStatus');
        $this->assign('orderNo',$orderNo);
        $this->assign('userName',$userName);
        $this->assign('userPhone',$userPhone);
        $this->assign('startTime',$startTime);
        $this->assign('endTime',$endTime);
        $this->assign('uName',$uName);
        
        $orderGoods= new OrderGoodsModel();
        $orderIdArr=$orderGoods->getOrdersByStockUpId($id);
        $condition=[];
        $condition['orderId']=['IN',$orderIdArr];
        $orderNo    &&  $condition['orderNo']       =   ['LIKE','%'.$orderNo.'%'];
        $userName   &&  $condition['userName']      =   ['LIKE','%'.$userName.'%'];
        $userPhone  &&  $condition['userName']      =   ['LIKE','%'.$userPhone.'%'];
        $uName      &&  $condition['uName']         =   ['LIKE','%'.$uName.'%'];
        $orderStatus&&  $condition['orderStatus']   =   $orderStatus;
        $startTime  &&  $condition['createTime'][]  =   ['EGT',strtotime($startTime)];
        $endTime    &&  $condition['createTime'][]  =   ['LT',strtotime($endTime)];       
        $orders= new OrdersModel();
        $list=$orders->getOrderList($condition);
        $this->assign('order',$list);
        $this->display('/depot/order');
                        
    }
    /**
    +----------------------------------------------------------
    * 功能：订单详情
    +----------------------------------------------------------
    *日期：2017年3月10日下午3:38:31
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function OrderDetail() {
        $id=I('get.id',0);
        $orders= new ordersModel();
        $info=$orders->getOneOrderAllInfo($id);
        $this->assign('order',$info);
        $this->display('/depot/detail');
    }
    /**
    +----------------------------------------------------------
    * 功能：发货
    +----------------------------------------------------------
    *日期：2017年3月7日下午3:56:35
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function sendStock() {
        $s=session('UNI_STAFF');
        $orderId    =   I('post.id',0);
        $expressCom =   I('post.expressCom','');
        $expressNu  =   I('post.expressNu','');
        $update=[
            'expressCom'    =>  $expressCom,
            'expressNu'     =>  $expressNu,
            'orderStatus'   =>  3,
        ];
        //更新订单
        $flag=(new OrdersModel())->where(['orderId'=>$orderId])->setField($update);
        //生成订单日志
        $var=[
            'orderId'       =>  $orderId,
            'logContent'    =>  '订单已发货,等待用户收货',
            'logUserId'     =>  $s['staffId'],
            'logType'       =>  1,
            'logTime'       =>  date('Y-m-d H:i:s'),
        ];
        $logId=(new LogOrdersModel())->add($var);
        $logId && ok(['logId'=>$logId]);
        error('发货失败');    
    } 
    
    /**
    +----------------------------------------------------------
    * 功能：完成备货单号
    +----------------------------------------------------------
    *日期：2017年3月7日下午3:57:43
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function finishStock() {
        $s=session('UNI_STAFF');
        $id=I('post.sid');
        $where=[];
        $update=[];
        $where['status']=0;
        $where['id']=$id;
        is_array($id) && $where['id']=array('IN',$id);
        $update['status']=2;
        $update['doneId']=$s['staffId'];
        $update['completeTime']=time();
        $stockUp= new StockUpModel();
        $flag=$stockUp->updateStatus($where,$update);
        ok(['flag'=>$flag]);
  
    }
    /**
    +----------------------------------------------------------
    * 功能：发货管理
    +----------------------------------------------------------
    *日期：2017年3月9日下午9:14:49
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function send() {
        $this->isLogin();
        $this->checkPrivelege('fhgl_00');
        $status     =   I('post.status',9);
        $startTime  =   I('post.startTime','');
        $endTime    =   I('post.endTime','');
        $billNo     =   I('post.billNo','');
        
        $this->assign('status',$status);
        $this->assign('startTime',$startTime);
        $this->assign('endTime',$endTime);
        $this->assign('billNo',$billNo);
        
        $startTime  &&  $startTime=strtotime($startTime);
        $endTime    &&  $endTime=strtotime($endTime);
        $condition=[
            'status'        =>  $status,
        ];
        $billNo     &&  $condition['billNo']=['LIKE','%'.$billNo.'%'];
        $startTime  &&  $condition['createTime'][]=['EGT',$startTime];
        $endTime    &&  $condition['createTime'][]=['LT',$endTime]; 
        if($condition['status']==9){
           // unset($condition['status']);
           $condition['status']=['EGT',2];
        }
        $stockUp= new StockUpModel();
        $data= $stockUp->getList($condition);
        $this->assign('stock',$data);

        $this->display('/depot/send');
    }
    /**
    +----------------------------------------------------------
    * 功能：入库列表
    +----------------------------------------------------------
    *日期：2017年3月12日下午9:31:27
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function entry() {
        $this->isLogin();
        $this->checkPrivelege('rkgl_00');
        $startTime  =   I('post.startTime','');
        $endTime    =   I('post.endTime','');
        $billNo     =   I('post.billNo','');
        
        $this->assign('startTime',$startTime);
        $this->assign('endTime',$endTime);
        $this->assign('billNo',$billNo);
        
        $startTime  &&  $startTime=strtotime($startTime);
        $endTime    &&  $endTime=strtotime($endTime);
        $condition=[
            'type'  => 1,
        ];
        $billNo     &&  $condition['billNo']=['LIKE','%'.$billNo.'%'];
        $startTime  &&  $condition['createTime'][]=['EGT',$startTime];
        $endTime    &&  $condition['createTime'][]=['LT',$endTime];
        $stockDown= new StockDownModel();
        $data=$stockDown->getList($condition);
        $this->assign('entry',$data);
        $this->display('/depot/entry');
    }
    /**
    +----------------------------------------------------------
    * 功能：新增入库
    +----------------------------------------------------------
    *日期：2017年3月12日下午10:02:20
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function addNewEntry() {
        $s=session('UNI_STAFF');
        $goodsId        =   I('post.goodsId',[]);
        $goodsPrice     =   I('post.goodsPrice',[]);
        $goodsNums      =   I('post.goodsNums',[]);
        $goodsName      =   I('post.goodsName',[]);
        $goodsAttrName  =   I('post.goodsAttrName',[]);
        $stockDown=  new StockDownModel();
        $count=$stockDown->getNum(date('Y-m-d'),1);
        $count+=1;
        $billNo ='RK'.date('YmdHi').mt_rand(1000,9999).$count;
        $var=[
            'billNo'        =>  $billNo,
            'goodsCount'    =>  array_sum($goodsNums),         
            'type'          =>  1,
            'status'        =>  1,
            'staffId'       =>  $s['staffId'],
            'createTime'=>  time(),
        ];
        //入库单
        $sid=$stockDown->add($var);
        //入库明细
        $list=[];
        $temp=[];
        foreach($goodsId as $k=>$v){
            $temp=explode('_',$v);
            $temp[1] || $temp[1]=0;
            $list[$k]['goodsId']       =   $temp['0'];
            $list[$k]['goodsAttrId']   =   $temp['1'];
            $list[$k]['goodsPrice']    =   $goodsPrice[$k];
            $list[$k]['goodsNums']     =   $goodsNums[$k];
            $list[$k]['goodsAttrName'] =   $goodsAttrName[$k];
            $list[$k]['goodsName']     =   $goodsName[$k];
            $list[$k]['billNo']        =   $billNo;
            $list[$k]['sid']           =   $sid;
            
            //暂时写在循环里 增加库存       
            if($temp['1']){
                $goodsAttr= new GoodsAttributesModel();
                $goodsAttr->where(['id'=>$temp[1]])->setInc('attrStock',$goodsNums[$k]);
            }
            else{
                $goods= new GoodsModel();
                $goods->where(['goodsId'=>$temp[0]] )->setInc('goodsStock',$goodsNums[$k]);
            }
            
        }
        $flag=(new DownGoodsModel())->addAll($list);
        $flag || error('失败');
        $var['id']=$sid;
        $var['dateTime']=date('Y-m-d H :i:s',$var['createTime']);
        $var['staffName']=$s['staffName'];
        ok($var);             
    }
    /**
    +----------------------------------------------------------
    * 功能：查看入库
    +----------------------------------------------------------
    *日期：2017年3月13日下午4:07:55
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function queryEntry() {
        $downGoods= new DownGoodsModel();
        $billNo= I('post.billNo','');
        $sid= I('post.sid',0);
        $map=[];
        $sid    &&  $map['sid']=$sid;
        $billNo &&  $map['billNo']=$billNo;
        $data=$downGoods->getSku($map);
        ok($data);
    }
    /**
    +----------------------------------------------------------
    * 功能：出库列表
    +----------------------------------------------------------
    *日期：2017年3月12日下午9:37:43
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function export() {
        $this->isLogin();
        $startTime  =   I('post.startTime','');
        $endTime    =   I('post.endTime','');
        $billNo     =   I('post.billNo','');
        
        $this->assign('startTime',$startTime);
        $this->assign('endTime',$endTime);
        $this->assign('billNo',$billNo);
        
        $startTime  &&  $startTime=strtotime($startTime);
        $endTime    &&  $endTime=strtotime($endTime);
        $condition=[
            'type'  => 0,
        ];
        $billNo     &&  $condition['billNo']=['LIKE','%'.$billNo.'%'];
        $startTime  &&  $condition['createTime'][]=['EGT',$startTime];
        $endTime    &&  $condition['createTime'][]=['LT',$endTime];
        $stockDown= new StockDownModel();
        $data=$stockDown->getList($condition);
        $this->assign('export',$data);        
        $this->display('/depot/export');
    }
    /**
     +----------------------------------------------------------
     * 功能：根据商品条形码获取商品详情
     +----------------------------------------------------------
     *日期：2016年12月21日上午10:57:45
     +----------------------------------------------------------
     *作者：龙衡<zuiaisansi@163.com>
     +----------------------------------------------------------
     * @参数    ： string
     * @返回值  ： string
     +----------------------------------------------------------
     */
    public function getGoodsInfoByBarcode() {
        $barcode=I('post.barcode','');
        $barcode ||  error('请输入商品条码');
        $goods= new GoodsModel();
        $info=$goods->getGoodsInfoByBarcode($barcode,11);
        $info || error('找不到这样的商品');
        $info['goods_num']=I('post.num');
        $info['goods_num'] || $info['goods_num']="1";
        ok($info);
    }
    /**
    +----------------------------------------------------------
    * 功能：新增出库
    +----------------------------------------------------------
    *日期：2017年3月12日下午10:03:01
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function addNewExport() {
        $s=session('UNI_STAFF');
        $goodsId        =   I('post.goodsId',[]);
        $goodsPrice     =   I('post.goodsPrice',[]);
        $goodsNums      =   I('post.goodsNums',[]);
        $goodsName      =   I('post.goodsName',[]);
        $goodsAttrName  =   I('post.goodsAttrName',[]);
        $stockDown=  new StockDownModel();
        $count=$stockDown->getNum(date('Y-m-d'),1);
        $count+=1;
        $billNo ='CK'.date('YmdHi').mt_rand(1000,9999).$count;
        $var=[
            'billNo'        =>  $billNo,
            'goodsCount'    =>  array_sum($goodsNums),         
            'type'          =>  0,
            'status'        =>  1,
            'staffId'       =>  $s['staffId'],
            'createTime'=>  time(),
        ];
        //出库单
        $sid=$stockDown->add($var);
        //入库明细
        $list=[];
        $temp=[];
        foreach($goodsId as $k=>$v){
            $temp=explode('_',$v);
            $temp[1] || $temp[1]=0;
            $list[$k]['goodsId']       =   $temp['0'];
            $list[$k]['goodsAttrId']   =   $temp['1'];
            $list[$k]['goodsPrice']    =   $goodsPrice[$k];
            $list[$k]['goodsNums']     =   $goodsNums[$k];
            $list[$k]['goodsAttrName'] =   $goodsAttrName[$k];
            $list[$k]['goodsName']     =   $goodsName[$k];
            $list[$k]['billNo']        =   $billNo;
            $list[$k]['sid']           =   $sid;
            
            //暂时写在循环里 减库存       
            if($temp['1']){
                $goodsAttr= new GoodsAttributesModel();
                $goodsAttr->where(['id'=>$temp[1]])->setDec('attrStock',$goodsNums[$k]);
            }
            else{
                $goods= new GoodsModel();
                $goods->where(['goodsId'=>$temp[0]] )->setDec('goodsStock',$goodsNums[$k]);
            }
            
        }
        $flag=(new DownGoodsModel())->addAll($list);
        $flag || error('失败');
        $var['id']=$sid;
        $var['dateTime']=date('Y-m-d H :i:s',$var['createTime']);
        $var['staffName']=$s['staffName'];
        ok($var);      
    }
    /**
    +----------------------------------------------------------
    * 功能：成员方法名
    +----------------------------------------------------------
    *日期：2017年3月13日下午5:21:04
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function queryExport() {
        $downGoods= new DownGoodsModel();
        $billNo= I('post.billNo','');
        $sid= I('post.sid',0);
        $map=[];
        $sid    &&  $map['sid']=$sid;
        $billNo &&  $map['billNo']=$billNo;
        $data=$downGoods->getSku($map);
        ok($data);
    }
    /**
    +----------------------------------------------------------
    * 功能：库存列表
    +----------------------------------------------------------
    *日期：2017年3月13日下午6:05:22
    +----------------------------------------------------------
    *作者：龙衡<zuiaisansi@163.com>
    +----------------------------------------------------------
    * @参数    ： string
    * @返回值  ： string
    +----------------------------------------------------------
    */
    public function all() {
       
       
        $barcode    =   I('post.barcode','');
        $goodsCatId   =   I('post.goodsCatId',0);
        $brandId    =   I('post.brandId',0);
        $goodsSn    =   I('post.goodsSn','');
        $goodsName  =   I('post.goodsName','');
        $this->assign('barcode',$barcode);
        $this->assign('goodsCatId',$goodsCatId);
        $this->assign('brandId',$brandId);
        $this->assign('goodsSn',$goodsSn);
        $this->assign('goodsName',$goodsName);
        //商品列表
        $goodsCat= (new GoodsCatsModel())->where(['parentId'=>1])->getField('catId,catName');
        p($goodsCat);exit;
        $condition=[];
        $barcode    &&  $condition['barcode']   =   ['LIKE','%'.$barcode.'%'];
        $goodsCatId &&  $condition['goodsCatId']=   $goodsCatId;
        $brandId    &&  $condition['brandId']   =   $brandId;
        $goodsSn    &&  $condition['goodsSn']   =   ['LIKE','%'.$goodsSn.'%'];
        $goodsName  &&  $condition['goodsName'] =   ['LIKE','%'.$goodsName.'%'];
        $goods= new GoodsModel();
        $list=$goods->getSku($condition);
        $this->assign('goods',$list);
        $this->display('/depot/all');
               
    }       
}