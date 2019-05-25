<?php
namespace app\tgbot\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\tgbot\model\LotteryUser as LotteryUserModel;

/**
 * TG 用户管理控制器
 * @package app\tgbot\admin
 */
class User extends Admin
{

    // 用户列表
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $LotteryUserModel = new LotteryUserModel();
        $data_list = $LotteryUserModel->where($map)->order($order)->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('参与人员列表')// 设置页面标题
            ->setSearch(['user_id' => '用户ID', 'first_name' => 'FirstName', 'username' => '用户名']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['user_id', '用户ID', 'text'],
                ['lottery_id', '活动ID', 'link', url('tgbot/lottery/index') . '?search_field=id&keyword=__lottery_id__', '_blank'],
                ['first_name', 'FirstName', 'text'],
                ['last_name', 'LastName', 'text'],
                ['username', '用户名', 'text'],
                ['create_time', '参与时间', 'datetime', '-'],
            ])
            ->setTableName('tgbot_lottery_user')
            ->addOrder('id,lottery_id,create_time')
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

}