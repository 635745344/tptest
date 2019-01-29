$.m.adminLog={};
(function ($){
    $.extend($.m.adminLog,{
    	dd:new Dd({
            table:{
                cols:{ 'account':'账号','name':'类型','remark':'备注','ip':'登录IP','create_time':'创建时间' }
            },
            search:{ title:'请输入账号名' }
        })
    }) 
    var m=$('#main_1511010745');
    m.find('[name="start_time"]').eq(0).datetimepicker({
        format: 'yyyy-mm-dd',
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
        autoclose: 1,
        minView: 2,
        forceParse: 0
    });  
    m.find('[name="end_time"]').eq(0).datetimepicker({  
        format: 'yyyy-mm-dd',
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
        autoclose: 1,
        minView: 2,
        forceParse: 0
    });
})(jQuery)