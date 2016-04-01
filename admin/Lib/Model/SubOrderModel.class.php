<?php

class SubOrderModel extends RelationModel{

   protected $_link = array(

        'client' => array(
                'mapping_type'  => BELONGS_TO,
                'class_name'    => 'client',
                'foreign_key'   => 'user_id',
                'mapping_fields'=> 'nickname',
        ),
       'spu' => array(
           'mapping_type'  => BELONGS_TO,
           'class_name'    => 'spu',
           'foreign_key'   => 'spu_id',
           'mapping_fields'=> 'spu_name,price_old',
       ),
	);



}
?>