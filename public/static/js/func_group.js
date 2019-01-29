//通用方法库
$.f={};
$(".xdsoft_datetimepicker.xdsoft_noselect.xdsoft_").remove();
;(function ($) {
    $.extend($.f,{
        //获取指定范围内所有元素id
        get_obj:function (id){
            var ids=[];
            get_child($('#'+id));
            function get_child(obj){
                obj.children(). each(function (){
                    var id = $(this).prop('id');
                    if(id){
                        var id_parts=id.split('_');
                        ids.push(id);
                        if(id_parts.length>1 
                            && id_parts[id_parts.length-1].length>=10 
                            && !/^[0-9]*$/.test(id_parts[id_parts.length-1])){
                            get_child($(this));
                        }
                    }else{
                      get_child($(this));
                    }
                });
            }
            var objs={};
            $.each(ids,function (index,item){
                // eval('objs['+item+']=$("#'+id+').find("#'+item').eq(0);');
                objs[item]=$('#'+id).find('#'+item).eq(0);
            });
            objs['main']=$('#'+id);
            return objs;
        },
        //获取页面url地址（精确到控制器，后面加/，去除其他参数）
        get_url:function(){
            var url_parts=window.content_url_1511187455.split('/');
            var new_url='/'+url_parts[0]+'/'+url_parts[1]+'/';
            
            var max_index=3; //默认url没有index.php
            
            if(/index.php/.test(window.location.pathname)){
                new_url='/index.php'+new_url;
            }
            
            return new_url;
        },
        //获取控制器名称
        get_controller:function(url){
            var result='';
            $.each(url.split('/'),function(){
                if(this!='' && this!='index.php'){
                    result=this;
                    return false;
                }
            })
            return result;
        },
        //传入：模块/控制器/方法 自动拼接网站url
        u:function (url){
            if(location.pathname.split('/')[1]=='index.php'){
                url='/index.php/'+url;
            }else{
              url='/'+url;
            }
            return url;
        },
        //base结果显示
        r:function (data){
            if(data.status==1){
               layer.alert(data.info,{icon:1});
            }else{
               layer.alert(data.info,{icon:2});
            }
        },
        //获取唯一id
        guid:function (){
            var guid=S4()+S4()+S4()+S4()+S4()+S4()+S4()+S4();
            if($('#'+guid).length>0){
                $.f.guid();
            }else{
                return guid;
            }
            function S4() {
               return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
            }
        },
        //唯一值
        unique:function (){
            return $.f.guid();
        },
        //下载文件
        download:function (url, data, method) {
            method=method||'post';
            if (url && data) {
                var inputs = '';
                $.each(data.toString().split('&'),function(index,item){
                    var kv=item.split('=');
                    inputs += '<input type="hidden" name="' + kv[0] + '" value="' + kv[1] + '" />';
                });
                $('<form action="' + url + '" method="' + (method || 'post') + '">' + inputs + '</form>').appendTo('body').submit().remove();
            };
        },
        //上传文件
        upload:function (url, data,method){
            method=method||'post';
            $.ajax({
                url: url,
                type: method,
                data: data,
                cache: false,
                processData: false,
                contentType: false,
                success:function (data){
                    if(data.status==1){
                        layer.alert(data.info,{icon:1}); 
                    }else{
                        layer.alert(data.info,{icon:2});
                    }
                  }
             });
        },
        date:{
            //获取默认时间 now时间戳
            format_date:function (date){
                var time = new Date(date);
                var y = time.getFullYear();
                var m = time.getMonth()+1;
                var d = time.getDate();
                var h = time.getHours();
                var mm = time.getMinutes();
                var s = time.getSeconds();
                return y+'-'+add0(m)+'-'+add0(d)+' '+add0(h)+':'+add0(mm)+':'+add0(s);

                function add0(num){
                    if(num<=9){
                        return '0'+num.toString();
                    }
                    return num;
                }
            },
            //时间字符串转时间戳（is_second 是否精确到秒）
            timestamp:function (date,is_second){
                is_second=is_second||true;
                var timestamp = Date.parse(new Date(date));
                if(!isNaN(timestamp)){
                     timestamp = is_second ? timestamp / 1000: timestamp;
                }else{
                    timestamp=0;
                }
                return timestamp;
            }
        },
        array:{
            //是否存在
            is_exist:function (array,val){
                for(var key in array){
                    if(array[key]==val){
                        return true;
                    }
                }
                return false;
            },
            //去重复
            distinct:function (array){
                 var result = [];
                 var json = {};
                 for(var i = 0; i < array.length; i++){
                    if(!json[array[i]]){
                       result.push(array[i]);
                       json[array[i]] = 1;
                    }
                }
                return result;
            }
        },
        url:{ 
            //拼接url参数，格式pars：id=1&name=1
            format_parms:function (url,pars){
                var pars_str=url.split('?');
                var new_url='';
                if(pars_str.length>1){
                    new_url=url+'&'+pars;
                }else{
                    new_url=url+'?'+pars;
                }
                return new_url;
            }
        },
        data:{
            //表单序列化后转json对象
            formTojson:function (form_obj){
                var data = form_obj.serialize();
                data= decodeURIComponent(data,true);//防止中文乱码  
                data = data.replace(/&/g, "','" );
                data = data.replace(/=/g, "':'" );
                if(data==''){
                    data={};
                }else{
                    data = "({'" +data + "'})";
                }
                obj = eval(data);
                return obj;
            }
        },
        str:{
            trim:function (str){
                return str.replace(/(^\s*)|(\s*$)/g,"");
            }
        },
        localLoad:function (url) {
            // var url="admin/question/addView?id="+data.id;
            window.location.href='/group/admin/index#'+url;
            $('#content_1510826929').load('/'+url);
        }
    })
})(jQuery)
//模拟弹窗插件
$.f.modal={};
;(function ($) {
    var modal_p='modal_';//创建弹出前缀
    $.extend($.f.modal,{
        //创建模拟弹窗
        //return 模拟弹窗id
        create:function (params){
            //模拟弹出id
            var modal_id= modal_p + $.f.unique();

            var conf={
                obj:null,   //加载内容jq对象
                content:'',  //加载内容
                url:'',  //远程地址
                title:'', //标题
                size:2,  //1.小，2.中 ,3.大
                btn:[],  //配置参考 d_btn_conf
                d_btn:{  //默认按钮 不需要是填 close:null
                    close:{
                        is_warn:false //关闭时是否警告
                    }
                }
            };
            var btn_conf={
                id:'', //按钮id，一般不用配置
                text:'', //按钮文本值
                class:'btn btn-primary btn-flat', //样式
                click:null //点击事件
            }
            
            $.extend(true,conf,params); //合并后配置
            $.each(params.btn,function (index,item){
                conf.btn[index] = $.extend(JSON.parse(JSON.stringify(btn_conf)),item);
            })
            //是否存在该弹窗
            if($('#'+modal_id).length>0){
                return;
            }
            var size_class='';
            switch(conf.size){
                case 1: size_class='modal-sm';break;
                case 3: size_class='modal-lg';break;
            }

            var btn_save_id='btn_save_'+conf.id;
            var btn_save='';
            //  fade
            var header= '<div class="modal fade" id="'+modal_id+'" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" style="overflow-y:auto">'+
                        '<div class="modal-dialog '+size_class+'">'+
                        '<div class="modal-content" >'+
                            '<div class="modal-header">'+
                                '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                                '<h4 class="modal-title">'+conf.title+'</h4>'+
                            '</div>'+
                            '<div class="modal-body">';
            var footer =    '</div>'+
                            '<div class="modal-footer">'+
                            '</div>'+
                        '</div>'+
                        '</div>'+
                        '</div>';
            if(conf.obj){
                conf.obj.after(header+footer);
            }else{
                $('#content_1510826929').eq(0).prepend(header+footer);
            }
            //添加内容
            if(conf.obj){
                var obj=conf.obj.clone();
                conf.obj.remove();
                obj.show();
                obj.appendTo($('#'+modal_id).find('.modal-body').eq(0));
            }else if(conf.url!=''){
                $.get(conf.url,{r:Math.random()},function (data){
                    $('#'+modal_id).find('.modal-body').eq(0).append(data);
                });
            }else if(conf.content!=''){
                $('#'+modal_id).find('.modal-body').eq(0).append(conf.content);
            }
            //添加按钮
            for(var key in conf.d_btn){
                var btn_conf=conf.d_btn[key];
                if(key=='close'){
                    var btn=$('<button type="button" class="btn btn-default btn-flat" >关闭</button>');
                    btn.click(function (){
                        if(btn_conf['is_warn']){
                            layer.confirm('确定关闭？',{ icon: 3 },function (index){
                                $('#'+modal_id).modal('toggle');
                                layer.close(index);
                            });
                        }else{
                            $('#'+modal_id).modal('toggle');
                        }
                    });
                }
                $('#'+modal_id).find('.modal-footer').append(btn);
            }
            //添加自定义按钮
            $.each(conf.btn,function (index,item){
                var btn=$('<button type="button" class="'+item.class+'">'+item.text+'</button>');
                btn.click(function (){
                    item.click();
                });
                $('#'+modal_id).find('.modal-footer').append(btn);
            });
            return modal_id;
        },
        create_form:function (conf){
            if(conf.obj.hasClass('dd-form')){
                $.f.form.adapt(conf.obj);
            }
            if(!conf.size){
                conf.size = $.f.form.size(conf.obj);
            }
            conf.obj.addClass('form-horizontal');
            return $.f.modal.create(conf);
        },
        open:function (modal_id){
            //展示
            $('#'+modal_id).modal('show');
            var modal_top=getNowModalTop();
            setTop();
            setInterval(function (){
                var now_modal_top=getNowModalTop();
                if(modal_top!=now_modal_top){
                    modal_top=now_modal_top;
                    setTop();
                }
            },10);
            //设置顶部距离
            function setTop(){
                if(modal_top<40){
                    modal_top=40;
                }
                $('#'+modal_id).find('.modal-content').css('margin-top',modal_top);
            }
            //获取现在弹窗top距离
            function  getNowModalTop() {
                return ($(window).height()-parseInt( $('#'+modal_id).find('.modal-content').eq(0).css('height')) )/2-60;
            }
        },
        colse:function (modal_id){
            $('#'+modal_id).modal('hide');
        }
    })
})(jQuery)
//表单插件
$.f.form={};
;(function ($){
    $.extend($.f.form,{ 
        //通用解析页面
        bind:function (form_id){
            var select_finish_group=[]; //下来列表完成分组
            var form=$('#'+form_id);
            //清空select
            form.find('select').each(function (index,item){
                var url=$(item).data('url')?$(item).data('url'):'';
                if(url!=''){
                    $(item).children().remove();
                }
            });
            form.find('select').each(function (index,item){
                var group=$(item).data('group')?$(item).data('group'):'';
                if(group==''){
                    $.f.form.bind_select(form_id,$(item));
                }else if(!$.f.array.is_exist(select_finish_group,group)){
                    $.f.form.bind_select(form_id,$(item));
                    select_finish_group.push(group);
                }
            });
        },
        //通用页面数据绑定
        //form jq对象
        bind_update:function(form_id,data){
            var select_finish_group=[]; //下来列表完成分组
            var form=$('#'+form_id);
            //清空select
            form.find('select').each(function (index,item){
                var url=$(item).data('url')?$(item).data('url'):'';
                if(url!=''){
                    $(item).children().remove();
                }
            });
            for(var key in data){
               var obj=form.find('[name="'+key+'"]').eq(0);
               if(obj.length>0){
                    var type=obj.prop('type');
                    if(type=='text'||type=='password'||type=='hidden'||type=='textarea'){
                        obj.val(data[key]); 
                    }
                    var tag_type=obj.prop("tagName").toLowerCase();
                    // alert(tag_type);
                    if(tag_type=='select'){
                        form.find('select').each(function (index,item){
                            var group=obj.data('group')?obj.data('group'):'';
                            if(group==''){
                                $.f.form.bind_select(form_id,$(item),data);
                            }else if(!$.f.array.is_exist(select_finish_group,group)){
                                $.f.form.bind_select(form_id,$(item),data);
                                select_finish_group.push(group);
                            }
                        });
                    }else if(tag_type=='checkbox'||tag_type=='radio'){
                        // obj.prop('checked',(data[key]==1?true:false));
                    }else{
                        // var obj2s=obj.find('[name^="'+key+'["]');
                        // if(obj2s.length>0){
                        //     obj2s.each(function (index,item){
                        //         var type2=$(item).prop('type');
                        //         if(type2=='checkbox'||type2=='radio'){
                        //             $.each(data[key].split(','),function (index2,item2){
                        //                 var obj2s_part=obj2s.split('[')[1];
                        //                 if(item2==obj2s_part.substring(0,obj2s_part.length)){
                        //                     obj.prop('checked',(item2==1?true:false));
                        //                 }
                        //             });
                        //         }
                        //     });
                        // }else{
                        //     obj.val(data[key]);
                        // }
                    }
               }
            }
        },
        bind_select:function (form_id,obj,data){
            data=data||{};
            var form=$('#'+form_id);
            //下来列表默认配置
            var conf_select={
                    url:'',
                    group:''
                };
            conf_select.group=obj.data('group')?obj.data('group'):'';
            conf_select.url=obj.data('url')?obj.data('url'):'';

            if(conf_select.url!='')
            {
                var bind_conf=[];
                if(conf_select.group==''){
                    //绑定数据
                    bind_conf.push(set_conf(obj));
                    $.f.select.bind(bind_conf);
                }else {
                    form.find('select').each(function (index,item){
                        if($(item).data('group')==conf_select.group){
                            bind_conf.push(set_conf($(item)));
                        }
                    });
                    $.f.select.bind(bind_conf);
                }
            }
            //设置参数
            function set_conf(obj){
                var bind_conf={};
                var url=obj.data('url')?obj.data('url'):'';
                var group=obj.data('group')?obj.data('group'):'';
                var is_first= obj.data('is_first')==false?false:true;
                var d_option= obj.data('d_option')?JSON.parse(obj.data('d_option')):'';

                var select_id='';
                for(var key2 in data){
                    if(obj.prop('name')==key2){
                        select_id=data[key2];
                        break;
                    }
                }
                //绑定数据配置
                bind_conf={
                    obj:obj,
                    url:url,
                    select_id:select_id,
                    is_first:is_first
                };
                if(d_option!=''){
                    bind_conf[d_option]=d_option;
                }
                return bind_conf;
            }
        },
        //表单自动适应排版
        adapt:function (form_obj){
            var objs=form_obj.children();
            var html='';
            objs.each(function (index,item){
                if($(item).children().length<=0){
                    return;
                }
                var clt=$(item).children().eq(0).clone();
                $(item).children().eq(0).remove();

                if(clt.prop("tagName").toLowerCase()=='div'){
                    // clt.children().each(function (){
                    //     $(this).addClass('form-control');
                    // })
                }else{
                    clt.addClass('form-control');
                }

                $(item).addClass('form-group');
                $(item).html('<label class="col-md-'+(objs.length>7?3:2)+' control-label" >'+$(item).html()+'</label>'+
                                '<div class="col-md-'+(objs.length>7?9:10)+' dd-form-ctl" >'+
                                    clt.prop('outerHTML')+
                            '</div>');
                var form_g = $(item).prop('outerHTML') ;

                if(objs.length>5){
                    form_g='<div class="col-md-6">'+form_g+'</div>';
                    if( index%2==0 ){
                        html+='<div class="row">'+form_g;
                    }else{
                        html+=form_g+'</div>';
                    }
                }else{
                    html+=form_g;
                }
            });
            form_obj.html(html);
        },
        //自动判断表单尺寸
        size:function (form_obj){
            if(form_obj.children().eq(0).hasClass('row')){
                return 3;
            }else{
                return 2;
            }
        }
    })
})(jQuery);
//下来列表select快捷功能）
$.f.select={};
;(function ($){
    $.extend($.f.select,{
        //多级联动
        // main_id:指定范围（只要select在该访问内）
        // conf [{obj:'',url:'',select_id:''}]
        bind:function(params){
            //默认配置
            var conf=[];
            var d_conf={
                obj:null, //select的jq对象
                url:'', 
                select_id:'', //选中id值
                is_first:true, //是否使用首行
                d_option:{ id:'0',text:'==请选择=='}
            };
            //初始化
            function init(){
                $.each(params,function (index,item){
                    var select_conf=JSON.parse(JSON.stringify(d_conf));
                    $.extend(true,select_conf,params[index]);
                    conf.push(select_conf);
                });
            };
            init();
            var url=conf[0].url;

            //开始绑定数据
            bind(0);

            function bind(index){
                var select_conf=conf[index];
                $.get(url,{},function (data){
                    var html='';
                    //默认节点
                    if(conf[index].is_first){
                        html+='<option value="'+select_conf.d_option.id+'">'+select_conf.d_option.text+'</option>';
                    }
                    var id_key=get_key(data)['id_key'];
                    var name_key=get_key(data)['name_key'];
                    $.each(data,function (index,item){
                        html+='<option ';
                        if(select_conf.select_id && select_conf.select_id==item[id_key]){
                            html+=' selected="selected" ';
                        }
                        html+=' value="'+item[id_key]+'" >'+item[name_key];
                        html+='</option>';
                    });
                    select_conf.obj.html(html);
                    select_event(index,id_key);
                });
            }
            //下拉列表选择事件
            function select_event(index,id_key){
                if(conf.length<=(index+1)){
                    return;
                }
                var select_conf=conf[index];
                select_conf.obj.off();
                change();
                select_conf.obj.change(function (){
                    change();
                });
                function change(){
                    var select_conf2=conf[index+1];
                    conf[index].select_id='';
                    url = $.f.url.format_parms(select_conf2.url,id_key+'='+select_id_val(index));
                    bind(index+1);
                }
            }
            //获取数据中的id与name的键名
            function get_key(data){
                var id_key='';
                var name_key='';
                if(data.length>0){
                    for(var key in data[0]){
                        if(/id$/.test(key)){
                           id_key=key;
                        }else{
                           name_key=key;
                        }
                    }
                }
                return { 'id_key':id_key,'name_key':name_key };
            }
            //被选中id值
            function select_id_val(index){
                var select_id='';
                var select_conf=conf[index];
                if(select_conf.select_id ==''){
                    select_id=select_conf.obj.val();
                }else{
                    select_id=select_conf.select_id;
                }
                return select_id;
            }
        }
    })
})(jQuery);
//bootstrapValidator扩展
$.f.bv={};
;(function ($){
    $.extend($.f.bv,{
        // form_obj:Jquery对象， data:[{'name':'',msg:''}]
        server_result:function(form_obj,data){
            var d_conf={
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {}
            }
            //设置新规则
            var fields={};
            $.each(data,function (index,item){
                fields[item.name]={
                    validators:{
                        callback:{
                            message:item.msg,
                            callback:function (value, validator, $field){
                                return false;
                            }
                        }
                    }
                }
            });
            d_conf.fields=fields;
            //检查之前是否是否配置过
            if(form_obj.data('bootstrapValidator')){ //配置过
                $.f.bv.reset(form_obj);
            }
            form_obj.bootstrapValidator(d_conf);
            //开始验证
            form_obj.bootstrapValidator('validate');
        },
        //清空表单验证信息
        reset:function (form_obj){
             if(form_obj.data('bootstrapValidator')){
                form_obj.data('bootstrapValidator').destroy();
             }
        }
    })
})(jQuery);
//进度条插件
;(function ($){
    // $.extend($.f,{
    //     progress:function(){
            
    //     }
    // })
})(jQuery);
//上传下载插件
$.f.updown={};
;(function ($){
    $.extend($.f.updown,{
        //单图片上传
        up_img:function(params){
            var conf={
                container_obj:null, //容器jq对象
                file_conf:{}, //插件配置
                file_name:'', //file控件name值
                bind_img:{key:'',val:''} //绑定字段键名与值
            }
            var file_conf={
                language: 'zh', //设置语言
                uploadUrl: '', //上传的地址
                allowedFileExtensions : ['jpg','jpeg','png'],//接收的文件后缀
                maxFileSize:20,
                showUpload: false, //是否显示上传按钮
                showCaption: false,//是否显示标题
                showRemove :false, //显示移除按钮
                showClose:false,
                browseClass: "btn btn-primary", //按钮样式    
                previewFileIcon: "<i class='glyphicon glyphicon-king'></i>",
            }
            conf.file_conf=file_conf;
            $.extend(true,conf,params);
            
            container_obj=conf.container_obj;

            var file=container_obj.find('[name="'+conf.file_name+'"]').eq(0);
            var file_conf=JSON.parse(JSON.stringify(conf.file_conf));
            if(conf.bind_img.val!=''){
                file_conf['initialPreview']=['<img src="'+conf.bind_img.val+'" class="file-preview-image">'];
            }
            file.fileinput('destroy');
            file.fileinput(file_conf);
            container_obj.find('.kv-file-remove').css('display','none');

            file.on('filebatchselected',function (event, files){
                file.fileinput('upload');
                container_obj.find('.file-preview-frame').css('display','none');
            }).on('fileuploaded',function (event, data){
                if(data.response.status==1){
                    container_obj.find('[name="'+conf.bind_img.key+'"]').eq(0).val(data.response.data);
                }else{
                    layer.alert(data.response.msg,{ icon:2 });
                }
                container_obj.find('.kv-upload-progress').css('display','none');
                container_obj.find('.kv-file-remove').css('display','none');
                container_obj.find('.kv-upload-progress').css('display','none');

                var files=container_obj.find('.file-preview-frame');
                files.each(function (index,item){
                    if(files.length-1!=index && files.length-2!=index){
                        $(item).css('display','none');
                    }else{
                        $(item).css('display','block');
                    }
                })
            });
        }
    })
})(jQuery);