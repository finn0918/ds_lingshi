<?php
/**
 * 首页
 * @author feibo
 *
 */
class IndexAction extends BaseAction {

    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index() { 

        $menuid = 103;
        $r = M('node')->field('module_name,action_name')->where('id=' . $menuid)->find();
        if ($r) {
            $current_pos = $r['module_name'] . '&nbsp;&gt;&nbsp;' . $r['action_name'];
            $this->assign('current_pos',$current_pos);
        }
        $this->display();
    }

    /**
    +----------------------------------------------------------
    * 当前位置
    +----------------------------------------------------------
    */
    public function currentPos() {
        $menuid = intval($_REQUEST['menuid']);
        
        $r = M('node')->field('module_name,action_name')->where('id=' . $menuid)->find();
        if ($r) {
            echo $r['module_name'] . '&nbsp;&gt;&nbsp;' . $r['action_name'];
        }
        exit();
    }

    /**
     +----------------------------------------------------------
     * 清理缓存
     +----------------------------------------------------------
     */
    function clearCache() {
    	
        import("ORG.Io.Dir");
        $dir = new Dir();
        
        if(is_dir(CACHE_PATH)) {
            $dir->del(CACHE_PATH);
        }
        
        if(is_dir(TEMP_PATH)) {
            $dir->del(TEMP_PATH);
        }
        
        if(is_dir(LOG_PATH)) {
            $dir->del(LOG_PATH);
        }
        if(is_dir(DATA_PATH . '_fields/')) {
            $dir->del(DATA_PATH . '_fields/');
        }
        
        if(is_dir("./admin/Runtime/Cache/")) {
            $dir->del("./admin/Runtime/Cache/");
        }
        
        if(is_dir("./admin/Runtime/Temp/")) {
            $dir->del("./admin/Runtime/Temp/");
        }
        
        if(is_dir("./admin/Runtime/Logs/")) {
            $dir->del("./admin/Runtime/Logs/");
        }
        
        if(is_dir("./admin/Runtime/Data/_fields/")) {
            $dir->del("./admin/Runtime/Data/_fields/");
        }
        $this->display('index');
    }

}
?>
