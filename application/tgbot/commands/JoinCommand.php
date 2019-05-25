<?php
/**
 * Created by LotteryBot.
 * User: TingV
 * Date: 2019-05-25
 * Time: 16:37
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Hashids\Hashids;
use app\tgbot\model\LotteryUser;
use app\tgbot\model\LotteryChannel;
use app\tgbot\model\Lottery;
use think\Queue;

/**
 * User "/join" command
 *
 * 私聊参与抽奖
 */
class joinCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'join';

    /**
     * @var string
     */
    protected $description = '私聊参与抽奖';

    /**
     * @var string
     */
    protected $usage = '/join';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * If this command is enabled
     *
     * @var boolean
     */
    protected $enabled = true;

    /**
     * @var bool
     */
    protected $need_mysql = false;

    /**
     * 是否在 /help 命令中显示
     *
     * @var bool
     */
    protected $show_in_help = false;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $from = $message->getFrom();
        $user_id = $from->getId();
        $first_name = htmlentities($message->getFrom()->getFirstName());
        $last_name = htmlentities($message->getFrom()->getLastName());
        $username = $from->getUsername();
        $text    = htmlentities(trim($message->getText(true)));

        $param = explode('-', $text);// 解析参数
        if (isset($param[1])){
            // 解密出 ID
            $hashids_config = config('hashids');
            $hashids = new Hashids($hashids_config['salt'], $hashids_config['min_hash_length']);
            $decode_id = $hashids->decode($param[1]);
            $lottery_id = isset($decode_id[0]) ? $decode_id[0] : null;
        }else{
            return Request::emptyResponse();
        }

        // 查询活动
        $lottery_info = Lottery::get(['id' => $lottery_id, 'join_type'=>2]);

        // 没有查到
        if ( !$lottery_info ){
            $data = [
                'chat_id' => $chat_id,
                'text' => '活动不存在',
            ];
            return Request::sendMessage($data);
        }

        // 活动已结束
        if ($lottery_info->status != 1){
            $data = [
                'chat_id' => $chat_id,
                'text'    => "<b>{$lottery_info->title}</b> 抽奖活动已" . ($lottery_info->status === 0 ? '结束' : '删除'),
                'parse_mode' => 'html',
                'disable_notification'=>true,
                'disable_web_page_preview'=>true,
            ];
            return Request::sendMessage($data);
        }

        // 获取机器人配置
        $bot_config = module_config('tgbot.bot_id,channel_username');
        $bot_id = $bot_config['bot_id'];
        $channel_username = $bot_config['channel_username'];

        // 获取机器人信息并缓存
        $bot_info = cache('bot_info:' . $lottery_info->chat_id);
        if ( !$bot_info ){
            $chat_member_request = Request::getChatMember([
                'chat_id' => $lottery_info->chat_id,
                'user_id' => $bot_id,
            ]);
            $bot_info = $chat_member_request->getRawData();
            if ( $bot_info['ok'] ){
                cache('bot_info:' . $lottery_info->chat_id, $bot_info, 2 * 3600);
            }else{
                cache('bot_info:' . $lottery_info->chat_id, NULL);
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '抽奖助手信息验证失败',
                ];
                return Request::sendMessage($data);
            }
        }

        // 验证机器人是否为管理员 “administrator”
        if ( $bot_info['result']['status'] != 'administrator' ){
            cache('bot_info:' . $lottery_info->chat_id, NULL);
            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖助手不是管理员，请联系群组管理员！',
            ];
            return Request::sendMessage($data);
        }

        // 超级群权限检查
        if ($lottery_info->chat_type == 'supergroup' && ( $bot_info['result']['can_delete_messages'] == false || $bot_info['result']['can_pin_messages'] == false) ){
            cache('bot_info:' . $lottery_info->chat_id, NULL);
            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖助手的权限设置不正确，请联系群组管理员！',
            ];
            return Request::sendMessage($data);
        }


        switch ( $lottery_info->conditions ){
            case  1 :    // 按时间自动开奖
                $condition_text = '开奖时间：' . $lottery_info->condition_time;
                break;
            case  2 :   // 按人数自动开奖
                $condition_text = "参与人数达到 <b>{$lottery_info->condition_hot}</b> 人后将自动开奖";
                break;
        }

        $LotteryUser = LotteryUser::get(['lottery_id'=>$lottery_info->id, 'user_id'=>$user_id]);

        if ( !$LotteryUser ){

            // 获取参与者的信息
            $result = Request::getChatMember([
                'chat_id' => $lottery_info->chat_id,
                'user_id' => $user_id,
            ]);
            // 获取失败
            if($result->isOk() == false){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '用户是否加群验证失败',
                    'disable_web_page_preview' => true,
                ];
                return Request::sendMessage($data);
            }
            // 验证用户是否加群 “creator”, “administrator”, “member”, “restricted”, “left” or “kicked”
            $member_status = $result->getResult()->getStatus();
            if ($member_status == 'left' || $member_status == 'kicked'){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '请先加入发布此活动的群组，才能参与抽奖。',
                    'disable_web_page_preview' => true,
                ];
                return Request::sendMessage($data);
            }


            // 新增参与用户
            LotteryUser::create([
                'user_id'  =>  $user_id,
                'lottery_id' =>  $lottery_info->id,
                'first_name' =>  $first_name,
                'last_name' =>  $last_name,
                'username' =>  $username,
            ]);
            // 累加参与人数
            $LotteryModel = new Lottery();
            $LotteryModel->where('id', $lottery_info->id)->setInc('hot', 1);

            // 满足按人数开奖的条件则自动开奖
            if ( $lottery_info->conditions == 2 && ($lottery_info->hot+1) >= $lottery_info->condition_hot ){
                $now_time = time();
                $data = $lottery_info->getData();
                $data['time'] = $now_time;
                // 有开奖任务就放到队列里去执行
                Queue::later(10,'app\tgbot\job\AutoLottery', $data, 'AutoLottery');
                $LotteryModel->update(['id' => $lottery_info->id, 'time'=>$now_time, 'status' => 0]);
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '感谢参与 <b>' . $lottery_info->title . '</b> 抽奖活动' . PHP_EOL .
                    '奖品数量：<b>' . $lottery_info->number . '</b>' . PHP_EOL .
                    $condition_text . PHP_EOL .
                    '当前参与人数：<b>' . ($lottery_info->hot + 1) . '</b>' . PHP_EOL . PHP_EOL .

                    '如果中奖，抽奖助手会把奖品通过私聊形式发送给你。' . PHP_EOL .
                    '活动期间请勿退群，否则将取消抽奖资格。' . PHP_EOL .
                    "更多抽奖活动请关注 @{$channel_username} 频道。",
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
            ];
        }else{
            $data = [
                'chat_id' => $chat_id,
                'text'    => "你已参与 <b>{$lottery_info->title}</b> 抽奖活动" . PHP_EOL .
                    $condition_text . PHP_EOL .
                    '当前参与人数：<b>' . $lottery_info->hot . '</b>',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
            ];
        }

        return Request::sendMessage($data);

    }
}