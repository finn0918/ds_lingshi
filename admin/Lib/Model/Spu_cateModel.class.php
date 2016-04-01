<?php

class Spu_cateModel extends RelationModel{

    function get_list($id=0){
		$items_cate_mod=M('spu_cate');

		$list=array();
		$res=$items_cate_mod->where('pid='.$id)->order('list_num DESC')->select();
		foreach($res as $key=>$val){
			$val['level']=0;
			$list[]=$val;
			//二级分类
			$arr=$items_cate_mod
			->where('pid='.$val['id'])
			->select();

			foreach($arr as $k2=>$v2){
				$v2['level']=1;
				$v2['cls']="sub_".$val['id'];
				$list[]=$v2;
			}
			$res[$key]['items']=$arr;
		}
		return array('list'=>$res,'sort_list'=>$list);
	}
}
?>