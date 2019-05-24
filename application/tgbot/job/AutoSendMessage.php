<?php
/**
 * Created by LotteryBot.
 * User: TingV
 * Date: 2019-05-25
 * Time: 03:06
 */

namespace app\tgbot\job;

use think\Queue;
use think\queue\Job;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;


/**
 * 这是一个消费者类，用于处理 AutoSendMessage 队列中的任务
 */
class AutoSendMessage
{
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job, $data){
        $config = module_config('tgbot.bot_token,bot_username');
        $bot_api_key  = $config['bot_token'];
        $bot_username = $config['bot_username'];

        try {
            new Telegram($bot_api_key, $bot_username);
        } catch (TelegramException $e) {
        }

        $method = $data['method'];
        $result = Request::$method($data['data']);

        if ($result->isOk()) {
            // 自动删除机器人发送的消息
            if ( isset($data['auto_delete']) && is_numeric($data['auto_delete']) && $data['auto_delete']>0 ){
                $delete_data = [
                    'data' => [
                        'chat_id' => $result->getResult()->getChat()->getId(),
                        'message_id'    => $result->getResult()->getMessageId(),
                    ],
                    'method' => 'deleteMessage',
                ];
                Queue::later($data['auto_delete'], 'app\tgbot\job\AutoSendMessage', $delete_data, 'AutoSendMessage');
            }

            // 自动删除某条消息
            if ( isset($data['delete_message']) && is_array($data['delete_message']) && count($data['delete_message']) > 0){
                $delete_data = [
                    'data' => [
                        'chat_id' => $data['data']['chat_id'],
                        'message_id'    => $data['delete_message']['message_id'],
                    ],
                    'method' => 'deleteMessage',
                ];
                if (isset($data['delete_message']['message_id']) && is_numeric($data['delete_message']['message_id']) && $data['delete_message']['message_id']>0){
                    if ( isset($data['delete_message']['later']) && is_numeric($data['delete_message']['later']) && $data['delete_message']['later']>0){
                        Queue::later($data['delete_message']['later'], 'app\tgbot\job\AutoSendMessage', $delete_data, 'AutoSendMessage');
                    }else{
                        Queue::push('app\tgbot\job\AutoSendMessage', $delete_data, 'AutoSendMessage');
                    }
                }
            }
            //如果任务执行成功，删除任务
            $job->delete();
            print("消息发送成功\n");
        }else{
            // 失败就直接删掉任务，不做过多尝试
            $job->delete();
        }
    }

    /**
     * 该方法用于接收任务执行失败的通知，可以发送邮件给相应的负责人员
     * @param $jobData  string|array|...      //发布任务时传递的 jobData 数据
     */
    public function failed($data){
        print('警告: 队列任务执行错误，尝试次数已达上限'. PHP_EOL .'任务数据: ' . PHP_EOL . PHP_EOL .var_export($data,true).PHP_EOL);
    }
}