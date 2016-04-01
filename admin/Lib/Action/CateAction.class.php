<?php
/**
 * 功能：商品分类
 * @author hhf(hhf@feibo.com)
 *
 */
class CateAction extends BaseAction{

	/**
	 +----------------------------------------------------------
	 * 分类列表
	 +----------------------------------------------------------
	 */
	public function index(){
		$cate_mod = M('spu_cate');
		//搜索
		$where = 'pid=0';
		if(isset($_GET['keyword']) && trim($_GET['keyword'])){
			$where .= " AND name LIKE '%" . $_GET['keyword'] . "%'";
			$this->assign('keyword', $_GET['keyword']);
		}
		import("ORG.Util.Page");
		$count = $cate_mod->where($where)->count();
		$Page = new Page($count,10);
		$show = $Page->show();
		$cate_list = $cate_mod->where($where)->order('list_num DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($cate_list as $key=>$val){
			$pid = $val['id'];
			$detail = $cate_mod->where(array('pid'=>$pid))->field('name')->select();
			$detail_cates = '';
			foreach($detail as $key2=>$val2){
				$detail_cates .= $val2['name'].'，';
			}
			$detail_cates = empty($detail_cates) ? "无" : substr($detail_cates,0,-3);
			$cate_list[$key]['detail_cates'] = $detail_cates;
			$cate_list[$key]['key'] = ++$Page->firstRow;
		}
		$this->assign('page', $show);
		$this->assign('cate_list', $cate_list);
		$this->display();
	}

	/**
	 +----------------------------------------------------------
	 * 立即发布
	 +----------------------------------------------------------
	 */
	public function publish(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : $this->error('id不能为空！','?m=cate&a=index');
		$goods_cate_mod = M('spu_cate');
    	$data['status'] = 2;
    	$count = $goods_cate_mod->where('id = '.$id.' AND status=2')->count();
		if($count){
			$this->error('分类已经发布啦！');
		}else{
			$flag = $goods_cate_mod->where('id = '.$id)->save($data);
			if($flag){
                updateCache("ls_home");//更新首页缓存
			    $this->success('立即发布成功！');
			}else{
			    $this->error('操作失败！');
			}
		}
		echo $id;
	}

	/**
	 +----------------------------------------------------------
	 * 添加分类
	 +----------------------------------------------------------
	 */
	public function add(){
		$cate_mod = D('spu_cate');
		$imgHost = C('LS_IMG_URL');
		if(isset($_POST['dosubmit'])){
			$type_name = isset($_POST['title']) ? trim(htmlspecialchars($_POST['title'])) : ''; //商品一级分类名
			$pid = 0;
			$index_order = isset($_POST['index_num']) ? intval($_POST['index_num']) : 0;//商品分类首页顺序
			$list_order = isset($_POST['list_num']) ? intval($_POST['list_num']) : 0;//商品分类列表顺序
            $desc = isset($_POST['desc']) ? htmlspecialchars(trim($_POST['desc'])) : ''; //分类描述
			$admin_info = $_SESSION['admin_info'];
			$editor = $admin_info['user_name'];
			if($type_name){
				$where = array();
				$where['name'] = $type_name;
				$count = $cate_mod->where($where)->count();
				if($count){
					$this->error('这个分类已经存在，请不要再重复添加！');
				}
				$imgs = isset($_POST['file']) ? $_POST['file'] : null;
				$path = save_img64($imgs[0]);
				if($path){
					$path = img_quality_zip($path);
					$res = up_yun($path);
					$data['image_src'] = $imgHost.$res['img_src'];
					$data['img_w']=$res['img_w'];
   					$data['img_h']=$res['img_h'];
					$result = @unlink($path);
				}
				$data['name'] = $type_name;
				$data['index_num'] = $index_order;
				$data['list_num'] = $list_order;
				$data['pid'] = $pid;
				$data['editor'] = $editor;
                $data['desc'] = $desc;
				$flag = $cate_mod->add($data);
				if($flag){
                    updateCache("ls_home");//更新首页缓存
					$this->success(L('operation_success'),'?m=Cate&a=detail_cate_add');
				}else{
					$this->error('添加失败！','?m=Cate&a=add');
				}
			}else{
				$this->error('名称不能为空！','?m=Cate&a=add');
			}
		}else{
			$cate_array = array();
			$index_num = C('CATE_INDEX_NUM');
			for($i=1;$i<=$index_num;$i++){
				$cate_array[]['value'] = $i;
			}
			$this->assign("index_num",$cate_array);
			$cate_array = array();
			$index_num = C('CATE_LIST_NUM');
			for($i=0;$i<=$index_num;$i++){
				$cate_array[]['value'] = $i;
			}
			$this->assign("list_num",$cate_array);
			$res = $cate_mod->where('index_num > 0')->field('index_num')->select();
			$index_num = '';
			foreach($res as $val){
				$index_num .=$val['index_num'].' ,';
			}
			$index_num = substr($index_num,0,-2);
			$this->assign('index_str',$index_num);
			$res = $cate_mod->where('list_num > 0')->field('list_num')->select();
			$list_num = '';
			foreach($res as $val){
				$list_num .=$val['list_num'].' ,';
			}
			$list_num = substr($list_num,0,-2);
			$this->assign('list_str',$list_num);
			$this->display();
		}
	}

