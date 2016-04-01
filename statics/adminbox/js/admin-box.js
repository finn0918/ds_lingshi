// feibo
//
// http://feibo.cn
//
// Copyright 2012, by jin
//
// 主容器页面
// 

var jin={
	numState:"20120410",
	getRandom:function(){
		var start=1000,end=9999;
		jin._getRandom=jin._getRandom+1 || start;
		if(jin._getRandom>end){
			jin._getRandom=start;
		}
		return "j"+new Date().getTime()+jin._getRandom;
	},
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
	getQueryValue:function(key,url){
		var strurl=url || jin.nowUrl;
		var vals=new RegExp("[\&|\?]"+key+"=([^&]*)&{0,1}","ig").exec(strurl);
		return vals?(vals[1].replace(/#.*/gi,"")):"";
	},
	Quad:{
		easeOut:function(t,b,c,d){
			return -c*(t/=d)*(t-2)+b;
		}
	},
	wScrollTop:function(v){
		var d = document;
		if(v){
			d.documentElement.scrollTop=d.body.scrollTop=v;
			return v;
		}
    	return window.pageYOffset || d.documentElement.scrollTop || d.body.scrollTop;
	},
	wClientHeight:function(r){
		var d = document;
		if(!jin._clientHeight || r){
			jin._clientHeight=window.innerHeight || d.documentElement.clientHeight;
		}
		return jin._clientHeight;
	},
	anScroll:function(r,fnBefore,fnAfter){
		fnBefore && fnBefore();
		var b=jin.wScrollTop(),c=r-jin.wScrollTop(),d=40,t=0;
		var run=function(){
			jin.wScrollTop(Math.ceil(jin.Quad.easeOut(t,b,c,d)));
			if(t<d){
				t++;setTimeout(run,10);
			}else{
				fnAfter && fnAfter();
			}
		};
		run();
	},
	//滚动事件二次封装
	winTimeoutScroll:function(callback){
		//scroll数据加载
		$(window).on("scroll",function(){
			clearTimeout(jin.timeout);
			jin.timeout=setTimeout(function(){
				callback && callback();
			},500);
		});
	}
};
var pl=pl || {
	webmap:function(html){
		$("#webmap").html(html);
	},
	minHeight:function(){
		return Math.max(jin.wClientHeight(),$("#slide").height());
	},
	rightMainHeight:function(h){
		$("#rightMain").height(h);
	}
};
(function(window, undefined){var document=window.document,location=document.location;
	//导航伸缩
	var slide=$("#slide");
	var navbind=function(e){
		var navlist=$(this).closest(".navlist");
		if(navlist.attr("id")==e.data.t){
			if(navlist.hasClass("navnow")){
				navlist.find(".subnav").hide("fast");
				navlist.removeClass("navnow");
			}else{
				navlist.find(".subnav").show("fast");
				navlist.addClass("navnow");
			}
		}else{
			$("#"+e.data.t).find(".subnav").hide("fast");
			$("#"+e.data.t).removeClass("navnow").removeAttr("id");
			navlist.find(".subnav").show("fast");
			navlist.attr("id",e.data.t).addClass("navnow");
		}
		return false;
	};
	$("#slide > [data-id]").each(function(){
		$(this).on("click",".navlink",{t:jin.getRandom()},navbind);
	});
	//隐藏显示侧边导航
	$("#changewidth").on("click",function(){
		if($("#main").hasClass("mainfull")){
			$("#main").removeClass("mainfull");
		}else{
			$("#main").addClass("mainfull");
		}
		return false;
	});
	//左侧导航切换
	var navtop=$("#navtop"),
		navloading=$("#navloading");
	navtop.on("click","a[data-id]",function(e){
		var self=$(this);
		navtop.find("a").removeClass("navtopnow");
		self.addClass("navtopnow");
		navloading.stop().fadeIn(0);
		slide.children("ul[data-id!="+self.data("id")+"]").hide("fast");
		slide.children("ul[data-id="+self.data("id")+"]").show("fast");
		navloading.fadeOut("slow");
		pl.webmap("<span>"+self.html()+"</span>");
		return false;
	});
	//导航跳转
	var nav=function(){
		var self=$(this);
		slide.find(".now").removeClass("now");
		self.addClass("now");
		var rightMain=$("#rightMain");
		rightMain.attr("src",jin.setQueryValue("t",jin.getRandom(),self.attr("link")));
		//第一级导航
		var nav1="";
		var navnow=self.closest(".navnow");
		if(navnow.size()>0){
			nav1+=navnow.find(".navlink").text()+" > ";
		}
		pl.webmap(nav1+"<span>"+self.html()+"</span>");
		//jin.anScroll(91);
		return false;
	};
	$("#header").on("click","[link]",nav);
	navtop.on("click","[link]",nav);
	slide.on("click","[link]",nav);
	//滚屏了
//	jin.winTimeoutScroll(function(){
//		if(jin.wScrollTop()>91){
//			slide.css({"position":"absolute","top":jin.wScrollTop(),"margin-top":"0"});
//		}else{
//			slide.css({"position":"relative","top":0,"margin-top":"25px"});
//		}
//	});
	//gototop
	var gototop=$("#lay-gototop"),
		gototopimg=gototop.find("img");
	gototop.on("click",function(){jin.anScroll(0);});
	$(window).on("scroll",function(){
		if(jin.wScrollTop()>100){
			gototop.show("fast");
		}else{
			gototop.hide("fast");
		}
	});
})(window);