//这是关于main加载内容
//noRightMain=true标示是否允许重新计算主容器高度
//winResetHeight && winResetHeight();子容器手工调用重新计算高度
//parent.winResetHeight && parent.winResetHeight();子子容器手工调用子容器重新计算高度

//这是关于table内容
//带.unselectbox样式的容器将不会触发点击选中
//默认table的外容器请添加id名为fbtablebox

var winResetHeight=function(){
	if(!top.pl){
		return false;
	}
	top.pl.rightMainHeight("auto");
	var sh=document.documentElement.scrollHeight;
	if(top.pl && (typeof noRightMain=="undefined")){
		if(sh<top.pl.minHeight()){
			sh=top.pl.minHeight();
		}
		top.pl.rightMainHeight(sh);
	}
};
var fbtable=function(){
	var fbtablebox=$("#fbtablebox");
	fbtablebox.click(function(e){
		if($(e.target).closest(".unselectbox").size()>0){//unselectbox
			
		}else if($(e.target).closest(".fbtable_checkbox").size()>0){//fbtable_checkbox选中效果
			var tr=$(e.target).closest(".fbtable_body_tr");
			if($(e.target).closest(".fbtable_checkbox").find("[type=checkbox]")[0].checked){
				tr.addClass("fbtable_selected");
			}else{
				tr.removeClass("fbtable_selected");
			}
		}else{//fbtable_body_tr选中效果
			var target=$(e.target).closest(".fbtable_body_tr");
			if(target.size()>0){
				if(target.hasClass("fbtable_selected")){
					target.removeClass("fbtable_selected");
				}else{
					target.addClass("fbtable_selected");
				}
			}
		}
	});
	//全选外容器点击事件
	fbtablebox.find(".fbtable_first_th").click(function(){
		if($(this).find("[type=checkbox]").size()>0){
			if($(this).find("[type=checkbox]")[0].checked){
				fbtablebox.find(".fbtable_body_tr").each(function(){
					var target=$(this);
					target.addClass("fbtable_selected");
				});
			}else{
				fbtablebox.find(".fbtable_body_tr").each(function(){
					var target=$(this);
					if(target.hasClass("fbtable_selected")){
						target.removeClass("fbtable_selected");
					}else{
						target.addClass("fbtable_selected");
					}
				});
			}
		}else{
			fbtablebox.find(".fbtable_body_tr").each(function(){
				var target=$(this);
				if(target.hasClass("fbtable_selected")){
					target.removeClass("fbtable_selected");
				}else{
					target.addClass("fbtable_selected");
				}
			});
		}
	});
};
$(document).ready(function(){
	winResetHeight();
	fbtable();
	setTimeout(winResetHeight,500);//保障体系
});
window.onload=function(){
	winResetHeight();
	setTimeout(winResetHeight,500);//保障体系
}