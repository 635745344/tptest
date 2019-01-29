$.m.admin_index2={};
$(function (){
    $.extend($.m.admin_index2,{
        dd:new Dd({
            table:{
                cols:{ 'account':'账号','role_name':'角色','status':'状态','last_login_time':'最后登录时间','create_time':'创建时间' },
                cols_conf:{
                    btn:['edit','del','status'],
                    other_btn:[{
                        'text':'重置密码',
                        'click':reset_pwd
                    }],
                    chk:true
               }
            },
            form:{ add_id:'form_1511010745', edit_id:'form_1511010745' },
            search:{ title:'请输入账号名' },
            toolbar:{ oper:['add','del','status','import','export'] }
        })
    })
    //重置密码
    function reset_pwd(id){
        layer.confirm('确定重置密码，默认密码:123456？',{icon:3},function (index){
            $.get('/admin/admin/reset_pwd',{ id:id, r:Math.random() },function (data){
                if(data.status==1){
                    layer.alert(data.info,{ icon:1 });
                }else{
                    layer.alert(data.info,{ icon:2 });
                }
            });
            layer.close(index);
        })
    }
});