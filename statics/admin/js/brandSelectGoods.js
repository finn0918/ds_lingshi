/**
 * Created by vqianduan on 15/7/24.
 */
var globalBrand;
var selectLock = true;
//时间转时间戳
Date.prototype.format = function(format) {
    var date = {
        "M+": this.getMonth() + 1,
        "d+": this.getDate(),
        "h+": this.getHours(),
        "m+": this.getMinutes(),
        "s+": this.getSeconds(),
        "q+": Math.floor((this.getMonth() + 3) / 3),
        "S+": this.getMilliseconds()
    };
    if (/(y+)/i.test(format)) {
        format = format.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length));
    }
    for (var k in date) {
        if (new RegExp("(" + k + ")").test(format)) {
            format = format.replace(RegExp.$1, RegExp.$1.length == 1
                ? date[k] : ("00" + date[k]).substr(("" + date[k]).length));
        }
    }
    return format;
}
//输出商品
function getGoods(d,i,brandId) {
    var listhtml = '';
    var postdata = {
        query  : '1',
        way    : proChoiceWay
    }
    if(d){
        postdata = d;
    }
    if(i) {
        postdata.pagex = i;
    }
    if(brandId) {
        postdata.goods_brand = brandId;
    }
    if(selectLock) {
        selectLock = false;
        $.ajax({
            type     : "POST",
            url      : "admin.php?m=Selectgoods&a=query",
            data     : postdata,
            dataType : "json",
            success  : function(d) {
                var info = d.info;
                if(d.flag) {
                    listhtml += '<ul class="selectGoodsList">';
                    for(var i=0; i<info.length; i++) {
                        listhtml += '<li><div class="inner">';
                        listhtml += mutiChoice ? '<input type="checkbox" class="goodsCheck" name="choiceInput" data-id="'+info[i].id+'">' : '<input type="radio" class="goodsCheck" name="choiceInput" data-id="'+info[i].id+'">';
                        listhtml += '<img src="'+info[i].image+'" class="thumb" />';
                        listhtml += '<h3>'+info[i].name+'</h3>';
                        listhtml += '<p>￥&nbsp;<span class="you">'+info[i].transform+'</span><strong>'+info[i].nowprice+'</strong>&nbsp;&nbsp;<s>'+info[i].oldprice+'</s></p>';
                        listhtml += '</div></li>';
                    }
                    listhtml += '</ul>';
                    /*
                    var pageCount = Math.ceil(d.len/100);
                    var pagehtml = '', page = 0;

                    pagehtml = '<div id="page">';
                    for(var p=0;p<pageCount;p++) {
                        page = p+1;
                        pagehtml += '<a href="javascript:;" onclick="changePage('+p+')">'+ page +'</a>';
                    }
                    pagehtml += '</div>';
                    if($(".page").length <= 0){
                        $(pagehtml).appendTo(".control");
                    }
                    */
                } else {
                    listhtml = d.msg;
                }
                $(".selectGoods .innerBox").html(listhtml);
            }
        })
    }
}
//翻页
function changePage(index){

    getGoods(queryData, index, globalBrand);
}


