<?php
namespace app\tgbot\job;

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;
use think\Queue;
use think\queue\Job;
use app\tgbot\model\LotteryPrize as LotteryPrizeModel;

// 自动发送奖品队列
class AutoSendPrize
{
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job, $data){
        if ($job->attempts() > 3) {
            return $job->delete();
        }

        $prize = LotteryPrizeModel::get(['lottery_id' => $data['lottery_id'], 'status'=>0]);

        $config = module_config('tgbot.bot_token,bot_username');
        $bot_api_key  = $config['bot_token'];
        $bot_username = $config['bot_username'];

        try {
            new Telegram($bot_api_key, $bot_username);
        } catch (TelegramException $e) {
        }

        // 获取参与者的信息
        $result = Request::getChatMember([
            'chat_id' => $prize->lottery->chat_id,
            'user_id' => $data['user_id'],
        ]);

        // 验证用户是否还在群里 “creator”, “administrator”, “member”, “restricted”, “left” or “kicked”
        $member_status = $result->getResult()->getStatus();
        if (isset($member_status)==false || $member_status == 'left' || $member_status == 'kicked'){
            $prize->status      = -1;   // 不在群里，取消资格
        }else{
            $prize->status      = 1;
        }

        if ($prize){
            $prize->user_id     = $data['user_id'];
            $prize->first_name     = $data['first_name'];
            $prize->last_name     = $data['last_name'];
            $prize->username     = $data['username'];
            $prize->time        = $data['time'];

            if ($prize->save()){

                if ($prize->status == -1){
                    $msg_data = [
                        'chat_id' => $prize->lottery->chat_id,
                        'text' =>
                            '<b>好消息</b>' . PHP_EOL . PHP_EOL .
                            "由于 @<a href=\"tg://user?id={$data['user_id']}\">{$data['first_name']} {$data['last_name']}</a> 开奖期间退群，奖品无法发送达，现已自动退回到 <a href=\"tg://user?id={$prize->lottery->user_id}\">活动发起人</a> 手中。",
                        'disable_web_page_preview' => true,
                        'parse_mode' => 'html',
                    ];
                    Queue::push('app\tgbot\job\AutoSendMessage', [
                        'method' => 'sendMessage',
                        'data' => $msg_data,
                    ], 'AutoSendMessage');
                }

                $msg_data = [
                    'chat_id' => $data['user_id'],
                    'text' =>
                        '🔔🔔 中奖信息 🔔🔔' . PHP_EOL . PHP_EOL .
                        '群组：<b>' . $data['chat_title'] . '</b>' . PHP_EOL .
                        '活动：<b>' . $data['title'] . '</b>' . PHP_EOL .
                        '奖品：' . ($prize->status == -1 ? '( 由于开奖期间退群，获奖资格被取消 )' : $prize->prize),
                    'disable_web_page_preview' => true,
                    'parse_mode' => 'html',
                ];
                Queue::push('app\tgbot\job\AutoSendMessage', [
                    'method' => 'sendMessage',
                    'data' => $msg_data,
                ], 'AutoSendMessage');
                return $job->delete();
            }else{
                $msg_data = [
                    'chat_id' => $data['user_id'],
                    'text' => $data['title'] . ' 更新中奖信息失败，请联系机器人开发者',
                    'parse_mode' => 'html',
                ];
                Queue::push('app\tgbot\job\AutoSendMessage', [
                    'method' => 'sendMessage',
                    'data' => $msg_data,
                ], 'AutoSendMessage');
                print($data['title'] . ' 更新中奖信息失败');
            }
        }else{
            print($data['title'] . '奖品发完了');
        }
    }

    /**
     * 该方法用于接收任务执行失败的通知，可以发送邮件给相应的负责人员
     * @param $jobData  string|array|...      //发布任务时传递的 jobData 数据
     */
    public function failed($data){

    }
}