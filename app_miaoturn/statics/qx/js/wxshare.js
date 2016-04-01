define(function(require, exports, module) {
	
	var v = require('./variable');
	
	exports.share = function (img,tlink,title,content){
		function onBridgeReady(){
			WeixinJSBridge.on('menu:share:appmessage', function(argv) {
				WeixinJSBridge.invoke('sendAppMessage', {
					"img_url" :  img ? img : v.imgUrl,
					"link"    :  tlink ? tlink : v.timeLineLink,
					"desc"    :  title ? title : v.tTitle,
					"title"   :  content ? content : v.tContent
				}, checkCookie);
			});
			WeixinJSBridge.on('menu:share:timeline', function(argv) {
				WeixinJSBridge.invoke('shareTimeline', {
					"img_url"    :  img ? img : v.imgUrl,
					"img_width"  :  "640",
					"img_height" :  "640",
					"link"       :  tlink ? tlink : v.timeLineLink,
					"desc"       :  content ? content : v.tContent,
					"title"      :  title ? title : v.tTitle
				}, checkCookie);
			});
		}
		function checkCookie(res){
			// 返回res.err_msg,取值
			// share_timeline:cancel 用户取消
			// share_timeline:fail　发送失败
			// share_timeline:ok 发送成功
			//WeixinJSBridge.log(res.err_msg);
			//alert(res.err_msg);
		}
		document.addEventListener('WeixinJSBridgeReady', onBridgeReady , false);
	}
	
});

