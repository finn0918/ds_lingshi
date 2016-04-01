<?php
/**
 * 后台缓存类
 * @author feibo
 *
 */
class CacheAction extends BaseAction
{
	
	/**
	 +----------------------------------------------------------
	 * 缓存首页
	 +----------------------------------------------------------
	 */
    function index() {
        $this->display();
    }

    /**
     +----------------------------------------------------------
     * 缓存清理
     +----------------------------------------------------------
     */
    function clearCache() {
        $i = intval($_REQUEST['id']);
        if (! $i)
        {
            $this->error('操作失败');
        }
        else
        {
            import("ORG.Io.Dir");
            $dir = new Dir();
            switch ($i)
            {
                case 1:
                    //更新全站缓存
                    is_dir(CACHE_PATH) && $dir->del(CACHE_PATH);
                    is_dir(DATA_PATH . '_fields/') && $dir->del(DATA_PATH . '_fields/');
                    is_dir(TEMP_PATH) && $dir->del(TEMP_PATH);
                    break;
                case 2:
                    //后台模版缓存
                    is_dir(CACHE_PATH) && $dir->del(CACHE_PATH);
                    break;
                case 3:
                    //前台模版缓存
                    //is_dir("./index/Runtime/Cache/") && $dir->del("./index/Runtime/Cache/");
                   // is_dir("./index/Html/") && $dir->del("./index/Html/");
                    break;
                case 4:
                    //数据库缓存
                    is_dir(DATA_PATH . '_fields/') && $dir->del(DATA_PATH . '_fields/');
                    break;
                case 5:
                    //客户端缓存
                    $topicKey = C('LS_TOPIC_LIST');//专题列表
                    $saleKey = C('LS_SPECIAL_SALE_LIST');//特卖列表
                    $dayKey = C('LS_DAILY_ON_NEW');//每日上新
                    $brandKey = C('LS_BRAND_SPU_LIST');//品牌团
                    $foundKey = C("LS_FOUND_AD");// 发现页广告位
                    $ls_home = "ls_home";
                    $mainKey = "mainActivity";//主会场
                    $options = C('REDIS_CONF');
                    $options['rw_separate'] = true; //写分离
                    $redis = Cache::getInstance('Redis',$options);
                    $redis->rm($topicKey);
                    $redis->rm($foundKey);
                    $redis->rm($saleKey);
                    $redis->rm($dayKey);
                    $redis->rm($brandKey."*");
                    $redis->rm($ls_home);
                    $redis->rm($mainKey);
                    $redis->close();
                    break;
                default:
                    break;
            }
            $runtime = defined('MODE_NAME') ? '~' . strtolower(MODE_NAME) . '_runtime.php' : '~runtime.php';
            $runtime_file_admin = RUNTIME_PATH . $runtime;
            is_file($runtime_file_admin) && @unlink($runtime_file_admin);
            $this->success('更新完成', U('Cache/index'));
        }
    }

}
?>