define(function(require){
	var jquery = require("./jquery.min");
	var lazyload = require("./jquery.lazyload");
	var ajax = require("./themes_ajax");
	var wx = require("./wxshare");
	
	ajax.info();
	$(function(){
       
		$(".close").on("click",function(){
			$(this).parents(".fixed_download").hide();
		})
	})
});