	/**
	 +----------------------------------------------------------
	 * 添加详细品类
	 +----------------------------------------------------------
	 */
	public function detail_cate_add(){
		$cate_mod = D('spu_cate');
		$search_hot_mod = D('search_hot');
		$imgHost = C('LS_IMG_URL');
		if(isset($_POST['dosubmit'])){
			$catename = isset($_POST['catename']) ? trim(htmlspecialchars($_POST['catename'])) : ''; //商品详细品类类名
			$first_cate = isset($_POST['first_cate']) ? intval($_POST['first_cate']) : 0;
			$is_hot = isset($_POST['is_search_hot']) ? intval($_POST['is_search_hot']) : 0;
			$is_show = isset($_POST['is_show']) ? intval($_POST['is_show']):1;
			$is_show = isset($_POST['is_show']) ? intval($_POST['is_show']):1;
			$pid = $first_cate;
			if($pid==0){
				$this->error('没有选择一级分类！','?m=cate&a=detail_cate_add');
			}
			if($catename){
				$where = array();
				$where['name'] = $catename;
				$count = $cate_mod->where($where)->count();
				if($count){
					$this->error('这个品类已经存在，请不要再重复添加！');
				}
				$imgs = isset($_POST['file']) ? $_POST['file'] : null;
				$path = save_img64($imgs[0]);
				if($path){
					$path = img_quality_zip($path);
					$res = up_yun($path);
					$data['image_src'] = $imgHost.$res['img_src'];
					$data['img_w']=$res['img_w'];
   					$data['img_h']=$res['img_h'];
					$result = @unlink($path);
				}
				$data['name'] = $catename;
				$data['index_num'] = 0;
				$data['list_num'] = 0;
				$data['pid'] = $pid;
				$data['is_show'] = $is_show;
				$flag = $cate_mod->add($data);
				if($flag){
					if($is_hot == 1){
					$data = array();
					$data['cid'] = $flag;
					$data['name'] = $catename;
					$data['type'] = 2;//分类添加到热词
					$data['order'] = 0;
					$flg = $search_hot_mod->add($data);
					if($flg){
						$data = array();
						$data['id'] = $flag;
						$data['is_search_hot'] = 1;
						$flag = $cate_mod->save($data);
						$this->success(L('operation_success'),'?m=Cate&a=detail_cate_add');
					}else{
						$this->error('添加失败！','?m=Cate&a=detail_cate_add');
					}
					}
					$this->success(L('operation_success'),'?m=Cate&a=detail_cate_add');

				}else{
					$this->error('添加失败！','?m=Cate&a=index');
				}
			}else{
				$this->error('名称不能为空！','?m=Cate&a=detail_cate_add');
			}
		}else{
			$where['pid'] = 0;
			$cate_result = $cate_mod->where($where)->field('id,name')->select();
			$this->assign("cate",$cate_result);
			$this->display();
		}
	}

