//提示
function prompt(text,time){
    layer.open({
        title:false,
        btnAlign:"c",
        content:"<p style='text-align:center;font-size:18px;'>"+text+"</p>",
        time:time||6000,
        shade:0.6,
        end:function(){
            layer.closeAll();
        }

    })
}

wx.ready(function() {
    wx.getLocation({
        type: 'wgs84',
        success : function(res) {
            var latitude = res.latitude; // 纬度，浮点数，范围为90 ~ -90
            var longitude = res.longitude; // 经度，浮点数，范围为180 ~ -180。
            $("#latitude").val(latitude);
            $("#longitude").val(longitude);
        },
        error : function(res){
            alert("获取地理位置信息失败，请确认您的手机是否允许微信使用定位服务。");
        }
    });


});
