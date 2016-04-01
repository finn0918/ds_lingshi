<?php

class Topic_relationModel extends RelationModel{

   protected $_link = array(

		'spu' => array(
        	'mapping_type'  => BELONGS_TO,
        	'class_name'    => 'spu',
        	'foreign_key'   => 'spu_id',
        	'mapping_fields'=> 'spu_name',
		),
	);
}
?>