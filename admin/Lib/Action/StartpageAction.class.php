<?php
/**
 * App 启动页控制后台
 * @author feibo
 *
 */
class StartpageAction extends BaseAction {
	
	/**
	 +----------------------------------------------------------
	 * 启动页列表
	 +----------------------------------------------------------
	 */
	public function index() {
        $imgHost = C('LS_IMG_URL');
        // 获得搜索词
        $title      =  I("get.title",'',"htmlspecialchars");// 标题
        $os_type    = I("get.os_type",0,'intval');// 系统类型
        $mark   = I("get.mark",0,"intval");// 启动图状态

        // 标题
        if(!empty($title)){
            $map['title'] = array("like","%{$title}%");
        }

        //默认查 android 平台
        if($os_type!=0){
            $map['os_type'] = array("eq",$os_type);
        }
        // 根据条件查询相应类型消息
        switch($mark){
            case 1:
                $map['start_time'] = array("elt",time());
                break;
            // 未完成状态
            case 2:
                $map['start_time'] = array("gt",time());
                break;
        }

        $startPageModel = D("StartPage");

        $startList = $startPageModel->getStartPageList($map);

        $page = $startList['page'];
        unset($startList['page']);
        // 反序列化输出图片。
        foreach($startList as $k=>$v){
            $imgArr = unserialize($v['img']);
            $startList[$k]['img'] = $imgHost.$imgArr[1]['img_url'];
        }
        $this->assign("page",$page);
        $this->assign("startPageList",$startList);
        $this->display();
	}
	
	/**
	 +----------------------------------------------------------
	 * 添加启动页
     * @author:hhf
	 +----------------------------------------------------------
	 */
	public function add(){
        // 如果是添加
        if($this->_post()){
            $options = C('REDIS_CONF');
            $options['rw_separate'] = true; //读取是false
            $Cache = Cache::getInstance('Redis',$options);
            $keyname = C('START_PAGE_REDIS').'1';
            $Cache->rm($keyname);
            $keyname = C('START_PAGE_REDIS').'3';
            $Cache->rm($keyname);
            $Cache->close();
            $startPageModel = D("StartPage");
            if($startPageModel->addStartPage()){
                die(json_encode(array('status'=>1,"msg"=>"添加成功")));
            }else{
                die(json_encode(array('status'=>0,"msg"=>$startPageModel->getError())));
            }
        }
        $this->display();
	}
	
	/**
	 +----------------------------------------------------------
	 * 修改启动页
	 +----------------------------------------------------------
	 */
	public function edit(){
        $imgHost = C('LS_IMG_URL');
        // 编辑 启动图
        if(I('post.')){
            $options = C('REDIS_CONF');
            $options['rw_separate'] = true; //读取是false
            $Cache = Cache::getInstance('Redis',$options);
            $keyname = C('START_PAGE_REDIS').'1';
            $Cache->rm($keyname);
            $keyname = C('START_PAGE_REDIS').'3';
            $Cache->rm($keyname);
            $Cache->close();
            $startPageModel = D("StartPage");
            if($startPageModel->saveStartPage()){
                die(json_encode(array('status'=>1,"msg"=>"修改成功")));
            }else{
                die(json_encode(array('status'=>0,"msg"=>$startPageModel->getError()."修改失败")));
            }
        }
        $map['id'] = I('request.id',0,'intval');
        $start = D('StartPage')->where($map)->find();
        // 如果没有该启动图数据
        if(empty($start)){
            $this->error("非法的id值");
            die;
        }
        $imgArr = unserialize($start['img']);// 反序列化图片
        switch($start['os_type']){
            case 1:
                $os ="ios";
                break;
            case 3:
                $os = "and";
                break;
        }
        foreach($imgArr as $k=>$v){
            $start[$os.$k] = $v['img_url'];
            $start['Host'.$os.$k] = $imgHost.$v['img_url'];
        }
        $this->assign("start",$start);
        $this->display();
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除启动页
	 +----------------------------------------------------------
	 */
    public function del(){
        $startpage_mod = M('start_page');
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if($startpage_mod->where('id ='.$id)->delete()!==false){
            $this->success(L('operation_success'));
        }
    }


    /**
     * ajax 方式删除所有相关数据
     */
    public function delAll(){
        //非ajax请求将被拒绝
        if(!$this->isAjax()) $this->error("非法请求");
        $ids = I("post.ids","",'htmlspecialchars');
        $ids = trim($ids,',');// 去除多余的,
        // 删除 指定的元素
        $map['id'] = array('in',$ids);
        $status = D('StartPage')->delAll($map);
        if($status){
            $this->ajaxReturn($status,'删除成功',1);
        }else{
            $this->ajaxReturn($status,'删除失败',0);
        }
    }


    /**
    +----------------------------------------------------------
     * 启动图上传
    +----------------------------------------------------------
     */
    public function upload(){
        import('ORG.Net.UploadFile');
        $imgHost = C('LS_IMG_URL');
        $upload = new UploadFile();
        $upload->allowExts = explode(',', 'jpg,jpeg,png,gif,bmp,mp3,wma');
        // 如果是ios type 为1
        $type = $this->_get("type",'intval',0);
        // 选择启动图存放路径
        $dir = $type==1? "img/startpage/ios":"img/startpage/android";
        // 根据扩展名存储不同素材
        $upload->savePath = mk_dirs($dir);

        $upload->saveRule =uniqid;// 生成唯一id
        if (!$upload->upload())
        {
            echo json_encode(array(
                'status' => 0,
                'msg' => $upload->getErrorMsg()
            ));
            die;
        }
        else
        {
            $info = $upload->getUploadFileInfo();
            //$uploadHander = A("upload");
            //$uploadHander->img_upyun($upload->savePath.$info[0]['savename']);//上传到又拍云
            $res = up_yun($upload->savePath.$info[0]['savename']);
            unlink($upload->savePath.$info[0]['savename']);//删除本地图片
            echo json_encode(array(
                'status'  => 1,
                //'name'    => __ROOT__.'/'.$upload->savePath.$info[0]['savename']
                'name'    => $imgHost.$res['img_src'],
                'img'    => $res['img_src']
            ));
        }
        die;
    }

}