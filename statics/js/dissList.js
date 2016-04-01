define(function(require) {
	
	var jquery = require("./jquery.min");
	
	var array = [];
	var stop = true;
	var page = 1;
	
	// 页面数据请求
	var init = {
		info : function(page){
			var ajax_data = {
                url: "api.php?apptype=0&srv=2503&cid=10002&uid=0&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=1&goods_id="+spu_id+"&version=7",
				data : {
					pg_cur : page,
					pg_size : 20
				},
				dataType : "json",
				type : "get",
				success : function(d){
					if(d.data.comments){
						var data = d.data;
						document.title = "喵亲口碑("+ data.total_num+")";
						var comment = data.comments;
						for(var i in comment){
							array[i] = comment[i];
							init.list_append(comment[i],i);
						}
						$(".comment-list ul").children().eq(data.total_num-1).css("border-bottom","none");
						stop = true;
					}else {
						stop = false;
					}
				}
			}
			$.ajax(ajax_data);
		},
		list_append : function(d,i){
			var htmls = '';	
			htmls += '<li>';
			htmls += '<div class="comment-wrap">';
			htmls += '<div class="comment-user">';
			htmls += '<span class="img" style="margin-right:0.5rem">';
			htmls += '<img src="'+d.avatar+'"></span>';
			htmls += '<span class="user-name">'+d.nickname+'</span>';
			htmls += '</div>';
			htmls += '<div class="comment-txt">'+d.content+'</div>';
			htmls += '</div></li>';
			$(".comment-list ul").append(htmls);
		},
		//滚动条在Y轴上的滚动距离
		getScrollTop:function(){
			var scrollTop = 0, bodyScrollTop = 0, documentScrollTop = 0;
			if(document.body){
				bodyScrollTop = document.body.scrollTop;
			}
			if(document.documentElement){
				documentScrollTop = document.documentElement.scrollTop;
			}
			scrollTop = (bodyScrollTop - documentScrollTop > 0) ? bodyScrollTop : documentScrollTop;
			return scrollTop;
		},
		//浏览器视口的高度
		getWindowHeight:function(){
			var windowHeight = 0;
			if(document.compatMode == "CSS1Compat"){
				windowHeight = document.documentElement.clientHeight;
			}else{
				windowHeight = document.body.clientHeight;
			}
			return windowHeight;
		},
		//文档的总高度
		getScrollHeight:function(){
			var scrollHeight = 0, bodyScrollHeight = 0, documentScrollHeight = 0;
			if(document.body){
				bodyScrollHeight = document.body.scrollHeight;
			}
			if(document.documentElement){
				documentScrollHeight = document.documentElement.scrollHeight;
			}
			scrollHeight = (bodyScrollHeight - documentScrollHeight > 0) ? bodyScrollHeight : documentScrollHeight;
			return scrollHeight;
		},
		set_loading : function(){
			if(init.getScrollTop() + init.getWindowHeight() >= init.getScrollHeight() - 50){
				if(stop == true) {
					stop = false;
					page += 1;
					init.info(page);
				}
			}
		}

	};
	
	$(".comment-list").on("touchstart touchmove touchend",init.set_loading);
	init.info(1);
});