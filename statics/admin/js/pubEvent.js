/**
 * Created by vqianduan on 15/7/21.
 */
function changeStatus() {
    var type = $(this).attr("data-type");
    var checkStatus = '';
    var delArr = [];
    $(".becheck").each(function(i) {
        checkStatus = $(this).attr("checked");
        myid = $(this).attr("data-id");
        if(checkStatus){
            delArr.push(myid);
        }
    });
    setTimeout(function(){
        $.ajax({
            type : "post",
            url  : apiConfig.publish,
            data : {
                id : delArr,
                status : type
            },
            success : function(d){
                if(d.flag) {
                    alert("操作成功");
                    window.location.href = apiConfig.list;
                } else {
                    alert("操作失败");
                }
            }
        })
    },100);
}
function singleUpTable() {
    var delArr = $(this).attr("data-id");
    setTimeout(function(){
        $.ajax({
            type : "post",
            url  : apiConfig.publish,
            data : {
                id : delArr,
                status : 'published'
            },
            success : function(d){
                if(d.flag) {
                    alert("操作成功");
                    window.location.href = apiConfig.list;
                } else {
                    alert("操作失败");
                }
            }
        })
    },100);
}
function singleDownTabel() {
    var delArr = $(this).attr("data-id");
    setTimeout(function(){
        $.ajax({
            type : "post",
            url  : apiConfig.publish,
            data : {
                id : delArr,
                status : 'locked'
            },
            success : function(d){
                if(d.flag) {
                    alert("操作成功");
                    window.location.href = apiConfig.list;
                } else {
                    alert("操作失败");
                }
            }
        })
    },100);
}
$(function(){
    $("#checkAll").on("click",function() {
        var mystatue = $(this).attr("checked");
        var checkStatus = '';
        $(".becheck").each(function(i) {
            checkStatus = $(this).attr("checked");
            if(mystatue) {
                !checkStatus && $(this).attr("checked","checked");
            } else {
                $(this).removeAttr("checked");
            }
        })
    });
    $(".inputRadio").on("click",function() {
        var $numBox = $(".checked_num");
        var totalNum = 0;
        $(".becheck").each(function(i) {
            var checkStatus = $(this).attr("checked");
            checkStatus && totalNum++;
        });
        $numBox.text(totalNum);
    });
    //上下架
    $(".upTable, .downTable").on("click", changeStatus);
    $(document).on("click", ".sDownTable", singleDownTabel);
    $(document).on("click", ".sUpTable", singleUpTable);

    $(".delete").on("click",function(){
        var checkStatus = '';
        var myid = '';
        var delArr = [];
        var checkLen = $(".becheck").length;
        var r;
        if(checkLen <= 0){
            alert("请先选择要删除的商品!");
            return false;
        }
        r = confirm("是否删除？");
        if (r) {
            $(".becheck").each(function (i) {
                checkStatus = $(this).attr("checked");
                myid = $(this).attr("data-id");
                if (checkStatus) {
                    delArr.push(myid);
                }
            });
            setTimeout(function () {
                $.ajax({
                    type: "post",
                    url: apiConfig.del,
                    data: {
                        did: delArr
                    },
                    success: function (d) {
                        if (d.flag) {
                            alert("删除成功！");
                            window.location.href = apiConfig.list;
                        } else {
                            alert("删除失败");
                        }
                    }
                })
            }, 100);
        }
    })
    //单删
    $(document).on("click",".singleDelete",function(){
        var r = confirm("是否删除？");
        var myid = $(this).attr("data-id");
        if (r) {
            $.ajax({
                type : "post",
                url  : apiConfig.del,
                data : {
                    did : myid
                },
                success : function(d){
                    if(d.flag) {
                        alert("删除成功！");
                        window.location.href = apiConfig.list;
                    } else {
                        alert("删除失败");
                    }
                }
            })
        }
    })
});