<?php
namespace app\crontab\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\crontab\model\CrontabLog as CrontabLogModel;

/**
 * 定时任务日志后台控制器
 */
class Log extends Admin
{

    // 日志列表
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = CrontabLogModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btn_clear = [
            'title' => '清空日志',
            'icon'  => 'fa fa-times-circle-o',
            'class' => 'btn btn-primary ajax-get confirm',
            'data-title' => '真的要清除吗？',
            'href'  => url('clear')
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('日志列表')// 设置页面标题
            ->setTableName('CrontabLog')// 设置数据表名
            ->setSearch(['id' => 'ID', 'cid' => '任务ID', 'title' => '标题', 'type'=>'类型'])// 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['type', '类型', 'status', '', ['url' => '请求URL:primary', 'sql' => '执行SQL:primary', 'shell' => '执行Shell:primary']],
                ['cid', '任务ID', 'callback', function($value){
                    $url = url('crontab/index/index', ['search_field'=>'id', 'keyword'=>$value]);
                    return '<a href="'.$url.'">'.$value.'</a>';
                }],
                ['title', '任务标题', 'callback', function($value, $data){
                    $url = url('crontab/index/index', ['search_field'=>'id', 'keyword'=>$data['cid']]);
                    return '<a href="'.$url.'">'.$value.'</a>';
                }, '__data__'],
                ['create_time', '执行时间', 'datetime', '', 'Y-m-d H:i:s'],
                ['status', '状态', 'status', '', [0 => '失败:danger', 1 => '成功:success']],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('delete')// 批量添加顶部按钮
            ->addTopButton('clear', $btn_clear) // 添加清空按钮
            ->addRightButtons(['edit' => ['title' => '浏览'], 'delete'])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->setPages($page)// 设置分页数据
            //->raw('cid_link,title_link') // 使用原值
            ->fetch(); // 渲染页面
    }

    // 编辑
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 返回列表
        if ($this->request->isPost()) {
            return $this->success('日志不允许编辑，返回列表页', cookie('__forward__'));
        }

        // 获取数据
        $info = CrontabLogModel::where('id', $id)->field(true)->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('日志浏览') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'type', '类型', '', '', '', 'readonly'],
                ['text', 'cid', '任务ID', '', '', '', 'readonly'],
                ['text', 'title', '任务标题', '', '', '', 'readonly'],
                ['text', 'execute_time', '执行时间', '', '', '', 'readonly'],
                ['text', 'status_text', '状态', '', '', '', 'readonly'],
                ['textarea', 'remark', '执行结果'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    // 清空日志
    public function clear()
    {
        $connect = \think\Db::name('crontab_log');
        $tableName = $connect->getTable();
        if ($tableName){
            $connect->execute("TRUNCATE `{$tableName}`");
            $this->success('日志清除成功');
        }else{
            $this->error('日志清除失败');
        }
    }

}