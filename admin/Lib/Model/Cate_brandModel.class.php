<?php

class Cate_brandModel extends RelationModel{

   protected $_link=array(
	   'brand'=>array(
	       'mapping_type'  => BELONGS_TO,
	       'class_name'    => 'brand',
            'foreign_key'   => 'brand_id',
	   ),
	   'cate'=>array(
	       'mapping_type'  => BELONGS_TO,
	       'class_name'    => 'goods_cate',
           'foreign_key'   => 'cate_id',
           'as_fields'     => 'name'
	   ),
	);
}
?>