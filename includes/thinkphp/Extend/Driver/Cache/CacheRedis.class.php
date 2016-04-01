<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * Redis缓存驱动 - 支持Redis集群与读写分离
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    terry <i@pengyong.info>
 */

class CacheRedis extends Cache
{
		 /**
		 * 架构函数
		 * @param array $options 缓存参数
		 * @access public
		 */
		public function __construct($options=array())
		{
			if ( !extension_loaded('redis') ) {
				throw_exception(L('_NOT_SUPPERT_').':redis');
			}
			if(empty($options))
			{
				$options = array (
					'host'          => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
					'port'          => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
					'timeout'       => C('REDIS_TIMEOUT') ? C('REDIS_TIMEOUT') : 10,
					//pengyong 2013年12月13日 16:21:01 add config
					'persistent'    => C('REDIS_PERSISTENT') ? C('REDIS_PERSISTENT') : false,
					'auth'		=> C('REDIS_AUTH') ? C('REDIS_AUTH'):null,//auth认证
					'rw_separate'	=> C('REDIS_RW_SEPARATE') ? C('REDIS_RW_SEPARATE') :false,//主从分离
				);
			}
			$this->options =  $options;
			$this->options['expire'] =  isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
			$this->options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');
			$this->options['length'] =  isset($options['length'])?  $options['length']  :   0;
			$this->options['func'] = $options['persistent'] ? 'pconnect' : 'connect';
			$this->handler  = new Redis;

		}


		/**
		 * 主从连接
		 * @access public
		 * @param bool $master true=主连接
		 */


		public function master($master=false)
		{

			$host = explode(",",$this->options['host']);

			if(count($host) > 1 && $master ==false && $this->options['rw_separate']==true)
			{
				array_shift($host);
			}
			if($this->options['rw_separate']==false)
			{
				shuffle($host);
			}
			$this->options['master'] = $master ==true ? $host[0]:'';
			$this->options['slave'] = $master ==false ? $host[0]:'';

			$func = $this->options['func'];
			$connect = $this->options['timeout'] ===false ?
			$this->handler->$func($host[0], $this->options['port']) :
			$this->handler->$func($host[0], $this->options['port'], $this->options['timeout']);
			//pengyong 2013年12月13日 16:17:50 支持认证模式
			if($this->options['auth']!=null)
			{
					$this->handler->auth($this->options['auth']);
			}
			$this->options['db'] = isset($this->options['db']) ? $this->options['db'] : 0;
			$this->handler->select($this->options['db']);
		}

		/**
		 * 读取缓存
		 * @access public
		 * @param string $name 缓存变量名
		 * @return mixed
		 */


		public function get($name)
		{
			N('cache_read',1);
			$this->master(false);
			#F('read',$this->options['slave']);
			$value = $this->handler->get($this->options['prefix'].$name);
			$jsonData  = json_decode( $value, true );
			return ($jsonData === NULL) ? $value : $jsonData;	//检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
		}
		/**
		* 读取缓存
		* @param: array $keys 缓存数组
		*/
		public function mget($keys)
		{
			N('cache_read',1);
			$this->master(false);
			#F('read',$this->options['slave']);
			$value = $this->handler->mget($keys);
			$jsonData  = json_decode( $value, true );
			return ($jsonData === NULL) ? $value : $jsonData;	//检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
		}
		/**
		 * 写入缓存
		 * @access public
		 * @param string $name 缓存变量名
		 * @param mixed $value  存储数据
		 * @param integer $expire  有效时间（秒）
		 * @return boolen
		 */


