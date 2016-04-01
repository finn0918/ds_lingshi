define(function(require, exports, module) {
	
	var vqd = require("../base");
	
	var appKey = '';
	var limit = true;               //是否有限制
	var limit_count = 999;           //剩余的次数
	var limit_time = 3600;           //次数多久刷新一次（cookie超时时间）
	var resData = '';
	
	var extra = 0;                   //当前已使用的 额外次数
	var maxExtra = 1;                //最多可使用的 额外次数
	
	// 判断浏览器类型
	var browser = {
		versions:function(){
			var u = navigator.userAgent, app = navigator.appVersion;
			return {
				mobile: !!u.match(/AppleWebKit.*Mobile.*/),
				ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/),
				android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1,
				iPhone: u.indexOf('iPhone') > -1, //是否为iPhone或者QQHD浏览器 
				iPad: u.indexOf('iPad') > -1, //是否iPad]
				weixin : u.match("MicroMessenger")
			};
		}(),
		language:(navigator.browserLanguage || navigator.language).toLowerCase()
	}
	
	//初始化，配置
	function init(keyName,limits,count,times){
		appKey = keyName;
		limit = limits;
		limit_count = count;
		limit_time = times;
		var checkCookie = vqd.cookie(appKey);
		//将次数记录cookie
		var exp = {"expires":1};
		!checkCookie && vqd.cookie(appKey,limit_count,exp);
	};
	//检测抽奖次数
	function getLimit(){
		//从cookie中获取当前次数
		//num == 0 =》 switchs == false;
		var count = vqd.cookie(appKey) ? vqd.cookie(appKey) : 0;
		var onoff = count > 0 ? true : false;
		var data = {
			num     : count,              //剩余次数
			switchs : onoff               //是否还能抽
		}
		return data;
	};
	function getRank(callback){
		//请求排行榜
		$.ajax({
			type: "GET",
			url: "lib/lhj_ajax_list.php",
			dataType : "json",
			success : callback
		});
	}
	//增加额外次数的操作
	function setExtra(){
		var extraKey = appKey + "extra";
		var extraNow = vqd.cookie(extraKey);
		if(extraNow > 0){
			extra -- ;
			limit_count ++;
			//记录cookie
			vqd.cookie(appKey,limit_count,{"expires":1});
			vqd.cookie(extraKey,extra,{"expires":1});
		}
		return limit_count;
	};
	
	//获取cookie
	function getCookie(name) { 
		var arr,reg = new RegExp("(^| )"+name+"=([^;]*)(;|$)");
		if(arr = document.cookie.match(reg)) {
			return unescape(arr[2]);
		} else {
			return null; 
		}
	} 
	
	//请求抽奖结果
	function setRequest(conf,done,err){
		//conf   配置信息
		//done   成功回调
		//err    错误回调
		var checkCookie = vqd.cookie(appKey);
		var ajaxCallback = function(data){
			resData = data;
			checkCookie --;
			vqd.cookie(appKey,checkCookie,{"expires":1});
			done(data,checkCookie,{"expires":1});
		};
		if(checkCookie > 0){
			$.ajax({
				type: "undefined" == typeof conf.postType ? "GET" : conf.postType,
				url: conf.url,
				data:conf.datas,
				dataType : "json",
				success : ajaxCallback,
				error : err
			});
		}else{
			if(!browser.versions.weixin){
				alert("抽奖次数已用完");
			}else {
				var share = getCookie("share")?getCookie("share"):'';
				if(!share){
					$(".cover_bg").show();
				}
			}
		}
	};
	
	$(".cover_bg").on("click",function(){	
		$(this).hide();
	})
	
	//创建提交表单
	function creatForm(){
		var giftCateDegree = $(".giftCate").text();
		console.log(giftCateDegree);
		if(giftCateDegree == "4" || giftCateDegree == "5") {
			console.log(giftCateDegree+",");
			$(".zj_tips").show();
		}
		var formHtml = '';
		formHtml += '<input type="text" class="ptext" id="'+appKey+'name" placeholder="请输入您的名字" />';
		formHtml += '<input type="tel" pattern="[0-9]*" class="ptext" id="'+appKey+'tel" placeholder="请输入您的电话号码" />';
		formHtml += '<input type="text" class="ptext" id="'+appKey+'address" placeholder="请输入您的收货地址" />';
		formHtml += '<input type="text" class="ptext" id="'+appKey+'mail" placeholder="请输入您的电子邮箱" />';
		formHtml += '<input type="button" class="psubmit" id="'+appKey+'submit" value="提交信息" />';
		return formHtml;
	};
	//收集用户信息(表单提交)
	function collectUserInfo(urls,suc,err){
		//urls 请求地址
		//suc  成功回调
		//err  错误回调
		var telVal = $("#" + appKey + "tel").val(),
			nameVal = $("#" + appKey + "name").val(),
			addressVal = $("#" + appKey + "address").val(),
			emailVal = $("#" + appKey + "mail").val();
		
		if(telVal == '' || nameVal == '' || addressVal == '' || emailVal == ''){
			alert("请认真填写您的信息!");
			return false;
		}
		
		var formdata = {
			hit_id : resData.hit_id,
			auth_code : resData.auth_code,
			tel : telVal,
			name : nameVal,
			address : addressVal,
			mail : emailVal
		};
		$.ajax({
			type: "POST",
			url: "lib/lhj_coll.php",
			dataType : "json",
			data : formdata,
			success : suc,
			error : err
		});
		return false;
	};
	function errFun(){
		alert("Requset Error!");
	}
	
	exports.init = init;
	exports.check = getLimit;
	exports.rank = getRank
	exports.extra = setExtra;
	exports.request = setRequest;
	exports.collect = collectUserInfo;
	exports.cform = creatForm;
})