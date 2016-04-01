define(function(require, exports, module) {
	/*
		status = 1 收藏
		status = 0 取消收藏
	*/
	var jquery = require("./jquery.min");
	var comnum;
	
	// 页面数据请求
	var init = {
		info:function(){
			var ajax_data = {
				url : "api.php?apptype=0&srv=2502&cid=80&uid=0&tms=20150713151836&sig=e9f75a3bdae950ea&wssig=9de6f856100a21ab&os_type=1&version=1&opt=1&add_id=8",
				dataType : "json",
				type : "get",
				data : {
					"goods_id" : $(".main").attr("spu_id")
				},
				success : infoMsg,
				error : function(d){
					console.log(d);
				}
			}
			$.ajax(ajax_data);
		}
	};
	
	var infoMsg = function(d) {
		if(d){
			$(".count-down-time").attr("end_time",d.timestamp);
			var data = d.data;
			$(".fl-title").text(data.title);
			
			/* 价格区域 */
			var prevNum = data.price.current ? data.price.current : '';
				float_price = (prevNum) ? (prevNum.toString().split(".")[1] ? prevNum.toString().split(".")[1] : "00") : "",
				marketNum = data.price.prime ?  (data.price.prime.toString().split(".")[1] ? data.price.prime.toString().split(".")[1] : "00") : '';
		
			// 当前价
			$(".price-current em").text(prevNum.toString().split(".")[0]),
			$(".price-current i").text(float_price),
			// 超市价
			$(".market-price a i").text(data.price.prime.toString().split(".")[0]+"."+marketNum);
			
			/* 折扣标签 */
			var tages = data.tags;
			for(var i in tages) {
				var htmls = '';
				if(tages[i].title){
					htmls += '<span style="background:'+tages[i].color+'">'+tages[i].title+'</span>';
				}
				$(".dis_icon").append(htmls);
			}
			
			/* 商品数量 2015-07-29*/
			var goodsType = data.guide_type;
			/* 判断是否为平台商品 goodsType = 0 */
			var istime = data.type;
			$(".count-down").attr("istime",istime);
			istime > 0 ? $(".count-down").show() : '';
			if(goodsType == 0){	
				var soldNum = data.sold_num ? data.sold_num : 0,
					surplusNum =  data.surplus_num ? data.surplus_num : 0;
				switch (istime){
					// 0 : 已售; 1 : 剩余，已售; 2 ：已售
					case 0 : $(".has-solded").show().find("a").text(soldNum);
					case 1 : $(".has-solded").show().find("a").text(soldNum),$(".only-store-num").show().find("a").text(surplusNum);
					case 2 : $(".has-solded").show().find("a").text(soldNum);
				}
			}
			
			/* 倒计时 */
			$(".count-down-time").attr("end_time",data.time).attr("server_time",data.server_time);
			
			/* 图片详情 */
			var imgUrl = data.details_imgs;
			var details = data.details;
			$(".img-box").html(details);
			
			/*主图轮播*/
			var sliderImg = data.main_imgs;
			// 页面轮播
			if(sliderImg.length > 1){
				for(var i in sliderImg) {
					var htmls = '';
					htmls += '<div class="swiper-slide">';
					htmls += '<img data-src="'+sliderImg[i].img_url+'" class="swiper-lazy">';
					htmls += '<div class="swiper-lazy-preloader swiper-lazy-preloader-white"></div>';
					htmls += '</div>';
					$(".swiper-wrapper").append(htmls);
				};
				var swiper = new Swiper('.swiper-container', {
					loop : true,
					onInit: function(swiper){
						document.getElementById("all-num").innerHTML = $(".swiper-slide").length - 2;
					},
					onSlideChangeEnd: function(swiper){
						document.getElementById("active-num").innerHTML = Number($(".swiper-slide-active").attr("data-swiper-slide-index"))+1;
					},
					lazyLoading : true,
					preloadImages: false
				});
			}else {
				for(var i in sliderImg) {
					var htmls = '';
					htmls += '<div class="swiper-slide">';
					htmls += '<img src="'+sliderImg[i].img_url+'" class="swiper-lazy">';
					htmls += '<div class="swiper-lazy-preloader swiper-lazy-preloader-white"></div>';
					htmls += '</div>';
					$(".swiper-wrapper").append(htmls);
					document.getElementById("all-num").innerHTML = "1",
					document.getElementById("active-num").innerHTML = "1",
					$(".swiper-lazy-preloader-white").hide();
				};
			}
			
			/* 美味信息 */
			var mwinfo = data.arguments.infos;
			if(mwinfo){
				for(var i in mwinfo) {
					var htmls = '';
					htmls += '<span>'+ mwinfo[i].key +'：'+ mwinfo[i].value +'</span>';
					$(".info-msg").append(htmls);
				}
			}else {
				$(".info-table").hide();
			}
			
			/* 评论 */
			var comments = data.comments.comments;
			$(".Cnum").text(data.comments.total_num);
			if(comments.length){
				for(var i in comments) {
					var htmls = '';
					htmls += ' <li> ';
					htmls += ' <div class="comment-wrap"> ';
					htmls += ' <div class="comment-user"> ';
					htmls += ' <span class="img"><img src="'+comments[i].avatar.img_url+'" /></span> ';
					htmls += ' <span class="user-name">'+comments[i].nickname+'</span> ';
					htmls += ' </div> ';
					htmls += ' <div class="comment-txt">'+comments[i].content;
					htmls += ' </div> ';
					htmls += ' </div> ';
					htmls += ' </li> ';
					$(".comment-list ul").append(htmls);
				}
				/* 评论数量少于2或者等于2的时候 查看更多评论按钮不显示*/
				data.comments.total_num <= 2 ? $(".comment-more").hide().parents(".comment-list").find("ul").children("li:last").css("border-bottom","none") : '';
			}else {
				$(".comment-list ul").html("<p class='comment-empty'>暂无评论</p>").next(".comment-more").hide();
			}
			
			/* 猜你喜欢 */
			var like = data.guess_love;
			like ? like : $(".user-liked").hide();
			for(var i in like) {
				var htmls = '';	
				htmls += '<li data-id='+like[i].id+'><a class="disBlock" onclick="">';
				htmls += '	<div class="img"> ';
				htmls += '		<img src="./statics/images/guess-bg.png" data-original="'+like[i].img.img_url+'" />';
				htmls += '	</div>';
				htmls += '	<div class="nr">';
				htmls += '		<div class="title">'+like[i].title;		
				htmls += '		</div>';
				htmls += '		<div class="price">';
				htmls += '			<span class="current_price red_txt">&yen;'+like[i].price.current+'</span>';
				htmls += '			<span class="mark_price a0a0a0">&yen;'+like[i].price.prime+'</span>';
				htmls += '		</div>';
				htmls += '	</div>';
				htmls += '</a></li>';
				$(".pro-list ul").append(htmls);
			}
			$("img[data-original]").lazyload({
				"effect" : "fadeIn"
			});
		}
	};
	
	exports.info = init.info;
	exports.zan = init.zan;
});