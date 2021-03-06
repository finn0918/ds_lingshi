<?php
class adminModel extends RelationModel
{
	protected $_validate=array(
		array("cate_id","require","分类不能为空",0,"regex",1),
		array("abst","require","内容不能为空",0,"regex",1)
	);
	protected $_link=array(
	   'role'=>array(
	       'mapping_type'  => BELONGS_TO,
	       'class_name'    => 'items_cate',
            'foreign_key'   => 'role_id',
	   ),
	);
    public function check_username($user_name,$id='')
    {
        $where = "user_name='$user_name'";
        if ($id) {
            $where .= " AND id<>'$id'";
        }
        $id = $this->where($where)->getField('id');
        if ($id) {
        	//存在
            return false;
        } else {
        	//不存在
            return true;
        }
    }
}