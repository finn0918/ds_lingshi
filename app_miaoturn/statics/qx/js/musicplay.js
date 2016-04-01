define(function(require, exports, module) {
	
	var base = require('./base');
	var musicPlayTime, musicPlayRemove;
	var $ = require('./zepto');
	
	//初始化
	function musicInit(){
		musicPlay();
		$(".music_icon").on("click",musicControl);
	};
	function musicPlay(){
		$('#audio')[0].play();
		$(".music_icon").addClass("play").removeClass("pause");
		musicPlayTime = setInterval(musicPlayerAnimate,600);
		musicPlayRemove = setInterval(musicPlayerRemove,7500);
	};
	function musicPause(){
		$('#audio')[0].pause();
		$(".music_icon").addClass("pause").removeClass("play");
		clearTimeout(musicPlayTime);
		clearTimeout(musicPlayRemove);
	};
	function musicControl(){
		if($('#audio')[0].paused){
			musicPlay();
		}else{
			musicPause();
		}
	};
	function musicPlayerAnimate(){
		var htmls = '';
		var w = base.getRandom(3,11);
		var x = base.getRandom(1,3);
		var xid = base.getRandom(999,9999);
		htmls = '<img src="images/musicalNotes_white.png" style="width:'+(15-w)+'px;position:absolute;left:30px;top:120px;opacity:1;" id="mpNote'+xid+'" />';
		$(htmls).appendTo(".musicIcon_plan");
		$("#mpNote"+xid).animate({"top":(0 + base.getRandom(5,15))+"px","left":(30 + base.getRandom(-35,25))+"px","opacity":0},2000);
	};
	function musicPlayerRemove(){
		var imgobj = $(".musicIcon_plan img");
		var imgobjLen = imgobj.length;
		imgobj.each(function(i){
			if(imgobjLen > 0){
				i <= 10 && (imgobj.eq(i).remove());
			}else{
				return false;
			}
		})
	};
	
	exports.init = musicInit;
	exports.play = musicPlay;
	exports.pause = musicPause;
	
});

