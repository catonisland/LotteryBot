<?php
namespace app\crontab\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\crontab\model\Crontab as CrontabModel;
use Cron\CronExpression;

/**
 * 定时任务后台控制器
 */
class Index extends Admin
{
    // 定时任务列表
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 获取排序
        $default_order = input('?param._order/s') ? '' : 'id DESC';
        $order = $this->getOrder($default_order);

        // 数据列表
        $data_list = CrontabModel::where($map)->order($order)->paginate();

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('定时任务')// 设置页面标题
            ->setTableName('crontab')// 设置数据表名
            ->setSearch(['id' => 'ID', 'title' => '标题', 'type'=>'类型'])// 设置搜索参数
            ->addOrder('id,maximums,begin_time,end_time,execute_time')// 添加排序
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['type', '类型', 'status', '', ['url' => '请求URL:primary', 'sql' => '执行SQL:primary', 'shell' => '执行Shell:primary']],
                ['title', '任务标题'],
                ['maximums', '最大次数', 'callback', function($value){
                    if ($value>0){
                        return $value;
                    }else{
                        return '无限制';
                    }
                }],
                ['executes', '已执行数'],
                ['begin_time', '开始时间'],
                ['end_time', '结束时间'],
                ['schedule', '下次预计时间', 'callback', function($value, $data){

                    if ($data['status'] != 'normal'){
                        return '';
                    }else{
                        return CronExpression::factory($value)->getNextRunDate()->format('Y-m-d H:i');
                    }
                }, '__data__'],
                ['execute_time', '最后执行时间'],
                ['status', '状态', 'status', '', ['completed' => '完成:default', 'expired' => '过期:warning', 'disable' => '禁用:danger', 'normal' => '正常:success']],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')// 批量添加顶部按钮
            ->addRightButtons('edit,enable,disable,delete')// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->setPages($page)// 设置分页数据
            //->raw('maximums_text,next_time') // 使用原值
            ->setColumnWidth([
                'id'  => 40,
                'type'  => 50,
                'title'  => 90,
                'maximums_text'  => 50,
                'executes'  => 50,
                'begin_time' => 70,
                'end_time' => 70,
                'next_time' => 70,
                'execute_time' => 70,
                'status' => 40,
                'right_button' => 80,
            ])
            ->fetch(); // 渲染页面
    }

    // 添加
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Crontab');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);
            $CrontabModel = new CrontabModel();
            if ($CrontabModel->isUpdate(false)->save($data)) {
                return $this->success('新增成功',cookie('__forward__'));
            } else {
                $this->error('新增失败');
            }
        }

        $checkSchedule_url = url('checkSchedule');
        $getScheduleFuture_url = url('getScheduleFuture');

        $js = <<<EOF
            <script type="text/javascript">
                 function checkSchedule() {
                    var schedule = $("#schedule").val();
                    $.post("{$checkSchedule_url}", { "schedule": schedule },
                    function(data){
                        if (data.status == false){
                            Dolphin.notify('Cron 表达式错误', 'danger', 'glyphicon glyphicon-warning-sign');
                            return false;
                        }
                        var days = $("#pickdays").val();
                        var begin_time = $("#begin_time").val();
                        $.post("{$getScheduleFuture_url}", { "schedule": schedule, "begin_time": begin_time, "days": days },
                            function(data){
                                if (data.status == true){
                                    var html = '';
                                    for(var i=0; i<data.time.length; i++){
                                        html += '<li class="list-group-item">'+data.time[i]+'<span class="badge">'+(i+1)+'</span></li>';
                                        //console.log(data.time[i]);
                                    }
                                    $('#scheduleresult').html(html);
                                }
                            }, "json");
                    }, "json");
                }
            
                $(function(){
                    checkSchedule();    // 页面加载后就执行一次
            
                    // 检查 Cron 表达式是否正确，如果正确，则获取预计执行时间
                    $("#schedule,#pickdays,#begin_time").blur(function(){
                        checkSchedule();
                    });
                });
            </script>
