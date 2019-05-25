<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use app\tgbot\model\Lottery;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;

class ReleasedCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'released';

    /**
     * @var string
     */
    protected $description = '查看你发起的抽奖活动';

    /**
     * @var string
     */
    protected $usage = '/released';

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

        $Lottery = new Lottery();
        $total = $Lottery->where(['user_id'=>$user_id])->count('id');
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

        $lottery_list = $Lottery->where(['user_id'=>$user_id])->page($page, 1)->order('id desc')->select();

        // 回复一个带按钮的消息
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '上一页',
            'callback_data'          => 'released-' . ($page-1),
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => ($total?$page:0) .'/'. $total,
            'callback_data'          => 'released-' . $page,
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '下一页',
            'callback_data'          =>  'released-' . ($page+1),
        ]);

        $conditions = [
            1 => '按时间自动开奖',
            2 => '按人数自动开奖',
        ];

        $join_type = [
            1 => '群内发送关键词参与抽奖',
            2 => '私聊机器人参与抽奖',
        ];

        $status_code = [
            '-1'=> '已关闭',
            '0' => '已开奖',
            '1' => '待开奖',
        ];
        $text = '';
        foreach ($lottery_list as $info){
            $prizes = $info->prizes;
            $prize_text = '';
            foreach ($prizes as $index => $prize){
                $condition_text = '';
                if ($info->conditions == 1){
                    $condition_text = '开奖时间：' . $info->condition_time;
                }
                if ($info->conditions == 2){
                    $condition_text = '开奖人数：' . $info->condition_hot;
                }

                switch ( $info->join_type ){
                    case  1 :   // 群内抽奖关键词
                        $keyword_text = '关键词：' . $info->keyword . PHP_EOL;
                        break;
                    case  2 :   // 私聊机器人抽奖无关键词
                        $keyword_text = '';
                        break;
                }

                if ($prize->status == 1){
                    $status_text = "<a href=\"tg://user?id={$prize->user_id}\">@{$prize->first_name} {$prize->last_name}</a>";
                }elseif($prize->status == -1){
                    $status_text = "<a href=\"tg://user?id={$prize->user_id}\">@{$prize->first_name} {$prize->last_name}</a> 已退群，奖品未发放";
                }else{
                    $status_text = '未发放';
                }
                $prize_text .= ($index+1) . '. ' . $prize->prize . ' ( ' . $status_text . ' )' . PHP_EOL ;
            }

            $text .=
                '活动 ID：' . $info->id . PHP_EOL .
                '活动群组：' . $info->chat_title . PHP_EOL .
                '奖品名称：' . $info->title . PHP_EOL .
                '奖品数量：' . $info->number . PHP_EOL .
                '开奖方式：' . $conditions[$info->conditions] . PHP_EOL .
                $condition_text . PHP_EOL .
                '参与方式：' . $join_type[$info->join_type] . PHP_EOL .
                $keyword_text .
                '发布时间：' . date('Y-m-d H:i', $info->getData('create_time')) . PHP_EOL .

                '参与人数：' . $info->hot . PHP_EOL .
                '奖品列表：' . PHP_EOL . $prize_text .
                '状态：' . $status_code[$info->status];
        }

        if ($callback_query){
            $data = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text'    => $text ?: '你还未发起任何抽奖活动',
                'reply_markup' => new InlineKeyboard($keyboard_buttons),
                'parse_mode' => 'html',
                'disable_notification'=>true,
                'disable_web_page_preview'=>true,
            ];
            return Request::editMessageText($data);
        }else{
            $data = [
                'chat_id' => $chat_id,
                'text'    => $text ?: '你还未发起任何抽奖活动',
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