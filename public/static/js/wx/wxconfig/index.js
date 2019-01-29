$.m.wxConfig={}
$(function (){
	$.extend($.m.wxConfig,{
        dd:new Dd({
            table:{
                cols:{ 'name':'公众号名称','appid':'应用id','create_time':'创建时间' },
                cols_conf:{
                    btn:['edit','del','status'],
                    chk:true
               }
            },
            form:{ add_id:'form_1511010745', edit_id:'form_1511010745'},
            toolbar:{ oper:['add'] }
        })
	})
});
