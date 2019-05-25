<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use app\tgbot\model\Lottery;
use app\tgbot\model\LotteryUser;
use app\tgbot\model\LotteryPrize;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;

class ListCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'list';

    /**
     * @var string
     */
    protected $description = '参与的抽奖活动';

    /**
     * @var string
     */
    protected $usage = '/list';

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

        $LotteryUser = new LotteryUser();
        $total = $LotteryUser->where('user_id', $user_id)->count('user_id');

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

        $user_list = $LotteryUser->where('user_id', $user_id)->page($page, 1)->order('create_time desc')->select();

        // 回复一个带按钮的消息
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '上一页',
            'callback_data'          => 'list-' . ($page-1),
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => ($total?$page:0) .'/'. $total,
            'callback_data'          => 'list-' . $page,
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '下一页',
            'callback_data'          =>  'list-' . ($page+1),
        ]);

        $conditions = [
            1 => '按时间自动开奖',
            2 => '按人数自动开奖',
        ];

        $status_code = [
            '-1'=> '已关闭',
            '0' => '已开奖',
            '1' => '待开奖',
        ];
        $text = '';
        foreach ($user_list as $info){
            $condition_text = '';
            if ($info->lottery->conditions == 1){
                $condition_text = '开奖时间：' . $info->lottery->condition_time;
            }
            if ($info->lottery->conditions == 2){
                $condition_text = '开奖人数：' . $info->lottery->condition_hot;
            }

            if ($info->lottery->status == 0){   // 已开奖
                // 获取中奖者名单
                $lottery_info = Lottery::get(['id'=>$info->lottery->id]);
                $lottery_user = $lottery_info->prizes()->where('status',1)->select();
                $lottery_user_text = '';
                if ( count($lottery_user)>0 ){
                    $lottery_user_text .= PHP_EOL . '中奖者：' . PHP_EOL;
                    foreach ($lottery_user as $user_index => $user_info){
                        $lottery_user_text .= ($user_index+1) . '. ' . $user_info->first_name .' '. $user_info->last_name . PHP_EOL;
                    }
                }

                // 中奖状态
                $prize = LotteryPrize::get(['lottery_id'=>$info->lottery->id, 'user_id'=>$info->user_id]);
                if ($prize){
                    $status =  $prize->status==-1 ? '开奖期间退群，获奖资格被取消' : '已中奖' . $lottery_user_text;
                }else{
                    $status = '未中奖' . $lottery_user_text;
                }
            }else{  // 未开奖
                $status = $status_code[$info->lottery->status];
            }

            $text .=    '群组：' . $info->lottery->chat_title . PHP_EOL .
                '活动：' . $info->lottery->title . PHP_EOL .
                '奖品数量：' . $info->lottery->number . PHP_EOL .
                '开奖方式：' . $conditions[$info->lottery->conditions] . PHP_EOL .
                $condition_text . PHP_EOL .
                '参与时间：' . date('Y-m-d H:i', $info->create_time) . PHP_EOL .
                '参与人数：' . $info->lottery->hot . PHP_EOL .
                '状态：' . $status;

        }

        if ($callback_query){
            $data = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text'    => $text ?: '你还未参与任何抽奖活动',
                'reply_markup' => new InlineKeyboard($keyboard_buttons),
                'parse_mode' => 'html',
                'disable_notification'=>true,
                'disable_web_page_preview'=>true,
            ];
            return Request::editMessageText($data);
        }else{
            $data = [
                'chat_id' => $chat_id,
                'text'    => $text ?: '你还未参与任何抽奖活动',
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