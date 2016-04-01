<?php

class SpuModel extends RelationModel{

   protected $_link = array(

	'spu_cate' => array(
        	'mapping_type'  => BELONGS_TO,
        	'class_name'    => 'spu_cate',
        	'foreign_key'   => 'cid',
        	'mapping_fields'=> 'name',
	),
	'spu_brand' => array(
        	'mapping_type'  => BELONGS_TO,
        	'class_name'    => 'brand',
        	'foreign_key'   => 'brand_id',
        	'mapping_fields'=> 'name',
	),
	'admin' => array(
        	'mapping_type'  => BELONGS_TO,
        	'class_name'    => 'admin',
        	'foreign_key'   => 'admin_id',
        	'mapping_fields'=> 'user_name',
	),
	'images1' => array(
        	'mapping_type'  => HAS_MANY,
        	'class_name'    => 'spu_image',
        	'foreign_key'   => 'spu_id',
        	'parent_key' => 'spu_id',
        	'condition'     => 'type = 1',
	),
	'images3' => array(
        	'mapping_type'  => HAS_MANY,
        	'class_name'    => 'spu_image',
        	'foreign_key'   => 'spu_id',
        	'parent_key' => 'spu_id',
        	'condition'     => 'type = 3',
	),
	'skulist' => array(
        	'mapping_type'  => HAS_MANY,
        	'class_name'    => 'sku_list',
        	'foreign_key'   => 'spu_id',
        	'parent_key' => 'spu_id',
	),
	'guess' => array(
        	'mapping_type'  => HAS_MANY,
        	'class_name'    => 'guess_love',
        	'foreign_key'   => 'spu_id',
        	'parent_key' => 'spu_id',
	),
   'relationCate' => array(
       'mapping_type'  => HAS_MANY,
       'class_name'    => 'spu_cate_relation',
       'foreign_key'   => 'spu_id',
       'parent_key' => 'spu_id',
   ),
	);



}
?>