<?php

class OrderModel extends RelationModel{

   protected $_link = array(

        'client' => array(
                'mapping_type'  => BELONGS_TO,
                'class_name'    => 'client',
                'foreign_key'   => 'user_id',
                'mapping_fields'=> 'nickname',
        ),
	);



}
?>