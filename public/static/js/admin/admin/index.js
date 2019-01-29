window.content_url_1511187455='';
$.m={};//页面js文件库
$.m.admin=[];
//框架中其他功能
$(function (){
    var edit_pwd_modal_id; //修改密码弹窗id
    $.extend($.m.admin,{
        //获取菜单列表
        get_menu:function (){
          var menu='';
          // $.get('/admin/menu/get_menu',{},function (data){
            var data=$.menu_data;
                menu=[]; //处理后数据
                //对菜单数据进行排序
                var result=[];
                //获取一级菜单
                var menu_one=$.Enumerable.From(data).Where('x=>x.parent_id==0').
                            OrderBy('x=>x.sort').ToArray();
                //二级菜单
                var menu_tow=$.Enumerable.From(data).Where('x=>x.parent_id!=0').
                            OrderBy('x=>x.sort').ToArray();
                $.each(menu_one,function (index,item){
                  item['content']=[];
                  $.each(menu_tow,function (index2,item2){
                    if(item2.parent_id==item.id){
                      item['content'].push(item2);
                    }
                  });
                  menu.push(item);
                });

            var html='';
            $.each(menu,function (index,item){
              html+='<li class="treeview">'+
                    '<a href="#"><i class="'+item.icon+'"></i> <span>'+item.name+'</span>'+
                      '<span class="pull-right-container">'+
                        '<i class="fa fa-angle-left pull-right"></i>'+
                      '</span>'+
                    '</a>'+
                    '<ul class="treeview-menu" >';
              $.each(item.content,function (index2,item2){
                html+='<li><i class="'+item2.icon+' pull-right"></i><a name="menu_url" href="#'+item2.url+'" class="ajax" data-conf="'+escape(JSON.stringify(item2))+'" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+item2.name+'</a></li>';
              });
              html+=  '</ul>'+
                  '</li>';
            });
            $('#menu').append(html);
            //菜单栏点击事件
            $('[name="menu_url"]').click(function (){
                $.m.admin.menu_click($(this));
            });
          // },'json');
        },
        menu_click:function (obj,is_load){

            // is_load= is_load||true;

            if(typeof(is_load) == "undefined"){
                is_load=true;
            }

            var data=JSON.parse(unescape(obj.data('conf')));
            window.content_url_1511187455=data.url;
            if(is_load){
                $('#content_1510826929').load('/'+data.url);
            }
            $('[name="menu_url"]').css('color','#8aa4af');
            obj.css('color','#fff');
        },
        //退出登录
        login_out:function (){
          $.get('/admin/login/login_out',{},function (data){
              if(data.status==1){
                window.location.href=data.data;
              }else{
                layer.alert(data.info,{icon:2});
              }
          },'json');
        },
        //更改密码
        edit_pwd:function (){
            $.f.modal.open(edit_pwd_modal_id);
        },
        //最新消息
        new:function (){
          var html='';
          $.get('/admin/adminLog/new_log',{},function (data){
            html+='<li>'+
                  '<a href="#">'+
                    '<i class="fa fa-users text-aqua"></i> 5分钟前登陆'+
                  '</a>'+
                '</li>';
          },'json');
          $('#new').html(html);
        },
        //加载公众号列表
        load_wx:function(){

        },
        //加载首页
        load_home:function(){
            $('#content_1510826929').load('/admin/admin/index3');
        },
        //选择公众号
        select_wx:function (){

        }
    });
    $(".main-header").css("backgroundColor","#fff");
    //公众号
    $.extend($.m.admin,{
        //加载公众号列表
        load_wx:function(){
            
        },
        //选择公众号
        select_wx:function (){

        }
    });

    (function (){
       edit_pwd_modal_id=$.f.modal.create_form({obj:$('#form_edit_pwd_1510826929'),title:'修改密码',btn:[
              {
                text:'保存',
                click:function(){
                    var form=$('#form_edit_pwd_1510826929');
                    var password1_val=$.trim(form.find('[name="password1"]').eq(0).val());
                    var password1_va2=$.trim(form.find('[name="password2"]').eq(0).val());
                    if(password1_val!=password1_va2){
                        layer.alert('密码不一致，请重新输入！');
                        return;
                    }
                    if(password1_val==''){
                        layer.alert('密码不能为空！');
                        return;
                    }
                    if(password1_val.length<6){
                        layer.alert('密码需大于或等于6任意字符！');
                        return;
                    }
                    $.get('/admin/admin/edit_pwd',{ password:password1_val },function (data){
                      if(data.status==1){
                          $.f.modal.colse(edit_pwd_modal_id);
                          layer.alert(data.info,{icon:1});
                      }else{
                          layer.alert(data.info,{icon:2});
                      }
                    },'json');
                }
              }
            ]});
        var ss = window.location.href.split("#");
        if(ss[1]){
            window.content_url_1511187455=ss[1];
            $('#content_1510826929').load('/'+ss[1]);
        }else{
            $('#content_1510826929').load('/admin/admin/index3');
        }
    })();
    $.m.admin.get_menu();
})