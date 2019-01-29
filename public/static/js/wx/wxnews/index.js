$.m.keys={}
$(function (){
	$.extend($.m.keys,{
        dd:new Dd({
            table:{
                cols:{ 'account':'账号','role_name':'角色','status':'状态','last_login_time':'最后登录时间','create_time':'创建时间' },
                cols_conf:{
                    btn:['edit','del','status'],
                    chk:true
               }
            },
            search:{ title:'请输入关键字' },
            toolbar:{ oper:['add','del','status'] },
            params:{id:'keys_1511010745'}
        })
	})
	
});