function getSpulist(type) {
    var spuId = $("#spu").val(),
        spuIdArr = new Array();
    var $table = $("#skulist").find("tbody");
    spuIdArr = spuId.split(",");
    //if(proChoiceWay != 4){
    //    $table.html('');
    //}
    var ix = 0;
    for(var i=0;i<spuIdArr.length;i++) {
        $.ajax({
            type : "POST",
            url  : "admin.php?m=Selectgoods&a=getSku",
            data : {
                spu_id : spuIdArr[i]
            },
            dataType : "json",
            success  : function(d) {

                var sku = d.sku;
                var len = sku ? sku.length : 0;
                var html = '';
                var listhtml = '';
                var totalLen = d.len;

                var eachTheSpu = '';
                var appendSwitch = true;
                var skulistLen = $("#skulist tr").length;

                for(var si=0;si<skulistLen;si++) {
                    eachTheSpu = $("#skulist tr").eq(si).attr("data-spu");
                    if(d.spu_id == eachTheSpu) {
                        appendSwitch = false;
                    }
                }

                if(appendSwitch) {
                    html += '<tr class="spuAdd spuLine" data-spu="'+d.spu_id+'">';
                    //设置淘客标识
                    html += '<td align="center" width="50"><a href="javascript:;" class="delThis" data-id="'+d.spu_id+'">删除</a></td>';
                    html += '<td align="center" width="80" data-taoke="'+d.istaoke+'" id="taoke'+d.spu_id+'">' +d.spu_id+ '</td>';
                    html += '<td align="center">' +d.title+ '</td>';
                    html += '<td align="center"><img src="'+d.image_src+'" width="80" /></td>';
                    html += '<td align="center" width="50">' +d.cate+ '</td>';
                    html += '<td align="center" width="50">';
                    html += '<a href="javascript:;" class="upLineControl"><font color="blue">上移</font></a><br/>';
                    html += '<a href="javascript:;" class="downLineControl"><font color="blue">下移</font></a>';
                    html += '</td>';
                    //SKU信息
                    html += '<td width="45%"><div class="likeATable">';
                    html += '<div class="row10">';
                    html += '<div class="col2" align="center">SKU</div>';
                    html += '<div class="col2" align="center">价格(元)</div>';
                    html += '<div class="col2" align="center">销量(件)</div>';
                    html += '<div class="col2" align="center">库存(件)</div>';
                    html += '<div class="col2" class="hidepriceHead" align="center">活动价格(元)</div>';
                    html += '<div class="col2" class="hidestockHead" align="center">活动库存(件)</div>';
                    html += '</div>';
                    if(d.istaoke == 'yes') {
                        //淘客商品
                        html += '<div class="row10">';
                        html += '<div align="center" class="col2 hideskuid a' +d.spu_id+ '">无</div>';
                        html += '<div align="center" class="col2 normalprice">'+d.spu_price+'</div>';
                        html += '<div align="center" class="col2 b' +d.spu_id+ '">'+d.spu_sale+'</div>';
                        html += '<div align="center" class="col2 e' +d.spu_id+ '">'+d.spu_stock+'</div>';
                        html += '<div align="center" class="col2 hideprice c' +d.spu_id+ '">'+d.spu_price+'</div>';
                        html += '<div align="center" class="col2 hidestock d' +d.spu_id+ '">'+d.spu_stock+'</div>';
                        html += '</div>';
                    } else {
                        for(var i=0; i<len; i++) {
                            html += '<div class="row10 spuAdd" data-spu="'+d.spu_id+'">';
                            html += '<div align="center" class="col2 hideskuid a' +d.spu_id+ '" data-id="'+ sku[i].id +'">' +sku[i].name+ '</div>';
                            html += '<div align="center" class="col2 normalprice">' +sku[i].price+ '</div>';
                            html += '<div align="center" class="col2 b' +d.spu_id+ '">' +sku[i].sku_sale+ '</div>';
                            html += '<div align="center" class="col2 e' +d.spu_id+ '">' +sku[i].sku_stocks+ '</div>';
                            html += '<div class="col2 hideprice c' +d.spu_id+ '" align="center"><input type="text" value="0" /></div>';
                            html += '<div class="col2 hidestock d' +d.spu_id+ '" align="center"><input type="text" value="0" /></div>';
                            html += '</div>';
                        }
                    }
                    html += '</div></td>';
                    $table.append(html);
                }

                //清除选中
                $(".selectGoodsList .goodsCheck").removeAttr("checked");
                //根据类型设置
                var mytype = proChoiceWay == 4 ? $("#activeType").val() : $("#typeChange option:selected").val();
                if(mytype == '0') {
                    $(".hideprice,.hidepriceHead,.hidestock,.hidestockHead").hide();
                } else if(mytype == '1') {
                    $(".hideprice,.hidepriceHead").show();
                    if(proChoiceWay == 4) {
                        $(".spuAdd .hidestock").html('');
                    }
                } else if(mytype == '2') {
                    $(".hideprice,.hidepriceHead,.hidestock,.hidestockHead").show();
                }
            }
        })
    }

}

var queryData = {
    query  : '1'
}

