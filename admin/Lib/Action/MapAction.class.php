<?php
/**
 * 收货地址文件版本管理
 * @author:hhf
 * Date: 2015/9/8
 * Time: 10:55
 */
class MapAction extends  BaseAction{
    /**
    +----------------------------------------------------------
     * 收货地址地图版本列表
    +----------------------------------------------------------
     */
    function index(){
        $mapMod = M('map_version');
        $mapResult = $mapMod->select();
        $this->assign('map',$mapResult);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 收货地址地图版本添加
    +----------------------------------------------------------
     */
    function add(){
        $host = $_SERVER['HTTP_HOST'];
        $mapFileHost = C('MAP_URL');
        $mapFile = $_FILES['map'];
        $version_num = I('post.version_number',0,'intval');
        $mapMod = M('map_version');
        if(isset($_POST['addsubmit'])){
            if($mapFile['error']==0){//上传没有出错
                $dirName =$_SERVER['DOCUMENT_ROOT'].__ROOT__."/map";
                if(!file_exists($dirName)){
                    $flag = mkdir($dirName,0775);
                }
                $newFilePath = $dirName."/{$mapFile['name']}";
                $flag = move_uploaded_file($mapFile['tmp_name'],$newFilePath);
                $res = up_file($newFilePath,"{$mapFile['name']}");
                $data = array();
                if($res['status']){//上传又拍云成功
                    $upFileUpyun = $mapFileHost.$res['url'];
                    $data['map_url'] = $upFileUpyun;
                }else{
                    $data['map_url'] = $host.__ROOT__."/map"."/{$mapFile['name']}";
                }
                if($flag){
                    $data['version_num'] = $version_num;
                    $data['create_time'] = $data['update_time'] = time();
                    $flag = $mapMod->add($data);
                    if($flag){
                        $this->success("上传成功","?m=Map&a=index");
                    }else{
                        $this->error("保存失败","","","edit");
                    }
                }else{
                    $this->error("上传失败","?m=Map&a=index");
                }
            }
        }else{
            $this->display();
        }
    }


    /**
    +----------------------------------------------------------
     * 收货地址地图版本修改
    +----------------------------------------------------------
     */
    function edit(){
        $host = $_SERVER['HTTP_HOST'];
        $mapFileHost = C('MAP_URL');
        $mapFile = $_FILES['map'];
        $version_num = I('post.version_number',0,'intval');
        $mapMod = M('map_version');
        if(isset($_POST['dosubmit'])){
            $id = I('post.id',0,'intval');
            $data = array();
            $data['version_num'] = $version_num;
            $data['update_time'] = time();
            $data['id'] = $id;
            if($mapFile['error']===0){//上传没有出错
                $dirName =$_SERVER['DOCUMENT_ROOT'].__ROOT__."/map";
                if(!file_exists($dirName)){
                    $flag = mkdir($dirName,0775);
                }
                $newFilePath = $dirName."/{$mapFile['name']}";
                $flag = move_uploaded_file($mapFile['tmp_name'],$newFilePath);
                if($flag){
                    $res = up_file($newFilePath,"{$mapFile['name']}");
                    if($res['status']){//上传又拍云成功
                        $upFileUpyun = $mapFileHost.$res['url'];
                        $data['map_url'] = $upFileUpyun;
                    }else{
                        $data['map_url'] = $host.__ROOT__."/map"."/{$mapFile['name']}";
                    }
                }else{
                    $this->error("上传失败","","","edit");
                }
            }
            $flag = $mapMod->save($data);
            if($flag!==false){
                $this->success("修改成功","","","edit");
            }else{
                $this->error("修改失败","","","edit");
            }
        }else{
            $id = I('get.id',0,'intval');
            $mapRes = $mapMod->find($id);
            $this->assign('map',$mapRes);
            $this->display();
        }
    }


    /**
    +----------------------------------------------------------
     * 收货地址地图版本删除
    +----------------------------------------------------------
     */
    function delete(){
        $id = I('get.id',0,'intval');
        if($id){
            $mapMod = M("map_version");
            $flag = $mapMod->delete($id);
            if($flag!==false){
                $this->error("删除成功","?m=Map&a=index");
            }else{
                $this->error("删除失败","?m=Map&a=index");
            }
        }else{
            $this->error("删除失败","?m=Map&a=index");
        }
    }


    /**
    +----------------------------------------------------------
     * 查看存在数据库中的收货地址（省，市）
    +----------------------------------------------------------
     */
    public function search(){
        $areaMod = M('shipping_area_code');//地区编码
        $result = $areaMod->group('province')->field('province')->select();
        foreach($result as $key=>$val){
            $city = $areaMod->where("province = '{$val['province']}'")->getField('city',true);
            $city = implode(',',$city);
            $result[$key]['citys'] = $city;
        }
        $this->assign('data',$result);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 修改存在数据库中的收货地址（省，市）
    +----------------------------------------------------------
     */
    public function editProvince(){
        $province = I('get.province','','trim,htmlspecialchars');
        $areaMod = M('shipping_area_code');//地区编码
        $city = $areaMod->where("province = '$province'")->field('id,code,province,city')->select();
        $this->assign('data',$city);
        $this->assign('province',$province);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 异步修改二级城市地区名称
    +----------------------------------------------------------
     */
    public function modifyCity(){
        $code = I('post.code',0,'intval');
        $city = I('post.city','','trim,htmlspecialchars');
        $areaMod = M('shipping_area_code');//地区编码
        $return = array();
        if($city){
            if($areaMod->where("city = '$city'")->find()){
                $return['flag'] = true;
                $return['data'] = "这个名称已经存在！请检查！";
                $this->ajaxReturn($return,'json');
            }
            $where['code'] = $code;
            $save['city'] = $city;
            $flag = $areaMod->where($where)->save($save);
            if($flag!==false){
                $return['flag'] = true;
                $return['data'] = "修改成功！";
                $this->ajaxReturn($return,'json');
            }else{
                $return['flag'] = true;
                $return['data'] = "修改失败！请检查！";
                $this->ajaxReturn($return,'json');
            }
        }else{
            $return['flag'] = false;
            $this->ajaxReturn($return,'json');
        }
    }
    /**
    +----------------------------------------------------------
     * 新增二级城市地区名称
    +----------------------------------------------------------
     */
    public function addCity(){
        $province = I('get.province','','trim,htmlspecialchars');//省份
        $areaMod = M('shipping_area_code');//地区编码
        if(isset($_POST['dosubmit'])){
            $province = I('post.province','','trim,htmlspecialchars');//省份
            $code = I('post.code','','intval');//地区编码
            $city = I('post.city','','trim,htmlspecialchars');//城市地区
            $data = array(
                "province"  =>  $province,
                "code"  =>  $code,
                "city"  =>  $city
            );
            $flag = $areaMod->add($data);
            if($flag){
                echo "<script>alert('添加成功')</script>";
                $this->success("ok","","","addCity");
            }else{
                echo "<script>alert('添加失败')</script>";
                $this->error("error","","","addCity");
            }
        }else{
            $areaCode = $areaMod->where("province = '{$province}'")->order('code desc')->find();
            $areaCode = $areaCode['code']+1;
            while($areaMod->where('code = '.$areaCode)->find()){
                $areaCode++;
            }
            $this->assign('code',$areaCode);
            $this->assign('province',$province);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 删除二级城市地区名称
    +----------------------------------------------------------
     */
    public function delCity(){
        $id = I('post.id',0,'intval');
        if($id){
            $areaMod = M('shipping_area_code');//地区编码
            $flag = $areaMod->delete($id);
            $return = array();
            if($flag!==false){
                $return['flag'] = true;
                $this->ajaxReturn($return,'json');
            }else{
                $return['flag'] = flase;
                $this->ajaxReturn($return,'json');
            }
        }
    }
}