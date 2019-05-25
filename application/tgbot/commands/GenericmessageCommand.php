<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\SystemCommand;
use app\tgbot\telegram\Conversation;
use app\tgbot\model\Lottery as LotteryModel;
use app\tgbot\model\LotteryUser as LotteryUserModel;
use think\Queue;
use Hashids\Hashids;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = '处理通用消息';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * 执行命令
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $type = $message->getChat()->getType();

        if ( $type == 'private' ){
            //如果存在会话，则执行命令会话
            $conversation = new Conversation(
                $this->getMessage()->getFrom()->getId(),
                $this->getMessage()->getChat()->getId()
            );

            //如果会话命令存在则获取并执行它
            if ($conversation->exists() && ($command = $conversation->getCommand())) {
                return $this->telegram->executeCommand($command);
            }
        }

        $message_id = $message->getMessageId();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $first_name = htmlentities($message->getFrom()->getFirstName());
        $last_name = htmlentities($message->getFrom()->getLastName());
        $username = $message->getFrom()->getUsername();
        $is_bot = $message->getFrom()->getIsBot();
        $text    = htmlentities(trim($message->getText(true)));

        // 获取机器人配置
        $bot_config = module_config('tgbot.bot_id,bot_username,channel_username');
        $bot_id = $bot_config['bot_id'];
        $bot_username = $bot_config['bot_username'];
        $channel_username = $bot_config['channel_username'];

        // 群内参与抽奖
        if ( ($type == 'group' || $type == 'supergroup') && empty($text) == false ){

            $has_lottery = cache('has_lottery:' . $chat_id);

            // 被缓存且缓存结果为没有活动时跳出 if ，直接到最后返回空
            if ($has_lottery !== false && $has_lottery == 0){
                goto  no_lottery ;
            }

            $keyword_text = '';
            $LotteryList = LotteryModel::all(['chat_id' => $chat_id, 'status'=>1]);

            // trace($LotteryList , 'info');

            // 在缓存里记录群里是否有抽奖活动
            if ( $LotteryList ){    // 有活动
                cache('has_lottery:' . $chat_id, 1, 3600);
            }else{    // 没有活动
                cache('has_lottery:' . $chat_id, 0, 3600);
            }

            // 获取群组管理员并缓存12个小时
            $administrator_ids = cache('chat_admins:' . $chat_id);
            if ( !$administrator_ids ){
                $result = Request::getChatAdministrators(['chat_id' => $chat_id]);
                if ($result->isOk()){
                    $data = $result->getResult();
                    foreach ($data as $key => $user){
                        $administrator_ids[] = $data[$key]->user['id'];
                    }
                    cache('chat_admins:' . $chat_id, $administrator_ids, 12 * 3600);
                }else{
                    $administrator_ids = [];
                }
            }

            // 循环处理每一条抽奖活动
            foreach ($LotteryList as $index => $Lottery){
                // 查询抽奖口令
                if ($is_bot == false && (
                        strpos ( $text ,  '抽奖口令' ) !== false ||
                        strpos ( $text ,  '抽奖地址' ) !== false ||
                        strpos ( $text ,  '抽奖链接' ) !== false ||
                        strpos ( $text ,  '抽奖方法' ) !== false ||
                        strpos ( $text ,  '怎么抽' ) !== false ||
                        strpos ( $text ,  '怎么参与' ) !== false ||
                        strpos ( $text ,  '咋参与' ) !== false ||
                        strpos ( $text ,  '如何抽' ) !== false ) ){
                    if ($Lottery->join_type == 1){  // 参与方式 1:群聊
                        $keyword_text .= ($index+1) . ". 发送『<b>{$Lottery->keyword}</b>』即可参与 <b>{$Lottery->title}</b> 的抽奖活动；" . PHP_EOL . PHP_EOL;
                    }elseif ($Lottery->join_type == 2){ // 参与方式 2:私聊
                        // 初始化 ID 加密类
                        $hashids_config = config('hashids');
                        $hashids = new Hashids($hashids_config['salt'], $hashids_config['min_hash_length']);

                        $join_link = 'https://t.me/' . $bot_username  . '?start=join-' . $hashids->encode($Lottery->id);
                        $keyword_text .= ($index+1) . ". [<a href=\"{$join_link}\">点击这里</a>] 即可参与 <b>{$Lottery->title}</b> 的抽奖活动；" . PHP_EOL . PHP_EOL;
                    }
                }

                // 查找关键词
                if ($is_bot == false && strpos ( $text ,  $Lottery->keyword ) !== false){
                    // 计数器 用来删4留1
                    $counter = cache('counter:' . $Lottery->id);
                    if ( !$counter ){   // 开始计数
                        $counter = 1;
                    }else{  // 正在计数累加 1
                        $counter += 1;
                    }
                    cache('counter:' . $Lottery->id, $counter, 3 * 86400); // 记录计数值 3 天过期

                    // 获取机器人信息并缓存
                    $bot_info = cache('bot_info:' . $chat_id);
                    if ( !$bot_info ){
                        $chat_member_request = Request::getChatMember([
                            'chat_id' => $chat_id,
                            'user_id' => $bot_id,
                        ]);
                        $bot_info = $chat_member_request->getRawData();
                        if ( $bot_info['ok'] ){
                            cache('bot_info:' . $chat_id, $bot_info, 2 * 3600);
                        }else{
                            $data = [
                                'chat_id' => $chat_id,
                                'reply_to_message_id' => $message_id,
                                'text'    => '抽奖助手信息验证失败',
                            ];
                            Queue::push('app\tgbot\job\AutoSendMessage', [
                                'method' => 'sendMessage',
                                'data' => $data,
                                'auto_delete' => 10,    // 延迟多少秒自动删除
                            ], 'AutoSendMessage');
                            cache('bot_info:' . $chat_id, NULL);
                            return Request::emptyResponse();
                        }
                    }

                    // 验证机器人是否为管理员 “administrator”
                    if ($bot_info['result']['status'] != 'administrator'){
                        $data = [
                            'chat_id' => $chat_id,
                            'reply_to_message_id' => $message_id,
                            'text'    => '抽奖助手不是管理员，请联系群组管理员！',
                        ];
                        Queue::push('app\tgbot\job\AutoSendMessage', [
                            'method' => 'sendMessage',
                            'data' => $data,
                            'auto_delete' => 10,    // 延迟多少秒自动删除
                        ], 'AutoSendMessage');
                        cache('bot_info:' . $chat_id, NULL);
                        return Request::emptyResponse();
                    }

                    // 超级群权限检查
                    if ($type == 'supergroup' && ($bot_info['result']['can_delete_messages'] == false || $bot_info['result']['can_pin_messages'] == false)){
                        $data = [
                            'chat_id' => $chat_id,
                            'reply_to_message_id' => $message_id,
                            'text'    => '抽奖助手的权限设置不正确，请联系群组管理员！',
                        ];
                        Queue::push('app\tgbot\job\AutoSendMessage', [
                            'method' => 'sendMessage',
                            'data' => $data,
                            'auto_delete' => 10,    // 延迟多少秒自动删除
                        ], 'AutoSendMessage');
                        cache('bot_info:' . $chat_id, NULL);
                        return Request::emptyResponse();
                    }

                    switch ( $Lottery->conditions ){
                        case  1 :    // 按时间自动开奖
                            $condition_text = '开奖时间：' . $Lottery->condition_time;
                            break;
                        case  2 :   // 按人数自动开奖
                            $condition_text = "参与人数达到 <b>{$Lottery->condition_hot}</b> 人后将自动开奖";
                            break;
                    }

                    $LotteryUser = LotteryUserModel::get(['lottery_id'=>$Lottery->id, 'user_id'=>$user_id]);
                    if ( !$LotteryUser ){
                        // 新增参与用户
                        LotteryUserModel::create([
                            'user_id'  =>  $user_id,
                            'lottery_id' =>  $Lottery->id,
                            'first_name' =>  $first_name,
                            'last_name' =>  $last_name,
                            'username' =>  $username,
                        ]);
                        // 累加参与人数
                        $LotteryModel = new LotteryModel();
                        $LotteryModel->where('id', $Lottery->id)->setInc('hot', 1);

                        // 满足按人数开奖的条件则自动开奖
                        if ($Lottery->conditions == 2 && ($Lottery->hot+1) >= $Lottery->condition_hot){
                            $now_time = time();
                            $data = $Lottery->getData();
                            $data['time'] = $now_time;
                            $LotteryModel->update(['id' => $Lottery->id, 'time'=>$now_time, 'status' => 0]);
                            // 有开奖任务就放到队列里去执行
                            Queue::later(10,'app\tgbot\job\AutoLottery', $data, 'AutoLottery');
                        }

                        $data = [
                            'chat_id' => $chat_id,
                            'reply_to_message_id' => $message_id,
                            'text'    => '感谢参与 <b>' . $Lottery->title . '</b> 抽奖活动' . PHP_EOL .
                                '奖品数量：<b>' . $Lottery->number . '</b>' . PHP_EOL .
                                $condition_text . PHP_EOL .
                                '当前参与人数：<b>' . ($Lottery->hot + 1) . '</b>' . PHP_EOL . PHP_EOL .

                                '请先私聊一次 @' . $bot_username . ' ，如果中奖，抽奖助手会把奖品通过私聊形式发送给你。' . PHP_EOL .
                                '活动期间请勿退群，否则会取消中奖资格。' . PHP_EOL .
                                "更多抽奖活动请关注 @{$channel_username} 频道。",
                            'parse_mode' => 'html',
                            'disable_web_page_preview' => true,
                        ];
                    }else{
                        $data = [
                            'chat_id' => $chat_id,
                            'reply_to_message_id' => $message_id,
                            'text'    => "你已参与 <b>{$Lottery->title}</b> 抽奖活动" . PHP_EOL .
                                $condition_text . PHP_EOL .
                                '当前参与人数：<b>' . $Lottery->hot . '</b>',
                            'parse_mode' => 'html',
                            'disable_web_page_preview' => true,
                        ];
                    }

                    // 消息发送者为群组管理员或者是第 10 条关键词的时候则不删他的关键词消息
                    if ( in_array($user_id, $administrator_ids) || $counter % 10 == 0 ){ // 不删
                        $send_data = [
                            'method' => 'sendMessage',
                            'data' => $data,
                            'auto_delete' => 30,    // 延迟多少秒自动删除机器人发送的这条消息
                        ];
                    }else{   // 删
                        $send_data = [
                            'method' => 'sendMessage',
                            'data' => $data,
                            'auto_delete' => 30,    // 延迟多少秒自动删除机器人发送的这条消息
                            'delete_message' => [   // 延迟多少秒自动删除触发这条消息的消息
                                'later' => 60,
                                'message_id' => $message_id,
                            ],
                        ];
                    }

                    Queue::push('app\tgbot\job\AutoSendMessage', $send_data, 'AutoSendMessage');
                }
            }

            // 发送抽奖口令
            if (empty($keyword_text)==false){
                if ( in_array($user_id, $administrator_ids) ){
                    $send_data = [
                        'method' => 'sendMessage',
                        'data' => [
                            'chat_id' => $chat_id,
                            'reply_to_message_id' => $message_id,
                            'text'    => $keyword_text,
                            'parse_mode' => 'html',
                            'disable_web_page_preview' => true,
                        ],
                    ];
                }else{
                    $send_data = [
                        'method' => 'sendMessage',
                        'data' => [
                            'chat_id' => $chat_id,
                            'reply_to_message_id' => $message_id,
                            'text'    => $keyword_text,
                            'parse_mode' => 'html',
                            'disable_web_page_preview' => true,
                        ],
                        'auto_delete' => 115,    // 延迟多少秒自动删除机器人发送的这条消息
                        'delete_message' => [   // 延迟多少秒自动删除触发这条消息的消息
                            'later' => 120,
                            'message_id' => $message_id,
                        ],
                    ];
                }

                Queue::push('app\tgbot\job\AutoSendMessage', $send_data, 'AutoSendMessage');
            }
        }

        // 没有抽奖活动的时候直接跳到这
        no_lottery :

        // 返回空
        return Request::emptyResponse();
    }
}
