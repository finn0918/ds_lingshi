define(function(require, exports, module) {
	
	var jquery = require("./jquery.min");
	
	// 分享文案
	var shareData = {
		title: '可能是史上最喷饭的APP',
		desc: '搞笑视频，十秒戳中笑点',
		link: window.location.href,
		imgUrl: 'http://v.lengxiaohua.cn:8080/statics/wap/images/shareIcon.png',
		trigger: function (res) {
		},
		success: function (res) {
		},
		cancel: function (res) {
		},
		fail: function (res) {
		},
		complete: function (res) {
		}
	};
	
	setTimeout(function(){
         $.ajax({
             type : "post",
             //url  : "/index.php?m=wap&a=signPackage",
             data : {
                 url :location.href.split('#')[0]
             },
             dataType : "json",
             success : function(d){
                 wx.config({
                     debug     : false,
                     appId     : d.appId,
                     timestamp : d.timestamp,
                     nonceStr  : d.nonceStr,
                     signature : d.signature,
                     jsApiList : [
                         'onMenuShareTimeline',
                         'onMenuShareAppMessage',
                         'onMenuShareQQ',
                         'onMenuShareWeibo'
                     ]
                 });
                 wx.ready(function(){
                     wx.onMenuShareAppMessage(shareData);
                     wx.onMenuShareTimeline(shareData);
                     wx.onMenuShareQQ(shareData);
                     wx.onMenuShareWeibo(shareData);
                 });
             }
         });
     },10)
})