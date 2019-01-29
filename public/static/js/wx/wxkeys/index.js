$.m.keys={}
$(function (){
	$.extend($.m.keys,{
        dd:new Dd({
            table:{
                cols:{ 'keys':'关键字','type':'回复类型','status':'状态','create_time':'创建时间' },
                cols_conf:{
                    btn:['edit','del','status'],
                    chk:true, 
                    event:{
                        data_after1:data_after1,
                    }
               }
            },
            form:{ add_id:'form_key_1511010745', edit_id:'form_key_1511010745',event:{
                add_open:add_open,
                edit_open:edit_open,
            }  },
            search:{ title:'请输入关键字' },
            toolbar:{ oper:['add','del','status'] },
            params:{
                id:'keys_1511010745'
            }
        }),
        //上传图片配置
        conf:{
            uploadUrl: '/admin/user/upload', //上传的地址
            maxFileSize:20
        }
	})
    var form_key_add=$('#form_key_1511010745_add');
    var form_key_edit=$('#form_key_1511010745_edit');

    function data_after1(data){
        var t_data=data.data;
        for(var i=0;i<t_data.length;i++){
           switch(t_data[i].type){
                case 'text': t_data[i].type='文本'; break;
                case 'image': t_data[i].type='图片'; break;
                case 'news': t_data[i].type='图文'; break;
                case 'voice': t_data[i].type='音频'; break;
                case 'video': t_data[i].type='视频'; break;
           }
        }
        return data;
    }

    //关键字添加
    function add_open(){
        $.f.updown.up_img({
            container_obj:$('#form_key_1511010745_add').find('#key_image'),
            file_conf:$.m.keys.conf,
            file_name:'image',
            bind_img:{key:'image_url',val:''}
        });
        $.f.updown.up_img({
            container_obj:$('#form_key_1511010745_add').find('#key_voice'),
            file_conf:$.m.keys.conf,
            file_name:'image',
            bind_img:{key:'music_image'}
        });
        change_type('text',form_key_add,{});
    }
    //绑定绑定
    form_key_add.find('[name="type"]').change(function (){
        change_type($(this).val(),form_key_add,{});
    })
     
    //关键字编辑
    function edit_open(data){
        $.f.updown.up_img({
            container_obj:$('#form_key_1511010745_edit').find('#key_image'),
            file_conf:$.m.keys.conf,
            file_name:'image',
            bind_img:{key:'image_url',val:''}
        });
        $.f.updown.up_img({
            container_obj:$('#form_key_1511010745_edit').find('#key_voice'),
            file_conf:$.m.keys.conf,
            file_name:'image',
            bind_img:{key:'music_image'}
        });
        change_type(data.type,form_key_edit,data);
    }

    form_key_edit.find('[name="type"]').change(function (){
        change_type($(this).val(),form_key_edit,{});
    })

    //显示关键字编辑类型
    function change_type(type='text',form_obj,data={}){
        var p='key_';
        form_obj.find('[name="type_1514389416"]').each(function (index,item){
            if($(item).prop('id')!= p+type){
                $(item).hide();
            }else{
                $(item).show();
            }
        })
        var selectVal = form_obj.find('[name="type"]').eq(0).val();
        form_obj[0].reset();
        form_obj.find('[name="type"]').eq(0).val(selectVal);
    }
});
//关注后回复
$(function (){
    $.f.form.bind_update('form_follow_1511010745',{});
    $.f.updown.up_img({
        container_obj:$('#follow_image'),
        file_conf:$.m.keys.conf,
        file_name:'image',
        bind_img:{key:'image_url'}
    });
    $.f.updown.up_img({
        container_obj:$('#follow_voice'),
        file_conf:$.m.keys.conf,
        file_name:'image',
        bind_img:{key:'image_url'}
    });

    $.get('/wx/keys/get_follow',{r:Math.random()},function (data){
        change_type(data.type,data);
    },'json');

    change_type('text',{});
    $('#form_follow_1511010745').find('[name="type"]').change(function (){
        change_type($(this).val(),{});
    })
    //显示关键字编辑类型
    function change_type(type='text',data={}){
        var p='follow_';
        $('#form_follow_1511010745').find('[name="type_1514389416"]').each(function (index,item){
            if($(item).prop('id')!= p+type){
                $(item).hide();
            }else{
                $(item).show();
            }
        })
    }
});
//默认回复
$(function (){
    $.f.form.bind_update('form_active_1511010745',{});
    $.f.updown.up_img({
        container_obj:$('#active_image_url'),
        file_conf:$.m.keys.conf,
        file_name:'image',
        bind_img:{key:'image_url'}
    });
    //默认回复编辑类型
    
});
