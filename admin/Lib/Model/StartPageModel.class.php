<?php
/**
 * Created by PhpStorm.
 * User: john3
 * Date: 2015/3/31
 * Time: 11:25
 */

class StartPageModel extends Model{
    // 自动验证
    protected $_validate = array(
        array("title","require","标题必填",1),//添加标题
        array("start_time","require","开始时间必填",1),// 必须验证
        array("start_time","checkStarttime","开始时间必须大于当前时间",1,'callback',3),
        array("end_time","require","结束时间必填",1),
        array("end_time","checkEndtime","结束时间必须大于开始时间",1,'callback',3),
    );
    protected $_auto = array(
        array("imglist",'setImg',3,"callback"),// 设置图片
        array("start_time","strtotime",3,"function"),// 开始时间
        array("end_time","strtotime",3,"function")
    );
    // 时间检验
    public function checkStarttime(){
        $result = strtotime($_POST['start_time'])-time();
        if($result<=0){
            return false;
        }else{
            return true;
        }
    }
    // 时间检验
    public function checkEndtime(){
        $result = strtotime($_POST['end_time'])-strtotime($_POST['start_time']);
        if($result<=0){
            return false;
        }else{
            return true;
        }
    }
    // 设置启动图
    public function setImg(){
        $startPageSize = C("STARTPAGE_SIZE");
        // 判断选择的平台类型
        $os_type = $_POST['os_type'];

        switch($os_type){
            // 全部
            case 0:
                $post_name = array(1=>"ios",3=>"and");
                break;
            // IOS
            case 1:
                $post_name = array(1=>"ios");
                break;
            // Android
            case 3:
                $post_name = array(3=>"and");

                break;
        }
        //

        $serial = array();
        foreach($post_name as $key =>$value){
            // 处理图片序列化。
            $arr = array();
            // 构建 启动图数组
            foreach($_POST[$value] as $k =>$v){
                $i = $k+1;
                // 如果有没上传的字段，则返回假
                if(empty($v)){

                    $this->error = "请上传所有规格的{$value}图片";

                    return false;
                }
                $config = $startPageSize[$i][$key];
                $config = explode("x",$config);//拆分配置信息
                $arr[$i] = array(
                    "img_url"   =>$v,
                    "img_w" =>$config[0],
                    "img_h"=>$config[1]
                );
            }

            //序列化
            $serial[$value] = serialize($arr);
        }

        return $serial;

    }
    // 启动图列表
    public function getStartPageList($where='1=1',$order="id desc"){
        // 导入分页类
        import("ORG.Util.Page");
        // 获取页面对象。同时计算视频数
        $page = new Page($this->where($where)->count(),10);
        // 获取总的数量
        $data = $this->where($where)->limit($page->firstRow.','.$page->listRows)->order($order)->select();

        // 设置分页
        $data['page'] = $page->show();
        return $data;// 返出数据
    }
    /**
     * 批量删除数据入口
     * @param $where
     * @return mixed
     *
     */
    public function delAll($where){
        //
        /*
         * 需要删除视频评论 用户关注视频 用户喜欢视频 用户表视频数 话题视频关联表
         * 更改视频状态。
         */
        return $this->where($where)->delete();
    }
    // 保存修改后的启动图数据
    public function saveStartPage(){
        // 验证错误返出错误信息
        if(!$this->create()) return false;
        // 判断是否是该后台用户编辑
        // 如果有error 的时候
        if(!empty($this->error)){
            return false;
        }
        foreach($this->data['imglist'] as $k=>$v){
            $data = array(
                'id'       =>$this->data['id'],
                "title"     =>$this->data['title'],
                "os_type"   =>$k=="and"?3:1,//获得对应启动图系统类型
                "start_time"=>$this->data['start_time'],
                "end_time"  =>$this->data['end_time'],
                "img"       =>$v,
                "auid"      =>$_SESSION['admin_info']['id'],
                "create_time"=>time()
            );

            $this->save($data);

        }
        return true;
    }
    // 添加启动页数据
    public function addStartPage(){
        // 验证错误返出错误信息
        if(!$this->create()) return false;
        // 如果有error 的时候
        if(!empty($this->error)){
            return false;
        }
        foreach($this->data['imglist'] as $k=>$v){
            $data = array(
                "title"     =>$this->data['title'],
                "os_type"   =>$k=="and"?3:1,//获得对应启动图系统类型
                "start_time"=>$this->data['start_time'],
                "end_time"  =>$this->data['end_time'],
                "img"       =>$v,
                "auid"      =>$_SESSION['admin_info']['id'],
                "create_time"=>time()
            );

            $this->add($data);
        }
        return true;
    }
}