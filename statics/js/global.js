// feibo
//
// http://feibo.cn
//
// Copyright 2012, by jin
//
// 飞博共创  微信内嵌页面
//

/*var gurl={
	initRequest:"init-request.php?",//初始化聚合请求
	comm:{
		add:"comm-add.php?",//新增评论
		more:"comm-more.php?"//点击查看更多
	},
	hy_more:"hy-more-"+gdata.history.template+".php?",//公共主页，再看几条，default模版
	feedbackAdd:"feedback-add.php?",//小编互动反馈提交
	histroylistMore:"histroylist-more.php?",//往期回顾，再看几条
	taobaoAdd:"taobao-add.php?",//淘宝购买提交
	weibo:"weibo.php?",//关注微博
	statsAd:"stats.php?a=bannerStat&m=stats",//广告条-统计
	statsTB:"stats.php?a=taobaoStat&m=stats",//淘宝精品推荐-统计
	statsApp:"stats.php?a=appStat&m=stats",//app推荐统计
	statsDefault:"stats.php?"//默认统计地址
};*/
var gurl={
	initRequest:"/?a=ajax_data&m=ajax",//初始化聚合请求
	comm:{
		add:"/index.php?a=add_comment&m=comment",//新增评论
		more:"/?a=more_comment&m=comment"//点击查看更多
	},
	hy_more:"/?a=more&m=home",//公共主页，再看几条
	feedbackAdd:"?m=fb&a=add",//小编互动反馈提交
	histroylistMore:"/?a=ajax_before&m=before",//往期回顾，再看几条
	taobaoAdd:"/index.php?a=add&m=order",//淘宝购买提交
	weibo:"/?a=focus&m=ajax",//关注微博
	statsAd:"/index.php?a=bannerStat&m=stats",//广告条-统计
	statsTB:"index.php?a=productStat&m=stats",//淘宝精品推荐-统计
	statsApp:"/index.php?a=appStat&m=stats",//app推荐统计
	statsDefault:"http://wx.ta.feibo.com/tongji.html?"//默认统计地址
};

