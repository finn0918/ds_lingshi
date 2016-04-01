(function($) {
	$.fn.dooupload = function(options) {    
		//debug(this);    
		// 合并设置
		var opts = $.extend({}, $.fn.dooupload.defaults, options);
		return this.each(function() {
			$this = $(this);
			var o = $.meta ? $.extend({}, opts, $this.data()) : opts;
			
			var dooUpload = {
				fileArray : [],
				//
				triggerFile : function(){
					var randomId = parseInt(Math.random()*(2000-10)+10);
					dooUpload.creat_uploadFile(randomId);
					$("#dooPicFile"+randomId).trigger("click");
					$("#dooPicFile"+randomId).live("change",dooUpload.change_uploadFile);
				},
				//创建表单
				creat_uploadFile : function(rid){
					var uploadFormHtml = "";
					uploadFormHtml += '<form action="'+o.actionUrl+'" id="dooMyform'+rid+'" class="dooMyform" style="display:none">';
					uploadFormHtml += '<input type="file" id="dooPicFile'+rid+'" class="dooPicFile" ';
					if(o.multiplePic){
						uploadFormHtml += 'multiple';
					}
					uploadFormHtml += ' accept="*.*" />';
					uploadFormHtml += '</form>';
					$(uploadFormHtml).appendTo("body");
				},
				change_uploadFile : function(){
					//获取本次上传结果
					var picArray = this.files,
						picArray_len = picArray.length;
					//拼接数组
					for(var i =0; i<picArray.length; i++){
						dooUpload.fileArray.push(picArray[i]);
					}
					//删除本次表单
					$("#dooMyform").remove();
					//设置预览图
					dooUpload.set_preview(picArray_len);
				},
				set_preview : function(picLen){
					var $previewBox = $(o.previewBox);
					var a = 0;
					var get_picload = function(ab){
						if( ab < picLen ){
							reader = new FileReader();
							reader.readAsDataURL(dooUpload.fileArray[ab]);
							reader.onload = function(e) {
								var img = new Image();
								var result_pic = e.target.result;
								img.src = result_pic;
								img.onload = function(){
									//添加预览图
									//是否有容器？ 填充 : 创建
									
									$previewBox.attr("src",result_pic);
									//执行上传
									dooUpload.fileUpload(ab);
									//自调用（多图）
									if(o.multiplePic){
										ab++;
										get_picload(ab);
									}
								}
							}
						}else{
							var clearAll = function(){
								dooUpload.fileArray.splice(0,picfiles_len);
								console.log("清楚所有内容" + dooUpload.fileArray);
							}
							setTimeout(clearAll,1000);
						}
					}
					get_picload(a);
				},
				fileUpload : function(index){
					// 上传内容
					var picfiles = dooUpload.fileArray;
					// 长度
					var picfiles_len = picfiles.length;
					// POST方法
					
							var xhr = new XMLHttpRequest();
							var formData = new FormData();
							//创建表单项
							formData.append('picture', picfiles[index]);
							if(o.progress){
								//进度条
								$.fn.dooupload.creat_progress();
								xhr.onprogress = dooUpload.updateProgress;
								xhr.upload.onprogress = dooUpload.updateProgress;
							}
							//完成上传
							xhr.onload = function(event){
								var returnValue = $.parseJSON(event.target.responseText);
								var returnValue_url = returnValue.url;
								//创建returnValue
								var hiddenInput = '<input name='+o.returnInputName+' data-type="'+o.previewBox+'" type="hidden" value="'+returnValue_url+'">';
								
								if( $('input[data-type="'+o.previewBox+'"]').length == 0 ){
									$(hiddenInput).appendTo("#myform");
								}else{
									$('input[data-type="'+o.previewBox+'"]').attr("src",returnValue_url);
								}
								//删除进度条
								$(".progressBor").remove();
								
								console.log("上传完成");
							}
							xhr.ontimeout = function(event){
						　　　　alert('请求超时！');
						　　}
							//执行POST
						　　xhr.open('POST', o.actionUrl);
							xhr.send(formData);
				},
				updateProgress : function(event){
					if (event.lengthComputable) {
						var percentComplete = event.loaded / event.total;
						console.log("progress:"+percentComplete);
						$(".progressBar").css("width",(percentComplete * 100)+"%");
						$(".progressText").text((percentComplete * 100)+"%");
					}
				}
			}
			
			//触发file控件
			$this.bind("click",dooUpload.triggerFile);
			//删除操作
			$.fn.dooupload.delFile(o.delLink);
		});

		
		
	};
	
	
	// 私有函数：debugging    
	function debug($obj) {    
		if (window.console && window.console.log)    
		window.console.log('dooupload selection count: ' + $obj.size());
	};    
	// 定义暴露函数    
	$.fn.dooupload.creat_progress = function() {    
		var progressHtml = '<div class="progressBor"><div class="progressBar"></div></div>';
		$(".gameIconBox").append(progressHtml);
	};
	$.fn.dooupload.delFile = function(delLink) {
		function delFileFunc(event){
			console.log("a");
			var $this = $(this),
				$thisFather = $this.closest("dd"),
				thisId = $thisFather.attr("data-id"),
				thisKey = $thisFather.attr("pic_key");
			var data_list = {
				"pic_key" : thisKey,
				"id" : thisId
			};
			var delFunc = function(data){
				if(data.flag){
					//alert("x");
					//$('input[data-type="'+event.data.a1+'"]').remove();
					$this.closest("dd").find(".mobanPic").attr("src","/statics/admin/images/nopic2.gif");
				}
			};
			$.post("/admin.php?m=game&a=del_pic",data_list,delFunc,"json");
		}
		
		$(delLink).bind("click",delFileFunc);
	};
	// 默认设置
	$.fn.dooupload.defaults = {       
		actionUrl : "server.php",			  //请求地址
		previewBox : ".gameIcon",			 //预览图容器
		multiplePic : false,				//是否多图
		progress : false,                  //是否进度条
		autoUpload : true,                //是否自动上传
		returnInputName : "pic_icon"     //返回值input name
	};    
	// 闭包结束    
})(jQuery);