	/**
	 +----------------------------------------------------------
	 * 添加品牌
	 +----------------------------------------------------------
	 */
	public function brand_add(){
		$brand_mod = D('Brand');
		$cate_brand_mod = D('cate_brand');
		$cate_mod = D('spu_cate');
		$search_hot_mod = D('search_hot');
		$imgHost = C('LS_IMG_URL');
		if(isset($_POST['dosubmit'])){
			$brandname = isset($_POST['brandname']) ? trim(htmlspecialchars($_POST['brandname'])) : ''; //商品推荐品牌名
			$first_cate = isset($_POST['first_cate']) ? intval($_POST['first_cate']) : 0;
			$brand_id = isset($_POST['brand']) ? intval($_POST['brand']) : 0;
			$is_hot = isset($_POST['is_search_hot']) ? intval($_POST['is_search_hot']) : 0;
			$is_show = isset($_POST['is_show']) ? intval($_POST['is_show']) : 0;
			if($first_cate==0){
				$this->error('没有选择一级分类！','?m=Cate&a=brand_add');
			}
			if($brand_id==0){ //添加新的品牌
				if($brandname){
					$where = array();
					$where['name'] = $brandname;
					$count = $brand_mod->where($where)->count();
					if($count){
						$this->error('这个品牌已经存在，请在已有品牌中挑选！');
					}
					$imgs = isset($_POST['file']) ? $_POST['file'] : null;
					$path = save_img64($imgs[0]);
					if($path){
						$path = img_quality_zip($path);
						$res = up_yun($path);
						$data['image_src'] = $imgHost.$res['img_src'];
						$data['img_w']=$res['img_w'];
	   					$data['img_h']=$res['img_h'];
						$result = @unlink($path);
					}
					$data['name'] = $brandname;
					$flag = $brand_mod->add($data);
					if($flag){
						$data = array();
						$data['cate_id'] = $first_cate;
						$data['brand_id'] = $flag;
						$data['is_show']= $is_show;
						$flg = $cate_brand_mod->add($data);
						if($flg){
								if($is_hot == 1){
									$data = array();
									$data['cid'] = $flag;
									$data['name'] = $brandname;
									$data['type'] = 3;//分类添加到热词
									$data['order'] = 0;
									$flg = $search_hot_mod->add($data);
									if($flg){
									$data = array();
									$data['id'] = $flag;
									$data['is_search_hot'] = 1;
									$flag = $brand_mod->save($data);
									$this->success(L('operation_success'),'?m=Cate&a=index');
									}else{
									$this->error('添加失败！','?m=Cate&a=brand_add');
									}
								}
							$this->success(L('operation_success'),'?m=Cate&a=index');
						}else{
							$this->error('添加失败！','?m=Cate&a=index');
					}
				}else{
					$this->error('添加失败！','?m=Cate&a=index');
				}
			}else{
				$this->error('名称不能为空！','?m=Cate&a=brand_add');
			}
			}else{//关联已有品牌
					$data = array();
					$data['cate_id'] = $first_cate;
					$data['brand_id'] = $brand_id;
					$data['is_show'] = $is_show;
					$count = $cate_brand_mod->where($data)->count();
					if($count){
						$this->error('添加失败！这个分类和这个品牌已经关联');
					}
					$flg = $cate_brand_mod->add($data);
					if($flg){
						$this->success(L('operation_success'),'?m=Cate&a=index');
					}else{
						$this->error('添加失败！','?m=Cate&a=index');
					}
			}
		}else{
			$where['pid'] = 0;
			$cate_result = $cate_mod->where($where)->field('id,name')->select();
			$this->assign("cate",$cate_result);
			$brand = $brand_mod->get_list();
			//$brand = $brand_mod->field('id,name')->select();
			$this->assign("brand",$brand['sort_list']);
			$this->display();
		}

	}
	/**
	 +----------------------------------------------------------
	 * 分类详情
	 +----------------------------------------------------------
	 */
	public function edit(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : $this->error('id不能为空！','?m=cate&a=index');
		$cate_mod = D('Spu_cate');
		$cate_brand_mod = D('Cate_brand');
		$brand_mod = D('Drand');
		$cate_msg = $cate_mod->where('id ='.$id)->find();
		$pid = $cate_msg['id'];
		$detail_cate_msg = $cate_mod->where('pid ='.$pid)->select(); //一级分类包含的二级分类
		$brand_id = $cate_brand_mod->where(array('cate_id'=>$pid))->field('brand_id,is_show')->select();
		$ids =array();
		foreach($brand_id as $key=>$val){
			$ids[] = $val['brand_id'];
		}
		$ids = implode(',',$ids);
		$map['id'] = array('in',$ids);
		$cate_brands = $brand_mod->where($map)->select(); //一级分类包含的推荐品牌
		foreach($cate_brands as $key=>$val){
			foreach($brand_id as $k=>$v){
				if($v['brand_id']==$val['id']){
					$cate_brands[$key]['is_cate_brand_show'] = $v['is_show'];
				}
			}
		}
		if(isset($_POST['dosubmit'])){

		}
		else{
			$this->assign('cate',$cate_msg);
			$this->assign('detail_cate_msg',$detail_cate_msg);
			$this->assign('cate_brands',$cate_brands);
			$this->display();
		}

	}

