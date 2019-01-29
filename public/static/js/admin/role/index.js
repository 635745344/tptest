$.m.role={};
(function ($){
    $.extend($.m.role,{
    	dd:new Dd({
            table:{
                cols:{ 'name':'名称','status':'状态','remark':'备注' },
                cols_conf:{
                    btn:['edit','del','status'],
                    other_btn:[{
                        'text':'配置权限',
                        'click':open_power
                    }],
                    chk:true
                }
            },
            form:{ add_id:'form_1511010745', edit_id:'form_1511010745' },
            toolbar:{ oper:['add'] }
        })
    });
    //创建权限编辑弹窗并获取id
    var edit_power_modal_id = $.f.modal.create_form(
                        {'obj':$('#form_power_1511010745'),
                        'title':'配置权限',
                        'btn':[ {
                            'text':'保存',
                            'click':function (){
                                set_power();
                            }
                        } ]
                    });
    //全部权限列表
    var all_power=[];
    var tree_obj=$('#form_power_1511010745').find('#tree').eq(0);

    //打开权限设置窗口
    function open_power(id){
        var form=$('#form_power_1511010745').eq(0);
        if(all_power.length<=0){
            //获取全部权限列表
            $.get('/admin/role/get_all_power',{r:Math.random()},function (data){
                //添加主键
                if(form.find('[name="id"]').length<=0){
                    form.prepend('<input type="hidden" name="id" value='+id+'>');
                }
                all_power=data;
                set_tree(id);
            },'json');
        }else{
            set_tree(id);
        }
        $.f.modal.open(edit_power_modal_id,'编辑');
    }
    //将后台数据转换tree需要格式数据
    function set_tree(role_id){
        var form=$('#form_power_1511010745').eq(0);
        $.get('/admin/role/get_power',{'id':role_id},function (data){
            tree_obj.treeview({
                data:convert_tree(data.power_ids),
                checkedIcon:'fa fa-check-square-o',
                uncheckedIcon:'fa fa-square-o',
                onNodeChecked:nodeChecked,
                onNodeUnchecked:nodeUnchecked,
                onNodeExpanded:nodeExpanded,
                showCheckbox:true,
                multiSelect:true
            })
        },'json');
    }
    //转换成tree需要的格式数据
    function convert_tree(power_ids){
        var power_ids=power_ids.split(',');
        var new_data=[];
        //获取一级菜单
        var menu_one=$.Enumerable.From(all_power).Where('x=>x.parent_id==0').OrderBy('x=>x.sort').ToArray();
        //二级菜单
        var menu_tow=$.Enumerable.From(all_power).Where('x=>x.parent_id!=0 && x.is_menu==1').OrderBy('x=>x.sort').ToArray();
        //其他权限操作
        var other_power=$.Enumerable.From(all_power).Where('x=>x.is_menu==0').ToArray();

        $.each(menu_one,function (index,item){
            new_data.push({
                'id':item.power_id,
                'text':item.name,
                'selectable':false,
                'state':{
                    'checked':$.f.array.is_exist(power_ids,item.power_id),
                    'expanded':false
                },
                'nodes':[]
            });
            $.each(menu_tow,function (index2,item2){
                if(item2.parent_id==item.menu_id){
                    new_data[index].nodes.push({
                        'id':item2.power_id,
                        'text':item2.name,
                        'selectable':false,
                        'state':{
                            'checked':$.f.array.is_exist(power_ids,item2.power_id),
                            'expanded':false
                        },
                        'nodes':[]
                    });
                    $.each(other_power,function (index3,item3){
                        if(item3.menu_id==item2.menu_id){
                            new_data[index].nodes[new_data[index].nodes.length-1].nodes.push({
                                'id':item3.power_id,
                                'text':item3.name,
                                'selectable':false,
                                'state':{
                                    'checked':$.f.array.is_exist(power_ids,item3.power_id),
                                    'expanded':false
                                }
                            });
                        }
                    });
                }
            });
        });
        return new_data;
    }
    //权限设置
    function set_power(){
        var form=$('#form_power_1511010745').eq(0);
        var power_ids=[];

        $.each(tree_obj.treeview('getChecked'),function (index,item){
            deal_power(item,power_ids);
        });
        power_ids=$.f.array.distinct(power_ids).sort().join(',');

        $.post('/admin/role/set_power?r='+Math.random(),{ id:form.find('[name="id"]').eq(0).val(), power_ids:power_ids },function (data){
            if(data.status==1){
                $.f.modal.colse(edit_power_modal_id);
                layer.alert(data.info,{ icon:1 });
            }else{
                layer.alert(data.info,{ icon:2 });
            }
        },'json');
    }
    //获取chenked的id
    function deal_power(node,power_ids){
        if(node.state.checked==true){
            power_ids.push(node.id);
            if(node.nodes!=undefined && node.nodes.length>0 ){
                $.each(node.nodes,function (index,item){
                    deal_power(item,power_ids);
                })
            }
        }
    }
    var nodeCheckedSilent = false;
    //选中节点事件
    function nodeChecked (event, node){  
        if(nodeCheckedSilent){  
            return;
        }
        nodeCheckedSilent = true;  
        checkAllParent(node);  
        checkAllSon(node);  
        nodeCheckedSilent = false;  
    }
    var nodeUncheckedSilent = false;
    //取消节点事件  
    function nodeUnchecked  (event, node){  
        if(nodeUncheckedSilent)  
            return;  
        nodeUncheckedSilent = true;  
        uncheckAllParent(node);  
        uncheckAllSon(node);  
        nodeUncheckedSilent = false;  
    }
    //选中全部父节点  
    function checkAllParent(node){  
        tree_obj.treeview('checkNode',node.nodeId,{silent:true});  
        var parentNode = tree_obj.treeview('getParent',node.nodeId);  
        if(!("nodeId" in parentNode)){  
            return;  
        }else{  
            checkAllParent(parentNode);  
        }  
    }  
    //取消全部父节点  
    function uncheckAllParent(node){  
        tree_obj.treeview('uncheckNode',node.nodeId,{silent:true}); 
        var siblings = tree_obj.treeview('getSiblings', node.nodeId);  
        var parentNode = tree_obj.treeview('getParent',node.nodeId);  
        if(!("nodeId" in parentNode)) {  
            return;  
        }  
        var isAllUnchecked = true;  //是否全部没选中  
        for(var i in siblings){  
            if(siblings[i].state.checked){  
                isAllUnchecked=false;  
                break;  
            }  
        }  
        if(isAllUnchecked){  
            uncheckAllParent(parentNode);
        }  
    }
    //级联选中所有子节点  
    function checkAllSon(node){
        tree_obj.treeview('checkNode',node.nodeId,{silent:true});
        if(node.nodes!=null&&node.nodes.length>0){  
            for(var i in node.nodes){
                checkAllSon(node.nodes[i]);
            }
        }
    }  
    //级联取消所有子节点  
    function uncheckAllSon(node){  
        tree_obj.treeview('uncheckNode',node.nodeId,{silent:true});  
        if(node.nodes!=null&&node.nodes.length>0){  
            for(var i in node.nodes){  
                uncheckAllSon(node.nodes[i]);  
            }  
        }  
    }

    //展开节点时事件
    function nodeExpanded(event,node){
        $.each(tree_obj.treeview('getChecked'),function (index,item){
            if(item.nodes!=undefined && item.nodes.length>0){
                $.each(item.nodes,function (index2,item2){
                    if(item2.nodes!=undefined && item2.nodes.length>0){
                        $.each(item2.nodes,function (index3,item3){
                            
                        });
                    }
                });
            }
        });
    }
})(jQuery)