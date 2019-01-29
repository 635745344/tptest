$.m.field={};
(function ($){
    var m=$('#main_1511010745');
    $.extend($.m.field,{
        dd:new Dd({
            table:{
                cols:{ 'module':'模块','table_name':'表名','field_name':'数据库字段名','name':'名称','status':'状态','remark':'备注' },
                cols_conf:{
                    btn:['edit','del','status'],
                    chk:true
                }
            },
            form:{ add_id:'form_1511010745', edit_id:'form_1511010745'},
            search:{ title:'请输入表名或模块名' },
            toolbar:{ oper:['add'] }
        })
    })
    //绑定数据
    $.get('/admin/field/getModuleList',{ },function (data){
        var html='';
        $.each(data,function (index,item){
            html+='<option value="'+item.module+'">'+item.module+'</option>';
        })
        m.find('#module1').append(html);
    },'json')
    // <option value="0">==模块==</option>
})(jQuery);