	/**
	 +----------------------------------------------------------
	 * 修改品牌
	 +----------------------------------------------------------
	 */
	public function edit_brand(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : $this->error('id不能为空！');
		$cate_id = isset($_REQUEST['cate']) ? intval($_REQUEST['cate']) : $this->error('操作失败！');
		$brandname = isset($_POST['brandname']) ? trim(htmlspecialchars($_POST['brandname'])) : '';
		$is_hot = isset($_POST['is_search_hot']) ? intval($_POST['is_search_hot']) : 0;
		$is_show = isset($_POST['is_show']) ? intval($_POST['is_show']) : 0;
		$brand_mod = D('brand');
		$hot_mod = D('search_hot');
		$cate_brand_mod = D('cate_brand');
		$imgHost = C('LS_IMG_URL');
		if(isset($_POST['dosubmit'])){
			if($brandname){
				$data['id'] = $id;
				$data['name'] = $brandname;
				$imgs = isset($_POST['file']) ? $_POST['file'] : null;
				$path = save_img64($imgs[0]);
				if($path){
					$path = img_quality_zip($path);
					$img1 = $brand_mod->where('id ='.$id)->field('image_src')->find();
					//$ress = del_upyun($img1['image_src']);//删除又拍云里面的图片
					$res = up_yun($path);
					$data['image_src'] = $imgHost.$res['img_src'];
					$data['img_w']=$res['img_w'];
   					$data['img_h']=$res['img_h'];
					$result = @unlink($path);
				}
				$flag = $brand_mod->save($data);
				if($cate_id>0){
				$data = array();
				$data['cate_id'] = $cate_id;
				$data['brand_id'] = $id;
				$map['is_show'] = $is_show;
				$cate_brand_mod->where($data)->save($map);
				}
				$hot_mod->where('cid ='.$id.' AND type = 3')->delete();
				if($is_hot==1){
					$data = array();
					$data['name'] = $brandname;
					$data['type'] = 3;
					$data['cid'] = $id;
					$data['order'] = 0;
					$flg = $hot_mod->add($data);
					$data = array();
					$data['is_search_hot'] = 1;
					$flg = $brand_mod->where('id ='.$id)->save($data);
				}else if($is_hot==0){
					$data = array();
					$data['is_search_hot'] = 0;
					$flg = $brand_mod->where('id ='.$id)->save($data);
				}
				$this->success(L('operation_success'), '', '', 'edit_brand');
			}else{
				$this->error('没有填写品牌名称');
			}
		}
		else{
			$brand = $brand_mod->where('id ='.$id)->find();
			$cate_brand_res = $cate_brand_mod->where('cate_id='.$cate_id.' AND brand_id='.$brand['id'])->field('is_show')->find();
			$brand['is_cate_brand_show'] = $cate_brand_res['is_show'];
			$brand['cate_id'] = $cate_id;
			$this->assign('brand',$brand);
			$this->display();
		}

	}

