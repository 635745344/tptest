$.m.menu={};
(function ($){
    var m=$('#main_1511010745');
    var show_row_id=0; //打开的菜单行
    var t_data=null; //异步加载table获取的数据
    var menu_level=1; //需要修改或添加的菜单级数
    var open_add_id='';//打开二级新增的数据id
    var that=$.m.menu;

    $.extend($.m.menu,{
        dd : new window.Dd({
                table:{
                    cols:{ 'name':'菜单名称','url':'链接','status':'状态' },
                    cols_conf:{
                        btn:['edit','del','status'],
                        event:{ 
                            data_after1:data_after1,
                            data_after2:data_after2
                        }
                    },
                    page:{is_page:false}
                },
                form:{ add_id:'form_1511010745', edit_id:'form_1511010745',event:{
                    add_open:add_open,
                    add_before:add_before,
                    edit_open:edit_open,
                    edit_before:edit_before
                } },
                toolbar:{ oper:['add'] }
            })
    });

    function data_after1(data){
            var new_data=data;
            var d_table=data['data'];
            var d_table_new=[];
            //获取一级菜单
            var menu_one=$.Enumerable.From(d_table).Where('x=>x.parent_id==0').
                        OrderBy('x=>x.sort').ToArray();
            //二级菜单
            var menu_tow=$.Enumerable.From(d_table).Where('x=>x.parent_id!=0').
                        OrderBy('x=>x.sort').ToArray();
            $.each(menu_one,function (index,item){
                item['name']='<i class="'+item.icon+'"></i>&nbsp;'+item.name;
                d_table_new.push(item);
                $.each(menu_tow,function (index2,item2){
                    if(item2.parent_id==item.id){
                       item2['name']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+item2.name;
                       d_table_new.push(item2);
                    }
                });
            });
            t_data = new_data['data']=d_table_new;
            return new_data;
    }
    function data_after2(data){
            var data=data['data'];
            var t = m.find('table').eq(0);
            t.find('tr').each(function (index,item){
                if(index==0){
                    return;
                }
                var d_row=data[index-1];
                var td_name = $(item).find('td').eq(1);
                //菜单名称列 
                if(d_row.parent_id!=0 && d_row.parent_id!=show_row_id){
                    // $(item).css('display','none');
                }else{
                    td_name.css('cursor','pointer');
                    var menu_tow=$.Enumerable.From(data).Where('x=>x.parent_id=='+d_row.id).ToArray();

                    td_name.click(function (){
                        t.find('tr').each(function (index2,item2){
                            var menu2_id = $(item2).find('input[type="hidden"]').eq(0).val();
                            if(!menu2_id){
                                return;
                            }
                            if($.Enumerable.From(menu_tow).Where('x=>x.id=='+menu2_id).ToArray().length>0){
                                if($(item2).css('display')=='none'){
                                    $(item2).css('display','table-row');
                                }else{
                                    $(item2).css('display','none');
                                }
                            }
                        })
                    });
                }
                //操作列
                var td_oper=$(item).find('td').eq($(item).find('td').length-1);
                if(d_row.parent_id==0){
                    var btn_add=$('<a>新增</a>');
                    btn_add.click(function (){
                        open_add_id=$.m.menu.dd.table.get_current_id(this);
                        that.dd.form.add_view();
                        open_add_id='';
                    });
                    td_oper.prepend(btn_add);
                }
            })
            function show(){

            }
    }

    function add_open(){
        init_form(open_add_id,'add');
    }
    function edit_open(data){
        init_form(data.id,'edit');
    }
    function add_before(data){
        return submit_before(data,'add');
    }
    function edit_before(data){
        return submit_before(data,'edit');
    }
        var form_add=$('#form_1511010745_add');
        var form_edit=$('#form_1511010745_edit');

        var add_sel_one=form_add.find('[name="parent_id"]').eq(0);
        var add_sel_two=form_add.find('[name="parent_id2"]').eq(0);
        var edit_sel_one=form_edit.find('[name="parent_id"]').eq(0);
        var edit_sel_two=form_edit.find('[name="parent_id2"]').eq(0);

    //打开表单时初始化 type：'add','edit'
    function init_form(id,type){
        var level=1; //菜单栏等级
        if(type=='add'){
            if(id!=''){
                level=2;
            }
            var row_data = $.Enumerable.From(t_data).Where('x=>x.parent_id==0').Select().LastOrDefault('');
            if(level==1){
                form_add.find('#div_url').hide();
                form_add.find('#div_icon').show();
                form_add.find('[name="parent_id2"]').hide();
                if(row_data!=''){
                    selected(add_sel_one,row_data.id);
                }
                show_row_id=0;
            }else if(level==2){
                form_add.find('#div_url').show();
                form_add.find('#div_icon').hide();
                form_add.find('[name="parent_id2"]').show();

                if(row_data!=''){
                    selected(add_sel_one,id);
                }
                show_row_id=row_data.parent_id;
            }
        }else if(type=='edit'){
            var row_data = $.Enumerable.From(t_data).Where('x=>x.id=='+id).Select().FirstOrDefault('');
            if(row_data!='' && row_data.parent_id!=0){
                level=2;
            }
            if(level==1){
                form_edit.find('#div_url').hide();
                form_edit.find('#div_icon').show();
                form_edit.find('[name="parent_id2"]').hide();
                show_row_id=row_data.id;
            }else if(level==2){
                form_edit.find('#div_url').show();
                form_edit.find('#div_icon').hide();
                form_edit.find('[name="parent_id2"]').show();
                var parent_id = $.Enumerable.From(t_data).Where('x=>x.id=='+id).Select().LastOrDefault('').parent_id;
                var group_data = $.Enumerable.From(t_data).Where('x=>x.parent_id=='+parent_id).Select().ToArray();
                var select_id=0;
                $.each(group_data,function (index,item){
                    if(item.id==id){
                        return false;
                    }else{
                        select_id=item.id;
                    }
                });

                selected(edit_sel_two,select_id);
                show_row_id=row_data.parent_id;
            }
        }
        menu_level=level;
    }
    //提交数据前 type：'add','edit' data：提交前数据
    function submit_before(data,type){
        var is_after = data.sort==2?true:false;
        var sort=1;
        var before_row_data=$.Enumerable.From(t_data).Where('x=>x.id=='+data.id).Select().FirstOrDefault('');

        if(menu_level==1){
            var menu_one_list=$.Enumerable.From(t_data).Where('x=>x.parent_id==0').Select().ToArray();
            var before_sort=0 ;
            if(type=='add'){
                before_sort=0;
            }else if(type='edit'){
                before_sort = before_row_data['sort'];
            }
            $.each(menu_one_list,function (index,item){
                if(item.id==data.parent_id){
                    var now_num=index+1;
                    if(before_sort==0){
                        now_num=index+2;
                    }
                    sort=get_sort(now_num,before_sort);
                    return false;
                }
            })
            data.parent_id=0;
        }else{
            var menu_two_list=$.Enumerable.From(t_data).Where('x=>x.parent_id=='+data.parent_id).Select().ToArray();
            if(type=='add'){
                before_sort=0;
            }else if(type='edit'){
                before_sort = before_row_data['sort'];
            }
            $.each(menu_two_list,function (index,item){
                if(item.id==data.parent_id2){
                    sort=get_sort(index+1,before_sort);
                    return false;
                }
            })
        }
        data.sort=sort;
        return data;
        
        function get_sort(sort,before_sort){
            var result=0;
            if(is_after){
                if(sort>=before_sort){
                    result=sort;
                }else{ 
                    result= sort+1;
                }
            }else{
                if(sort>before_sort){
                    result= sort-1;
                }else{
                    result=sort;
                }
            }
            return result;
        }

    }
    //打开表单绑定
    function selected(select,id){
        setTimeout(function (){
            var is_exit=false;
            try{
                var options=select.find('option');
                $.each(options,function (index,item){
                    if($(item).val()==id){
                        $(item).prop('selected',true).change();
                        select_last();
                        is_exit=true;
                        return false;
                    }
                });
            }catch(e){}
            if(!is_exit){
                selected(select,id);
            }
        },200)
    }
    //选中最后一个option
    function select_last(){
        setTimeout(function (){
            var is_exit=false;
            try{
                var options=add_sel_two.find('option');
                if(options.length>1){
                    options.eq(options.length-1).prop('selected',true);
                    is_exit=true;
                }
            }catch(e){}
            if(!is_exit){
                select_last();
            }
        },200)
    }
})(jQuery)