$.m.user={};
(function ($){
    var m=$('#main_1511010745');
    $.extend($.m.user,{
    	dd:new Dd({
            table:{
                cols:{ 'account':'账号','nickname':'昵称','headimgurl':'头像','phone':'手机','status':'状态','create_time':'创建时间' },
                cols_conf:{
                    btn:['edit','del','status'],
                    other_btn:[{
                        'text':'重置密码',
                        'click':reset_pwd
                    }],
                    event:{data_after1:data_after1},
                    chk:true
                }
            },
            form:{ add_id:'form_1511010745', edit_id:'form_1511010745',event:{
                add_open:add_open,
                edit_open:edit_open,
            } },
            search:{ title:'请输入账号名' },
            toolbar:{ oper:['add'] }
        }),
        //上传图片配置
        conf:{
            uploadUrl: '/admin/user/upload', //上传的地址
            maxFileSize:20
        }
    })
    m.find('[name="start_time"]').eq(0).datetimepicker({
        format: 'yyyy-mm-dd',
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
        autoclose: 1,
        minView: 2,
        forceParse: 0
    });  
    m.find('[name="end_time"]').eq(0).datetimepicker({  
        format: 'yyyy-mm-dd',
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
        autoclose: 1,
        minView: 2,
        forceParse: 0
    });
    function data_after1(data){
        $.each(data.data,function (index,item){
            data.data[index].headimgurl='<img data-tips-image src="'+item.headimgurl+'"' +
                ' style="width:24px;height:24px;"  onerror="this.src=\'/static/image/admin/imgError.jpg\'" >';
        });
        return data;
    }
    //重置密码
    function reset_pwd(id){
        layer.confirm('确定重置密码，默认密码:123456？',{icon:3},function (index){
            $.get('/admin/user/reset_pwd',{ id:id, r:Math.random() },function (data){
                if(data.status==1){
                    layer.alert(data.info,{ icon:1 });
                }else{
                    layer.alert(data.info,{ icon:2 });
                }
            });
            layer.close(index);
        })
    }
    function add_open(){
        // alert($('#form_1511010745_add').prop('id'));
        $.f.updown.up_img({
            container_obj:$('#form_1511010745_add'),
            file_conf:$.m.user.conf,
            file_name:'image',
            bind_img:{key:'headimgurl',val:''}
        });
    }
    function edit_open(data){
        $.f.updown.up_img({
            container_obj:$('#form_1511010745_edit'),
            file_conf:$.m.user.conf,
            file_name:'image',
            bind_img:{key:'headimgurl',val:data.headimgurl}
        });
    }
})(jQuery);