(function($) {
	var adsUpload = {
		actionUrl : "/admin.php?m=upload&a=img_up",
		//参数 id,title,id_str,img
		saveUrl : "/admin.php?m=square&a=edit",
		delUrl : "/admin.php?m=square&a=del_img",
		fileArray : [],
		addCateUpload : function(){
			var listLen = $(".uploadFileList li").length,
				adsLen = $(".adsList li").length;
			var listHtml = '';
				listHtml += '<li data-id="'+(listLen+1)+'">';
				listHtml += '<p>';
				listHtml += '<input type="button" class="uploadList_trigger" id="trigger'+(parseInt(listLen)+1)+'" value="选择图片" />';
				listHtml += '<input type="file" class="uploadList_file" />';
				listHtml += '<input type="text" class="uploadList_title input-text" placeholder="这是男人喜欢玩的游戏" />';
				listHtml += '<input type="hidden" class="uploadList_img" value="" />';
				listHtml += '</p>';
				listHtml += '<p><input type="text" class="uploadList_content input-text" placeholder="游戏全称或者游戏编号添加游戏，逗号隔开。" /></p>';
				listHtml += '<p><input type="text" class="uploadList_content input-text iosData" placeholder="游戏全称或者游戏编号添加游戏，逗号隔开。" /></p>';
				listHtml += '<p><input type="button" class="uploadList_del" value="删除" /><input type="button" class="uploadList_save" value="保存" /></p>';
				listHtml += '</li>';
			var adsHtml = '<li class="ads05" id="ads'+(parseInt(adsLen)+1)+'"><img src="" alt="612 × 200" /></li>';
			
			//创建
			if(listLen == adsLen && listLen >= 5){
				$(listHtml).appendTo(".uploadFileList");
				$(adsHtml).appendTo(".adsList");
			}
		},
		triggerFile : function(){
			var randomId = parseInt(Math.random()*(2000-10)+10);
			var thisIndex = $(this).closest("li").index();
			adsUpload.creat_uploadFile(randomId);
			$("#dooPicFile"+randomId).trigger("click");
			$("#dooPicFile"+randomId).live("change",{index:thisIndex},adsUpload.change_uploadFile);
		},
		creat_uploadFile : function(rid){
			var uploadFormHtml = "";
				uploadFormHtml += '<form action="'+adsUpload.actionUrl+'" id="dooMyform'+rid+'" class="dooMyform" style="display:none">';
				uploadFormHtml += '<input type="file" id="dooPicFile'+rid+'" class="dooPicFile" ';
				uploadFormHtml += ' accept="*.*" />';
				uploadFormHtml += '</form>';
				$(uploadFormHtml).appendTo("body");
		},
		change_uploadFile : function(event){
			//获取本次上传结果
			var picArray = this.files,
				picArray_len = picArray.length,
				thisIndex = event.data.index;
			//拼接数组
			for(var i =0; i<picArray.length; i++){
				adsUpload.fileArray.push(picArray[i]);
			}
			//删除本次表单
			$("#dooMyform").remove();
			//设置预览图
			adsUpload.set_preview(thisIndex,picArray_len);
		},
		set_preview : function(index,picLen){
			var $previewBox = $("#ads"+String(parseInt(index)+1));
			var a = 0;
			var clearAll = function(){
				adsUpload.fileArray.splice(0,picLen);
				//console.log("清楚所有内容" + adsUpload.fileArray);
			}
			var get_picload = function(ab){
				if( ab < picLen ){
					reader = new FileReader();
					reader.readAsDataURL(adsUpload.fileArray[ab]);
					reader.onload = function(e) {
						var img = new Image();
						var result_pic = e.target.result;
							img.src = result_pic;
						img.onload = function(){
							$previewBox.text(" ");
							$previewBox.append('<img src="'+result_pic+'" />');
							//执行上传
							adsUpload.fileUpload(ab,index);
							ab++;
							get_picload(ab);
						}
					}
				}else{
					setTimeout(clearAll,1000);
				}
			}
			//执行
			get_picload(a);
		},
		fileUpload : function(index,index_eq){
			
			// 上传内容
			var picfiles = adsUpload.fileArray;
			// 长度
			var picfiles_len = picfiles.length;
			// POST方法
			var xhr = new XMLHttpRequest();
			var formData = new FormData();
			//创建表单项
			formData.append('picture', picfiles[index]);
				/*
				var progress = false;
				if(progress){
					//进度条
					$.fn.dooupload.creat_progress();
					xhr.onprogress = dooUpload.updateProgress;
					xhr.upload.onprogress = dooUpload.updateProgress;
				}
				*/
				//完成上传
				xhr.onload = function(event){
					
					var returnValue = $.parseJSON(event.target.responseText);
					//console.log(event.target.responseText);
					var returnValue_url = returnValue.url;
					var $box = $(".uploadFileList li").eq(index_eq);
					$box.find(".uploadList_img").attr("value",returnValue_url);
					/*
					//创建returnValue
					
								
					if( $('input[data-type="'+o.previewBox+'"]').length == 0 ){
						$(hiddenInput).appendTo("#myform");
					}else{
						$('input[data-type="'+o.previewBox+'"]').attr("src",returnValue_url);
					}
					*/
					//删除进度条
					$(".progressBor").remove();
					//console.log("上传完成");
				}
				xhr.ontimeout = function(event){
					alert('请求超时！');
				}
				//执行POST
			xhr.open('POST', adsUpload.actionUrl);
			xhr.send(formData);
		},
		updateProgress : function(event){
			if (event.lengthComputable) {
				var percentComplete = event.loaded / event.total;
				//console.log("progress:"+percentComplete);
				$(".progressBar").css("width",(percentComplete * 100)+"%");
				$(".progressText").text((percentComplete * 100)+"%");
			}
		},
		saveUpload : function(){
			var $this = $(this),
				$thisFather = $this.closest("li")
				thisId = $thisFather.attr("data-id"),
				thisTitle = $thisFather.find(".uploadList_title").val(),
				thisIdstr = $thisFather.find(".uploadList_content").val(),
				thisIdstrForIos = $thisFather.find(".uploadList_content.iosData").val(),
				thisImg = $thisFather.find(".uploadList_img").val(),
				save_post_url = adsUpload.saveUrl,
				data_list = {
					"id" : thisId,
					"title" : thisTitle,
					"id_str" : thisIdstr,
					"id_str_ios" : thisIdstrForIos,
					"img" : thisImg
				};
			var saveCallback = function(data){
				if(data.flag){
					alert(data.msg);
				}
			}
			$.post(save_post_url,data_list,saveCallback,"json");
		},
		delUpload : function(){
			var $this = $(this),
				$thisFather = $this.closest("li")
				thisId = $thisFather.attr("data-id"),
				del_post_url = adsUpload.delUrl;
			var data_list = {
				"id" : thisId
			}
			var delCallback = function(data){
				if(data.flag){
					alert(data.msg);
				}
			}
			$.post(del_post_url,data_list,delCallback,"json");
		}
	}

	$(function(){
		$(".addMoreCate").live("click",adsUpload.addCateUpload);
		$(".uploadList_trigger").live("click",adsUpload.triggerFile);
		$(".uploadList_save").live("click",adsUpload.saveUpload);
		$(".uploadList_del").live("click",adsUpload.delUpload);
		$(".uploadFileList li").live({
			mouseenter : function(){
				var $this = $(this), thisIndex = $this.index();
				$(".adsList li").eq(thisIndex).addClass("hovered");
			},
			mouseleave : function(){
				var $this = $(this), thisIndex = $this.index();
				$(".adsList li").eq(thisIndex).removeClass("hovered");
			}
		});
	})
})(jQuery);