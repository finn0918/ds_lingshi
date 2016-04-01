define(function(require) {

	var $ = require('./zepto');
	var base = require('./base');
	var raffle = require('./vqdRaffle/main');
	
	var holdLottery = true;
	function setCollectInfo(){
		var innerHtml = raffle.cform();
		$(".innerBox").html(innerHtml);
		$(".formBox").show();
		hideFlybox();
	};
	function noticebox(flag,text){
		var $box = $(".noticeBox"),
			$shadow = $(".shadow"),
			boxCenter = ($(window).width() - 257) / 2,
			btnClass = flag == 1 ? "btn-flybox get" : (flag == 2 ? "btn-flybox hide" : "btn-flybox again"),
			textPlanClass = flag == 1 ? "textplan get" : (flag == 2 ? "textplan normal" : "textplan again");
		$box.find(".btn-flybox").attr("class",btnClass);
		$box.find(".textplan").attr("class",textPlanClass).html(text);
		$box.css("left",boxCenter+"px").show();
		// $shadow.show();
	};
	function setGetit(res,num){
		//res 请求成功的返回值
		//num 剩余抽奖次数
		
		//清除连续点击限制
		setTimeout(function(){
			holdLottery = true;
		},3200);
		
		var giftName = '',
			giftCate = 'number' == typeof res.cate ? res.cate : parseInt(res.cate),
			flag = res.flag;
		var id_id = res.hit_id;
		var expp  = {"expires":1};// 设置1天过期
		var afterAnimate = function(){
			var $num = $(".num");
			var noticeText = '';        //提示框内容
			var picDir = 'statics/images/'
			var giftInfo = [
				{giftPic:"",giftName:""},
				{giftPic:picDir+"bigGift.png",giftName:"承包2015年的零食"},
				{giftPic:picDir+"250g.png",giftName:"宅家看剧大礼包"},
				{giftPic:picDir+"80g.png",giftName:"暑期降温大礼包"},
				{giftPic:picDir+"5s.png",giftName:"200MB流量"},
				{giftPic:picDir+"3s.png",giftName:"100MB流量"}
			];

			if(flag == 1){
				base.cookie("id_d",id_id,expp);
				//中奖
				noticeText += '<p>恭喜你！</p>';
				noticeText += '<p>获得 <span class="giftCate">'+giftCate+'</span> 等奖！</p>';
				noticeText += '<p><img src="'+giftInfo[giftCate].giftPic+'" class="giftPic" /></p>';
				noticeText += '<p>'+giftInfo[giftCate].giftName+'</p>';
			}else if(flag == 0){
				noticeText += '没中奖！<br/>';
				noticeText += '差一点点就中奖了！<br/>';
				
				if (num==0) {
					noticeText += '分享给好友，明天<br/>还有3次机会';
					
				}else{
					noticeText += '还有'+num+'次机会哟~<br/>';
					noticeText += '加油！加油！';
				}
				
			}else{
				alert("返回值错误");
			}
			noticebox(flag,noticeText);
			if (num==0) {
				$(".btn-flybox.again").addClass("over");
			}
			
			$num.text("您有"+num+"次抽奖机会");
			num <= 0 && setShareNotice();
		};
		var lotteryAnimate = function(){
			var $tbg = $(".turntableBG"),
				nowRotate = parseInt($tbg.attr("data-tran")) || 0;
			//计算转动角度
			var a = nowRotate,										
				b = 1800,
				c = b * Math.round(a/b) - a,
				less = 60*(giftCate-1) + giftCate-(giftCate-1),
				max = less + 58;
				nowRotate += 1800 - (giftCate == 0 ? base.random(max,less) : base.random(less,max)) + c;
			//执行
			$(".turntableBG").css({"transform":"rotate("+nowRotate+"deg)","-webkit-transform":"rotate("+nowRotate+"deg)"}).attr("data-tran",nowRotate);
			//动画执行后回调
			setTimeout(afterAnimate,3200);
		};
		lotteryAnimate();
	}
	function setShareNotice(){
		// var $shareNotice = $(".share");
		// $shareNotice.show();
		// $shareNotice.on("click",function(){
		// 	$shareNotice.hide();
		// });
	}
	function setError(){
		alert("Request Error!");
	}
	function hideFlybox(){
		var $noticeBox = $(".noticeBox"), 
			$shadow = $(".shadow");
		$noticeBox.hide();
		$shadow.hide();
	};
	function doLottery(){
		if(holdLottery){
			holdLottery = false;
			// 补充
			var id_d = base.cookie("id_d");
			var confData = {
				postType : "POST",
				datas:{id_d:id_d},
				url : "lib/lhj.php"
			}	
			raffle.request(confData,setGetit,setError);
		}
	};
	function submitForm(){
		raffle.collect("",submitDone,setError);
	};
	function submitDone(data){
		var noticeText = '';
		if(data.flag == 1){
			noticeText += '<strong>信息提交成功！</strong><br/>';
			noticeText += '请耐心等待我们客服与您核实！';
			$(".formBox").hide();
			noticebox(2,noticeText);
		}
	};
	function fillTheRank(data){
		var rankHtml = '', runnerTime = 0, runnerTop = 0;
		//自动播放
		var rankRunner = function(){
				var runnerLen = $(".resultInner p").length;
				if(runnerTime >= runnerLen-1){
					runnerTime = 0;
					runnerTop = 0;
				}else{
					runnerTop += 30;
					runnerTime++;
				}
				$(".resultInner").css("top","-"+runnerTop+"px");
			},
			rankJoin = function(){
				var rank_tel = '';

				//遍历结果，输出
				for(var i in data){
					rank_tel =data[i].tel;//新增
	
					rank_tel = rank_tel.replace(data[i].tel.substring(3,7),"****");//修改
				
					rankHtml += '<p>' + rank_tel + ' 获得'+ (parseInt(data[i].hit) == 5 ? ("幸运奖</p>") :(data[i].hit+'等奖</p>'));
				}
				$(".resultInner").html(rankHtml);
			};
		rankJoin();
		setInterval(rankRunner,3000);
	};
	//页面初始化
	function lotteryInit(){
		var $num = $(".num");
		/* 
		 * 模块初始化
		 * 设置抽奖次数为3次
		 */
		raffle.init("keys",true,3,7200);
		//获取排行榜
		raffle.rank(fillTheRank);
		$num.css({"visibility":"visible"});
		$num.text("您有"+raffle.check().num+"次抽奖机会");
	};
	function lotteryEvent(){
		
		$(".noticeBox").on("click",".btn-close",hideFlybox);
		$(".noticeBox").on("click",".again",hideFlybox);
		$(".noticeBox").on("click",".get",setCollectInfo);
		$(".turntableArrow").on("click",doLottery);
		$(".turntableArrow").on("touchstart",function(e){
			var $self = $(this);
			$self.addClass("hovered");
		});
		$(".turntableArrow").on("touchend",function(){
			var $self = $(this);
			$self.removeClass("hovered");
		});
		$("body").on("click","#keyssubmit",submitForm);
	};
	
	lotteryInit();
	lotteryEvent();
});