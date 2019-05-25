<?php
namespace app\tgbot\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\tgbot\model\Chat as ChatModel;

/**
 * 抽奖群管理控制器
 * @package app\tgbot\admin
 */
class Chat extends Admin
{
    /**
     * 抽奖群列表
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('create_time desc');
        // 数据列表
        $ChatModel = new ChatModel();
        $data_list = $ChatModel->where($map)->order($order)->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('抽奖活动列表')// 设置页面标题
            ->setSearch(['id' => '群组ID', 'title' => '群组名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['type', '类型', 'status', '', ['group' => '普通群:info', 'supergroup' =>'超级群:success']],
                ['title', '名称', 'text'],
                ['username', 'username', 'callback', function($value){
                    if (empty($value) == false){
                        return "<a href=\"https://t.me/{$value}\" target=\"_blank\">{$value}</a>";
                    }else{
                        return '';
                    }
                }],
                ['create_time', '创建时间', 'datetime', '-'],
                ['public_channel', '推送频道', 'switch'],
                ['status', '创建活动', 'switch'],
            ])
            ->addOrder('id,status')
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }
}