// jin.js模块
(function(window, undefined) {

	//使document指向参数window里的document
	var document = window.document,
		location = document.location;

	var jin={
		// fn:		类型判断
		// author:	jin
		// param:	{any} o 判断对象
		// return:	{string} 返回判断字符串
		//			可选字符串有:"Boolean","Number","String","Function","Array","Date","RegExp","Object","undefined",等
		type:function(o){
			//"Boolean","Number","String","Function","Array","Date","RegExp","Object","undefined"
			var t=Object.prototype.toString.call(o),l=t.length;
			return o==null?String(o):t.slice(8,l-1);
		},

		// fn:		删除左右两端的空格
		// author:	jin
		// param:	{string} str 要处理的字符串
		// return:	{string} 返回处理好的字符串
		trim:function(str){
			 return str.replace(/(^\s+|\s+$)/g,"");
		},

		//当前url
		nowUrl:window.document.location.href,

		// fn:		生成固定长度随机字符串
		// author:	jin
		// return:	{string} 随机字符串
		getRandom:function(){
			var start=1000,end=9999;
			jin._getRandom=jin._getRandom+1 || start;
			if(jin._getRandom>end){
				jin._getRandom=start;
			}
			return "j"+new Date().getTime()+jin._getRandom;
		},

		// fn:		url添加参数
		// author:	jin
		// param:	{string} key 键名
		// 			{string} val 键值
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串
		// remark:	请注意使用该函数时url中的锚点将会被免费清除
		addQueryValue:function(key,val,url){
			var strurl=url || jin.nowUrl;
			strurl=strurl.replace(/#.*/gi,"");
			if(strurl.indexOf("?")<0){
				return strurl+"?"+key+"="+val;
			}else{
				var laststr=strurl.slice(-1);
				if(laststr=="&"){
					return strurl+key+"="+val;
				}else{
					return strurl+"&"+key+"="+val;
				}
			}
		},

		// fn:		url添加字符串
		// author:	jin
		// param:	{string} str 待添加字符串
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串
		// remark:	请注意使用该函数时url中的锚点将会被免费清除
		addQueryString:function(str,url){
			var strurl=url || jin.nowUrl;
			strurl=strurl.replace(/#.*/gi,"");
			if(strurl.indexOf("?")<0){
				return strurl+"?"+str;
			}else{
				var laststr=strurl.slice(-1);
				if(laststr=="&"){
					return strurl+str;
				}else{
					return strurl+"&"+str;
				}
			}
		},

		// fn:		url参数设置
		// author:	jin
		// param:	{string} key 键名
		// 			{string} val 键值，不会被编码
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串
		setQueryValue:function(key,val,url){
			var strurl=url || jin.nowUrl;
			//url清理
			strurl=strurl.replace(new RegExp("([\&|\?])"+key+"=[^&]*(&{0,1})","ig"),"$1");
			if(val==null){
				return strurl;
			}else{
				//添加
				return jin.addQueryValue(key,encodeURIComponent(val),strurl);
			}
		},

		// fn:		url参数获取
		// author:	jin
		// param:	{string} key 键名
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串，值不会被解码
		getQueryValue:function(key,url){
			var strurl=url || jin.nowUrl;
			var vals=new RegExp("[\&|\?]"+key+"=([^&]*)&{0,1}","ig").exec(strurl);
			return vals?(vals[1].replace(/#.*/gi,"")):"";
		},
		
		// fn:		url添加参数
		// author:	jin
		// param:	{string} key 键名
		// 			{string} val 键值
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串
		// remark:	请注意使用该函数时url中的锚点将会被免费清除
		addQueryValue:function(key,val,url){
			var strurl=url || jin.nowUrl;
			strurl=strurl.replace(/#.*/gi,"");
			if(strurl.indexOf("?")<0){
				return strurl+"?"+key+"="+val;
			}else{
				var laststr=strurl.slice(-1);
				if(laststr=="&"){
					return strurl+key+"="+val;
				}else{
					return strurl+"&"+key+"="+val;
				}
			}
		},

		// fn:		url添加字符串
		// author:	jin
		// param:	{string} str 待添加字符串
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串
		// remark:	请注意使用该函数时url中的锚点将会被免费清除
		addQueryString:function(str,url){
			var strurl=url || jin.nowUrl;
			strurl=strurl.replace(/#.*/gi,"");
			if(strurl.indexOf("?")<0){
				return strurl+"?"+str;
			}else{
				var laststr=strurl.slice(-1);
				if(laststr=="&"){
					return strurl+str;
				}else{
					return strurl+"&"+str;
				}
			}
		},

		// fn:		url参数设置
		// author:	jin
		// param:	{string} key 键名
		// 			{string} val 键值，不会被编码
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串
		setQueryValue:function(key,val,url){
			var strurl=url || jin.nowUrl;
			//url清理
			strurl=strurl.replace(new RegExp("([\&|\?])"+key+"=[^&]*(&{0,1})","ig"),"$1");
			if(val==null){
				return strurl;
			}else{
				//添加
				return jin.addQueryValue(key,encodeURIComponent(val),strurl);
			}
		},

		// fn:		url参数获取
		// author:	jin
		// param:	{string} key 键名
		// 			{string} [url] 待处理url；默认使用当前url
		// return:	{string} 结果url字符串，值不会被解码
		getQueryValue:function(key,url){
			var strurl=url || jin.nowUrl;
			var vals=new RegExp("[\&|\?]"+key+"=([^&]*)&{0,1}","ig").exec(strurl);
			return vals?(vals[1].replace(/#.*/gi,"")):"";
		},

		// scrolltop
		wScrollTop:function(v){
			var d = document;
			if(jin.type(v)!="undefined"){
				window.pageYOffset=d.documentElement.scrollTop=d.body.scrollTop=v;
				return v;
			}
			return window.pageYOffset || d.documentElement.scrollTop || d.body.scrollTop;
		},

		// scrollleft
		wScrollLeft:function(){
			var d = document;
			return window.pageXOffset || d.documentElement.scrollLeft || d.body.scrollLeft;
		},

		// fn:		scrollWidth
		// author:	jin
		// param:	{boolean} r 是否刷新当前数据
		// return:	{number} scrollWidth
		wScrollWidth:function(r){
			var d = document;
			if(!this._scrollWidth || r){
				this._scrollWidth=d.documentElement.scrollWidth;
			}
			return this._scrollWidth;
		},
		// fn:		scrollHeight
		// author:	jin
		// param:	{boolean} r 是否刷新当前数据
		// return:	{number} scrollHeight
		wScrollHeight:function(r){
			var d = document;
			if(!this._scrollHeight || r){
				this._scrollHeight=d.documentElement.scrollHeight;
			}
			return this._scrollHeight;
		},

		// fn:		clientWidth
		// author:	jin
		// param:	{boolean} r 是否刷新当前数据
		// return:	{number} clientWidth
		wClientWidth:function(r){
			var d = document;
			if(!this._clientWidth || r){
				this._clientWidth=d.documentElement.clientWidth;
			}
			return this._clientWidth;
		},

		// fn:		clientHeight
		// author:	jin
		// param:	{boolean} r 是否刷新当前数据
		// return:	{number} clientHeight
		wClientHeight:function(r){
			var d = document;
			if(!jin._clientHeight || r){
				jin._clientHeight=window.innerHeight || d.documentElement.clientHeight;
			}
			return jin._clientHeight;
		},
		easeOut:function(t,b,c,d){
			return -c*(t/=d)*(t-2)+b;
		},
		anScroll:function(r,fnBefore,fnAfter){
			fnBefore && fnBefore();
			var b=jin.wScrollTop(),c=r-jin.wScrollTop(),d=40,t=0;
			var run=function(){
				jin.wScrollTop(Math.ceil(jin.easeOut(t,b,c,d)));
				if(t<d){t++;setTimeout(run,10);}
			};
			run();
		},
		//feibo.objCheck("o.taobao.list","Array",function(d){return (d.length>0);})
		objCheck:function(o,type,fn){
			var flag=false;
			var os=o.split(",");
			var i=0,len=os.length;
			for(;i<len;i++){
				try{
					var temp=eval(os[i]);
					if(typeof temp=="undefined"){
						flag=false;
						break;
					}else{
						if(type && type.indexOf(jin.type(temp))>-1){
							flag=fn?fn(temp):true;
						}else if(!type){
							flag=fn?fn(temp):true;
						}else{
							flag=false;
						}
					}
					temp=null;
				}
				catch(e){
					temp=null;
					flag=false;
					break;
				}
			}
			return flag;
		},
		//feibo.objAttrCheck(o,"taobao.list","Array",function(d){return (d.length>0);})
		objAttrCheck:function(o,attr,type,fn){
			if(typeof o=="undefined" || !o){
				return false;
			}else if(attr){
				var flag=false;
				var attrs=attr.split(",");
				var i=0,len=attrs.length;
				for(;i<len;i++){
					try{
						var temp=eval("o."+attrs[i]);
						if(typeof temp=="undefined"){
							flag=false;
							break;
						}else{
							if(type && type.indexOf(jin.type(temp))>-1){
								flag=fn?fn(temp):true;
							}else if(!type){
								flag=fn?fn(temp):true;
							}else{
								flag=false;
							}
						}
						temp=null;
					}
					catch(e){
						temp=null;
						flag=false;
						break;
					}
				}
				return flag;
			}
			return true;
		},
		//Create a cookie with the given name and value and other optional parameters.
		//
		//@example jin.cookie('the_cookie', 'the_value');
		//@desc Set the value of a cookie.
		//@example jin.cookie('the_cookie', 'the_value', {expires: 7, path: '/', domain: 'jquery.com', secure: true});
		//@desc Create a cookie with all available options.
		//@example jin.cookie('the_cookie', 'the_value');
		//@desc Create a session cookie.
		//@example jin.cookie('the_cookie', null);
		//@desc Delete a cookie by passing null as value.
		//
		//@param String name The name of the cookie.
		//@param String value The value of the cookie.
		//@param Object options An object literal containing key/value pairs to provide optional cookie attributes.
		//@option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
		//                            If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
		//                            If set to null or omitted, the cookie will be a session cookie and will not be retained
		//                            when the the browser exits.
		//@option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
		//@option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
		//@option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
		//                       require a secure protocol (like HTTPS).
		cookie : function(name, value, options) {
			if (typeof value != 'undefined') { // name and value given, set cookie
				options = options || {};
				if (value === null) {
					value = '';
					options.expires = -1;
				}
				var expires = '';
				if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
					var date;
					if (typeof options.expires == 'number') {
						date = new Date();
						date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
					} else {
						date = options.expires;
					}
					expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
				}
				var path = options.path ? '; path=' + options.path : '';
				var domain = options.domain ? '; domain=' + options.domain : '';
				var secure = options.secure ? '; secure' : '';
				document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
			} else { // only name given, get cookie
				var cookieValue = null;
				var len=parseInt(name.length+1);
				if (document.cookie && document.cookie != '') {
					var cookies = document.cookie.split(';');
					for (var i = 0; i < cookies.length; i++) {
						var cookie = cookies[i].replace(/(^\s+|\s+$)/g,"");
						// Does this cookie string begin with the name we want?
						//alert(cookie.substring(0, len)+"对"+len+"比"+(name + '='));
						if (cookie.substring(0,len) == (name + '=')) {
							cookieValue = decodeURIComponent(cookie.substring(len));
							break;
						}
					}
				}
				return cookieValue;
			}
		},
		//本地存储get
		getItem:function(key){
			if(localStorage&&typeof localStorage[key]!='undefined'){
				return localStorage.getItem(key);
			}else{
				return jin.cookie(key);  
			}
		},
		//本地存储set
		setItem:function(key,val,opts){
			if(localStorage&&typeof localStorage[key]!='undefined'){
				if(!val){
					localStorage.removeItem(key);
				}else{
					localStorage.setItem(key,val);
				}
			}else{
				jin.cookie(key,val,opts);
			}
		},
		loadImage:function(url,callback,data) {
			var img=new Image();
			img.src=url+"&t="+jin.getRandom();
			if(img.complete){
				callback && callback.call(img,data || {});
				return;
			}
			img.onload=function(){
				callback && callback.call(img,data || {});
			};
		}
	}

	window.jin=window.feibo=jin;

})(window);
//公共逻辑
var form={
	fieldValue:function(el, successful) {
		var _el=el[0];
		var n = _el.name, t = _el.type, tag = _el.tagName.toLowerCase();
		if (successful === undefined) {
			successful = true;
		}

		if (successful && (!n || _el.disabled || t == 'reset' || t == 'button' ||
			(t == 'checkbox' || t == 'radio') && !_el.checked ||
			(t == 'submit' || t == 'image') && _el.form && _el.form.clk != _el ||
			tag == 'select' && _el.selectedIndex == -1)) {
				return null;
		}

		if (tag == 'select') {
			var index = _el.selectedIndex;
			if (index < 0) {
				return null;
			}
			var a = [], ops = _el.options;
			var one = (t == 'select-one');
			var max = (one ? index+1 : ops.length);
			for(var i=(one ? index : 0); i < max; i++) {
				var op = ops[i];
				if (op.selected) {
					var v = op.value;
					if (!v) { // extra pain for IE...
						v = (op.attributes && op.attributes['value'] && !(op.attributes['value'].specified)) ? op.text : op.value;
					}
					if (one) {
						return v;
					}
					a.push(v);
				}
			}
			return a;
		}
		return el.val();
	},
	resetForm:function(f) {
		return f.each(function() {
			// guard against an input with the name of 'reset'
			// note that IE reports the reset function as an 'object'
			if (typeof this.reset == 'function' || (typeof this.reset == 'object' && !this.reset.nodeType)) {
				this.reset();
			}
		});
	},
	//fn:		表单验证
	//author:	jin {string} key 键名
	//param:	//{json} opt {
								//cName:{string}, 传送的键值队列
								//cList:{string}, 传送的验证对象
								//cVal:[{string}, 等待验证的内容
								//data:{key:val}] 带入返回值的名值对对象
							//}
				//①{array} cRegExp [{RegExp}] 正则表达式
				//②{array} cRegExp [{string}] 正则字符串简化
				//简化字符串包括
				//				notempty 不为空
				//				mobile 11位电话号码
				//				num 数字
				//				
				//{array} cMsg [{string}] 错误消息
				//{function} errFn({string} 错误消息,jQuery 当前对象) 错误回调
				//{function} [defauFn({jQuery} 当前对象)] 默认回调，无论成功或失败都会执行该回调
	//return:	如果验证不通过返回false，如果验证通过返回自动计算的命值对的json对象
	check:function(opt,cRegExp,cMsg,errFn,defauFn){
		//可选参数重定义
		var cList=opt.cList;
		//获取表单长度
		var cLen=cList.length;
		//错误返回
		if(cLen<1){
			alert("err by form.check():form check key length Less than 1");
			return false;
		}
		//获取cVal
		var cName=opt.cName || new Array(cLen),cVal=opt.cVal || new Array(cLen);
		//初始化标示和返回值变量
		var flag=false,formdata=opt.data || {},formlay={};
		//循环表单
		for(var i=0;i<cLen;i++){
			//获取当前对象
			var cl=cList[i];
			if(jin.type(cl)=="String"){
				cl=$("#"+cList[i]);
			}
			if(cl.size()<1){
				alert("err by form.check():$(#"+cList[i]+").size()<1");
			}
			//获取当前对象值
			var v=cVal[i] || form.fieldValue(cl);
			if(v==null){//form.fieldValue 会返回null 还有传入配置时也可能null
				v="";
			}
			defauFn && defauFn(cl);
			//正则判断
			if(jin.type(cRegExp[i])=="String"){
				switch(cRegExp[i]){
					case "notempty":
						cRegExp[i]=/\S+/;
						break;
					case "email":
						cRegExp[i]=/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/;
						break;
					case "mobile":
						cRegExp[i]=/^[0-9]{11}$/;
						break;
					case "num":
						cRegExp[i]=/\d+/;
						break;
				}
			}
			if(jin.type(cRegExp[i])=="RegExp"){
				if(cRegExp[i].test(v)){
					flag=true;
				}else{
					//alert(cRegExp[i]);
					errFn(cMsg[i],cl);
					flag=false;
					return flag;
				}
			}else{
				if(cRegExp[i]){
					flag=true;
				}else{
					errFn(cMsg[i],cl);
					flag=false;
					return flag;
				}
			}
			//设置返回的json名值对，名获取顺序为1.cName[];2.attr("name");3.idName
			formdata[cName[i] || cl.attr("name") || ((jin.type(cl)=="String")?cList[i]:cl.attr("id")) || "佚名有木有"]=v;
			formlay[cName[i] || cl.attr("name") || ((jin.type(cl)=="String")?cList[i]:cl.attr("id")) || "佚名有木有"]=cl;
		};
		if(flag){
			return {"formdata":formdata,"formlay":formlay};
		}else{
			return flag;
		}
	}
};
var gl={
	isEmail:function(strEmail){
		return /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/.test(strEmail);
	},
	// fn:			全屏消息提醒
	// author:		jin
	// relation:	jin.type
	//				jin.w*(Example:jin.wClientHeight)
	fullMsg:function(opt){
		var set=$.extend({
			html:"",//填充html
			idName:"full-msg",//id名
			className:"full-msg",//附加样式名
			mask:false,//遮罩对象，请使用fullMask来创建对象
			zIndex:false,//z-index，默认不设置
			animate:{//动画配置，false表示禁用动画
				time:1000,//动画时间
				anIn:"fade-in",//入场动画样式
				anOut:"fade-out"//出场动画样式
			},
			autoPosition:{//自动定位偏移量设置，false表示禁用自动定位
				xAdd:0,//弹窗定位x轴偏移量
				yAdd:0//弹窗定位y轴偏移量
			},//滚动或改变窗口大小时自动定位并偏移，取消该功能请设置false;
			isFixed:false,//是否fixed定位
			//set.isFixed==false时以下配置方有效
			scrollPosition:false,//scroll触发自动定位开关
			resizePosition:false//resize触发自动定位开关
		},opt);
		var	$fullMsg=$("#"+set.idName);
		if(!$fullMsg.size()){
			$("body").append('<div id="'+set.idName+'" class="'+set.className+'" style="left:-100%;top:-100%;'+(set.zIndex?'z-Index:'+set.zIndex:'')+'"><div class="flms-box">'+set.html+'</div></div>');
			$fullMsg=$("#"+set.idName);
		}else{
			set.zIndex && $fullMsg.css({zIndex:set.zIndex});
			set.className && $fullMsg.addClass(set.className);
			set.html && $fullMsg.html('<div class="flms-box">'+set.html+'</div>');
		}
		var _return={
			//自身对象
			self:$fullMsg,
			//设置获取宽度
			//jin.type(r)=="Number"设置宽度
			//jin.type(r)=="Boolean" && r==true刷新获取新宽度（自动计算）
			width:function(r){
				if(jin.type(r)=="Number"){
					_return._width=r;
				}else if(!_return._width || r){
					_return._width=$fullMsg.width();
				}
				return _return._width;
			},
			//设置获取高度
			//jin.type(r)=="Number"设置高度
			//jin.type(r)=="Boolean" && r==true刷新获取新高度（自动计算）
			height:function(r){
				if(jin.type(r)=="Number"){
					_return._height=r;
				}else if(!_return._height || r){
					_return._height=$fullMsg.height();
				}
				return _return._height;
			},
			//未传值情况，刷新宽/高
			//已传值情况，则设置宽/高
			boxResize:function(w,h){
				_return.width(w || true);
				_return.height(h || true);
				return _return;
			},
			//填充html
			html:function(html){
				$fullMsg.html('<div class="flms-box">'+html+'</div>');
				//刷新宽高值
				_return.boxResize();
				return _return;
			},
			//居中定位
			positionMiddle:function(){
				if(set.isFixed){
					$fullMsg.css({
						top:"50%",left:"50%",
						marginLeft:-(_return.width()/2)+set.autoPosition.xAdd,
						marginTop:-(_return.height()/2)+set.autoPosition.yAdd
					});
				}else{
					var top=(jin.wClientHeight()/2-_return.height()/2+set.autoPosition.yAdd)+jin.wScrollTop();
					if(top<0){top=0;}
					$fullMsg.css({
						left:"50%",
						marginLeft:-(_return.width()/2)+set.autoPosition.xAdd,
						top:top
					});
				}
				return _return;
			},
			//window resize绑定事件
			winResize:function(){
				jin.wClientWidth(true);
				jin.wClientHeight(true);
				if(set.resizePosition){
					_return.positionMiddle();
				}
				return _return;
			},
			//显示时绑定
			showBin:false,
			//显示函数
			show:function(){
				//自动定位
				if(set.autoPosition){
					_return.positionMiddle();
				}
				if(set.autoPosition && !set.isFixed){//开启非fixed下scroll自动定位功能
					if(set.scrollPosition){
						$(window).on("scroll",_return.positionMiddle);
					}
					if(set.resizePosition){
						$(window).on("onorientationchange" in window?"orientationchange":"resize",_return.winResize);
						//$(window).on("resize",_return.winResize);
					}
				}
				//修正为css控制
				//$fullMsg.show();
				_return.showBin && _return.showBin();
				return _return;
			},
			//隐藏时绑定
			hideBin:false,
			//隐藏函数
			hide:function(){
				if(set.autoPosition && !set.isFixed){//开启非fixed下scroll自动定位功能
					if(set.scrollPosition){
						$(window).off("scroll",_return.positionMiddle);
					}
					if(set.resizePosition){
						$(window).off("resize",_return.winResize);
					}
				}
				//修正为css控制
				//$fullMsg.hide();
				$fullMsg.css({top:"-100%",left:"-100%"})
				_return.hideBin && _return.hideBin();
				return _return;
			},
			animateShowStart:false,
			animateShowEnd:false,
			animateShow:function(){
				//动画初始回调
				_return.animateShowStart && _return.animateShowStart();
				_return.show();
				//清洁
				$fullMsg.removeClass(set.animate.anIn);
				$fullMsg.removeClass(set.animate.anOut);
				clearTimeout(_return.fullMsgTimeout);
				//显示
				$fullMsg.addClass(set.animate.anIn);
				//动画结束回调
				if(_return.animateShowEnd){
					_return.fullMsgTimeout=setTimeout(function(){
						_return.animateShowEnd && _return.animateShowEnd();
					},set.animate.time);
				}
				return _return;
			},
			animateHideStart:false,
			animateHideEnd:false,
			animateHide:function(){
				//动画初始回调
				_return.animateHideStart && _return.animateHideStart();
				//清洁
				set.animate && $fullMsg.removeClass(set.animate.anIn);
				set.animate && $fullMsg.removeClass(set.animate.anOut);
				clearTimeout(_return.fullMsgTimeout);
				//隐藏
				$fullMsg.addClass(set.animate.anOut);
				//动画结束回调
				_return.fullMsgTimeout=setTimeout(function(){
					_return.hide();
					_return.animateHideEnd && _return.animateHideEnd();
					$fullMsg.removeClass(set.animate.anOut);
				},set.animate.time);
				return _return;
			},
			//自动显示隐藏，默认配置
			showAndAutoHideSettings:{
				waitTime:2000,
				hoverShow:false,
				animate:true
			},
			//自动显示隐藏
			//不支持animateShowStart、animateShowEnd、animateHideStart
			//支持animateHideEnd
			showAndAutoHide:function(opt){
				//配置
				var settings=$.extend(_return.showAndAutoHideSettings,opt);
				//show
				var thisShow=function(){
					if(settings.animate){
						_return.animateShow();
					}else{
						_return.show();
					}
				};
				//hide
				var thisHide=function(){
					if(settings.animate){
						_return.animateHide();
					}else{
						_return.hide();
					}
				};
				//return
				_return.thisreturn={
					mouseover:function(){
						thisShow();
					},
					mouseout:function(){
						_return.fullMsgTimeout=setTimeout(function(){
							$fullMsg.off("mouseover",_return.thisreturn.mouseover);
							$fullMsg.off("mouseout",_return.thisreturn.mouseout);
							thisHide();
						},settings.waitTime);
					},
					show:function(){
						thisShow();
						_return.fullMsgTimeout=setTimeout(function(){
							_return.thisreturn.hide();
						},settings.waitTime);
						if(settings.hoverShow){
							$fullMsg
								.off("mouseover",_return.thisreturn.mouseover)
								.on("mouseover",_return.thisreturn.mouseover);
							$fullMsg
								.off("mouseout",_return.thisreturn.mouseout)
								.on("mouseout",_return.thisreturn.mouseout);
						}
					},
					hide:function(){
						thisHide();
						if(settings.hoverShow){
							$fullMsg.off("mouseover",_return.thisreturn.mouseover);
							$fullMsg.off("mouseout",_return.thisreturn.mouseout);
						}
					}
				};
				_return.thisreturn.show();
				return _return;
			},
			maskShowBin:false,
			maskShow:function(){
				if(!set.mask){alert("!set.mask");return _return;}//禁用mask时自动返回
				set.mask.show();
				_return.maskShowBin && _return.maskShowBin();
				if(set.animate){
					_return.animateShow();
				}else{
					_return.show();
				}
				return _return;
			},
			maskHideBin:false,
			maskHide:function(){
				if(!set.mask){return _return;}//禁用mask时自动返回
				set.mask.hide();
				_return.maskHideBin && _return.maskHideBin();
				if(set.animate){
					_return.animateHide();
				}else{
					_return.hide();
				}
				return _return;
			}
		};
		return _return;
	}
};
//公共页面相关
var gp={
	init:function(fn){
		$.ajax({url:gurl.initRequest,type:"GET",dataType:"json",data:{"media":gp.media(),ids:gdata.idlist,"cate_id":gdata.ua,id:gdata.id,action:gdata.action,"request_taobao":gdata.taobao?"1":"0","request_app":gdata.app?"1":"0","request_ad":gdata.ad?"1":"0","request_share":gdata.share?"1":"0","request_like":gdata.like?"1":"0","request_comm":gdata.comm?"1":"0",type:"app"},success:function(o){
			fn(o);
		}});
	},
	pvi:function(){
		if(feibo.cookie("feibo_pvi")){
			//nothing
		}else{
			feibo.cookie("feibo_pvi",feibo.getRandom(),{expires:new Date(Date.parse("Sun, 18 Jan 2038 00:00:00 GMT;"))});
		}
		return feibo.cookie("feibo_pvi");
	},
	si:function(){
		if(feibo.cookie("feibo_si")){
			//nothing
		}else{
			feibo.cookie("feibo_si",feibo.getRandom());
		}
		return feibo.cookie("feibo_si");
	},
	//微信状态
	wxState:function(s){
//		发送app消息：
//		send_app_msg:cancel 用户取消  1
//		send_app_msg:fail　发送失败   2
//		send_app_msg:ok 发送成功      0
//
//		分享到微博
//		share_weibo:no_weibo 用户未开通微薄        201
//		share_weibo:not_bind_qq　未绑定QQ          202
//		share_weibo:ok 发送成功                    0
//		share_weibo:cancel 用户取消                1
//		share_weibo:fail_<内部错误码> 发送失败     2
//
//		分享到朋友圈
//		share_timeline:cancel 用户取消            1
//		share_timeline:fail　发送失败             2
//		share_timeline:ok 发送成功                0
//
//		添加联系人
//		add_contact:added 已添加过               301
//		add_contact:cancel 用户取消              1
//		add_contact:fail　添加失败               2
//		add_contact:ok 添加成功                  0
		var _return={
			"cancel":"1",//用户取消
			"fail":"2",//发送失败
			"fail_":"2",//发送失败
			"ok":"0",//发送成功
			"added":"301",//已添加过
			"no_weibo":"201",//用户未开通微薄
			"not_bind_qq":"202"//未绑定QQ
		};
		var temp=s.split(":");
		if(temp.length>1){
			return _return[temp[1]];
		}
	},
	//媒介判断
	media:function(){
		if(gp._media){
			return gp._media;
		}else{
			if(/android/i.test(navigator.userAgent)){
				gp._media="android";
			}else if(/ipad|iphone|mac/i.test(navigator.userAgent)){
				gp._media="ios";
			}else{
				gp._media="other";
			}
			$("#layout").addClass("wp-"+gp._media);
			//额外手工改变媒介
			var media=feibo.getQueryValue("media");
			if(media){
				$("#layout").addClass("wp-"+media);
			}
			//媒介名称程序端有用，改名记得通知程序
			return gp._media;
		}
	},
	meidaNum:function(){
		switch(gp.media()){
			case "android":
				return "1";
				break;
			case "ios":
				return "2";
				break;
			case "other":
				return "3";
				breka;
		}
	},
	//按钮媒介判断
	btnMedia:function(){
		//为简化样式pc下所做的蠢事，remove后方便标签做:only-child判断
		$(".wp-other .android,.wp-other .ios").remove();
	},
	//添加联系人
	addContact:function(uid,fn){
		if(typeof WeixinJSBridge=="undefined"){
			return false;
		}else{
			WeixinJSBridge.invoke('addContact',{
				"webtype" : "1", // 添加联系人的场景，1表示企业联系人。
				"username" : uid　// 需要添加的联系人username
			},function(res){
				// 返回res.err_msg,取值
				// add_contact:added 已添加过
				// add_contact:cancel 用户取消
				// add_contact:fail　添加失败
				// add_contact:ok 添加成功
				WeixinJSBridge.log(res.err_msg);
				fn && fn(res.err_msg);
			});
		}
	},
	//分享朋友圈
	shareFriend:function(self,fn){
		var id=self.data("id"),
			imgurl=self.data("img"),
			title=self.data("title"),
			desc=self.data("desc"),
			t=feibo.getQueryValue("t");
		//微信私有接口
		if(typeof WeixinJSBridge=="undefined"){
			
		}else{
			WeixinJSBridge.invoke('shareTimeline',{
				"img_url": imgurl || "",
				//"img_width": "640",
				//"img_height": "640",
				"link": "/?a=detail&m=article&ref=pyq&t="+t+"&id="+id,
				"desc": desc,
				"title": title
			},function(res){
				// 返回res.err_msg,取值
				// share_timeline:cancel 用户取消
				// share_timeline:fail　发送失败
				// share_timeline:ok 发送成功
				//WeixinJSBridge.log(res.err_msg);
				fn && fn(res.err_msg);
			});
		}
		return false;
	},
	//统计
	stats:function(){
		if(!gdata.stats){
			return false;
		}
		//r参数构建
		if(feibo.getQueryValue("ref")=="pyq"){
			gdata.stats.r="pyq.wx.qq.com";
		}else{
			gdata.stats.r="wx.qq.com";
		}
		//页面类
		switch(gdata.stats.ref){//来源
			case "channel"://列表页
			case "review"://所有评论
			case "item"://详情页
				gdata.stats.ref=gdata.stats.ref+"/"+gdata.id;
				break;
		}
		switch(gdata.stats.u){//目标
			case "channel"://返回更多信息链接
			case "item"://详情页点击率统计
			case "review"://
				gdata.stats.u=gdata.stats.u+"/"+gdata.id;
				break;
		}
		//公共参数构建
		gurl.statsDefault2=gurl.statsDefault+"&r="+gdata.stats.r+"&pgv_pvi="+gp.pvi()+"&pgv_si="+gp.si()+"&ref="+gdata.stats.ref+"&ua="+gdata.ua;
		//页面加载统计
		feibo.loadImage(gurl.statsDefault2+"&u="+gdata.stats.u+"&p=0");
		//事件类
		$(document).on("click","[data-statsu]",function(e){
			gurl.statsDefault2=gurl.statsDefault2;
			var self=$(this);
			gp.flag=true;
			var _stats_u=self.data("statsu");
			switch(_stats_u){
				case "like"://喜欢
					if(feibo.cookie("likenum"+self.data("id"))){
						gp.fulltip().html("谢谢您能喜欢!<br/>你还可以点击右上角分享给好友。").showAndAutoHide();
						return;
					}
					var _like=$("#likenum"+self.data("id"));
					self.addClass("on");
					_like.parent().addClass("on");
					var num=parseInt(parseInt(_like.html())+1);
					_like.html(num);
					//cookie缓存1分钟
					feibo.cookie("likenum"+self.data("id"),num,{expires:(1/(24*60))});
					//self.removeAttr("data-statsu");
					feibo.loadImage(gurl.statsDefault2+"&u="+_stats_u+"/"+self.data("statsid")+"&p="+(self.data("statsp") || "0"));
					break;
				case "share"://点击分享
					//分享朋友圈模式
					gp.shareFriend(self,function(msg){
						feibo.loadImage(gurl.statsDefault2+"&u="+_stats_u+"/"+self.data("statsid")+"&p="+(self.data("statsp") || "0")+"&s="+gp.wxState(msg));
					});
					break;
				case "focuson"://链接模式
					if(gp.media()=="other"){
						feibo.loadImage(gurl.statsDefault2+"&u="+_stats_u+"/"+self.data("statsid")+"&p="+(self.data("statsp") || "0"),function(){
							if(gp.flag){
								gp.flag=false;
								//链接模式
								location.href=self.attr("link");
							}
						});
						setTimeout(function(){
							if(gp.flag){
								gp.flag=false;
								//链接模式
								location.href=self.attr("link");
							}
						},500);
					}else{//点击关注
						if(self.data("weixinid")){
							gp.addContact(self.data("weixinid"),function(msg){
								feibo.loadImage(gurl.statsDefault2+"&u="+_stats_u+"/"+self.data("statsid")+"&p="+(self.data("statsp") || "0")+"&s="+gp.wxState(msg));
							});
						}else{
							feibo.loadImage(gurl.statsDefault2+"&u="+_stats_u+"/"+self.data("statsid")+"&p="+(self.data("statsp") || "0"));
							location.href=self.attr("href");
						}
					}
					break;
				case "download"://点击下载
				case "channel"://返回更多信息链接
					feibo.loadImage(gurl.statsDefault2+"&u="+_stats_u+"/"+self.data("statsid")+"&p="+(self.data("statsp") || "0"),function(){
						if(gp.flag){
							gp.flag=false;
							//链接模式
							location.href=self.attr("href");
						}
					});
					setTimeout(function(){
						if(gp.flag){
							gp.flag=false;
							//链接模式
							location.href=self.attr("href");
						}
					},500);
					break;
			}
			return false;
		});
	},
	//更多精彩推荐
	weixin:function(){
		if(gp.media()=="other"){

		}else{//用户行为记录
			$(document).on("click","[data-wid]",function(e){
				var self=$(this);
				var wid=$(this).data("wid"),
					uid=$(this).data("uid"),
					cid=$(this).data("cid"),
					pid=$(this).data("pid");
					$.ajax({url:"/index.php",type:"GET",data:{"a":"index","m":"stats","uid":uid,"cid":cid,"wid":wid,"pid":pid,"media":gp.media()}});
				return false;
			});
		}
	},
	//淘宝精品推荐
	taobao:function(o,d){
		var mod_tb=$("#mod-tb"),
			tb_buy=$("#tb-buy"),
			tb_buy_form=$("#tb-buy-form");
		if(!o || mod_tb.size()<1){return;}
		//cookie
		$("#receiver").val(feibo.cookie("receiver"));
		$("#tel").val(feibo.cookie("tel"));
		$("#addressArea").val(feibo.cookie("address_area"));
		$("#address").val(feibo.cookie("address"));
		//数据填充
		if(feibo.objAttrCheck(o,"taobao.list","Array",function(d){return (d.length>0);})){
			var html=
				'<div class="mod-title tb-tit">'+o.taobao.business+' - '+o.taobao.title+'</div>'+
				'<div class="tb-con"><ul class="tb-goods">';

			$.each(o.taobao.list,function(i,v){
				html+=
					'<li data-list="true">'+
					'	<div class="tb-good-describe clearfix tb-detail-on" data-header="true" data-id="'+v.id+'" data-type="'+v.type+'" data-bid="'+v.bid+'" data-link="'+v.taobao_url+'">'+
					'		<dl class="tb-info">'+
					'			<dt>'+v.title+'</dt>'+
					'			<dd>价&nbsp;&nbsp;&nbsp;格&nbsp;：<span class="tb-sale">'+v.ksb_price+'元</span></dd>'+
					'			<dd>市&nbsp;场&nbsp;价&nbsp;：<span class="tb-sale-market">'+v.market_price+'元</span></dd>'+
					'		</dl>'+
					'		<div class="tb-img"><img src="'+v.img+'" alt="缩略图" /></div>'+
					'		<div class="tb-detail"></div>'+
					'	</div>'+
					'	<div class="tb-good-detail" jdetail="true" style="display:none;">'+
					'		<span class="tb-shop">'+v.desc+'</span>'+
					'	</div>'+
					'</li>';
			});
			html+='</ul></div>';
			mod_tb.html(html).show();
			html=null;
		}
		//查看详细
		mod_tb.on("click","[data-header]",function(){
			//重新开始
			var header=$(this);
			var detail=header.closest("[data-list]").find("[jdetail]");
			if(header.hasClass("tb-detail-on")){
				header.removeClass("tb-detail-on");
				header.addClass("tb-detail-off");
				detail.append(tb_buy.show());
				tb_buy_form.hide();
				$("#tb-buy-btn")
					.data("id",header.data("id"))
					.data("type",header.data("type"))
					.data("bid",header.data("bid"))
					.data("link",header.data("link"));
				$("#tb-buy-submit").data("id",header.data("id"));
				detail.show();
			}else{
				header.removeClass("tb-detail-off");
				header.addClass("tb-detail-on");
				detail.hide();
			}
			//关闭上次打开的历史对象
			if(mod_tb.lay_header && mod_tb.lay_header.data("id")!=header.data("id")){
				mod_tb.lay_header.removeClass("tb-detail-off");
				mod_tb.lay_header.addClass("tb-detail-on");
				mod_tb.lay_detail.hide();
			}			
			//重新设置历史对象
			mod_tb.lay_header=header;
			mod_tb.lay_detail=detail;
			return false;
		});
		//立即购买
		$("#tb-buy-btn,#tb-buy-cancel").on("click",function(){
			var self=$(this);
			//按钮点击统计
			if(self.attr("id")=="tb-buy-btn"){
				gp.flag=true;
				$.ajax({url:gurl.statsTB,type:"GET",data:{"cate_id":gdata.ua,"pid":self.data("id"),"bid":self.data("bid"),platform_id:gp.meidaNum()},success:function(o){
					if(gp.flag){
						gp.flag=false;
						//链接模式
						if(self.data("type")=="1"){location.href=self.data("link");}
					}
				}});
				setTimeout(function(){
					if(gp.flag){
						gp.flag=false;
						//链接模式
						if(self.data("type")=="1"){location.href=self.data("link");}
					}
				},500);
				//如果链接模式函数返回
				if(self.data("type")=="1"){
					return false;
				}
			}
			tb_buy_form.toggle();
			if(self.hasClass("tb-buy-on")){
				self.removeClass("tb-buy-on");
				self.addClass("tb-buy-off");
			}else{
				self.removeClass("tb-buy-off");
				self.addClass("tb-buy-on");
			}
			return false;
		});
		//提交
		$("#tb-buy-submit").on("click",function(){
			if(gp.taobao.issubmit){return;}//防止重复提交
			var self=$(this);
			var data=form.check(
				{cList:["receiver","tel","inum","addressArea","address","abst"]},
				["notempty","mobile","num","notempty","notempty",true],
				["亲，您填写的姓名或称呼有误！","亲，您填写的手机号码有误！","亲，您填写的购买数量有误！","亲，您填写的所在地区有误！","请填写详细地址",""],
				function(msg,cl){
					cl.addClass("err");
					gp.fulltip().html(msg).showAndAutoHide();
				},
				function(cl){
					cl.removeClass("err");
				}
			);
			//验证没通过
			if(!data){return false;}
			//数据补充
			data.formdata.aid=self.data("id"),gdata.detail?gdata.detail.id:"";
			data.formdata.rid=gdata.list?gdata.list.id:"";
			data.formdata.pid=$("#tb-buy-btn").data("id");
			//
			gp.taobao.issubmit=true;
			gp.fulltip().html("...提交中...").show();
			//setTimeout(function(){
			$.post(gurl.taobaoAdd,data.formdata,function(d){
				if(d.flag || d.flag==1 || d.flag=="1"){
					gp.fulltip().html("您的订单提交成功").showAndAutoHide();
					//form reset
					$("#inum").val("");
					$("#abst").val("");
					//cookie
					feibo.cookie("receiver",data.formdata.receiver);
					feibo.cookie("tel",data.formdata.tel);
					feibo.cookie("address_area",data.formdata.address_area);
					feibo.cookie("address",data.formdata.address);
				}else{
					gp.fulltip().html(d.info).showAndAutoHide();
				}
				gp.taobao.issubmit=false;
			},"json");
			//},3000);
			return false;
		});
	},
	//app推荐
	app:function(o){
		if(!o){return;}
		if(feibo.type(o.app)=="String"){
			if(o.app){
				$("#mod-app").html(o.app).show();
			}
			//以下是统计，app推荐的统计又不一样了，收尸吧！
			$("#mod-app").on("click","[platformid]",function(){
				var self=$(this);
				gp.flag=true;
				feibo.loadImage(gurl.statsApp+"&app_id="+self.attr("appid")+"&platform_id="+self.attr("platformid")+"&pos="+self.attr("pos")+"&type=app",function(){
					if(gp.flag){
						gp.flag=false;
						//链接模式
						location.href=self.attr("link");
					}
				});
				setTimeout(function(){
					if(gp.flag){
						gp.flag=false;
						//链接模式
						location.href=self.attr("link");
					}
				},500);
				return false;
			});
			//按钮点击又扩大到容器点击平台判断再次遭遇洪水
			$("#mod-app").on("click",".app-con-box",function(e){
				var self=$(this);
				if($("#layout").hasClass("wp-android")){
					self.find(".app-btn-android").find("a").click();
				}else if($("#layout").hasClass("wp-ios")){
					self.find(".app-btn-ios").find("a").click();
				}else if($("#layout").hasClass("wp-other")){
					self.find(".app-btn-pc").find("a").click();
				}
				return false;
			});
		}
	},
	//tip
	_fulltip:false,
	fulltip:function(txt){
		if(!gp._fulltip){
			gp._fulltip=gl.fullMsg({idName:"full-tip",className:"full-tip",isFixed:false});
			gp._fulltip.html(txt);
		}
		return {
			html:function(txt){gp._fulltip.html(txt);return gp.fulltip();},
			show:function(){gp._fulltip.animateShow();return gp.fulltip();},
			hide:function(){gp._fulltip.animateHide();return gp.fulltip();},
			showAndAutoHide:function(t){gp._fulltip.showAndAutoHide({waitTime:t || 2000});return gp.fulltip();}
		};
	},
	//广告模块
	ad:function(o){
		if(!o){return;}
		if(feibo.type(o.ad)=="Array"){
			$.each(o.ad,function(i,n){
				var ad=$("#lay-ad"+n.site);
				if(gp.media()=="ios"){
					ad.find("a").attr("href",n.ios_url);
				}else if(gp.media()=="android"){
					ad.find("a").attr("href",n.android_url);
				}else{
					ad.find("a").attr("href",n.other_url);
				}
				ad.attr("data-id",n.id);
				ad.attr("data-pos",n.pos);
				ad.find("img").attr("src",n.img);
				ad.show();
			});
		}
		$("#lay-ad1,#lay-ad2").on("click",function(){
			var self=$(this);
			var id=self.data("id"),
				pos=self.data("pos");
			gp.flag=true;
			$.ajax({url:gurl.statsAd,type:"GET",dataType:"json",data:{cate_id:gdata.ua,banner_id:id,platform_id:gp.meidaNum(),pos:pos},success:function(data){
				if(gp.flag){
					gp.flag=false;
					//链接模式
					location.href=self.find("a").attr("href");
				}
			}});
			setTimeout(function(){
				if(gp.flag){
					gp.flag=false;
					//链接模式
					location.href=self.find("a").attr("href");
				}
			},2000);
			return false;
		});
	},
	//分享数
	sharenum:function(o){
		if(!o){return;}
		if(feibo.type(o.share)=="Array"){
			$.each(o.share,function(i,v){
				$("#sharenum"+o.share[i].id).html(o.share[i].count);
			});
		}
	},
	//喜欢数
	likenum:function(o){
		if(!o){return;}
		if(feibo.type(o.like)=="Array"){
			$.each(o.like,function(i,v){
				$("#likenum"+o.like[i].id).html(o.like[i].count);
				if(feibo.cookie("likenum"+o.like[i].id)){
					$("#likenum"+o.like[i].id).html(feibo.cookie("likenum"+o.like[i].id));
				}
			});
		}
	},
	//往期回顾
	histroylistMore:function(){
		$("#histroylistmore").on("click",function(){
			var self=$(this);
			self.addClass("btnloading");
			$.ajax({url:gurl.histroylistMore,type:"GET",dataType:"json",data:{page:self.data("page"),cate_id:gdata.ua},success:function(data){
				if(data.flag){
					var html="";
					$.each(data.list,function(i,n){
						html+='<h3><a href="'+n.url+'">'+n.txt+'</a></h3>';
					});
					$("#hy-list").append(html);
					html="";
					self.data("page",data.page);
					self.removeClass("btnloading");
				}else{

				}
				if(!data.has_data){
					self.removeClass("btnloading");
					self.html("没有更多了亲！");
					$("#histroylistmore").off("click");
				}
			}});
			return false;
		});
	},
	//小编互动
	feedback:function(){
		if($("#mod-fb").size()<1){return;}
		//
		$('#fb-btn-add').on("click",function(){
			$("#fb-form").toggle();
			return false;
		});
		//cookie
		$("#fb-email").val(feibo.getItem("fb-email"));
		//发布
		$("#fb-submit").on("click",function(e){
			var self=$(this);
			var mod_fb_form=$("#fb-form");
			var data=form.check(
				{cList:["fb-content","fb-email"]},
				["notempty","email"],
				["亲，反馈内容不能为空哦！","亲，您输入的邮箱地址不正确哦！"],
				function(msg,cl){
					cl.addClass("alert").addClass("err");
					gp.fulltip().html(msg).showAndAutoHide();
				},
				function(cl){
					cl.removeClass("alert").removeClass("err");
				}
			);
			//验证没通过
			if(!data){return false;}
			//数据补充
			data.formdata.ua=gdata.ua;
			data.formdata.ref=gdata.stats.ref;
			//
			feibo.setItem("fb-email",data.formdata.email);
			//
			self.addClass("btnloading");
			$.ajax({url:gurl.feedbackAdd,type:"POST",dataType:"json",data:data.formdata,success: function(o){
				if(o.flag){;
					gp.fulltip().html("感谢您的反馈！").showAndAutoHide();
					data.formlay.content.val("");
					self.removeClass("btnloading");
					mod_fb_form.hide();
				}else{
					self.removeClass("btnloading");
					alert(o.info);
				}
			}});
			return false;
		});
	},
	//评论模块
	comment:function(o){
		if(!o){return;}
		if(feibo.type(o.comm)=="Array"){
			$.each(o.comm,function(i,n){
				$("#comcount"+n.id).find("em").html("("+n.count+")");
			});
		}
		//返回按钮
		$("#layout").on("click","[data-comreturn]",function(e){
			window.location=decodeURIComponent(feibo.getQueryValue("prm"));
			return false;
		});
		//我要发表评论
		$("#layout").on("click","[data-comadd]",function(e){
			var id=$(this).data("id");
			$("#comadd"+id).toggle();
			if($("#username"+id).val()==""){
				$("#username"+id).val(feibo.getItem("username"));
			}
			return false;
		});
		//发布
		$("#layout").on("click","[data-submit]",function(e){
			var self=$(this);
			var r=feibo.getRandom();
			var id=self.data("id");
			var comlist=$("#comlist"+id),
				comadd=$("#comadd"+id),
				comord=$("#comord"+id);
			var data=form.check(
				{cList:["content"+id,"username"+id]},
				["notempty","notempty"],
				["亲，评论内容不能为空哦！","亲，昵称不能为空！"],
				function(msg,cl){
					cl.addClass("alert").addClass("err");
					gp.fulltip().html(msg).showAndAutoHide();
				},
				function(cl){
					cl.removeClass("alert").removeClass("err");
				}
			);
			//验证没通过
			if(!data){return false;}
			//数据补充
			data.formdata.id=id;
			//本地存储
			feibo.setItem("username",data.formdata["username"]);
			//无效化
			self.addClass("btnloading");
			self.removeAttr("data-submit");
			//请求
			$.ajax({url:gurl.comm.add,type:"POST",dataType:"json",data:data.formdata,success: function(o){
				if(o.flag){
					var html="";
					$.each(o.list,function(i,n){
						html+=
							'<dl>'+
							'	<dt><a id="'+r+'" name="'+r+'"></a>'+n.nickname+'<span>'+n.create_time+'</span></dt>'+
							'	<dd>'+n.comment+'</dd>'+
							'</dl>';
					});
					comlist.show();
					comord.prepend(html);
					html="";
					data.formlay["content"].val("");
					//location.href="#"+r;
					//feibo.anScroll(feibo.wScrollTop()-50);
					var top=$("#"+r).offset().top;
					feibo.anScroll(top);
					self.removeClass("btnloading");
					self.attr("data-submit","true");
					comadd.hide();
				}else{
					self.removeClass("btnloading");
					self.attr("data-submit","true");
					alert(o.info);
				}
			}});
			return false;
		});
		//查看更多
		$("#layout").on("click","[data-commore2]",function(e){
			var self=$(this);
			var page=self.data("page");
			var id=self.data("id");
			var comord=$("#comord"+id);
			self.addClass('btnloading');
			$.ajax({url:gurl.comm.more,type:"GET",dataType:"json",data:{id:id,page:page,type:"comment"},success:function(data){
				if(data.flag){
					var html="";
					$.each(data.list,function(i,n){
						html+=
							'<dl>'+
							'	<dt>'+n.nickname+'<span>'+n.create_time+'</span></dt>'+
							'	<dd>'+n.comment+'</dd>'+
							'</dl>';
					});
					comord.append(html);
					html="";
					//self.data("page",(parseInt(page)+1))
					self.data("page",data.page)
					self.removeClass('btnloading');
				}else{

				}
				if(!data.has_data){
					self.html("加载完毕");
					self.removeAttr("data-commore2");
				}
			}});
			return false;
		});
	},
	//公共主页
	hy_more:function(){
		if(!feibo.objCheck("gdata.history.template","String")){
			alert("亲！找不到gdata.history.template哦！");
			return;
		}
		$("#hy-more").on("click",function(){
			var self=$(this);
			self.addClass("btnloading");
			$.ajax({url:gurl.hy_more,type:"GET",dataType:"json",data:{page:self.data("page"),cate_id:gdata.ua,template:gdata.history.template,type:"app"},success:function(data){
				if(data.flag && gdata.history.template=="default"){
					var html="",htmlMain="",htmlHeader="",htmlFooter="";
					$.each(data.list,function(i,n){
						htmlHeader='<li class="daily clearfix">';
						$.each(n.data,function(i,o){
							//图片模式
							var imgclass="imgoff";
							if(feibo.objAttrCheck(o,"img","String",function(d){return (d.length>0)?true:false;})){
								imgclass="imgon";
							}
							//是否显示喜欢数
							var strlike="";
							if(gdata.history.show_like && gdata.history.show_like!=="0"){
								strlike='	<span class="ico-h1600 concount"><em>'+o["like-num"]+'</em></span>';
							}
							//是否显示评论数
							var strcom="";
							if(gdata.history.show_comment && gdata.history.show_comment!=="0"){
								strcom='	<span class="ico-h850 concount"><em>'+o["comment-num"]+'</em></span>';
							}
							htmlMain+=
								'<a href="'+o.url+'" class="'+imgclass+' clearfix">'+
								'	<div class="clearfix">'+
								'	<div class="context">'+
								'		<h2>'+o.title+'</h2>'+
								'		<p>'+o.text+'</p>'+
								'	</div>'+
								'	<img src="'+o.img+'" alt="" />'+
								'	</div>'+
								'	<div class="conact">'+
								strlike+
								strcom+
								'	</div>'+
								'</a>';
						});
						htmlaFooter='	<div class="time" id="d'+n.month+n.day+'">'+
							'		<p class="month">'+n.month+'月</p>'+
							'		<span class="day">'+n.day+'日</span>'+
							'	</div>'+
							'</li>';
						if($("#d"+n.month+n.day).size()<1){
							html+=htmlHeader+htmlMain+htmlaFooter;
						}else{					
							$("#d"+n.month+n.day).before(htmlMain);
						}
						htmlMain="";
					});
					$("#hy-list").append(html);
					html="";
					self.data("page",data.page)
					self.removeClass("btnloading");
				}else if(data.flag && gdata.history.template=="xqx"){
					var html="";
					$.each(data.list,function(i,o){
						//图片模式
						var imgclass="imgoff";
						if(feibo.objAttrCheck(o,"img","String",function(d){return (d.length>0)?true:false;})){
							imgclass="imgon";
						}
						//是否显示喜欢数
						var strlike="";
						if(gdata.history.show_like && gdata.history.show_like!=="0"){
							strlike='			<span class="ico-h1600 concount"><em>'+o["like-num"]+'</em></span>';
						}
						//是否显示评论数
						var strcom="";
						if(gdata.history.show_comment && gdata.history.show_comment!=="0"){
							strcom='			<span class="ico-h850 concount"><em>'+o["comment-num"]+'</em></span>';
						}
						html+=
							'<a href="'+o.url+'"  class="'+imgclass+' content clearfix">'+
							'	<h2>'+o.title+'</h2>'+
							'	<div class="context">'+
							'		<p>'+o.text+'</p>'+
							'		<span class="conact">'+
							strlike+
							strcom+
							'		</span>'+
							'	</div>'+
							'	<div class="conimg">'+
							'		<img src="'+o.img+'" alt="" />'+
							'	</div>'+
							'</a>';
					});
					$("#hy-list").append(html);
					html="";
					self.data("page",data.page)
					self.removeClass("btnloading");
				}
				if(!data.has_data){
					self.removeClass("btnloading");
					self.html("没有更多了亲！");
					self.off("click");
					self.remove();
				}
			}});
			return false;
		});
	},
	//微博
	weibo:function(){
		var wb=feibo.objCheck("gdata.weibo.uid","String",function(d){return (d.length>0);});
		var gz=function(uid){
			$.ajax({url:gurl.weibo,type:"GET",dataType:"json",data:{uid:uid},success:function(data){
				if(feibo.type(data.flag)=="Object"){
					gp.fulltip().html("关注成功！").showAndAutoHide();
					for(var k in data.flag){
						if(data.flag[k]==0 || data.flag[k]=="0"){
							feibo.setItem(k,data.flag[k]);
							//console.log(k);
							$("[data-uid='"+k+"']").removeClass("ed").addClass("un").removeAttr("data-uid");
							feibo.loadImage(gurl.statsDefault2+"&u=focuson/"+k+"&p=2");
						}
					}
					setTimeout(function(){
						var t=decodeURIComponent(feibo.getItem("historyurl"));
						feibo.setItem("historyurl","");
						window.location.href=t;
					},1000);
				}else if(data.flag==0 || data.flag=="0"){
					if(gdata.weibo.uid=="a"){
						gp.fulltip().html("关注成功！").showAndAutoHide();
						feibo.setItem(uid,"0");
						$("[data-uid='"+uid+"']").removeClass("ed").addClass("un").removeAttr("data-uid");
						feibo.loadImage(gurl.statsDefault2+"&u=focuson/"+uid+"&p=2");
						setTimeout(function(){
							var t=decodeURIComponent(feibo.getItem("historyurl"));
							feibo.setItem("historyurl","");
							window.location.href=t;
						},1000);
					}else{
						gp.fulltip().html("关注成功！").showAndAutoHide();
						feibo.loadImage(gurl.statsDefault2+"&u=focuson/"+uid+"&p=1");
						feibo.setItem(gdata.weibo.uid,gdata.weibo.uid);
					}
				}else{
					gp.fulltip().html("关注失败！").showAndAutoHide();
				}
				feibo.setItem("wbsq","");
			}});
		};
		if(!wb){
			$("[data-weibo]").hide();
			return false;
		}else if(gdata.weibo.uid=="a"){//多条关注
			$("[data-uid]").each(function(){
				if(feibo.getItem($(this).data("uid"))){
					$(this).removeAttr("data-uid");
					$(this).addClass("un").removeClass("ed");
				}
			});
			$("#wb-gz").on("click","[data-uid],.l",function(){
				var self=$(this);
				if(self.data("uid")){
				}else{
					self=self.find("[data-uid]");
				}
				if(self.hasClass("ed")){
					self.removeClass("ed");
				}else{
					self.addClass("ed");
				}
				return false;
			});
			$("#wb-submit").on("click",function(){
				var t="";
				$("#wb-gz").find(".ed").each(function(){
					t+=","+$(this).data("uid");
				});
				if(t.substring(1).length>0){
					gp.fulltip().html("请稍等！").showAndAutoHide();
					gz(t.substring(1));
				}else{
					gp.fulltip().html("请选择关注账号！").showAndAutoHide();
				}
				return false;
			});
			$("#layout").on("click","[data-weibo]",function(){
				feibo.setItem("historyurl",encodeURIComponent(window.location.href));
				if(feibo.cookie("wbjs")){
					setTimeout(function(){location.href="/?a=follow&m=article&t=public&r="+gdata.list.id;},200);
				}else{
					setTimeout(function(){location.href=gdata.weibo.anthorize_url+escape("/?a=follow&m=article&t=public&r="+gdata.list.id);},200);
				}
				return false;
			});
		}else{//单条关注
			if(feibo.getItem("wbsq")){
				if(feibo.cookie("wbjs")){
					gz(gdata.weibo.uid);
				}
			}
			$("#layout").on("click","[data-weibo]",function(){
				if(feibo.getItem(gdata.weibo.uid)){
					gp.fulltip().html("您已关注过！").showAndAutoHide();
				}else if(feibo.cookie("wbjs")){
					gz(gdata.weibo.uid);
				}else{
					feibo.setItem("wbsq","true");
					setTimeout(function(){location.href=gdata.weibo.anthorize_url+escape(window.location.href);},200);
				}
				return false;
			});
		}
	},
	//下载浮动框
	dloadBox:function(){
		
		var $box=$(".lay-fixed"),$dload=$("#dload_btn"),href='http://img.yingyonghui.com/apk/1125194/com.feibo.joke.1373338069877.apk',media=gp.media(),$pos_lay=$("#pos_lay");
		
		if(media=='android'){
			href="http://img.yingyonghui.com/apk/1125194/com.feibo.joke.1373338069877.apk";
			$pos_lay.removeClass('open-content').removeClass('down-content').addClass('down-content');
			$("#dload_btn_box").attr("href", href);
		}else if(media=='ios'){
			href="http://admin.appd.lengxiaohua.cn/count_ios.php?source=wxweb";
			$pos_lay.removeClass('open-content').removeClass('down-content').addClass('open-content');
			$("#open_btn_box").attr("href", href);
		}
		if(jin.getItem("lxh_app1")){
			$box.hide();
		}else{
			var wHeight=jin.wClientHeight(),pos_lay_top=$pos_lay.offset().top;
			$box.show();
			$dload.attr("href",href);
			$(window).on("scroll",function(){
				var wTop=jin.wScrollTop(),height=$box.height();
				wTop=wTop+height*10;
				if(!jin.getItem("lxh_app1")){
					if(wTop>=pos_lay_top){
						$box.hide();
					}else{
						$box.show();
					}
				}
			});
			
			$("#close_btn").on("click",function(){
				jin.setItem("lxh_app1","true",{expires:1});
				$box.hide();
				return false; 
			});
		}
		$("[data-appload]").on("click",function(){
			jin.setItem("lxh_app1","true",{expires:1});
			var href=$(this).attr('href'),type=$(this).data("appload"),timestamp=new Date().getTime();
			var url="index.php?a=flowDownloadStat&m=stats",platform_id=3;
	
			if(media=='android'){
				platform_id="1";
			}else if(media=='ios'){
				platform_id="2";
			}
			var data={
				platform_id:platform_id,
				cat_id:gdata.ua,
				type:type
			};
			$.post(url,data,function(d){
				if(media=='android'){
					window.location.href=href;
				}
				if(media=='ios'){
					//window.location.href = "joke://";
					window.location.href = "http://admin.appd.lengxiaohua.cn/count_ios.php?source=wxweb";
				}
			});
			
			$box.hide();
			return false; 
		});
	}
};



























//页面环境运行入口

	//公用模块
	gp.media();//媒介判断
	gp.btnMedia();//按钮媒介判断
	gp.weixin();//旧统计
	gp.stats();//统计
	gp.feedback();//小编互动
	gp.weibo();//关注微博
	//模块化
	gp.init(function(o){//公共初始化，ajax初始化
		if(gdata.comm){gp.comment(o);}//评论模块
		if(gdata.share){gp.sharenum(o);}//分享数
		if(gdata.like){gp.likenum(o);}//喜欢数
		if(gdata.app){gp.app(o);}//app推荐
		if(gdata.taobao){gp.taobao(o);}//淘宝精彩推荐
		if(gdata.ad){gp.ad(o);}//广告模块
		if(gdata.list&&gdata.list.show_app_tip){
			gp.dloadBox();//冷笑话下载悬浮框
		}
	});
	//页面类
	if(gdata.hym){gp.histroylistMore();}//往期回顾
	//页面类
	if(gdata.history){gp.hy_more();}//公共主页
	//返回顶部
	$("#returntop").on("click",function(){
		feibo.anScroll(0);
		return false;
	});
	
























































	/*横排推荐位，点击显示推荐说明功能，乱得不想说什么，代码没整理，并且懒得包含进gp命名空间，抱歉了代码！*/
	var tj_infor=function(n){
		if($("#lay-tj-box"+n).size()){
			var tj_box=$("#lay-tj-box"+n);
			var tj_point=$("#tj-point");
			var tj_infor=$("#tj-infor");
			var tj_box_w=tj_box.width();
			var tj_box_child_len=tj_box.children("a").length;
			var tj_info=function(nowindex){
				if(!nowindex){
					alert("err:nowindex");
					return;
				}
				var splen=75;
				var spgroup=Math.floor(tj_box_w/splen);
				var point=(nowindex%spgroup)*splen-40;
				if(point==-40){point=(splen*spgroup)-40;}
				var inserto=Math.ceil(nowindex/spgroup)*spgroup;
				tj_point.css({left:(point-5)+"px"});
				if(inserto>tj_box_child_len){
					inserto=tj_box_child_len;
				}
				tj_box.children("a").eq(inserto-1).after(tj_infor);
			};

			var tjlist=tj_box.find("a.tj-base");
			tj_box.on("click","a.tj-base",function(e){
				var i=tjlist.index($(this));
				tj_info(i+1);
				var btn=tj_infor.find(".btn");
				btn.attr("data-statsu",$(this).data("statsu"));
				btn.attr("data-weixinid",$(this).data("weixinid"));
				btn.attr("data-statsp",$(this).data("statsp"));
				btn.attr("data-statsid",$(this).data("statsid"));
				btn.attr("href",$(this).attr("href"));
				btn.attr("link",$(this).attr("link"));
				btn.attr("data-wid",$(this).data("wid"));
				btn.attr("data-uid",$(this).data("uid"));
				btn.attr("data-cid",$(this).data("cid"));
				tj_infor.find("[tit]").html($(this).find("[tit]").html());
				tj_infor.find("[infor]").html($(this).find("[infor]").html());
				tj_infor.find("[ico]").attr("class",$(this).attr("class"));
				tj_infor.show();
				e.stopPropagation();
				return false;
			});

			$(window).on("onorientationchange" in window?"orientationchange":"resize",function(){
				tj_infor.hide();
				tj_box_w=tj_box.width();
			});
		}
	};
	tj_infor("");
	