	/**
	 +----------------------------------------------------------
	 * 修改详细品类
	 +----------------------------------------------------------
	 */
	public function edit_detail_cate(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : $this->error('id不能为空！');
		$catename = isset($_POST['catename']) ? trim(htmlspecialchars($_POST['catename'])) : '';
		$is_hot = isset($_POST['is_search_hot']) ? intval($_POST['is_search_hot']) : 0;
		$is_show = isset($_POST['is_show']) ? intval($_POST['is_show']) : 0;
		$cate_mod = D('spu_cate');
		$imgHost = C('LS_IMG_URL');
		if(isset($_POST['dosubmit'])){
			if($catename){
				$data['id'] = $id;
				$data['name'] = $catename;
				$data['is_search_hot'] = $is_hot;
				$data['is_show'] = $is_show;
				$imgs = isset($_POST['file']) ? $_POST['file'] : null;
				$path = save_img64($imgs[0]);
				if($path){
					$path = img_quality_zip($path);
					$img1 = $cate_mod->where('id ='.$id)->field('image_src')->find();
					//$ress = del_upyun($img1['image_src']);//删除又拍云里面的图片
					$res = up_yun($path);
					$data['image_src'] = $imgHost.$res['img_src'];
					$data['img_w']=$res['img_w'];
   					$data['img_h']=$res['img_h'];
					$result = @unlink($path);
				}
				$flag = $cate_mod->save($data);
				if($flag){
					$hot_mod = D('search_hot');
					$data = array();
					$hot_mod->where('cid ='.$id.' AND type = 2')->delete();
					if($is_hot==1){
						$data = array();
						$data['name'] = $catename;
						$data['type'] = 2;
						$data['cid'] = $id;
						$data['order'] = 0;
						$flg = $hot_mod->add($data);
						$data = array();
						$data['is_search_hot'] = 1;
						$flg = $cate_mod->where('id ='.$id)->save($data);
					}else if($is_hot==0){
						$data = array();
						$data['is_search_hot'] = 0;
						$flg = $cate_mod->where('id ='.$id)->save($data);
					}
					$this->success(L('operation_success'), '', '', 'edit_detail_cate');
				}
				else{
					$this->error('操作失败');
				}
			}else{
				$this->error('没有填写品类名称');
			}
		}
		else{
			$cate = $cate_mod->where('id ='.$id)->find();
			$this->assign('detail_cate',$cate);
			$this->display();
		}

	}

