<?php
namespace app\tgbot\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\tgbot\model\Lottery as LotteryModel;

/**
 * 抽奖活动管理控制器
 * @package app\tgbot\admin
 */
class Lottery extends Admin
{

    /**
     * 抽奖活动列表
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $LotteryModel = new LotteryModel();
        $data_list = $LotteryModel->where($map)->order($order)->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('抽奖活动列表')// 设置页面标题
            ->setSearch(['id' => '活动ID', 'title' => '奖品名称', 'chat_title' => '群组名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['chat_title', '群组名称', 'text'],
                ['title', '奖品名称', 'text'],
                ['conditions', '开奖条件', 'callback', function($value, $data){
                    switch ($value){
                        case 1:
                            $text = $data['condition_time'];
                            break;
                        case 2:
                            $text = $data['condition_hot'] . ' 人';
                            break;
                        default:
                            $text = '-';
                    }
                    return $text;
                }, '__data__'],
                ['hot', '参与人数', 'text'],
                ['status', '状态', 'status', '', [-1 => '已关闭:danger', 0 => '已开奖:info', 1=>'待开奖:success']],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom', [
                'title' => '禁用',
                'icon'  => 'fa fa-ban',
                'class' => 'btn btn-danger ajax-post confirm',
                'href'  => url('disable')
            ])
            ->addRightButtons(['edit', 'disable'])
            ->addOrder('id,status')
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    // 禁用
    public function disable($record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (empty($ids) || is_array($ids) && count($ids) < 1) return $this->error('缺少参数');

        $LotteryModel = new LotteryModel();
        $result = $LotteryModel->save(
            ['status' => -1],
            ['id' => ['in', $ids]]
        );
        if (false !== $result) {
            return $this->success('操作成功');
        } else {
            return $this->error('操作失败');
        }
    }

}