<?php
/**
 * Created by PhpStorm.
 * User: wjq
 * Date: 2018/11/18
 * Time: 上午12:07
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use think\Db;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;

class WaitCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'wait';

    /**
     * @var string
     */
    protected $description = '待开奖的活动';

    /**
     * @var string
     */
    protected $usage = '/wait';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * 是否仅允许私聊机器人时使用
     *
     * @var bool
     */
    protected $private_only = true;

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
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        // 翻页操作
        $callback_query = $this->getUpdate()->getCallbackQuery();
        if ($callback_query){
            $message = $callback_query->getMessage();
            $message_id = $message->getMessageId();
            $user_id = $callback_query->getFrom()->getId();
            $chat_id = $message->getChat()->getId();
            $query_data = $callback_query->getData();
        }else{
            $message = $this->getMessage();
            $message_id = $message->getMessageId();
            $user_id = $message->getFrom()->getId();
            $chat_id = $message->getChat()->getId();
        }

        $LotteryUser = Db::name('tgbot_lottery_user');
        $total = $LotteryUser->alias('tlu')
                            ->join('__TGBOT_LOTTERY__ tl','tlu.lottery_id = tl.id')
                            ->where('tlu.user_id', $user_id)
                            ->where('tl.status', 1)
                            ->count('tlu.user_id');

        if (isset($query_data)){
            $param = explode('-', $query_data);
            if ($param[1]<1){
                $page = 1;
            }elseif ($param[1]>$total){
                $page = $total;
            }else{
                $page = $param[1];
            }
        }else{
            $page = 1;
        }

        $LotteryUser = Db::name('tgbot_lottery_user');
        $user_list = $LotteryUser->field('tlu.id, tlu.user_id, tlu.lottery_id, tlu.first_name, tlu.last_name, tlu.username, tlu.create_time, tl.chat_title, tl.title, tl.number, tl.conditions, tl.condition_time, tl.condition_hot, tl.hot')
                                ->alias('tlu')
                                ->join('__TGBOT_LOTTERY__ tl','tlu.lottery_id = tl.id')
                                ->where('tlu.user_id', $user_id)
                                ->where('tl.status', 1)
                                ->page($page, 1)
                                ->order('tlu.create_time desc')
                                //->fetchSql(true)
                                ->select();
        //trace($user_list, 'error');

        // 回复一个带按钮的消息
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '上一页',
            'callback_data'          => 'wait-' . ($page-1),
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => ($total?$page:0) .'/'. $total,
            'callback_data'          => 'wait-' . $page,
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '下一页',
            'callback_data'          =>  'wait-' . ($page+1),
        ]);

        $conditions = [
            1 => '按时间自动开奖',
            2 => '按人数自动开奖',
        ];

        $text = '';
        foreach ($user_list as $info){
            $condition_text = '';
            if ($info['conditions'] == 1){
                $condition_text = '开奖时间：' .  date('Y-m-d H:i', $info['condition_time']);
            }
            if ($info['conditions'] == 2){
                $condition_text = '开奖人数：' . $info['condition_hot'];
            }

            $text .=    '群组：' . $info['chat_title'] . PHP_EOL .
                '活动：' . $info['title'] . PHP_EOL .
                '奖品数量：' . $info['number'] . PHP_EOL .
                '开奖方式：' . $conditions[$info['conditions']] . PHP_EOL .
                $condition_text . PHP_EOL .
                '参与时间：' . date('Y-m-d H:i', $info['create_time']) . PHP_EOL .
                '参与人数：' . $info['hot'] . PHP_EOL .
                '状态：待开奖';
        }

        if ($callback_query){
            $data = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text'    => $text ?: '未查询到待开奖的活动',
                'reply_markup' => new InlineKeyboard($keyboard_buttons),
                'parse_mode' => 'html',
                'disable_notification'=>true,
                'disable_web_page_preview'=>true,
            ];
            return Request::editMessageText($data);
        }else{
            $data = [
                'chat_id' => $chat_id,
                'text'    => $text ?: '未查询到待开奖的活动',
                'reply_to_message_id' => $message_id,
                'reply_markup' => new InlineKeyboard($keyboard_buttons),
                'parse_mode' => 'html',
                'disable_notification'=>true,
                'disable_web_page_preview'=>true,
            ];
            return Request::sendMessage($data);
        }
    }
}