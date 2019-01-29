//后台全局js
$(function () {
    var $body = $('body');

    /*! 注册 data-tips-image 事件行为 */
    $body.on('click', '[data-tips-image]', function () {
        var img = new Image(), src = this.getAttribute('data-tips-image') || this.src;
        var imgWidth = this.getAttribute('data-width') || '480px';
        img.onload = function () {
            var $content = $(img).appendTo('body').css({background: '#fff', width: imgWidth, height: 'auto'});
            layer.open({
                type: 1, area: imgWidth, title: false, closeBtn: 1,
                skin: 'layui-layer-nobg', shadeClose: true, content: $content,
                end: function () {
                    $(img).remove();
                }
            });
        };
        img.src = src;
    });
//ajax请求
  var AjaxRequest =  function (option,success){
        var defaults = {
            async:true,//是否同步true:异步,false:同步
            dataType:'json',//请求数据类型
            type:"get",//请求方式
            timeout:10000,//请求超时时间
            layer: true            //是否开启加载动画 true：开启，false：关闭
        }
        var settings =$.extend(defaults,option)
      var layerIndex;
      if(settings.layer){
          //加载动画
          layerIndex = layer.load(2, { isOutAnim: false });
      }
        $.ajax({
            async:settings.async,
            type:settings.type,
            timeout:settings.timeout,
            dataType:settings.dataType,
            data:settings.data,
            success:function(res){
                layer.close(layerIndex)
                success(res)
            },
            error:function(err){
                layer.close(layerIndex)
                alert(JSON.stringify(err))
            },
            complete:function(xmlHttpRequest,status){
                layer.close(layerIndex)
                if(status=="timeout"){
                    alert("请求超时")
                }
            }
        })
    }
    //加载
   var load = function(text){
        layer.load(3,{
            content:text,
            shade:0.5,
        });

    }
    //提示
    var prompt =function(text,go,time){
        layer.open({
            title:false,
            btnAlign:"c",
            content:"<p style='text-align:center;font-size:18px;'>"+text+"</p>",
            time:time||6000,
            shade:0.6,
            end:function(){
                if(go=="1"){
                    history.go(0);
                }
                layer.closeAll();
            }

        })
    }

})