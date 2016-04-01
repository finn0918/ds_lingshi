///import core
///commands 本地图片引导上传
///commandsName  WordImage
///commandsTitle  本地图片引导上传


UE.plugins["selectmodule"] = function(){
    var me = this,
        images;
    me.commands['selectmodule'] = {
        execCommand : function() {
            //me.execCommand("inserthtml","http://192.168.45.152/x/");
			UE.insertTxt=function(txt){
				me.execCommand("inserthtml",txt);
			}
			$("#popbox2").show().css({top:top.jin.wScrollTop()+200});
			
        }
    };
};