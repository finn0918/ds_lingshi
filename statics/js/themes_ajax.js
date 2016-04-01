define(function(require, exports, module){
	var init = {
		ajax_all : function(){
			var callback = function(d){
				if(d){
					var data = d.data;	
				
					/* 获取赞的次数，分享次数 */
					var zanNum = data.subject.hotindex;
					var shareNum = data.subject.share_num;
					
					$(".fix-banner .zan").find("span").text(zanNum),
					$(".zan-fl span").text(zanNum+"人喜欢"),
					$(".fix-banner .share").find("span").text(shareNum),
					$(".share-fl span").text(shareNum+"次分享");
					
					/* 商品标题 */
					$(".themes_headerImg_txt span").text(data.subject.title);	
					/* 主图 */
					$(".themes_headerImg_img").attr("data-original",data.subject.img.img_url);
					/* 商品描述 */
					$(".themes_desc").text(data.subject.desc);
					
					/* 专题商品列表 */
					var list = data.goodses;
					for(var i in list) {
						var htmls = '';
						htmls += '<div class="themes_list" data-id='+list[i].id+'>';
						htmls += '	<div class="themes_detail_title">';
						htmls += '<span>'+ ((parseInt(i)+1) >= 10 ? (parseInt(i)+1) : "0" + (parseInt(i)+1)) +'</span>'+list[i].title;
						htmls += '	</div>';
						htmls += '	<div class="themes_detail_txt">';
						htmls += '<div class="themes_descript">'+list[i].desc+'</div>';
						htmls += '		<div class="themes_detail_Img">';
						var imgList = list[i].posters;
						for(var s in imgList) {
							htmls += '			<p>';
							htmls += '				<a href="javascript:;" class="themes_link" onclick="">';
							htmls += '					<img src="./statics/images/themes-detail-bg.jpg" data-original="'+imgList[s].img_url+'">';
							htmls += '				</a>';
							htmls += '			</p>';
						}
						htmls += '</div>';
						htmls += '			<div class="themes_detail_price">';
						// 2015-08-11 by eherry
						var current_price;
						var prime_price;
						if(list[i].price.current.toString().split(".")[1]){
							if(list[i].price.current.toString().split(".")[1].length == 1) {
								current_price = list[i].price.current + "0";
							}else if(list[i].price.current.toString().split(".")[1].length == 2){
								current_price = list[i].price.current;
							}
						}else {
							current_price = list[i].price.current + ".00";
						}
						if(list[i].price.prime.toString().split(".")[1]){
							if(list[i].price.prime.toString().split(".")[1].length == 1) {
								prime_price = list[i].price.prime + "0";
							}else if(list[i].price.prime.toString().split(".")[1].length == 2){
								prime_price = list[i].price.prime;
							}
						}else {
							prime_price = list[i].price.prime + ".00";
						}
						htmls += '				<span class="current_price">￥'+current_price+'</span>';
						htmls += '				<span class="non_price">￥'+prime_price+'</span>';
						htmls += '				<a class="detail_link themes_link" href="javascript:;" onclick="">商品详情</a>';
						htmls += '			</div>';
						htmls += '		</div>';
						htmls += '	</div>';
						htmls += '</div>';
						$(".themes_detail").append(htmls);
					}
					$("img[data-original]").lazyload({
						"effect" : "fadeIn"
					});
				}
			};
			var ajax_data = {
				url:"api.php?apptype=0&srv=2404&cid=10002&uid=0&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=1&version=7",
				data : {
					subject_id : $("body").attr("subject_id")
				},
				type : "get",
				dataType : "json",
				success : callback
			}
			$.ajax(ajax_data);
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
			if(init.getScrollTop() + init.getWindowHeight() >= init.getScrollHeight() - 100){
				$(".fix-banner").fadeOut();
			}else {
				$(".fix-banner").fadeIn();
			}
		}
	}
	$(document).on("scroll",init.set_loading);
	exports.info = init.ajax_all;
	
})