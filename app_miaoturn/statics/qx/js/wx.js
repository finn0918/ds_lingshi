/**
 * Created by vqianduan on 15/5/28.
 */
function getCookie(name) 
{ 
	var arr,reg = new RegExp("(^| )"+name+"=([^;]*)(;|$)");
	if(arr = document.cookie.match(reg))
		return unescape(arr[2]); 
	else 
		return null; 
} 
var myurl = 'http://qing.comwei.com:8888/new_miaoturn/index.html';
var shareData = {
    title: '零食小喵请你免费吃零食！',
    desc: '下载并登陆【零食小喵app】，每天100份零食礼包免费吃！',
    link: myurl,
    imgUrl: "http://qing.comwei.com:8888/new_miaoturn/statics/images/shareImg.jpg?1",
    trigger: function (res) {
        //alert('用户点击分享到朋友圈');
        //alert(res);
    },
    success: function (res) {
		
		var share = getCookie("share")?getCookie("share"):'',
			cookieNum = getCookie("keys")?getCookie("keys"):'';
			
		if(!share) {
			if(cookieNum>0){
				document.cookie = "keys="+(parseInt(cookieNum)+1)+"";
				window.location.reload();
			}else if(cookieNum == 0){
				document.cookie = "keys=1";
				window.location.reload();
			}
			document.cookie = "share=true";
		}
    },
    cancel: function (res) {
        //alert('已取消');
    },
    fail: function (res) {
        //alert(JSON.stringify(res));
    },
    complete: function (res) {
        //weibo
        //alert(JSON.stringify(res));
    }
};

setTimeout(function(){
    $.ajax({
        type : "post",
        url  : "lib/wx.php",
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
                jsApiList : ["onMenuShareTimeline","onMenuShareAppMessage","onMenuShareQQ","onMenuShareWeibo","chooseImage","previewImage","uploadImage"]
            });
            wx.ready(function(){
                wx.onMenuShareAppMessage(shareData);
                wx.onMenuShareTimeline(shareData);
                wx.onMenuShareQQ(shareData);
                wx.onMenuShareWeibo(shareData);
            });
            //document.write("<script>wx.ready(function () {wx.onMenuShareAppMessage(shareData);wx.onMenuShareTimeline(shareData);wx.onMenuShareQQ(shareData);wx.onMenuShareWeibo(shareData);})<\/script>");
        }
    });
},10);

