<?php
/**
 * Created by PhpStorm.
 * User: lsqi
 * Date: 2017/5/5
 * Time: 10:24
 */

namespace Admin\Action;


//App更新
use Admin\Model\VersionModel;
use Common\Util\ConfigUtils;

class AppUpdateAction extends BaseAction
{
    public function index()
    {
        $this->display('/app-update/index');
        echo 112;
    }

    //获取全部
    public function all()
    {
        $this->ajaxReturn((new VersionModel())->getAll());
    }

    //创建或更新
    public function update()
    {
        if (!$this->requireParam('type', 'update_type', 'version', 'url', 'desc')) {
            $this->ajaxReturn(['error' => '参数错误']);
        }
        $id = (int)$this->getParam('id');
        $type = (int)$this->getParam('type');
        $update_type = (int)$this->getParam('update_type');
        $version = $this->getParam('version');
        $url = $this->getParam('url');
        $desc = $this->getParam('desc');

        if (!$id) {
            VersionModel::singleton()->addVersion($type, $update_type, $version, $url, $desc);
        } else {
            VersionModel::singleton()->updateVersion($id, $type, $update_type, $version, $url, $desc);
        }
        $this->ajaxReturn(['ok' => true]);
    }

    //上传文件
    public function upload()
    {
        $sha1 = strtolower(I('request.sha1'));
        if (empty($_FILES['file']) || !$sha1) {
            $this->ajaxReturn(['error' => '参数错误']);
        }

        $file = $_FILES['file'];
        if ($file['error']) {
            $this->ajaxReturn(['error' => '上传失败']);
        }

        $filename = $file['name'];
        $dir = realpath(__DIR__ .  '/../../../Upload') . '/apk';
        if (!is_dir($dir)) {
            mkdir($dir) or $this->ajaxReturn(['error' => '创建目录失败']);
        }
        if (!is_writable($dir)) {
            $this->ajaxReturn(['error' => '目录没有写权限']);
        }
        $destination = $dir . '/' . $filename;

        $config = require __DIR__ . '/../../Api/Conf/config.php';

        $url = rtrim($config['SERVER_URL'], '/') . '/Upload/apk/' . $filename;

        if (file_exists($destination)) {
            if (strtolower(sha1_file($destination)) === $sha1) {
                $this->ajaxReturn(['url' => $url]);
            } else {
                $this->ajaxReturn(['error' => '文件已存在']);
            }
        }

        if (false === @move_uploaded_file($file['tmp_name'], $destination)) {
            $this->ajaxReturn(['error' => '移动文件失败']);
        }

        if (strtolower(sha1_file($destination)) === $sha1) {
            $this->ajaxReturn(['url' => $url]);
        } else {
            @unlink($destination);
            $this->ajaxReturn(['error' => '上传失败, sha1不匹配']);
        }
    }
}