		public function set($name, $value, $expire = null)
		{
			N('cache_write',1);
			$this->master(true);
			#F('write',$this->options['master']);
			if(is_null($expire))
			{
				$expire  =  $this->options['expire'];
			}
			$name   =   $this->options['prefix'].$name;
			//对数组/对象数据进行缓存处理，保证数据完整性
			$value  =  (is_object($value) || is_array($value)) ? json_encode($value) : $value;
			//删除缓存操作支持
			if($value===null)
			{
				return $this->handler->delete($this->options['prefix'].$name);
			}
			if(is_int($expire) && $expire !=0)
			{
				$result = $this->handler->setex($name, $expire, $value);
			}
			else
			{
				$result = $this->handler->set($name, $value);
			}
			if($result && $this->options['length']>0)
			{
				// 记录缓存队列
				$this->queue($name);
			}
			return $result;
		}
		/**
		* 删除缓存
		* @access public
		* @param string $name 缓存变量名
		* @return boolen
		*/


		public function rm($name)
		{
			$this->master(true);
			//@yfy
			if(is_array($name)){
				return $this->handler->delete($name);
			}else{
				return $this->handler->delete($this->options['prefix'].$name);
			}
		}


		/**
		* 清除缓存
		* @access public
		* @return boolen
		*/


		public function clear()
		{
			$this->master(true);
			return $this->handler->flushDB();
		}

		/**
		 * 获取匹配key
		 * @access public
		 * @return boolen
		 * @author yfy
		 */
 		public function key($key)
		{
			$this->master(true);
			return $this->handler->keys($key);
		}
		/**
		 * 写入缓存
		 * @access public
		 * @param string $name 缓存变量名
		 * @param mixed $value  存储数据
		 * @return boolen
		 * @author:tb
		 */
		public function sAdd($name,$value)
		{
			$this->master(true);
			$this->handler->SADD($name,$value);
		}
		/**
		 * 获取匹配key
		 * 返回true存在 false不存在
		 * @author:tb
		 * **/
		 public function setKeys($name,$value)
		{
			$this->master(true);
			return $this->handler->sIsMember($name,$value);   //结果：存在true 不存在false
		}
		/**
		 * 返回集合key中的所有成员
		 * @access public
		 * @return array
		 * @author yfy
		 */
		public function smembers($key)
		{
			$this->master(true);
			return $this->handler->SMEMBERS($key);
		}

		/**
		* 随机返回一个集合成员
		* @param:string $key
		*/
		public function srandmember($key){
			$this->master(true);
			return $this->handler->SRANDMEMBER($key);
		}
		/**
		* 删除集合中的成员
		* @param: string $key
		* @param: string $member
		*/
		public function srem($key,$member){
			$this->master(true);
			return $this->handler->SREM($key,$member);
		}
		/**
		 * 将一个或多个值value插入到列表key的表头
		 * @access public
		 * @author yfy
		 */
		public function lpush($key,$value)
		{
			$this->master(true);
			return $this->handler->lpush($key,$value);
		}

		/**
		 * 返回列表key的长度
		 * @access public
		 * @author yfy
		 */
		public function llen($key)
		{
			$this->master(true);
			return $this->handler->llen($key);
		}

		/**
		 * 移除并返回列表key的头元素
		 * @access public
		 * @author yfy
		 */
		public function lpop($key)
		{
			$this->master(true);
			return $this->handler->lpop($key);
		}
		/**
		* 设置key的过期时间
		*/
		public function expire($key,$time){
			$this->master(true);
			return $this->handler->EXPIRE($key,$time);
		}
		//关闭链接
		public function close()
		{
			$this->handler->close();
		}

		/**
		 * 将key中储存的数字值增一
		 * @param:string $key
		 */
		public function incr($key){
			$this->master(true);
			return $this->handler->INCR($key);
		}

		/**
		* 添加有序集合
		*/
		public function zadd($key,$sort,$val){
			$this->master(true);
			return $this->handler->ZADD($key,$sort,$val);
		}
		/**
		* 返回有序集key中，指定区间内的成员。
		* @param:
		*/
		public function zangebyscore($key,$start,$stop,$array=''){
			$this->master(true);
			return $this->handler->ZRANGEBYSCORE($key,$start,$stop,$array);
		}
		/**
		* 析构释放连接
		* @access public
		*/


		public function __destruct()
		{
			//$this->handler->close();
		}
}
