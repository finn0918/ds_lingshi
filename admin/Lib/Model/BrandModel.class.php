<?php

class BrandModel extends RelationModel{
	function getFirstCharter($str){
		$no = array("咪", "7", "饕", "喵","怡","梵","昊","梓","槟","奕","饴","咔","楽","町","茱","浏","懿","琥","喔","杞");
		$yes = array("m", "q", "t", "m","y","f","h","z","b","y","y","k","l","d","z","l","y","h","w","q");
		$str = str_replace($no, $yes, $str);
		if(empty($str)){return '';}
		$fchar=ord($str{0});
		if(is_numeric($str{0})) return strtoupper($str{0});
		if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
		$s1=iconv('UTF-8','GBK',$str);
		$s2=iconv('GBK','UTF-8',$s1);
		$s=$s2==$str?$s1:$str;
		$asc=ord($s{0})*256+ord($s{1})-65536;
		if($asc>=-20319&&$asc<=-20284) return 'A';
		if($asc>=-20283&&$asc<=-19776) return 'B';
		if($asc>=-19775&&$asc<=-19219) return 'C';
		if($asc>=-19218&&$asc<=-18711) return 'D';
		if($asc>=-18710&&$asc<=-18527) return 'E';
		if($asc>=-18526&&$asc<=-18240) return 'F';
		if($asc>=-18239&&$asc<=-17923) return 'G';
		if($asc>=-17922&&$asc<=-17418) return 'H';
		if($asc>=-17417&&$asc<=-16475) return 'J';
		if($asc>=-16474&&$asc<=-16213) return 'K';
		if($asc>=-16212&&$asc<=-15641) return 'L';
		if($asc>=-15640&&$asc<=-15166) return 'M';
		if($asc>=-15165&&$asc<=-14923) return 'N';
		if($asc>=-14922&&$asc<=-14915) return 'O';
		if($asc>=-14914&&$asc<=-14631) return 'P';
		if($asc>=-14630&&$asc<=-14150) return 'Q';
		if($asc>=-14149&&$asc<=-14091) return 'R';
		if($asc>=-14090&&$asc<=-13319) return 'S';
		if($asc>=-13318&&$asc<=-12839) return 'T';
		if($asc>=-12838&&$asc<=-12557) return 'W';
		if($asc>=-12556&&$asc<=-11848) return 'X';
		if($asc>=-11847&&$asc<=-11056) return 'Y';
		if($asc>=-11055&&$asc<=-10247) return 'Z';
		return null;
	}
	function get_list(){
		$brand_mod=M('brand');
		$list=array();
		$first = array();
		for($i=ord("A");$i <= ord("Z");$i++){
  			$first[]['name'] = chr($i);
  			$first[]['id'] = chr($i);
 		}
 		$res = $brand_mod->field('id,name')->select();
 		$letter = array();
 		foreach($res as $k=>$v){
				$res_code = $this->getFirstCharter($v['name']);
				$res[$k]['first'] = $res_code;
				$letter[] =$res_code;
		}
		$letter = array_unique($letter);
		foreach($first as $key=>$val){
			if(in_array($val['name'],$letter)){
				$val['level'] = 0;
				$list[] = $val;
				foreach($res as $k=>$v){
					if($v['first'] == $val['name']){
						$v['level']=1;
						$v['cls']="sub_".$v['first'];
						$list[] = $v;
						unset($res[$k]);
					}
				}
			}
		}
		return array('sort_list'=>$list);
	}
	function brand_list($brand_list){
		//$brand_mod=M('brand');
		$list=array();
		$first = array();
		for($i=ord("A");$i <= ord("Z");$i++){
  			$first[]['name'] = chr($i);
  			$first[]['id'] = chr($i);
 		}
 		$res = $brand_list;
 		$letter = array();
 		foreach($res as $k=>$v){
				$res_code = $this->getFirstCharter($v['name']);
				$res[$k]['first'] = $res_code;
				$letter[] =$res_code;
		}
		$letter = array_unique($letter);
		foreach($first as $key=>$val){
			if(in_array($val['name'],$letter)){
				$val['level'] = 0;
				$list[] = $val;
				foreach($res as $k=>$v){
					if($v['first'] == $val['name']){
						$v['level']=1;
						$v['cls']="sub_".$v['first'];
						$list[] = $v;
						unset($res[$k]);
					}
				}
			}
		}
		return array('sort_list'=>$list);
	}
}
?>