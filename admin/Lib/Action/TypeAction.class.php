<?php
/**
 * 类型管理模块，商品分类的sku属性管理
 * @author hhf
 *
 */

class TypeAction extends BaseAction{

	/**
	 +----------------------------------------------------------
	 * 类型列表
	 +----------------------------------------------------------
	 */
    function index() {
    	$typeMod = M('type');
    	$count = $typeMod->count();
    	$pageSize = 10;
        import("ORG.Util.Page");
        $p = new Page($count, $pageSize);
    	$typeResult = $typeMod->limit($p->firstRow . ',' . $p->listRows)->select();
    	$this->assign('type',$typeResult);
    	$page = $p->show();
		$this->assign('page',$page);
		$this->display();
    }

	/**
	 +----------------------------------------------------------
	 * 类型添加
	 +----------------------------------------------------------
	 */
    function add() {
		$typeMod = M('type');
    	if(isset($_POST['dosubmit'])){
			$typeName = I('post.name','','trim,htmlspecialchars');
			$typeName = make_semiangle($typeName);//全角逗号改成半角逗号
			$typeArray = explode(',',$typeName);
			$data = array();
			foreach($typeArray as $k=>$v){
				$data[]['type_name'] = $v;
			}
			$flag = $typeMod->addAll($data);
			if($flag){
				$this->success('类型新增成功！','?m=Type&a=index');
			}else{
				$this->error('类型新增失败！请重试！！');
			}
    	}else{
    		$this->display();
    	}

    }

    /**
	 +----------------------------------------------------------
	 * 类型的属性列表
	 +----------------------------------------------------------
	 */

	 function typeAttr () {
	 	$id = I('get.id',0,'intval');
	 	$typeAttrMod = M('type_attr');
	 	$count = $typeAttrMod->count();
    	$pageSize = 10;
        import("ORG.Util.Page");
        $p = new Page($count, $pageSize);
	 	$typeAttrResult = $typeAttrMod->limit($p->firstRow . ',' . $p->listRows)->select();
	 	$page = $p->show();
	 	$this->assign('typeId',$id);
	 	$this->assign('typeAttr',$typeAttrResult);
	 	$this->display();
	}

	/**
	 +----------------------------------------------------------
	 * 类型的属性增加
	 +----------------------------------------------------------
	 */

	 function typeAttrAdd () {

	 	if(isset($_POST['dosubmit'])){
	 		$attrMod = M('type_attr');
	 		$id = I('post.id',0,'intval');
	 		$attrName = I('post.name','','trim,htmlspecialchars');
	 		$attrType = I('post.attrType','','intval');
			$attrName = make_semiangle($attrName);//全角逗号改成半角逗号
			$attrArray = explode(',',$attrName);
			$data = array();
			foreach($attrArray as $k=>$v){
				$data[$k]['attr_name'] = $v;
				$data[$k]['attr_type'] = $attrType;
				$data[$k]['attr_input_type'] = 0;
				$data[$k]['type_id'] = $id;
			}
			$flag = $attrMod->addAll($data);
			if($flag){
				$this->success('类型属性新增成功！','?m=Type&a=typeAttr&id='.$id);
			}else{
				$this->error('类型属性新增失败！请重试！！');
			}
	 	}else{
	 		$id = I('get.id',0,'intval');
	 		$this->assign("typeId",$id);
			$this->display();
	 	}
	}
}
?>