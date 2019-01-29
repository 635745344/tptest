;(function ($) {
    //插件全称data_deal
    window.Dd = function(config) {
    	//默认配置
	    conf = {
	    	table:{
	    		cols:{}, //表字段（一.主键默认返回数据第一列，以id结尾，可不填主键，二.当需要主键需要显示时，在填写第一字段并且有title，如：'我是ID的字段名':'我是ID的title'）
	    		cols_conf:{	//列其它操作
	    			id:'',	//指定用户主键（返回主键没有id结尾，自行定义主键）
	    			chk:false, //是否开启checkbox类
	    			btn:[], //已封装好按钮 edit.编辑 del.删除 status.启用或禁用  启动后表格会自动添加到table最后一行 //details.详情 
	    			other_btn:[], //自定义按钮 配置参考btn_conf
	    			event:{
	    				data_after1:null,//获取数据前加载前
	    				data_after2:null//获取数据后前加载后
	    			}
	    		},
	    		page:{
	    			is_page:true, //是否开启分页
	    			limit:10, //没有填时默认根据表格最大height计算limit，计算失败时填默认值
	    			page:1	//默认获取的页码
	    		}
	    	},
	    	search:{
	    		form_id:'search_form',   //快捷搜索表单
	    		more_id:'more_form',	//更多搜索表单id
	    		enable:true, //是否开启（是否显示整个search模块）
	    		title:'' //搜索框内的提示文字（没有文字默认不显示搜索框）
	    	},
	    	toolbar:{
	    		oper:[]//启动操作类型 add.添加 del.删除 status.禁用或启用 import.导入 export.导出 
	    	},
	    	form:{
	    		add_id:'',//添加表单id
	    		edit_id:'',//编辑表单id
	    		event:{
	    			add_open:null, //打开添加表单后
	    			add_before:null,//发送数据前
	    			edit_open:null, //打开编辑表单后
	    			edit_before:null, //发送数据前
	    		}
	    	},
	    	params:{
	    		id:'main_1511010745', //默认所有工具被被该div包揽
	    		url:'' //访问url
	    		// module:[] //需要初始模块,当有配置指定模块时，自动加载失效
	    	}
	    };

        var btn_conf={
        	id:'', //按钮id，一般不用配置
            text:'', //按钮文本值
            click:null //点击事件 function(id){} //传入id
        }
	    //id,name等属性后缀
	    p=((new Date().getTime()).toString() + Math.random()).replace('.','');
		that=this;

        //合并配置
	    $.extend(true,conf,config); //合并后配置
	    $.each(conf.table.cols_conf.other_btn,function (index,item){
	        conf.table.cols_conf.other_btn[index] = $.extend(JSON.parse(JSON.stringify(btn_conf)),item);
	    })
	    if(conf.params.url==''){ conf.params.url=$.f.get_url(); }

		//指定访问对象
        main=$('#'+conf.params.id);
		//对象
		dd_table=main.find('.dd-table').eq(0);
		dd_toolbar=main.find('.dd-toolbar').eq(0);
		dd_search=main.find('.dd-search').eq(0);
		dd_more=main.find('.dd-more').eq(0);
		dd_quick=main.find('.dd-quick').eq(0);

		//加载
        init=function (config) {
            f= new f();
        	//自动填充table row数
            conf.table.page.limit=f.max_rows_count();
            
			//加载模块
			for(var obj in conf){
				try{ eval('that.'+obj+'=new '+obj+'();'); }catch(e){}
		 	}
        	//获取需要启动模块
        	var modules=[];
        	for(var module in config){
        		modules.push(module);
			}
			modules=$.Enumerable.From(modules).Distinct().Where('x=>x!="params"').ToArray();
		 	//启动
			for(var module in modules){
				try{ eval('that.'+modules[module]+'.init();'); }catch(e){}
		 	}
        }
		//模块Start
	    //表格模块
	    table = function (){
	    	//初始化
	    	this.init = function(){
	    		this.load();
	    	}
			//加载表格
		    this.load = function(){
			    var c=conf.table;
			    var chk=c.cols_conf.chk;
			    //添加表结构
			    if(dd_table && dd_table.find('table').length<=0){
					var html='<div class="col-md-12" style="padding:0"><table class="table" style="margin-bottom:0px"><tbody><tr>';
					//表头
					if(chk){
						html+='<th><input id="chk_header_'+p+'" type="checkbox"></th>';
					}
					for(var val in c.cols){
						html+='<th>'+c.cols[val]+'</th>';
					}
					if(c.cols_conf.btn.length>0){
						html+='<th>操作</th>';
					}
					html+='</tr>';
					html+='</table></div>';
					dd_table.append(html);
					// alert($('#'+conf.params.id).find('.dd-table').eq(0).html());
			    }
				var html='';
				//数据内容 （返回的数据id必须为最前面）
                $.post(conf.params.url+'lists?page='+c.page.page+'&limit='+c.page.limit+'&r='+Math.random(),that.search.get_form_data(),function (data){
                    addData(data);
                },'json');
				//将数据填充到table
				function  addData(data) {

                    if(data.status==0){
                        layer.alert(data.info,{ icon:2 });
                        return;
                    }
                    if(c.cols_conf.event.data_after1){
                        data = c.cols_conf.event.data_after1(data);
                    }
                    var id_name=''; //主键名称
                    if(data.data){
                        for(var obj in data.data[0]) {
                            if(/id/.test(obj) ){
                                id_name=obj;
                                break;
                            }
                        }
                    }
                    $.each(data.data,function (index,item){
                        html+='<tr>';
                        html+='<td style="'+(chk?'':'display:none')+'"><input name="chk_'+p+'" type="checkbox"><input type="hidden" value="'+item[id_name]+'" /></td>';
                        for(var obj2 in c.cols){
                            for (var key in item) {
                                if(key==obj2){
                                    //转换状态
                                    if(key=='status'){
                                        var val=item[key];
                                        if(/^[0-9]*$/.test(item[key])){
                                            // item[key] = (item[key]==1?'启用':'禁用');
                                            if(item[key]==1){
                                                item[key] = '<i data-table-switch data-status="1" class="fa fa-toggle-on" style="color:green; font-size:22px;cursor:pointer "></i>';
                                            }else{
                                                item[key] = '<i data-table-switch data-status="0" class="fa fa-toggle-off " style="font-size:22px;cursor:pointer " ></i>';
                                            }
                                        }
                                    }
                                    //转换时间
                                    try{
                                        if(item[key]==0){
                                            item[key]='';
                                        }else{
                                            if(/_time$/.test(key) && /^[0-9]*$/.test(item[key]) && item[key].toString().length==10){
                                                item[key] = $.f.date.format_date(item[key]*1000);
                                            }
                                        }
                                    }catch(e){}
                                    html+='<td>'+item[key]+'</td>';
                                    break;
                                }
                            }

                        }
                        var btn=c.cols_conf.btn;
                        //操作
                        if(btn.length>0 || c.cols_conf.other_btn.length>0){
                            html+='<td></td>';
                        }
                        html+='</tr>';
                    });
                    dd_table.find('table').eq(0).find('tr').each(function (index,item){
                        if(index!=0){
                            $(item).remove();
                        }
                    });
                    dd_table.find('table').eq(0).append(html);

                    if(c.cols_conf.btn.length>0 || c.cols_conf.other_btn>0){
                        //添加按钮
                        $.each(data.data,function (index,item){
                            var oper_td=main.find('table tr').eq(index+1).find('td').last(); //操作行的td对象
                            // //自定义按钮
                            $.each(c.cols_conf.other_btn,function (index2,item2){
                                var btn_obj = $('<a '+(item2.id!=''?'id="'+item2.id+'"':'')+'>'+item2.text+'</a>');
                                btn_obj.click(function (){
                                    item2.click(that.table.get_current_id(this));
                                })
                                btn_obj.appendTo(oper_td);
                            });
                            //系统封装好按钮
                            $.each(c.cols_conf.btn,function (index2,item2){
                                var html='';
                                var click;
                                if(item2=='detail'){ //详情
                                    html+='<a>查看</a>';
                                    click=function (obj){
                                        that.form.detail_view(obj);
                                    }
                                }else if(item2=='edit'){ //编辑
                                    html+='<a>编辑</a>';
                                    click=function (obj){
                                        that.form.edit_view(obj);
                                    }
                                }else if(item2=='del'){ //删除
                                    html+='<a>删除</a>';
                                    click=function (obj){
                                        that.table.del(that.table.get_current_id(obj));
                                    }
                                }
                                // else if(item2=='status'){ //禁用或启用
                                // 	var title='';
                                // 	for(var obj2 in c.cols){
                                // 		for (var obj in item) {
                                // 			if(obj==obj2){
                                // 				//转换状态
                                // 				if(obj=='status'){
                                // 					var val=item[obj];
                                // 					// if(/^[0-9]*$/.test(item[obj]))
                                // 					title = (item[obj]=='启用'?'禁用':'启用');
                                // 				}
                                // 				break;
                                // 			}
                                // 		}
                                // 	}
                                // 	html+='<a>'+title+'</a>';
                                // 	click=function (obj){
                                // 		var status=($(obj).html()=='启用'?1:0);
                                // 		that.table.status(that.table.get_current_id(obj),status);
                                // 	}
                                // }
                                var btn_obj=$(html);
                                btn_obj.click(function (){
                                    click(this);
                                });
                                oper_td.append(btn_obj);
                            });
                        });
                    }
                    //全选与取消
                    if(chk){
                        main.find('#chk_header_'+p).click(function (){
                            main.find('[name="chk_'+p+'"]').prop('checked',$(this).prop('checked'));
                        });
                    }
                    //开启分页
                    if(c.page.is_page){
                        // if(data.count){
                        that.table.page(data.count);
                        // }
                    }
                    if(c.cols_conf.event.data_after2){
                        c.cols_conf.event.data_after2(data);
                    }
                    //注册事件
                    $('#'+conf.params.id).off( "click", "[data-table-switch]" )
                    $('#'+conf.params.id).on('click', '[data-table-switch]', function () {
                        var obj=this;
                        // alert($(obj).data('status'));
                        var status=($(obj).data('status')==1?0:1);
                        that.table.status(that.table.get_current_id(obj),status);
                    });
                    table_event();

                }
		    }
		    //鼠标在table上的样式变化
		    function table_event(){
		    	dd_table.find('tr').each(function (index,item){
		    		$(item).on('mouseout',function (){
		    			$(item).css('background-color','#FFFFFF');
		    		}).on('mouseover',function (){
		    			$(item).css('background-color','#F2F2F2');
		    		});
		    	})
		    }

            //重新加载表格(回到第一页)
			this.reload=function (){
			    conf.table.page.page=1;
				that.table.load();
			}
		    //获取选中行id 以英文逗号隔开
		    this.get_select_rows=function (){
				var ids='';
				main.find('[name="chk_'+p+'"]').each(function (index,item){
					if($(item).prop('checked')){
						ids += $(item).next().val()+',';
					}
				});
				if(ids!=''){
					ids=ids.substring(0,ids.length-1);
				}
				return ids;
			}
		    //分页
		    this.page=function (count){
		    	var c=conf.table;
		    	var page_count = parseInt(Math.ceil(count/c.page.limit)); //分页数
		    	var page_id='page_1511766309';

		    	var html = '<div id="'+page_id+'" class="col-md-12 dd-page" ><div class="col-sm-5"><div style="padding-top:30px" role="status" aria-live="polite">共'+count+'条记录 当前'+c.page.page+'/'+page_count+'页 </div></div>';
				html += '<div class="col-sm-7" style="text-align:right">'+
							'<ul class="pagination">';
			    html +=	 	'<li '+(c.page.page==1?'class="disabled"':'')+'>'+
						 		'<a href="#" data-type="previous" aria-label="Previous">'+
			    		 			'<span aria-hidden="true" >上一页</span>'+
			    		 		'</a>'+
			    		 	'</li>';
		    	if(page_count>1){
		    		var btn_num = 7; //限定分页按钮数
		    		if(page_count>btn_num){
		    			if(c.page.page < Math.floor(btn_num/2) || c.page.page > (page_count - Math.floor(btn_num/2)) ){
				    		for(var i=1;i<=btn_num;i++ ){
				    			var num;
				    			if(i==1 || i==2|| i==3){
				    				num=i;
				    			}
				    			if(i==4 && page_count>btn_num){
				    				html+='<li><span>···</span></li>';
				    				continue;
				    			}else{
				    				num=i;
				    			}
				    			if(i>=5){
				    				num= parseInt(page_count) - parseInt(parseInt(btn_num)-i);
				    			}
				    			html+='<li><a href="#" data-type="num" >'+num+'</a></li>';
				    		}
		    			}else{
		    				html+='<li><a href="#" data-type="num" >1</a></li>';
		    				if(parseInt(c.page.page)!=Math.floor(btn_num/2)){
		    					html+='<li><span>···</span></li>';
		    				}
		    				for(var i=1;i<=3;i++){
		    					var num=  parseInt(c.page.page);
		    					if(i==1){
									num -=1;
		    					}else if(i==2){
									num = num;
		    					}else if(i==3){
		    						num += 1;
		    					}
		    					html+='<li><a href="#" data-type="num" >'+num+'</a></li>';
		    				}
		    				if(parseInt(c.page.page)!= (page_count - Math.floor(btn_num/2) + 1 ) ){
		    					html+='<li><span>···</span></li>';
		    				}
		    				html+='<li><a href="#" data-type="num" >'+page_count+'</a></li>';
		    			}
		    		}else{
						for(var i=1;i<=page_count;i++ ){
				    		var num=i;
				    		html+='<li><a href="#" data-type="num" >'+num+'</a></li>';
				    	}
		    		}
		    	}

		    	html += 	'<li '+((c.page.page==page_count || page_count==0 )?'class="disabled"':'')+'>'+
			    			'<a href="#" data-type="next" aria-label="Next">'+
			    				'<span aria-hidden="true" >下一页</span>'+
			    			'</a>'+
			    			'</li>'+
			    		'</ul>'+
			    		'</div></div>';
			    if(main.find('#'+page_id).length!=0){
			    	main.find('#'+page_id).remove();
			    }
			    dd_table.find('table').parent().parent().append(html);
			    if(page_count<=1){
					$('#'+page_id).css('display','none');
				}
			    main.find('#'+page_id).find('a').each(function (index,item){
			    	var num=parseInt($(item).html());
			    	if(num==parseInt(c.page.page)){
						$(item).parent().addClass('active');
			    	}else{
			    		$(item).parent().removeClass('active');
			    	}
			    });
			    	main.find('#'+page_id).find('a').click(function (){
			    		var type = $(this).data('type');
			    		if(type){
			    			var num=parseInt(c.page.page);
			    			if(type=='previous'){
			    				if(parseInt(c.page.page)>1){
			    					num = parseInt(c.page.page) - 1;
			    				}
			    			}else if(type=='next'){
			    				if(parseInt(c.page.page)< page_count){
			    					num = parseInt(c.page.page) + 1;
			    				}
			    			}else if(type=='num'){
			    				num = parseInt(c.page.page) - 1;
			    				num = $(this).html();
			    			}
			    			c.page.page = num ;
			    		}
			    		if(/^[0-9]*$/.test(num) && $(this).parent().prop('class')!='disabled'){
			    			that.table.load();
			    		}
			    	});
		    }
		    //删除请求（多条数据请用英文,隔开）
			this.del=function (id){
				if(id==''){
					layer.alert('请至少选择一行！',{ icon:3 });
					return;
				}
				layer.confirm('确定删除？',{ icon:3 },function(){
					$.get(conf.params.url+'del',{ 'id': id },function (data){
						if(data.status==1){
							that.table.load();
							layer.alert(data.info,{ icon:1 });
						}else{
							layer.alert(data.info,{ icon:2 });
						}
					},'json');
				})
			}
			//禁用或启用
			this.status=function (id,status){
				if(id==''){
					layer.alert('请至少选择一行！',{ icon:3 });
					return;
				}
				$.get(conf.params.url+'status',{ 'id': id, 'status':status },function (data){
					if(data.status==1){
						that.table.load();
						layer.alert(data.info,{ icon:1 });
					}else{
						layer.alert(data.info,{ icon:2 });
					}
				},'json');
			}
			//获取当前行id
			this.get_current_id=function (obj){
				try{
					return $(obj).parent().parent().find('td').eq(0).find('[type="hidden"]').eq(0).val();
				}catch(e){
					return '';
				}
			}
	    }
	    //form模块
	    form = function (){
	    	var add_id=conf.form.add_id;
	    	var edit_id=conf.form.edit_id;
	    	var add_modal_id='';
	    	var edit_modal_id='';
	    	var c=conf.form;
	    	this.init=function (){
	    		if(add_id!='' && add_id==edit_id){
	    			var add_id_name=conf.form.add_id+'_add';
	    			var edit_id_name=conf.form.edit_id+'_edit';
	    			//拷贝一份，用来做编辑
	    			var obj=$('#'+add_id).clone();
	    			//更换id
	    			$('#'+add_id).prop('id',add_id_name);
	    			obj.prop('id',edit_id_name);

	    			obj.insertBefore($('#'+add_id_name));

	    			add_id=conf.form.add_id=add_id_name;
	    			edit_id=conf.form.edit_id=edit_id_name;
	    		}
	    		bind_event();
	    	}
	    	bind_event=function (){
	    		if(add_id!=''){
					add_modal_id = $.f.modal.create_form(
						{'obj':$('#'+add_id),
						'title':'<i class="fa fa-plus"></i>&nbsp;&nbsp;新增',
						'btn':[ {
							'text':'保存',
							'click':function (){
								that.form.add();
							}
						} ]
					});
	    		}
	    		if(edit_id!=''){
					edit_modal_id = $.f.modal.create_form(
						{'obj':$('#'+edit_id),
						'title':'<i class="fa fa-edit"></i>&nbsp;&nbsp;编辑',
						'btn':[ {
							'text':'保存',
							'click':function (){
								that.form.edit();
							}
						} ]
					});
	    		}
	    	}
			//打开查看视图
			this.detail_view=function (id){
				$.get(conf.params.url+'detail',{},function (data){
					
				},'json');
			}
			//打开添加视图
			this.add_view=function (){
				var form=$('#'+add_id).eq(0);
				form[0].reset();//清空表单
				$.f.form.bind(add_id);
				$.f.modal.open(add_modal_id);
				$.f.bv.reset($('#'+add_id));
				if(c.event.add_open){
					c.event.add_open();
				}
			}
			//打开编辑视图
			this.edit_view=function (obj){
				var form=$('#'+edit_id).eq(0);
				form[0].reset();//清空表单
				$.f.bv.reset($('#'+edit_id));
				$.get(conf.params.url+'model',{ 'id':that.table.get_current_id(obj) },function (data){
					$.f.form.bind_update(edit_id,data);
					var id_name=f.get_id_name(data);
					if(form.find('[name="'+id_name+'"]').length<=0){
						form.prepend('<input type="hidden" name="'+id_name+'" value='+data[id_name]+'>');
					}
					if(c.event.edit_open){
						c.event.edit_open(data);
					}
				},'json');
				$.f.modal.open(edit_modal_id,'编辑');
			}
			//添加请求
			this.add=function (){
				var data=$.f.data.formTojson($('#'+add_id));
				for(var key in data){
					data[key]=$.f.str.trim(data[key]);
				}
				if(c.event.add_before){
					 data = c.event.add_before(data);
				}
				$.post(conf.params.url+'add',data,function (data){
					if(data.status==1){
						that.table.reload();
						$.f.modal.colse(add_modal_id);
						layer.alert(data.info,{ icon:1 });
					}else{
						if(data.data){
							$.f.bv.server_result($('#'+add_id),data.data);
						}else{
							layer.alert(data.info,{ icon:2 });
						}
					}
				},'json');
			}
			//编辑请求
			this.edit=function (){
				// $('#'+edit_id).serialize();
				var data= $.f.data.formTojson($('#'+edit_id));
				for(var key in data){
					data[key]=$.f.str.trim(data[key]);
				}
				if(c.event.edit_before){
					data=c.event.edit_before(data);
				}
				$.post(conf.params.url+'edit?r='+Math.random(),data,function (data){
					if(data.status==1){
						that.table.load();
						$.f.modal.colse(edit_modal_id);
						layer.alert(data.info,{ icon:1 });
					}else{
						if(data.data){
							$.f.bv.server_result($('#'+edit_id),data.data);
						}else{
							layer.alert(data.info,{ icon:2 });
						}
					}
				},'json');
			}
	    }
	    //搜索框模块
	    search = function (){
	    	var c=conf.search;
	    	this.init=function(){
	    		//初始化快捷搜索表单
	    		if(dd_quick.length>0){
	    			if(c.title!='' || dd_quick.html()!=''){
		    			var html='';
		    			if(c.title!=''){
		    				html+='<div class="input-group-btn"><input id="txt_quick_1510626461" name="search" placeholder="'+c.title+'" class="form-control"><input style="display:none"></div>';
	    					dd_quick.append(html);
		    				html='';
		    			}
		    			html+='<span class="input-group-btn" style="width:0"><button id="btn_quick_submit_1510626461" type="button" class="btn btn-primary btn-flat"><i class="fa fa-search"></i></button>';
	    			}
	    			if(dd_quick.html()!=''){
	    				var form_html=dd_quick.html();
	    				dd_quick.html('');
	    				dd_quick.prepend('<div></div>');
	    				dd_quick.parent().find('div').eq(0).append(form_html);
	    			}
	    			if(dd_more.length>0 && dd_more.html()!=''){
	    				html+='<button id="btn_more_1510626461" type="button" class="btn btn-primary btn-flat"><i class="fa fa-arrow-down"></i>&nbsp;&nbsp;更多搜索</button></span>';
	    			}
	    			dd_quick.append(html);
	    		}
	    		//初始化更多搜索表单
	    		if(dd_more.length>0 && dd_more.html()!=''){
	    			dd_quick.parent().parent().after('<div class="row"><div class="col-md-12"></div></div>');
	    			dd_more.appendTo(dd_quick.parent().parent().next().find('.col-md-12').eq(0));
	    			dd_more=main.find('.dd-more').eq(0);
	    			//排版更多搜索表单
	    			var html='';
	    			var more_div=dd_more.find('div');
	    			more_div.each(function (){
	    				$(this).addClass('col-md-3');
	    			});
	    			var first_md_num=0;
	    			if(more_div.length==1){
	    				first_md_num=6;
	    			}else if(more_div.length==2){
	    				first_md_num=3;
	    			}else if(more_div.length>=3){
	    				first_md_num=1;
	    			}
					more_div.each(function (index,item){
		    			if(index%3==0){
		    				dd_more.append('<div class="row" style="margin:8px 0 4px 0"><div class="col-md-'+first_md_num+'"></div></div>');
		    			}
		    			var child=$(item).children();
		    			if(child.length>0){
		    				var child_first=child.eq(0);
		    				var child_first2=child.eq(0).clone();
		    				child_first2.addClass('form-control');
		    				child_first2.addClass('form-control');
		    				child.remove();
		    				try{
		    					child_first2.prop('placeholder','请输入'+$(item).html());
		    				}catch(e){}
		    				$(item).html($(item).html()+'&nbsp;&nbsp;');
		    				child_first2.appendTo($(item));
		    			}
		    			$(item).appendTo(dd_more.children().last());
		    		});
		    		var btn_offset_num=0;
					if(more_div.length>3 && more_div.length%3!=0){
						switch(more_div.length%3){
							case 1:btn_offset_num+=2*3;break;
							case 2:btn_offset_num+=1*3;break;
						}
					}
					var emp='';
					if(btn_offset_num!=0){
						emp='<div class="col-md-'+btn_offset_num+'"></div>';
					}
	    			//添加更多搜索按钮
	    			dd_more.children().last().append(emp+'<div class="col-md-2" style="text-align:center"><button type="reset" class="btn btn-default btn-flat">重置</button>&nbsp;'+
	    				'<button type="button" id="btn_more_submit_1510626461" class="btn btn-primary btn-flat" >搜索</button><div>');
	    		}
	    		that.search.bind_event();

	    	}
	    	this.bind_event=function (){
	    		var btn_more=main.find('#btn_more_1510626461');
		        var btn_quick_submit=main.find('#btn_quick_submit_1510626461');
		        var btn_more_submit=main.find('#btn_more_submit_1510626461');
	    		//更多搜索按钮事件
	    		if(btn_more.length>0){
			        btn_more.click(function (){
			        	if($(this).html().substring($(this).html().length-4)=='更多搜索'){
			        		dd_more.show();
			        		btn_quick_submit.css('visibility','hidden');
			        		dd_quick.find('div').css('visibility','hidden');
			        		$(this).html('<i class="fa fa-arrow-up"></i>&nbsp;&nbsp;快捷搜索');
			        	}else{
			        		dd_more.hide();
			        		btn_quick_submit.css('visibility','visible');
			        		dd_quick.find('div').css('visibility','visible');
			        		$(this).html('<i class="fa fa-arrow-down"></i>&nbsp;&nbsp;更多搜索');
			        	}
			        })
	    		}
		        //快捷搜索提交事件
		        if(btn_quick_submit.length>0){
			    	btn_quick_submit.click(function (){
			    		that.table.reload();
			    	});
		        }
		        //更多搜索提交事件
		        if(btn_more_submit.length>0){
			    	btn_more_submit.click(function (){
			     		that.table.reload();
			     	});
		        }
		        //自动提交
		        that.search.keydown(main.find('#txt_quick_1510626461').eq(0));
		        dd_more.find("input").each(function (index,item){
		        	if($(item).prop('type')=='text'){
		        		that.search.keydown($(item));
		        	}
		        });
	    	}
	    	//获取当前搜索表单数据，并序列化
	    	this.get_form_data=function (){
	    		var data={};
	    		if(dd_quick.length>0 || dd_more.length>0){
	    			var btn_more=main.find('#btn_more_1510626461');
	    			if(btn_more.length<=0 || btn_more.html().substring(btn_more.html().length-4)=='更多搜索'){
	    				data=$.f.data.formTojson(dd_quick);
	    			}else{
	    				data=$.f.data.formTojson(dd_more);
	    			}
	    			//去除空字符
					for(var key in data){
						data[key]=$.f.str.trim(data[key]);
					}
	    			//转换表单start_time,end_time
		    		for(var key in data){
		    			if(key=='start_time'){
		    				data[key]=$.f.date.timestamp(data[key]);
		    			}
		    			if(key=='end_time'){
		    				var end_time=$.f.date.timestamp(data[key]);
		    				if(data[key].split(' ').length<=1){
		    					end_time = end_time==0? 0 : end_time+3600*24-1;
		    				}
		    				data[key]=end_time;
		    			}
		    		}
	    		}
	    		return data;
	    	}
	    	//按键盘自动提交事件
	    	this.keydown=function (obj){
	    		obj.keydown(function (){
			        if(window.event.keyCode == 13) {   
			           that.table.reload();
			        }
			    });
	    	}
	    }
	    //工具栏模块
	    toolbar = function (){
	    	var c=conf.toolbar;
	    	var import_file_id='import_file_'+p; //导入上传文件域id
	    	this.init=function(){
	    		var html='';
	    		$.each(c.oper,function (index,item){ 
	    			if(item=='add'){
	    				html+='<button id="btn_add_1511519312" type="button" class="btn btn-success btn-flat"><i class="fa fa-plus" ></i> 新增</button>&nbsp;';
	    			}else if(item=='del'){
						html+='<button id="btn_del_1511519312" type="button" class="btn btn-success btn-flat"><i class="fa fa-times"></i> 批量删除</button>&nbsp;';
	    			}else if(item=='status'){
	    				html+='<button id="btn_status1_1511519312" type="button" class="btn btn-success btn-flat"><i class="fa fa-plus-circle"></i> 启用</button>&nbsp;';
						html+='<button id="btn_status0_1511519312" type="button" class="btn btn-success btn-flat"><i class="fa fa-minus-circle" ></i> 禁用</button>&nbsp;';	
	    			}else if(item=='import'){
						html+='<div class="btn-group"><button id="btn_import_1511519312" type="button" class="btn btn-success btn-flat" onclick="document.getElementById(\''+import_file_id+'\').click();"><i class="fa fa-arrow-up" ></i> 导入execl</button>'+
						'<input type="file" name="file" id="'+import_file_id+'" style="display:none" />'+
						'<a class="btn btn-success btn-flat" href="/temp/data/'+$.f.get_controller(conf.params.url)+'.xls">下载模板</a></div>&nbsp;';
	    			}else if(item=='export'){
						html+='<button id="btn_export_1511519312" type="button" class="btn btn-success btn-flat"><i class="fa fa-arrow-down"></i> 导出execl</button>&nbsp;';
	    			}
	    		});
	    		dd_toolbar.html(html);
	    		this.bind_event();
	    	}
	    	this.bind_event=function (){
	    		$.each(c.oper,function (index,item){ 
	    			if(item=='add'){
	    				main.find('#btn_add_1511519312').eq(0).click(function (){
	    					that.form.add_view();
	    				});
	    			}else if(item=='del'){
		    			main.find('#btn_del_1511519312').eq(0).click(function (){
		    				that.table.del(that.table.get_select_rows());
	    				});
	    			}else if(item=='status'){
	    				main.find('#btn_status1_1511519312').eq(0).click(function (){
		    				that.table.status(that.table.get_select_rows(),1);
	    				});	
		    			main.find('#btn_status0_1511519312').eq(0).click(function (){
							that.table.status(that.table.get_select_rows(),0);
	    				});
	    			}else if(item=='export'){
		    			main.find('#btn_export_1511519312').eq(0).click(function (){
		    				that.toolbar.export();
	    				});
	    			}
	    		});
	    		//上传
	    		if($('#'+import_file_id)){
	    			$('#'+import_file_id).change(function (){
	    				var formData = new FormData();
						formData.append('file', $("#"+import_file_id)[0].files[0]);
						$.f.upload(conf.params.url+'import',formData);
	    			});
	    		}
	    	}
	    	//导出
	    	this.export=function (){
				$.f.download(conf.params.url+'export?r='+Math.random(),that.search.get_form_data());
	    	}
	    }
		//模块End
        
	    //共用方法模块func
	    f = function (){
			//获取表格最高高度并计算行数
			this.max_rows_count=function (){
                var limitCount = Math.floor(( parseInt( $(window).height() )-200 )/42);
				return limitCount;
                // f.max_rows_count(); c.page.limit
			}
			//获取主键 data:{admin_id:1}
			this.get_id_name=function (data){
				var id_name='';
				for(var key in data){
					if(/id/.test(key.toLowerCase()) ){
						id_name=key;
						break;
					}
				}
				return id_name;
			}
	    }
        init(config);

    }
})(jQuery);