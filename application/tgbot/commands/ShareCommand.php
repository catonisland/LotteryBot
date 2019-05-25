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
use app\tgbot\model\LotteryChannel;

/**
 * User "/share" command
 *
 * 分享抽奖活动的命令
 */
class ShareCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'share';

    /**
     * @var string
     */
    protected $description = '频道分享按钮';

    /**
     * @var string
     */
    protected $usage = '/share';

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
        $text    = htmlentities(trim($message->getText(true)));

        $param = explode('-', $text);// 解析参数
        $share_id = isset($param[1]) ? $param[1] : null;

        $channel_info = LotteryChannel::get(['id' => $share_id, 'status' => 1]);
        if (!$channel_info){
            $data = [
                'chat_id' => $chat_id,
                'text' => '此活动无法分享',
            ];
            return Request::sendMessage($data);
        }

        $conditions = [
            1 => '按时间自动开奖',
            2 => '按人数自动开奖',
        ];
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '加入',
            'url'          => $channel_info->lottery->chat_url,
        ]);
        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '分享',
            'switch_inline_query'          => $share_id,
        ]);
        $condition_text = '';
        if ($channel_info->lottery->conditions == 1){
            $condition_text = '开奖时间：' . $channel_info->lottery->condition_time;
        }
        if ($channel_info->lottery->conditions == 2){
            $condition_text = '开奖人数：' . $channel_info->lottery->condition_hot;
        }
        $data = [
            'chat_id' => $chat_id,
            'text'    => '抽奖群：' . $channel_info->lottery->chat_title . PHP_EOL .
                '奖品名称：' . $channel_info->lottery->title . PHP_EOL .
                '奖品数量：' . $channel_info->lottery->number . PHP_EOL .
                '开奖条件：' . $conditions[$channel_info->lottery->conditions] . PHP_EOL .
                $condition_text . PHP_EOL . PHP_EOL .
                '具体参与方式请在群内发送『怎么抽奖』进行查询。',
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => new InlineKeyboard($keyboard_buttons),
        ];
        return Request::sendMessage($data);
    }
}