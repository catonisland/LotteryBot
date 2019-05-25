<?php
namespace app\tgbot\home;

use think\Queue;
use app\tgbot\model\Lottery as LotteryModel;

class Lottery
{
    // 按时间开奖的监控
    public function index(){
        $now_time = time();
        $LotteryModel = new LotteryModel();
        $LotteryList = $LotteryModel->where('condition_time','<=',$now_time)->where('status', 1)->select();
        if ( !$LotteryList ){
            return '';
        }

        // 有开奖任务就放到队列里去执行
        $lotterys = '';
        foreach ($LotteryList as $Lottery){
            $data = $Lottery->getData();
            $data['time'] = $now_time;
            $queue_id = Queue::push('app\tgbot\job\AutoLottery', $data, 'AutoLottery');
            if ($queue_id){
                $LotteryModel->update(['id' => $Lottery->id, 'time'=>$now_time, 'status' => 0]);
                $lotterys .= $Lottery->title . PHP_EOL;
            }
        }

        return $lotterys;

    }

    // 按人数开奖（非监控，而是代码主动来调用）
    public function hot( $id = 0 ){
        $lottery_info = LotteryModel::get(['id'=>$id, 'conditions'=>2, 'status'=>1]);
        if ( !$lottery_info ){
            return false;
        }

        if ($lottery_info->hot != $lottery_info->condition_hot){
            return false;
        }

        // 有开奖任务就放到队列里去执行
        Queue::push('app\tgbot\job\AutoLottery', $lottery_info->getData(), 'AutoLottery');
        $lottery_info->status = 0;
        $lottery_info->save();
        return true;

    }
}