	/**
	 +----------------------------------------------------------
	 * 修改商品分类
	 +----------------------------------------------------------
	 */
	public function edit_cate(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : $this->error('id不能为空！');
        $catename = isset($_POST['catename']) ? trim(htmlspecialchars($_POST['catename'])) : '';
        $desc = isset($_POST['desc']) ? trim(htmlspecialchars($_POST['desc'])) : '';
		$index_num = isset($_POST['index_num']) ? intval($_POST['index_num']) : 0;
		$list_num = isset($_POST['list_num']) ? intval($_POST['list_num']) : 0;
		$cate_mod = D('spu_cate');
		$imgHost = C('LS_IMG_URL');
		if(isset($_POST['dosubmit'])){
			if($catename){
				$data['id'] = $id;
				$data['name'] = $catename;
				$data['index_num'] = $index_num;
				$data['list_num'] = $list_num;
                $data['desc'] = $desc;
				$admin_info = $_SESSION['admin_info'];
				$editor = $admin_info['user_name'];
				$data['editor'] = $editor;
				$imgs = isset($_POST['file']) ? $_POST['file'] : null;
				$path = save_img64($imgs[0]);
				if($path){
					$path = img_quality_zip($path);
					$img1 = $cate_mod->where('id ='.$id)->field('image_src')->find();
					//del_upyun($img1['image_src']);//删除又拍云里面的图片
					$res = up_yun($path);
					$data['image_src'] = $imgHost.$res['img_src'];
					$data['img_w']=$res['img_w'];
   					$data['img_h']=$res['img_h'];
					$result = @unlink($path);
				}
				$flag = $cate_mod->save($data);
				if($flag){
                    updateCache("ls_home");//更新首页缓存
					$this->success(L('operation_success'), '', '', 'edit_cate');
				}
				else{
					$this->error('操作失败');
				}
			}else{
				$this->error('没有填写分类名称');
			}
		}
		else{
			$cate = $cate_mod->where('id ='.$id)->find();
			$this->assign('cate',$cate);
			$index_num = C('CATE_INDEX_NUM');
			$cate_array = array();
			for($i=1;$i<=$index_num;$i++){
				$cate_array[]['value'] = $i;
			}
			$this->assign("index_num",$cate_array);
			$index_num = C('CATE_LIST_NUM');
			$cate_array = array();
			for($i=0;$i<=$index_num;$i++){
				$cate_array[]['value'] = $i;
			}
			$this->assign("list_num",$cate_array);
			$res = $cate_mod->where('index_num > 0')->field('index_num')->select();
			$index_num = '';
			foreach($res as $val){
				$index_num .=$val['index_num'].' ,';
			}
			$index_num = substr($index_num,0,-2);
			$this->assign('index_str',$index_num);
			$res = $cate_mod->where('list_num > 0')->field('list_num')->select();
			$list_num = '';
			foreach($res as $val){
				$list_num .=$val['list_num'].' ,';
			}
			$list_num = substr($list_num,0,-2);
			$this->assign('list_str',$list_num);
			$this->display();
		}
	}

	/**
	 +----------------------------------------------------------
	 * 删除分类
	 +----------------------------------------------------------
	 */
	public function del(){
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : $this->error('请选择要删除的分类！','?m=Cate&a=index');
		$ids = is_array($_REQUEST['id']) ? implode(',', $_REQUEST['id']) : intval($_REQUEST['id']);
		$cate_mod = D('spu_cate');
		$cate_brand_mod = D('cate_brand');
		//$goods_mod = D('spu');
		$cate_mod->delete($ids);
		$cate_brand_mod->where('cate_id in('.$ids.')')->delete();
		$res = $cate_mod->where('pid in ('.$ids.')')->field('id')->select();
		$catesid = '';
		foreach($res as $val){
			$catesid .= $val['id'].',';
		}
		$catesid = substr($catesid,0,-1);
		/*
		$res = $goods_mod->where('cid in ('.$catesid.')')->field('id')->select();
		$goodsid = '';
		foreach($res as $val){
			$goodsid .= $val['id'].',';
		}
		$goodsid = substr($goodsid,0,-1);
		$goods = A('goods');
		$goods->deletes($goodsid);
		*/
		$cate_mod->where('pid in ('.$ids.')')->delete();
        updateCache("ls_home");//更新首页缓存
		$this->success('删除成功！','?m=Cate&a=index');

	}

	/**
	 +----------------------------------------------------------
	 * 删除详细品类
	 +----------------------------------------------------------
	 */
	public function del_detail_cate(){
		$id = isset($_REQUEST['id']) ? ($_REQUEST['id']) : $this->error('请选择要删除的分类！','?m=cate&a=index');
		$ids = is_array($_REQUEST['id']) ? implode(',', $_REQUEST['id']) : intval($_REQUEST['id']);
		$cate_mod = D('spu_cate');
		/*
		$goods_mod = D('spu');
		$res = $goods_mod->where('cid in ('.$ids.')')->field('id')->select();
		$goodsid = '';
		foreach($res as $val){
			$goodsid .= $val['id'].',';
		}
		$goodsid = substr($goodsid,0,-1);
		$goods = A('goods');
		$cate_mod->delete($ids);
		*/
		//$goods->deletes($goodsid); //删除商品
		$cate_mod->delete($ids);
		$this->success('删除成功！','?m=Cate&a=index');
	}


