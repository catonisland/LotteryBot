<?php
/**
 * Created by LotteryBot.
 * User: TingV
 * Date: 2019-05-25
 * Time: 02:35
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use app\tgbot\telegram\Conversation;
use app\tgbot\model\Lottery as LotteryModel;
use app\tgbot\model\LotteryChannel as LotteryChannelModel;
use app\tgbot\model\Chat as ChatModel;
use think\Queue;

/**
 * User "/create" command
 *
 * 创建一个新的抽奖活动命令
 */
class CreateCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'create';

    /**
     * @var string
     */
    protected $description = '创建一个新的抽奖活动';

    /**
     * @var string
     */
    protected $usage = '/create';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * 是否仅允许私聊机器人时使用
     *
     * @var bool
     */
    protected $private_only = false;

    /**
     * 命令是否启用
     *
     * @var boolean
     */
    protected $enabled = true;

    /**
     * 是否在 /help 命令中显示
     *
     * @var bool
     */
    protected $show_in_help = false;

    /**
     * 开奖条件
     *
     * @var array
     */
    private $conditions = [
        1 => '按时间自动开奖',
        2 => '按人数自动开奖',
    ];

    /**
     * 开奖通知
     *
     * @var array
     */
    private $notification = [
        1 => '是',
        0 => '否',
    ];

    /**
     * 用户参与方式
     *
     * @var array
     */
    private $join_type = [
        1 => '群内发送关键词参与抽奖',
        2 => '私聊机器人参与抽奖',
    ];

    private $is_push_channel = [
        1 => '是，我同意推送',
        0 => '不，谢谢',
    ];

    private $submit = [
        1 => '✅ 确定',
        0 => '🚫 取消',
    ];


    /**
     * 机器人配置
     *
     * @var array
     */
    private $bot_config = [];

    /**
     * 执行命令
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat = $message->getChat();
        $type = $chat->getType();
        $chat_id = $chat->getId();
        $chat_title = htmlentities($chat->getTitle());
        $chat_username = $chat->getUsername();
        $message_id = $message->getMessageId();
        $user_id = $message->getFrom()->getId();
        $first_name = $message->getFrom()->getFirstName();
        $last_name = $message->getFrom()->getLastName();
        $nickname = htmlentities($first_name . (isset($last_name)? ' ' . $last_name : ''));
        $text    = htmlentities(trim($message->getText(true)));

        // 机器人配置
        $this->bot_config = module_config('tgbot.bot_username,bot_id,channel_title,channel_URL,channel_push_review');
        $bot_username = $this->bot_config['bot_username'];
        $bot_id = $this->bot_config['bot_id'];
        $channel_title = $this->bot_config['channel_title'];
        $channel_URL = $this->bot_config['channel_URL'];
        $channel_push_review = $this->bot_config['channel_push_review'];

        Request::sendChatAction(['chat_id' => $chat_id, 'action'=>'typing']);

        // 第一步，先接收群里的 /create 命令
        if ( $type == 'group' || $type == 'supergroup' ){
            // 获取群组管理员
            $administrator_ids = [];
            $result = Request::getChatAdministrators(['chat_id' => $chat_id]);
            if ($result->isOk()){
                $data = $result->getResult();
                foreach ($data as $key => $user){
                    $administrator_ids[] = $data[$key]->user['id'];
                }
            }else{
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '获取群组管理员失败，请重试',
                ];
                Queue::push('app\tgbot\job\AutoSendMessage', [
                    'method' => 'sendMessage',
                    'data' => $data,
                    'auto_delete' => 10,    // 延迟多少秒自动删除
                    'delete_message' => [   // 延迟多少秒自动删除触发这条消息的消息
                        'later' => 12,
                        'message_id' => $message_id,
                    ],
                ], 'AutoSendMessage');
                return Request::emptyResponse();
            }

            // 判断命令执行者是否为群组管理员或机器人管理员
            if ($this->telegram->isAdmin($user_id) != true && in_array($user_id, $administrator_ids) != true){
                $data = [
                    'chat_id' => $chat_id,
                    'reply_to_message_id' => $message_id,
                    'text'    => '非群组管理员不能发起抽奖活动',
                ];
                Queue::push('app\tgbot\job\AutoSendMessage', [
                    'method' => 'sendMessage',
                    'data' => $data,
                    'auto_delete' => 10,    // 延迟多少秒自动删除
                    'delete_message' => [   // 延迟多少秒自动删除触发这条消息的消息
                        'later' => 12,
                        'message_id' => $message_id,
                    ],
                ], 'AutoSendMessage');
                return Request::emptyResponse();
            }

            // 清理通用消息中缓存的管理员列表
            cache('chat_admins:' . $chat_id,NULL);
            // 清除用户参与抽奖时生成的缓存
            cache('bot_info:' . $chat_id, NULL);

            // 获取机器人信息
            $chat_member_request = Request::getChatMember([
                'chat_id' => $chat_id,
                'user_id' => $bot_id,
            ]);

            $bot_info = $chat_member_request->getRawData();

            if ( !$bot_info['ok'] ){
                $data = [
                    'chat_id' => $chat_id,
                    'reply_to_message_id' => $message_id,
                    'text'    => '机器人信息验证失败',
                ];
                Queue::push('app\tgbot\job\AutoSendMessage', [
                    'method' => 'sendMessage',
                    'data' => $data,
                    'auto_delete' => 10,    // 延迟多少秒自动删除
                    'delete_message' => [   // 延迟多少秒自动删除触发这条消息的消息
                        'later' => 12,
                        'message_id' => $message_id,
                    ],
                ], 'AutoSendMessage');
                return Request::emptyResponse();
            }

            // 如果机器人被任何限制，直接退群
            if ($bot_info['result']['status'] == 'restricted'){
                return Request::leaveChat(['chat_id'=>$chat_id]);
            }

            // 验证机器人是否为管理员 “administrator”
            if ( $bot_info['result']['status'] != 'administrator' ){
                $data = [
                    'chat_id' => $chat_id,
                    'reply_to_message_id' => $message_id,
                    'text'    => $type == 'group' ? '请先给我管理员身份，之后才能发起抽奖活动。' : '请先给我管理员身份并授予删除消息和置顶消息权限，之后才能发起抽奖活动。',
                ];
                Queue::push('app\tgbot\job\AutoSendMessage', [
                    'method' => 'sendMessage',
                    'data' => $data,
                    'auto_delete' => 10,    // 延迟多少秒自动删除
                    'delete_message' => [   // 延迟多少秒自动删除触发这条消息的消息
                        'later' => 12,
                        'message_id' => $message_id,
                    ],
                ], 'AutoSendMessage');
                return Request::emptyResponse();
            }

            // 超级群权限检查
            if ( $type == 'supergroup' && ($bot_info['result']['can_delete_messages'] == false || $bot_info['result']['can_pin_messages'] == false) ){
                $data = [
                    'chat_id' => $chat_id,
                    'reply_to_message_id' => $message_id,
                    'text'    => '权限不足，请给我删除消息和置顶消息权限。',
                ];
                Queue::push('app\tgbot\job\AutoSendMessage', [
                    'method' => 'sendMessage',
                    'data' => $data,
                    'auto_delete' => 10,    // 延迟多少秒自动删除
                    'delete_message' => [   // 延迟多少秒自动删除触发这条消息的消息
                        'later' => 12,
                        'message_id' => $message_id,
                    ],
                ], 'AutoSendMessage');
                return Request::emptyResponse();
            }

            // 记录使用机器人的群
            $ChatModel = new ChatModel();
            $chat_info = $ChatModel->where('id', $chat_id)->find();
            if ($chat_info){
                if ($chat_info->status != 1){   // 此群被禁止发起抽奖活动
                    $ChatModel->where('id', $chat_id)->delete();
                    return Request::leaveChat(['chat_id'=>$chat_id]);
                }
                $is_update = true;
            }else{
                $is_update = false;
            }
            $ChatModel->isUpdate($is_update)->save(['id' => $chat_id, 'type' => $type, 'title'=>$chat_title, 'username'=>$chat_username]);

            // 创建会话
            $conversation = new Conversation($user_id, $user_id, $this->getName());

            // 初始化 ID 加密类
            $hashids_config = config('hashids');
            $hashids = new \Hashids\Hashids($hashids_config['salt'], $hashids_config['min_hash_length']);

            // 回复一个带按钮的消息
            $keyboard_buttons[] = new InlineKeyboardButton([
                'text'          => '抽奖设置',
                'url'          => 'https://t.me/' . $bot_username  . '?start=create-' . $hashids->encode($user_id),
            ]);

            $data = [
                'chat_id' => $chat_id,
                'reply_to_message_id' => $message_id,
                'text'    => "<a href=\"tg://user?id={$user_id}\">@{$nickname}</a> 抽奖活动的目标群组已选定，请你点击下面按钮与我私聊进行后续设置。",
                'reply_markup' => new InlineKeyboard($keyboard_buttons),
                'parse_mode' => 'html',
            ];
            $result = Request::sendMessage($data);

            // 30秒后删除创建命令
            Queue::later(10,'app\tgbot\job\AutoSendMessage', [
                'method' => 'deleteMessage',
                'data' => [
                    'chat_id' => $chat_id,
                    'message_id'    => $message_id,
                ],
            ], 'AutoSendMessage');

            // 删除群组里的那条有创建活动按钮的消息
            $delete_message_id = $result->isOk() ? $result->getResult()->getMessageId() : 0;

            $conversation->notes = [ 'step'=>'start', 'chat_id' => $chat_id, 'chat_type'=>$type, 'user_id' => $user_id, 'chat_title' => $chat_title, 'delete_message_id'=>$delete_message_id];
            $conversation->update();
            return $result;
        }

        $conversation = new Conversation($user_id, $user_id);
        $notes = $conversation->notes;

        // 不是从群组创建的活动，而是直接私聊发送的 /create 命令
        if (($type == 'channel' || $type == 'private') && $notes == null){
            $data = [
                'chat_id' => $chat_id,
                'reply_to_message_id' => $message_id,
                'text'    => '如果你是群组管理员，请在群里使用此命令来创建抽奖活动',
            ];
            return Request::sendMessage($data);
        }

        // 开始设置
        if ($type == 'private' && $notes['step'] == 'start' && $user_id == $notes['user_id']){
            if (isset($notes['delete_message_id']) && $notes['delete_message_id']>0){
                Request::deleteMessage([
                    'chat_id' => $notes['chat_id'],
                    'message_id' => $notes['delete_message_id'],
                ]);
                unset($notes['delete_message_id']);
            }

            $notes['step'] = 'title';
            $conversation->notes = $notes;
            $conversation->update();
            $data = [
                'chat_id' => $chat_id,
                'text'    => '开始设置 <b>'. $notes['chat_title'] .'</b> 群的抽奖活动' . PHP_EOL . PHP_EOL .
                    '请设置奖品名称：',
                'parse_mode' => 'html',
            ];
            return Request::sendMessage($data);
        }

        // 设置名称
        if ($type == 'private' && $notes['step'] == 'title' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['title'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $notes['step'] = 'number';
            $notes['title'] = $text;
            $conversation->notes = $notes;
            $conversation->update();
            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $text . PHP_EOL . PHP_EOL .
                    '请设置奖品数量：',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
            ];
            return Request::sendMessage($data);
        }

        // 设置数量
        if ($type == 'private' && $notes['step'] == 'number' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['number'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $notes['step'] = 'prize';
            $notes['prize'] = [];
            $notes['number'] = $text;
            $conversation->notes = $notes;
            $conversation->update();

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $text . PHP_EOL . PHP_EOL .
                    '请设置奖品内容 ( 1. 可以直接填写 <b>APP 兑换码</b>、<b>支付宝口令红包</b>等奖品让机器人自动发奖；也可留下你的联系方式，让中奖者主动联系你领奖。2. 有多少奖品数就回复多少次。 )：',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
            ];
            return Request::sendMessage($data);
        }

        // 设置奖品
        if ($type == 'private' && $notes['step'] == 'prize' && $user_id == $notes['user_id'] && count($notes['prize']) < intval($notes['number']) && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['prize'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }
            $notes['prize'][] = $text;
            if (count($notes['prize']) >= intval($notes['number']) ){
                $notes['step'] = 'conditions';
            }else{
                $conversation->notes = $notes;
                $conversation->update();
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '继续设置下一个奖品内容：',
                ];
                return Request::sendMessage($data);
            }
        }

        // 选择开奖条件
        if ($type == 'private' && $notes['step'] == 'conditions' && $user_id == $notes['user_id'] && empty($text)==false){
            $notes['step'] = 'conditions_select';
            $conversation->notes = $notes;
            $conversation->update();

            $prize_list = PHP_EOL;
            foreach ($notes['prize'] as $index=>$prize){
                $prize_list .= $index+1 . '. ' . $prize . PHP_EOL;
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $notes['number'] . PHP_EOL .
                    '奖品列表：' . $prize_list . PHP_EOL .
                    '请选择开奖方式：',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => new Keyboard([
                    'resize_keyboard'   => true,
                    'one_time_keyboard' => true,
                    'selective'         => true,
                    'keyboard'          => [$this->conditions],
                ]),
            ];
            return Request::sendMessage($data);
        }

        // 要求设置开奖 时间/人数 条件
        if ($type == 'private' && $notes['step'] == 'conditions_select' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['conditions'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $conditions = array_search($text, $this->conditions);
            $notes['step'] = 'notification';
            $notes['conditions'] = $conditions;
            $conversation->notes = $notes;
            $conversation->update();

            switch ( $conditions ) {
                case  1 :
                    $next_text = '请设置开奖时间 ( 格式：<b>年-月-日 时:分</b> ) ：';
                    break;
                case  2 :
                    $next_text = '请设置开奖人数 ：';
                    break;
            }

            $prize_list = PHP_EOL;
            foreach ($notes['prize'] as $index=>$prize){
                $prize_list .= $index+1 . '. ' . $prize . PHP_EOL;
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $notes['number'] . PHP_EOL .
                    '奖品列表：' . $prize_list .
                    '开奖方式：' . $text . PHP_EOL . PHP_EOL .
                    $next_text,
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => Keyboard::remove(['selective' => true]),
            ];
            return Request::sendMessage($data);
        }

        // 设置开奖通知
        if ($type == 'private' && $notes['step'] == 'notification' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');

            switch ( $notes['conditions'] ){
                case  1 :    // 按时间自动开奖
                    if(!$validate->check(['condition_time'=>$text])){
                        $data = [
                            'chat_id' => $chat_id,
                            'text'    => $validate->getError(),
                        ];
                        return Request::sendMessage($data);
                    }

                    $notes['condition_time'] = $text;
                    $condition_text = '开奖时间：' . $notes['condition_time'];
                    break;
                case  2 :   // 按人数自动开奖
                    if(!$validate->check(['condition_hot'=>$text])){
                        $data = [
                            'chat_id' => $chat_id,
                            'text'    => $validate->getError(),
                        ];
                        return Request::sendMessage($data);
                    }
                    $notes['condition_hot'] = $text;
                    $condition_text = '开奖人数：' . $text;
                    break;
            }

            $notes['step'] = 'join_type';
            $conversation->notes = $notes;
            $conversation->update();

            $prize_list = PHP_EOL;
            foreach ($notes['prize'] as $index=>$prize){
                $prize_list .= $index+1 . '. ' . $prize . PHP_EOL;
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $notes['number'] . PHP_EOL .
                    '奖品列表：' . $prize_list .
                    '开奖方式：' . $this->conditions[$notes['conditions']] . PHP_EOL .
                    $condition_text . PHP_EOL . PHP_EOL .
                    '是否置顶通知所有人开奖的结果 ：',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => new Keyboard([
                    'resize_keyboard'   => true,
                    'one_time_keyboard' => true,
                    'selective'         => true,
                    'keyboard'          => [$this->notification],
                ]),
            ];

            return Request::sendMessage($data);
        }

        // 设置参与类型
        if ($type == 'private' && $notes['step'] == 'join_type' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['notification'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $notes['notification'] = array_search($text, $this->notification);;
            $notes['step'] = 'keyword';
            $conversation->notes = $notes;
            $conversation->update();

            $prize_list = PHP_EOL;
            foreach ($notes['prize'] as $index=>$prize){
                $prize_list .= $index+1 . '. ' . $prize . PHP_EOL;
            }

            switch ( $notes['conditions'] ){
                case  1 :    // 按时间自动开奖
                    $condition_text = '开奖时间：' . $notes['condition_time'];
                    break;
                case  2 :   // 按人数自动开奖
                    $condition_text = '开奖人数：' . $notes['condition_hot'];
                    break;
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $notes['number'] . PHP_EOL .
                    '奖品列表：' . $prize_list .
                    '开奖方式：' . $this->conditions[$notes['conditions']] . PHP_EOL .
                    $condition_text . PHP_EOL .
                    '开奖通知：' . $text . PHP_EOL . PHP_EOL .
                    '请选择参与方式：',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => new Keyboard([
                    'resize_keyboard'   => true,
                    'one_time_keyboard' => true,
                    'selective'         => true,
                    'keyboard'          => [$this->join_type],
                ]),
            ];

            return Request::sendMessage($data);
        }

        // 设置关键词
        if ($type == 'private' && $notes['step'] == 'keyword' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['join_type'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            switch ( $notes['conditions'] ){
                case  1 :    // 按时间自动开奖
                    $condition_text = '开奖时间：' . $notes['condition_time'];
                    break;
                case  2 :   // 按人数自动开奖
                    $condition_text = '开奖人数：' . $notes['condition_hot'];
                    break;
            }

            $notes['join_type'] = array_search($text, $this->join_type);
            $notes['step'] = 'channel_push_select';

            // 群内发送关键词参与抽奖则要求设置关键词
            if($notes['join_type'] == 1){
                $conversation->notes = $notes;
                $conversation->update();

                $prize_list = PHP_EOL;
                foreach ($notes['prize'] as $index=>$prize){
                    $prize_list .= $index+1 . '. ' . $prize . PHP_EOL;
                }

                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                        '奖品名称：' . $notes['title'] . PHP_EOL .
                        '奖品数量：' . $notes['number'] . PHP_EOL .
                        '奖品列表：' . $prize_list .
                        '开奖方式：' . $this->conditions[$notes['conditions']] . PHP_EOL .
                        $condition_text . PHP_EOL .
                        '开奖通知：' . $this->notification[$notes['notification']] . PHP_EOL .
                        '参与方式：' . $text . PHP_EOL . PHP_EOL .

                        '请设置参与关键词：',
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => Keyboard::remove(['selective' => true]),
                ];
                return Request::sendMessage($data);
            }
        }

        // 选择是否发布抽奖信息到抽奖活动频道
        if ($type == 'private' && $notes['step'] == 'channel_push_select' && $user_id == $notes['user_id'] && empty($text)==false){

            switch ( $notes['join_type'] ){
                case  1 :   // 群内抽奖关键词
                    $validate = validate('Create');
                    if(!$validate->check(['keyword'=>$text])){
                        $data = [
                            'chat_id' => $chat_id,
                            'text'    => $validate->getError(),
                        ];
                        return Request::sendMessage($data);
                    }
                    $notes['keyword'] = $text;
                    $keyword_text = '关键词：' . $text . PHP_EOL . PHP_EOL;
                    break;
                case  2 :   // 私聊机器人抽奖无关键词
                    $notes['keyword'] = null;
                    $keyword_text = PHP_EOL;
                    break;
            }

            $notes['step'] = 'is_submit';
            $conversation->notes = $notes;
            $conversation->update();

            switch ( $notes['conditions'] ){
                case  1 :    // 按时间自动开奖
                    $condition_text = '开奖时间：' . $notes['condition_time'];
                    break;
                case  2 :   // 按人数自动开奖
                    $condition_text = '开奖人数：' . $notes['condition_hot'];
                    break;
            }

            $prize_list = PHP_EOL;
            foreach ($notes['prize'] as $index=>$prize){
                $prize_list .= $index+1 . '. ' . $prize . PHP_EOL;
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $notes['number'] . PHP_EOL .
                    '奖品列表：' . $prize_list .
                    '开奖方式：' . $this->conditions[$notes['conditions']] . PHP_EOL .
                    $condition_text . PHP_EOL .
                    '开奖通知：' . $this->notification[$notes['notification']] . PHP_EOL .
                    '参与方式：' . $this->join_type[$notes['join_type']] . PHP_EOL .
                    $keyword_text .
                    "是否推送此活动到 <a href=\"{$channel_URL}\">{$channel_title}</a> 频道，这可以让更多的人参与进来，同时也能推广你的群组？" . ($channel_push_review ? '( 需要审核 )' : ''),
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => new Keyboard([
                    'resize_keyboard'   => true,
                    'one_time_keyboard' => true,
                    'selective'         => true,
                    'keyboard'          => [['是，我同意推送', '不，谢谢']],
                ]),
            ];

            return Request::sendMessage($data);
        }

        // 确认是否发布抽奖活动
        if ($type == 'private' && $notes['step'] == 'is_submit' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['is_push_channel'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $notes['is_push_channel'] = array_search($text, $this->is_push_channel);

            if ($notes['is_push_channel']==1){
                $is_push_channel_text = '是';
                $is_ask_group_url_text = '请发送你的群组邀请链接 ( 格式：<i>https://t.me/xxxx</i> )：';
                $notes['step'] = 'chat_url';
                $reply_markup = Keyboard::remove(['selective' => true]);
            }else{
                $is_push_channel_text = '否';
                $is_ask_group_url_text = '已全部设置完成，是否发布？';
                $notes['step'] = 'submit';
                $notes['chat_url'] = null;
                $reply_markup = new Keyboard([
                    'resize_keyboard'   => true,
                    'one_time_keyboard' => true,
                    'selective'         => true,
                    'keyboard'          => [$this->submit],
                ]);
            }

            $conversation->notes = $notes;
            $conversation->update();

            switch ( $notes['conditions'] ){
                case  1 :    // 按时间自动开奖
                    $condition_text = '开奖时间：' . $notes['condition_time'];
                    break;
                case  2 :   // 按人数自动开奖
                    $condition_text = '开奖人数：' . $notes['condition_hot'];
                    break;
            }

            switch ( $notes['join_type'] ){
                case  1 :   // 群内抽奖关键词
                    $keyword_text = '关键词：' . $notes['keyword'] . PHP_EOL;
                    break;
                case  2 :   // 私聊机器人抽奖无关键词
                    $keyword_text = '';
                    break;
            }

            // 奖品列表
            $prize_list = PHP_EOL;
            foreach ($notes['prize'] as $index=>$prize){
                $prize_list .= $index+1 . '. ' . $prize . PHP_EOL;
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $notes['number'] . PHP_EOL .
                    '奖品列表：' . $prize_list .
                    '开奖方式：' . $this->conditions[$notes['conditions']] . PHP_EOL .
                    $condition_text . PHP_EOL .
                    '开奖通知：' . $this->notification[$notes['notification']] . PHP_EOL .
                    '参与方式：' . $this->join_type[$notes['join_type']] . PHP_EOL .
                    $keyword_text .
                    "推送到 <a href=\"{$channel_URL}\">{$channel_title}</a> 频道：" . $is_push_channel_text . PHP_EOL . PHP_EOL .
                    $is_ask_group_url_text,
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => $reply_markup,
            ];

            return Request::sendMessage($data);
        }

        // 获取群组链接
        if ($type == 'private' && $notes['step'] == 'chat_url' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['chat_url'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                    'parse_mode' => 'html',
                ];
                return Request::sendMessage($data);
            }

            $notes['step'] = 'submit';
            $notes['chat_url'] = $text;
            $conversation->notes = $notes;
            $conversation->update();

            switch ( $notes['conditions'] ){
                case  1 :    // 按时间自动开奖
                    $condition_text = '开奖时间：' . $notes['condition_time'];
                    break;
                case  2 :   // 按人数自动开奖
                    $condition_text = '开奖人数：' . $notes['condition_hot'];
                    break;
            }

            switch ( $notes['join_type'] ){
                case  1 :   // 群内抽奖关键词
                    $keyword_text = '关键词：' . $notes['keyword'] . PHP_EOL;
                    break;
                case  2 :   // 私聊机器人抽奖无关键词
                    $keyword_text = '';
                    break;
            }

            // 奖品列表
            $prize_list = PHP_EOL;
            foreach ($notes['prize'] as $index=>$prize){
                $prize_list .= $index + 1 . '. ' . $prize . PHP_EOL;
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => '抽奖群：'. $notes['chat_title'] . PHP_EOL .
                    '奖品名称：' . $notes['title'] . PHP_EOL .
                    '奖品数量：' . $notes['number'] . PHP_EOL .
                    '奖品列表：' . $prize_list .
                    '开奖方式：' . $this->conditions[$notes['conditions']] . PHP_EOL .
                    $condition_text . PHP_EOL .
                    '开奖通知：' . $this->notification[$notes['notification']] . PHP_EOL .
                    '参与方式：' . $this->join_type[$notes['join_type']] . PHP_EOL .
                    $keyword_text .
                    "推送到 <a href=\"{$channel_URL}\">{$channel_title}</a> 频道：" . ($notes['is_push_channel']?'是':'否') . PHP_EOL .
                    '群组邀请链接：' . $text . PHP_EOL . PHP_EOL .
                    '已全部设置完成，是否发布？',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => new Keyboard([
                    'resize_keyboard'   => true,
                    'one_time_keyboard' => true,
                    'selective'         => true,
                    'keyboard'          => [$this->submit],
                ]),
            ];

            return Request::sendMessage($data);
        }

        // 确定或取消
        if ($type == 'private' && $notes['step'] == 'submit' && $user_id == $notes['user_id'] && empty($text)==false){
            $validate = validate('Create');
            if(!$validate->check(['submit'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $conversation->cancel(); // 取消会话

            $submit = array_search($text, $this->submit);   // 确认类型

            // 取消
            if ($submit != 1){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '已取消 <b>' . $notes['title'] . '</b> 抽奖活动发布',
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => Keyboard::remove(['selective' => true]),
                ];
                $conversation->cancel(); // 取消会话
                return Request::sendMessage($data);
            }

            // 数据入库
            $LotteryModel = LotteryModel::create($notes);
            $lottery_id = $LotteryModel->id;

            // 批量增加关联数据
            $save_data = [];
            foreach ($notes['prize'] as $prize){
                $save_data[] = [
                    'lottery_id' => $lottery_id,
                    'prize' => $prize,
                    'status' => 0, // 状态 0: 未领取 1: 已领取
                ];
            }
            $LotteryModel->prizes()->saveAll($save_data);

            // 清除群里是否有抽奖活动的缓存记录
            cache('has_lottery:' . $notes['chat_id'], NULL);

            // 抽奖的具体方式
            switch ( $notes['join_type'] ){
                case  1 :   // 群内抽奖关键词
                    $join_type_text = '参与关键词：<b>' . $notes['keyword'] . '</b>' . PHP_EOL;
                    break;
                case  2 :   // 私聊机器人抽奖无关键词
                    // 初始化 ID 加密类
                    $hashids_config = config('hashids');
                    $hashids = new \Hashids\Hashids($hashids_config['salt'], $hashids_config['min_hash_length']);
                    $join_link = 'https://t.me/' . $bot_username  . '?start=join-' . $hashids->encode($lottery_id);
                    $join_type_text = "参与链接：<a href=\"{$join_link}/\">{$join_link}</a>" . PHP_EOL;
                    break;
            }

            // 推送到频道的数据
            $chat_info = ChatModel::get( ['id'=>$notes['chat_id']] );   // 查询群组信息，判断是否允许推送到频道
            if ( $notes['is_push_channel'] == 1 && $chat_info->public_channel == 1){
                // 记录频道发布的数据
                $data = [
                    'lottery_id' => $lottery_id,
                    'message_id' => 0,
                    'status' => 0,
                ];
                $LotteryChannelModel = LotteryChannelModel::create($data);
                if ($channel_push_review == 1){   // 需要审核
                    $this->review( $LotteryChannelModel->id );
                    $public_channel_text = "· 活动信息请等待审核通过后将自动推送到 <a href=\"{$channel_URL}\">{$channel_title}</a> 频道；" . PHP_EOL;
                }else{  // 不审核
                    self::push_channel( $LotteryChannelModel->id );
                    $public_channel_text = "· 活动信息已自动推送到 <a href=\"{$channel_URL}\">{$channel_title}</a> 频道；" . PHP_EOL;
                }

            }else{
                $public_channel_text = '';
            }

            $data = [
                'chat_id' => $chat_id,
                'text' => '<b>' . $notes['title'] . '</b> 抽奖活动已发布' . PHP_EOL .
                    $join_type_text . PHP_EOL .
                    $public_channel_text .
                    ($notes['notification']? PHP_EOL . '· 开奖结果会在群内临时置顶1分钟并发出通知，1分钟之后静默恢复原置顶消息；':'') . PHP_EOL .
                    '· 请尽快组织抽奖活动的进行，对于长期无人参与或测试性质的活动，机器人管理员有权关闭以释放系统资源。',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => Keyboard::remove(['selective' => true]),
            ];
            $result = Request::sendMessage($data);

            if ($result->isOk()){
                $data = [
                    'chat_id' => $notes['chat_id'],
                    'text' => "<a href=\"tg://user?id={$user_id}\">@{$nickname}</a> 已发布 <b>{$notes['title']}</b> 抽奖活动。",
                    'parse_mode' => 'html',
                ];
                Request::sendMessage($data);
            }
            return $result;
        }

        if (!isset($data)){
            $data = [
                'chat_id' => $chat_id,
                'reply_to_message_id' => $message_id,
                'text'    => '请按照之前的提示进行操作，或使用 /cancel 命令取消当前会话！',
            ];
        }

        return Request::sendMessage($data);

    }

    // 推送到频道
    public static function push_channel( $id )
    {
        //开奖方式
        $conditions = [
            1 => '按时间自动开奖',
            2 => '按人数自动开奖',
        ];

        $channel_info = LotteryChannelModel::get( $id );

        // 已审核过了
        if ($channel_info->status == 1){
            return true;
        }

        // 已开奖或被禁
        if ($channel_info->lottery->status != 1){
            return false;
        }

        // 修改状态
        $channel_info->status = 1;
        $channel_info->save();

        // 机器人配置
        $bot_config = module_config('tgbot.bot_username,admin_users_ids,channel_id,channel_title,channel_URL');
        $bot_username = $bot_config['bot_username'];
        $channel_id = $bot_config['channel_id'];

        $condition_text = '';
        if ($channel_info->lottery->conditions == 1){
            $condition_text = '开奖时间: ' . $channel_info->lottery->condition_time;
        }
        if ($channel_info->lottery->conditions == 2){
            $condition_text = '开奖人数: ' . $channel_info->lottery->condition_hot;
        }

        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '加入',
            'url'          => $channel_info->lottery->chat_url,
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '分享',
            'url'          => 'https://t.me/' . $bot_username . '?start=share-' . $id,
        ]);

        $data = [
            'chat_id' => $channel_id,
            'text'    => '抽奖群: ' . $channel_info->lottery->chat_title . PHP_EOL .
                '奖品名称: ' . $channel_info->lottery->title . PHP_EOL .
                '奖品数量: ' . $channel_info->lottery->number . PHP_EOL .
                '开奖方式: ' . $conditions[$channel_info->lottery->conditions] . PHP_EOL .
                $condition_text . PHP_EOL .
                '状态: 待开奖' . PHP_EOL . PHP_EOL .
                '具体参与方式请在群内发送『怎么抽奖』进行查询。',
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => new InlineKeyboard($keyboard_buttons),
        ];
        $result = Request::sendMessage($data);
        if ($result->isOk()){
            $channel_info->message_id = $result->getResult()->getMessageId();
            $channel_info->save();
            return true;
        }else{
            // 修改状态
            $channel_info->status = 0;
            $channel_info->save();

            return false;
        }
    }

    // 推送给频道管理员审核
    private function review( $id )
    {
        $channel_info = LotteryChannelModel::get( $id );

        // 机器人配置
        $bot_config = $this->bot_config;
        $admin_users_ids = $bot_config['admin_users_ids'];

        $condition_text = '';
        if ($channel_info->lottery->conditions == 1){
            $condition_text = '开奖时间：' . $channel_info->lottery->condition_time;
        }
        if ($channel_info->lottery->conditions == 2){
            $condition_text = '开奖人数：' . $channel_info->lottery->condition_hot;
        }

        switch ( $channel_info->lottery->join_type ){
            case  1 :   // 群内抽奖关键词
                $keyword_text = '关键词：' . $channel_info->lottery->keyword . PHP_EOL;
                break;
            case  2 :   // 私聊机器人抽奖无关键词
                $keyword_text = '';
                break;
        }

        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '✅ 通过',
            'callback_data'          => 'review-ratify-' . $id,
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '🚫 拒绝',
            'callback_data'          => 'review-reject-' . $id,
        ]);

        $data = [
            'text'    => '🔔🔔 有活动需审核 🔔🔔' . PHP_EOL . PHP_EOL .
                '抽奖群：' . $channel_info->lottery->chat_title . PHP_EOL .
                '奖品名称：' . $channel_info->lottery->title . PHP_EOL .
                '奖品数量：' . $channel_info->lottery->number . PHP_EOL .
                '开奖方式：' . $this->conditions[$channel_info->lottery->conditions] . PHP_EOL .
                $condition_text . PHP_EOL .
                '参与方式：' . $this->join_type[$channel_info->lottery->join_type] . PHP_EOL .
                $keyword_text .
                '群组地址：' . $channel_info->lottery->chat_url,
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => new InlineKeyboard($keyboard_buttons),
        ];

        // 机器人管理员列表
        if (empty($admin_users_ids) == false){
            $admin_users = explode(PHP_EOL, $admin_users_ids);
        }else{
            $admin_users = [];
        }

        // 通知机器人管理员
        foreach ($admin_users as $chat_id){
            $data['chat_id'] = $chat_id;
            Queue::push('app\tgbot\job\AutoSendMessage', [
                'method' => 'sendMessage',
                'data' => $data,
            ], 'AutoSendMessage');
        }

        return true;
    }

}