EOF;

        return ZBuilder::make('form')
            ->setPageTitle('新增')// 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '任务标题'],
                ['select', 'type', '类型', '1. URL类型分为两种，一是完整的URL地址，如： <code>http://www.dolphinphp.com/download.html</code> ；二是本地路径 <code>/模块名/控制器名/方法名</code>，如：<code>/index/index/test</code><br />2. 如果你的服务器 php.ini 未开启 <code>shell_exec()</code> 函数，则不能使用本地URL类型模式和Shell类型！', ['url' => '请求URL', 'sql' => '执行SQL', 'shell' => '执行Shell'], 'url'],
                ['textarea', 'content', '内容', ''],
                ['text', 'schedule', '执行周期', '请使用 <code>Cron</code> 表达式', '* * * * *', [], 'style="font-size:12px;font-family: Verdana;word-spacing:23px;"'],
                ['number', 'maximums', '最大执行次数', '0为不限次数', '0'],
                ['number', 'executes', '已执行次数', '如果任务执行次数达到上限，则会自动把状态改为“完成”<br />如果已“完成”的任务需要再次运行，请重置本参数或者调整最大执行次数并把下面状态值改成“正常”', '0'],
                ['datetime', 'begin_time', '开始时间', '任务从什么时间点开始执行', '', 'YYYY-MM-DD HH:mm:ss'],
                ['datetime', 'end_time', '结束时间', '如果需要长期执行，请把结束时间设置得竟可能的久', '', 'YYYY-MM-DD HH:mm:ss'],
                ['number', 'weigh', '权重', '多个任务同一时间执行时，按照权重从高到底执行', '0'],
                ['radio', 'status', '状态', '', ['normal' => '正常', 'disable' => '禁用', 'completed' => '完成', 'expired' => '过期'], 'normal'],
            ])
            ->setExtraJs($js)
            ->fetch('add');
    }

    // 编辑
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Crontab');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);
            $CrontabModel = new CrontabModel();
            if ($CrontabModel->isUpdate(true)->save($data)) {
                return $this->success('编辑成功',cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = CrontabModel::where('id', $id)->field(true)->find();

        $checkSchedule_url = url('checkSchedule');
        $getScheduleFuture_url = url('getScheduleFuture');
        $js = <<<EOF
            <script type="text/javascript">
                 function checkSchedule() {
                    var schedule = $("#schedule").val();
                    $.post("{$checkSchedule_url}", { "schedule": schedule },
                    function(data){
                        if (data.status == false){
                            Dolphin.notify('Cron 表达式错误', 'danger', 'glyphicon glyphicon-warning-sign');
                            return false;
                        }
                        var days = $("#pickdays").val();
                        var begin_time = $("#begin_time").val();
                        $.post("{$getScheduleFuture_url}", { "schedule": schedule, "begin_time": begin_time, "days": days },
                            function(data){
                                if (data.status == true){
                                    var html = '';
                                    for(var i=0; i<data.time.length; i++){
                                        html += '<li class="list-group-item">'+data.time[i]+'<span class="badge">'+(i+1)+'</span></li>';
                                        //console.log(data.time[i]);
                                    }
                                    $('#scheduleresult').html(html);
                                }
                            }, "json");
                    }, "json");
                }
            
                $(function(){
                    checkSchedule();    // 页面加载后就执行一次
            
                    // 检查 Cron 表达式是否正确，如果正确，则获取预计执行时间
                    $("#schedule,#pickdays,#begin_time").blur(function(){
                        checkSchedule();
                    });
                });
            </script>
EOF;

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '任务标题'],
                ['select', 'type', '类型', '1. URL类型分为两种，一是完整的URL地址，如： <code>http://www.dolphinphp.com/download.html</code> ；二是本地路径 <code>/模块名/控制器名/方法名</code>，如：<code>/index/index/test</code><br />2. 如果你的服务器 php.ini 未开启 <code>shell_exec()</code> 函数，则不能使用本地URL类型模式和Shell类型！', ['url' => '请求URL', 'sql' => '执行SQL', 'shell' => '执行Shell']],
                ['textarea', 'content', '内容', ''],
                ['text', 'schedule', '执行周期', '请使用 <code>Cron</code> 表达式', '* * * * *', [], 'style="font-size:12px;font-family: Verdana;word-spacing:23px;"'],
                ['number', 'maximums', '最大执行次数', '0为不限次数'],
                ['number', 'executes', '已执行次数', '如果任务执行次数达到上限，则会自动把状态改为“完成”<br />如果已“完成”的任务需要再次运行，请重置本参数或者调整最大执行次数并把下面状态值改成“正常”', '0'],
                ['datetime', 'begin_time', '开始时间', '任务从什么时间点开始执行', '', 'YYYY-MM-DD HH:mm:ss'],
                ['datetime', 'end_time', '结束时间', '如果需要长期执行，请把结束时间设置得竟可能的久', '', 'YYYY-MM-DD HH:mm:ss'],
                ['number', 'weigh', '权重', '多个任务同一时间执行时，按照权重从高到底执行'],
                ['radio', 'status', '状态', '', ['normal' => '正常', 'disable' => '禁用', 'completed' => '完成', 'expired' => '过期']],
                ['hidden', 'id'],
            ])
            ->setFormData($info) // 设置表单数据
            ->setExtraJs($js)
            ->fetch('add');

    }

    // 禁用
    public function disable($record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (empty($ids) || is_array($ids) && count($ids) < 1) return $this->error('缺少参数');

        $CrontabModel = new CrontabModel();
        $result = $CrontabModel->save(
            ['status' => 'disable'],
            ['id' => ['in', $ids]]
        );
        if (false !== $result) {
            return $this->success('操作成功');
        } else {
            return $this->error('操作失败');
        }
    }

    // 启用
    public function enable($record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (empty($ids) || is_array($ids) && count($ids) < 1) return $this->error('缺少参数');

        $CrontabModel = new CrontabModel();
        $result = $CrontabModel->save(
            ['status' => 'normal'],
            ['id' => ['in', $ids]]
        );
        if (false !== $result) {
            return $this->success('操作成功');
        } else {
            return $this->error('操作失败');
        }
    }

    /**
     * 判断 Cron 表达式是否正确
     */
    public function checkSchedule()
    {
        $schedule = input('post.schedule', '');
        $result = $this->validate(['schedule'=>$schedule], 'Crontab.schedule');
        if ($result === true) {
            return ['status' => true];
        } else {
            return ['status' => false];
        }
    }

    /**
     * 根据Crontab表达式获取未来N次的时间
     */
    public function getScheduleFuture($schedule = '* * * * *', $begin_time = 'now', $days = 7)
    {
        $data = [
            'status' => false,
            'time' => [],
        ];
        $schedule = $schedule ?: input('schedule', '* * * * *');
        $begin_time = $begin_time ?: input('begin_time', 'now');
        $days = $days ?: input('days/d', 7);

        try {
            $cron = CronExpression::factory($schedule);
            for ($i = 0; $i < $days; $i++) {
                $data['time'][] = $cron->getNextRunDate($begin_time, $i)->format('Y-m-d H:i:s');
            }
            $data['status'] = true;
        } catch (\Exception $e) {
        }

        return $data;
    }

}