	/**
	 +----------------------------------------------------------
	 * 删除品牌
	 +----------------------------------------------------------
	 */
	public function del_brand(){
		$brand_mod = D('brand');
		$goods_mod = D('spu');
		$cate_brand_mod = D('cate_brand');
        if ((! isset($_GET['id']) || empty($_GET['id'])) && (! isset($_POST['id']) || empty($_POST['id']))){
            $this->error('请选择要删除的品牌！');
        }
        	$brand_id = intval($_REQUEST['id']);
            $where['brand_id'] = $brand_id;
            $cate_brand_mod->where($where)->delete();
            $data = array();
            $data['brand_id'] = 0;
            $goods_mod->where('brand_id ='.$brand_id)->save($data);
            $brand_mod->delete($brand_id);
       		$this->success(L('operation_success'));
	}

	/**
	 +----------------------------------------------------------
	 * 取消品牌与分类的关联
	 +----------------------------------------------------------
	 */
	public function cancel_brand(){
		$brand_mod = D('cate_brand');
		$goods_mod = D('spu');
		$cate_mod = D('spu_cate');
        if ((! isset($_GET['id']) || empty($_GET['id'])) && (! isset($_POST['id']) || empty($_POST['id']))){
            $this->error('请选择要取消关联的品牌！');
        }
        $brand_id = intval($_REQUEST['id']);
        $cate_id = intval($_REQUEST['cid']);
        $where['brand_id'] = $brand_id;
        $where['cate_id'] = $cate_id;
        $brand_mod->where($where)->delete();
        //还要取消掉和商品的关联
		$second_cates = $cate_mod->where('pid = '.$cate_id)->field('id')->select();
		$ids = '';
		foreach($second_cates as $key=>$val){
			$ids .=$val['id'].',';
		}
		$ids = substr($ids,0,-1);
		$data = array();
		$data['brand_id'] = 0;
		$goods_mod->where('cid in ('.$ids.')')->save($data);
        $this->success(L('operation_success'));
	}


    /**
	 +----------------------------------------------------------
	 * 管理品牌
	 +----------------------------------------------------------
	 */
    public function brand_index () {
    	$brand_mod = D('brand');
    	$cate_brand_mod = D('Cate_brand');
    	$cate_mod = M('spu_cate');
    	$where = '1=1';
		if(isset($_GET['keyword']) && trim($_GET['keyword'])){
			$where .= " AND name LIKE '%" . $_GET['keyword'] . "%'";
			$this->assign('keyword', $_GET['keyword']);
		}
    	import("ORG.Util.Page");
		$count = $brand_mod->where($where)->count();
		$Page = new Page($count,10);
		$show = $Page->show();
    	$brand_res = $brand_mod->where($where)->field('id,name,image_src')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach($brand_res as $key=>$val){
    		$brand_res[$key]['key'] = ++$Page->firstRow;
    		$ceteArray = array();
    		$cate = $cate_brand_mod->relation(true)->where('brand_id ='.$val['id'])->select();
    		$brand_res[$key]['cates'] = '';
    		foreach($cate as $v){
    			$ceteArray[] = $v['cate_id'];
				//$brand_res[$key]['cates'] .= $v['name'].',';
    		}
    		$cates = implode(',',$ceteArray);
    		$cateResult = $cate_mod->where('id in ( '.$cates.' )')->getField('name',true);
    		//$brand_res[$key]['cates'] = substr($brand_res[$key]['cates'],0,-1);
    		$brand_res[$key]['cates'] = implode(',',$cateResult);
    		if(empty($brand_res[$key]['cates'])){
    			$brand_res[$key]['cates'] = '<font color="red">暂无关联分类</font>';
    		}
    	}
    	$this->assign('brand',$brand_res);
    	$this->assign('page',$show);
		$this->display();
	}

}