$(function(){
    //列表删除
    $(document).on("click",".delThis",function(){
        var mySpu = $(this).closest("tr").attr("data-spu"),
            myClass = $(this).closest("tr").attr("class"),
            myTr = $("#skulist tr[data-spu='"+mySpu+"']");
        if(myClass && myClass.match('spuAdd') == 'spuAdd') {
            myTr.remove();
        } else {
            myTr.addClass("spuCut").hide();
        }
    });
    //列表上移
    $(document).on("click",".upLineControl",function(){
        var index = $(this).closest("tr").index();
        if(index <= 0) {
            alert("到顶了");
            return false;
        }
        $("#skulist tbody tr:nth-child("+index+")").insertAfter($("#skulist tbody tr:nth-child("+(index+1)+")"));
    });
    //列表下移
    $(document).on("click",".downLineControl",function(){
        var index = $(this).closest("tr").index();
        console.log(index);
        $("#skulist tbody tr:nth-child("+(index+1)+")").insertAfter($("#skulist tbody tr:nth-child("+(index+2)+")"));
    });
    //选品翻页
    $(window).on("click",".selectgoodsNextPage",function(){
        var nowpage = parseInt($(".selectgoodsNowpage").text()) + 1;
        $(".selectGoods .innerBox").html('加载中...');
        //翻页读取商品
        getGoods(queryData, nowpage, globalBrand);
        $(".selectgoodsNowpage").text(nowpage);
    });
    $(window).on("click",".selectgoodsPrevPage",function(){
        var nowpage = $(".selectgoodsNowpage").text() == '0' ? 0 : ( parseInt($(".selectgoodsNowpage").text()) - 1 );
        $(".selectGoods .innerBox").html('加载中...');
        //翻页读取商品
        getGoods(queryData, nowpage, globalBrand);
        $(".selectgoodsNowpage").text(nowpage);
    });
    //商品搜索
    $("#selectGoods_search").on("click",function(){
        var key = $("#goods_keyword").val();
        var timeStart = $("#goods_time_start").val();
        var timeEnd = $("#goods_time_end").val();
        var priceStart = $("#price_start").val();
        var priceEnd = $("#price_end").val();
        var type = $("#goods_type option:selected").val();
        var cate = $("#cate_id option:selected").val();
        var brand = $("#goods_brand option:selected").val();

        queryData.way = proChoiceWay;

        if(key) {
            queryData.goods_keyword = key;
        }
        if(timeStart) {
            queryData.goods_time_start = timeStart;
        }
        if(timeEnd) {
            queryData.goods_time_end = timeEnd;
        }
        if(priceStart) {
            queryData.price_start = priceStart;
        }
        if(priceEnd) {
            queryData.price_end = priceEnd;
        }
        if(type) {
            queryData.goods_type = type;
        }
        if(cate) {
            queryData.cate_id = cate;
        }
        if(brand) {
            queryData.goods_brand = brand;
        }
        selectLock = true;
        getGoods(queryData);

    });
    //保存并关闭窗口
    $(".selectGoods #choiceThis").on("click", function(){
        var checkId = [],
            status  = '',
            mypage = $(this).attr("data-type");
        $(".selectGoodsList li").each(function(i){
            status = $(".goodsCheck").eq(i).attr("checked");
            if(status == "checked") {
                checkId.push($(".goodsCheck").eq(i).attr("data-id"));
            }
        });
        //8.1 选择完成后，锁定类型
        $("#typeChange").attr("disabled","disabled").css("background","#F4F4F4");
        //8.1 赋值
        $("#typeChangeValue").val($("#typeChange option:selected").val());
        /*
        if($("#brand").length > 0) {
            $("#brand").attr("disabled","disabled").css("background","#F4F4F4");
        }
        */
        $("#group_show_value, #spu").val(checkId);
        $(".selectGoods,.fullscreen_shadow").hide();
        if(mypage) {
            getSpulist(mypage);
        } else {
            getSpulist();
        }
    });
    //关闭弹窗
    $(".selectGoods #cancel").on("click", function(){
        $(".selectGoods,.fullscreen_shadow").hide();
    });
    //显示弹窗
    $("#select_group").on("click", function(){
        var brandLen = $("#brand option:selected").length,
            brandValue = $("#brand option:selected").val();
        if(brandLen > 0) {
            globalBrand = brandValue;
            if(brandValue == 0) {
                alert("请先选择品牌！");
                return false;
            } else {
                getGoods('','',brandValue);
            }
        } else if(proChoiceWay == '4') {
            getGoods('','',$("#activeBrandId").val());
        } else {
            getGoods();
        }
        $(".selectGoods,.fullscreen_shadow").show();
    });
})
