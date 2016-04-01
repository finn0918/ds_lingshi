<?php
/**
 * xunsearcht添加 更新 删除
 * @author hhf
 *
 */
class XunsearchAction extends BaseAction{
	public function _initialize()
	{
        $prefix = '/usr/local/xunsearch';
        require_once "$prefix/sdk/php/lib/XS.php";
	}
	//添加索引
	public function all_add(){
		$xs = new XS('ds_lingshi'); // 建立 XS 对象
		$index = $xs->index; // 获取 索引对象
		$goods_mod = M('spu');
		$goods_list = $goods_mod->where('status=0')->field('spu_id,spu_name,details')->select();
		//dump($goods_list);exit;
		foreach($goods_list as $key=>$val){
			$data = array(
					'spu_id' => $val['spu_id'],
					'spu_name' => $val['spu_name'],
					'details' => $val['details'],
			);
			// 创建文档对象
			$doc = new XSDocument;
			$doc->setFields($data);
			$index->add($doc);
		}
        $index->flushIndex();
		echo "ok";
	}
	//添加索引
	public function add($id){
		$goods_mod = M('spu');
		$goods = $goods_mod->where('status=0 and spu_id='.$id)->field('spu_id,spu_name,details')->find();
		$xs = new XS('ds_lingshi'); // 建立 XS 对象
		$index = $xs->index; // 获取 索引对象
		$doc = new XSDocument;
		$doc->setFields($goods);
		$index->add($doc);
        $index->flushIndex();
	}

	//更新索引
	public function update($id){
		$goods_mod = M('spu');
		$goods = $goods_mod->where('status=0 and spu_id='.$id)->field('spu_id,spu_name,details')->find();
		$xs = new XS('ds_lingshi'); // 建立 XS 对象
		$index = $xs->index; // 获取 索引对象
		$doc = new XSDocument;
		$doc->setFields($goods);
		$index->update($doc);
        $index->flushIndex();
	}
	//删除索引
	public function del($id){
		$xs = new XS('ds_lingshi'); // 建立 XS 对象
		$index = $xs->index; // 获取 索引对象
		//$index->del('1');  // 删除主键值为 123 的记录
		$id = explode(',',$id);
		$index->del($id); // 同时删除主键值为 123, 789, 456 的记录
        $index->flushIndex();
	}
	//搜索关键词
	public function search(){
		$xs = new XS('ds_lingshi'); // 建立 XS 对象，项目名称为：demo
		$search = $xs->search; // 获取 搜索对象
		$search->setLimit(10, 0); // 设置返回结果最多为 5 条，并跳过前 0 条
		$docs = $search->search('进口');
		dump($docs);
	}
	//清空索引
	public function clean(){
		$xs = new XS('ds_lingshi'); // 建立 XS 对象
		$index = $xs->index; // 获取 索引对象
		$index->clean();
	}

	//中文分词
	public function cut_word($text){
		$xs = new XS('ds_lingshi'); // 建立 XS 对象
		$tokenizer = new XSTokenizerScws;
		$words = $tokenizer->getResult($text);
		foreach($words as $key=>$val){
			$word_data[] = $val['word'];
		}
	    return $word_data;
	}
}