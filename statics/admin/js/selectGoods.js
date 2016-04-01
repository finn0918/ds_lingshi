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

    //var key = $("#goods_keyword").val();
    //var timeStart = $("#goods_time_start").val();
    //var timeEnd = $("#goods_time_end").val();
    //var priceStart = $("#price_start").val();
    //var priceEnd = $("#price_end").val();
    //var type = $("#goods_type option:selected").val();
    //var cate = $("#cate_id option:selected").val();
    //var brand = $("#goods_brand option:selected").val();
    //
    //var queryData = {
    //    query  : '1',
    //    way    : proChoiceWay
    //}
    //
    //if(key) {
    //    queryData.goods_keyword = key;
    //}
    //if(timeStart) {
    //    queryData.goods_time_start = timeStart;
    //}
    //if(timeEnd) {
    //    queryData.goods_time_end = timeEnd;
    //}
    //if(priceStart) {
    //    queryData.price_start = priceStart;
    //}
    //if(priceEnd) {
    //    queryData.price_end = priceEnd;
    //}
    //if(type) {
    //    queryData.goods_type = type;
    //}
    //if(cate) {
    //    queryData.cate_id = cate;
    //}
    //if(brand) {
    //    queryData.goods_brand = brand;
    //}

    getGoods(queryData, index, globalBrand);
}


function getSpulist(type) {
    var spuId = $("#spu").val(),
        spuIdArr = new Array();
    var $table = $("#skulist").find("tbody");
    spuIdArr = spuId.split(",");

    if(proChoiceWay != 4){
        $table.html('');
    }

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
                console.log(i);
                var sku = d.sku;
                var len = sku ? sku.length : 0;
                var html = '';
                var listhtml = '';
                var totalLen = d.len;
                html += proChoiceWay == 4 ? '<tr class="spuAdd spuLine" data-spu="'+d.spu_id+'">' : '<tr>';
                //设置淘客标识
                html += proChoiceWay == 4 ? '<td rowspan="'+len+'" align="center"><a href="javascript:;" class="delThis" data-id="'+d.spu_id+'">删除</a></td>' : '';
                html += '<td rowspan="'+len+'" align="center" data-taoke="'+d.istaoke+'" id="taoke'+d.spu_id+'">' +d.spu_id+ '</td>';
                html += '<td rowspan="'+len+'" align="center">' +d.title+ '</td>';
                html += '<td rowspan="'+len+'" align="center"><img src="'+d.image_src+'" width="80" /></td>';
                html += '<td rowspan="'+len+'" align="center">' +d.cate+ '</td>';
                //if(proChoiceWay == 3 || proChoiceWay == 4) {
                //    html += '<td rowspan="'+len+'" align="center">';
                //    html += '<a href="javascript:goUp('+ i +','+ spuIdArr.length +','+ len +')"><font color="blue">上移</font></a>&nbsp;&nbsp;&nbsp;';
                //    html += '<a href="javascript:goDown('+ i +','+ spuIdArr.length +','+ len +')"><font color="blue">下移</font></a></td>';
                //}
                if(d.istaoke == 'yes') {
                    //淘客商品
                    html += '<td align="center" class="hideskuid a' +d.spu_id+ '">无</td>';
                    html += '<td align="center" class="normalprice">'+d.spu_price+'</td>';
                    html += '<td align="center" class="b' +d.spu_id+ '">'+d.spu_sale+'</td>';
                    html += '<td align="center" class="e' +d.spu_id+ '">'+d.spu_stock+'</td>';
                    html += '<td class="hideprice c' +d.spu_id+ '" align="center">'+d.spu_price+'</td>';
                    html += '<td class="hidestock d' +d.spu_id+ '" align="center">'+d.spu_stock+'</td>';
                    html += '</tr>';
                } else {
                    for(var i=0; i<len; i++) {
                        if(i == 0) {
                            html += '<td align="center" class="hideskuid a' +d.spu_id+ '" data-id="'+ sku[i].id +'">' +sku[i].name+ '</td>';
                            html += '<td align="center" class="normalprice">' +sku[i].price+ '</td>';
                            html += '<td align="center" class="b' +d.spu_id+ '">' +sku[i].sku_sale+ '</td>';
                            html += '<td align="center" class="e' +d.spu_id+ '">' +sku[i].sku_stocks+ '</td>';
                            html += '<td class="hideprice c' +d.spu_id+ '" align="center"><input type="text" value="0" /></td>';
                            html += '<td class="hidestock d' +d.spu_id+ '" align="center"><input type="text" value="0" /></td>';
                            //html += '<td rowspan="'+len+'" align="center"><a href="javascript:;" class="deleteMe">移除</a></td>';
                            html += '</tr>';
                        } else {
                            html += '<tr class="spuAdd" data-spu="'+d.spu_id+'">';
                            html += '<td align="center" class="hideskuid a' +d.spu_id+ '" data-id="'+ sku[i].id +'">' +sku[i].name+ '</td>';
                            html += '<td align="center" class="normalprice">' +sku[i].price+ '</td>';
                            html += '<td align="center" class="b' +d.spu_id+ '">' +sku[i].sku_sale+ '</td>';
                            html += '<td align="center" class="e' +d.spu_id+ '">' +sku[i].sku_stocks+ '</td>';
                            html += '<td class="hideprice c' +d.spu_id+ '" align="center"><input type="text" value="0" /></td>';
                            html += '<td class="hidestock d' +d.spu_id+ '" align="center"><input type="text" value="0" /></td>';
                            html += '</tr>';
                        }
                    }
                }
                $table.append(html);
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
    //翻页
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
    $(".selectGoods #choiceThis").on("click", function(){
        var checkId = [],
            status  = '';
        var mypage = $(this).attr("data-type");
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
