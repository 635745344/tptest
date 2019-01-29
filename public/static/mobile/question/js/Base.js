//提示框
(function (w) {
    var ui = w.ui = {};
    ui.opendivshow = function (title) {
     
        $("#lightshow").css("display", "block");
        $("#fadeshow").css("display", "block");
        $("#messagebox").html(title);
        
        
        $("#surebuttonno").css("display", "none");
        $("#surebuttonok").css("margin-left", "32.5%").css("float", "");
       
        $("#surebuttonok").click(function () {
            $("#lightshow").css("display", "none");
            $("#fadeshow").css("display", "none");
        });
        
        if ($(":focus").length > 0) {
            $(":focus")[0].blur();
        }
    }
    ui.confirm = function (title, url) {
        $("#lightshow").css("display", "block");
        $("#fadeshow").css("display", "block");
        $("#messagebox").html(title);
        if ($(":focus").length>0) {
            $(":focus")[0].blur();
        }
        $("#surebuttonok").click(function () {
            window.location = url;
        });
        
    }
    ui.confirmajax = function (title) {
        $("#lightshow").css("display", "block");
        $("#fadeshow").css("display", "block");
        $("#messagebox").html(title);
        if ($(":focus").length > 0) {
            $(":focus")[0].blur();
        }
        $("#surebuttonok").click(function () {
            return true;
        });
        $("#surebuttonno").click(function () {
            return false;
        });

    }
})(window);

//跳转到提示信息页面
//tipMessage：提示信息
//tipType：是否成功
//isShowGuanZhu：是否已经关注微信
//date：2015-5-21
function showMessage(tipMessage, tipType, isShowGuanZhu) {
    window.location.href = "ShowTip?tipMessage=" + tipMessage + "&tipType=" + tipType + "&isShowGuanZhu=" + isShowGuanZhu
}

//获取参数值
function getURLParameter(paramName) {
    var params = (window.location.href.substr(window.location.href.indexOf("?") + 1)).split("&");
    if (params != null) {
        for (var i = 0; i < params.length; i++) {
            var strs = params[i].split("=");

            // 使用toLowerCase()解决url大小写错误导致Post请求过去的数据不对，最终导致WCF记录不下抽奖记录，让客户觉得每次都能显示抽奖转盘（实际抽不中）
            if (strs[0].toLowerCase() == paramName.toLowerCase()) {
                return strs[1];
            }
        }
    }
    return "";
}


