function redirect(url) {
	location.href = url;
}
//滚动条
$(function(){
	$(":text").addClass('input-text');
})

/**
 * 全选checkbox,注意：标识checkbox id固定为为check_box
 * @param string name 列表check名称,如 uid[]
 */
function selectall(name) {
	if ($("#check_box").attr("checked")==false) {
		$("input[name='"+name+"']").each(function() {
			this.checked=false;
		});
	} else {
		$("input[name='"+name+"']").each(function() {
			this.checked=true;
		});
	}
}
//禁止选择一级分类
function check_cate(obj){
	var level=parseInt($("option:selected",$(obj)).attr('level'));
	var pid=parseInt($("option:selected",$(obj)).attr('pid'));
	if(pid==0||level==0||level==1){
		alert("一级、二级分类禁止选择!");
		$('option[value="0"]',$(obj)).attr('selected','selected');
	}
}

//标签页点击
function SwapTab(name,cls_show,cls_hide,cnt,cur){
    for(i=1;i<=cnt;i++){
		if(i==cur){
			 $('#div_'+name+'_'+i).show();
			 $('#tab_'+name+'_'+i).attr('class',cls_show);
		}else{
			 $('#div_'+name+'_'+i).hide();
			 $('#tab_'+name+'_'+i).attr('class',cls_hide);
		}
	}
}

//取消backspace
var cancelbp = function(e) {
    var keyCode = 0;
    var target = '';
    if (e) {
        keyCode = e.which || e.keyCode;
        target = e.target.tagName;
    } else {
        keyCode = window.event.keyCode;
        target = window.event.srcElement.tagName;
    }
    target = target.toLowerCase();
    if (keyCode == 8 && (target == 'body' || target == 'html')) {
        return false;
    }
}
