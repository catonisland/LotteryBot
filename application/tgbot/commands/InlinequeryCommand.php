<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;
use app\tgbot\model\LotteryChannel;

/**
 * Inline query command
 */
class InlinequeryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'inlinequery';

    /**
     * @var string
     */
    protected $description = 'Reply to inline query';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {

        $inline_query = $this->getUpdate()->getInlineQuery();
        $query        = trim($inline_query->getQuery());
        //$offset        = $inline_query->getOffset();
        //$page        = empty($offset) ? 1 : $offset;

        // 机器人配置
        $channel_username = module_config('tgbot.channel_username');

        // 空查询或查询字符串少于 2 的时候返回空
        if ( empty($query) ){
            return Request::answerInlineQuery([
                'inline_query_id' => $this->getUpdate()->getInlineQuery()->getId(),
                'cache_time' => 60,
            ]);
        }

        // 查询数据
        $channel_info = LotteryChannel::get(['id' => $query, 'status' => 1]);

        // 查询不到内容返回空
        if ( !$channel_info ){
            return Request::answerInlineQuery([
                'inline_query_id' => $this->getUpdate()->getInlineQuery()->getId(),
                'cache_time' => 60,
            ]);
        }

        $conditions = [
            1 => '按时间自动开奖',
            2 => '按人数自动开奖',
        ];

        $condition_text = '';
        if ($channel_info->lottery->conditions == 1){
            $condition_text = '开奖时间: ' . $channel_info->lottery->condition_time;
        }
        if ($channel_info->lottery->conditions == 2){
            $condition_text = '开奖人数: ' . $channel_info->lottery->condition_hot;
        }

        $keyboard_buttons[] = new InlineKeyboardButton([
            'text'          => '加群参与抽奖',
            'url'          => $channel_info->lottery->chat_url,
        ]);

        $data[0] = [
            'type' => 'article',
            'id' => $channel_info->id,
            'title' => $channel_info->lottery->title,
            'description' => $channel_info->lottery->chat_title,
            //'thumb_url' => $thumb_url,
            'input_message_content' => [
                'message_text' =>   '抽奖群: ' . $channel_info->lottery->chat_title . PHP_EOL .
                    '奖品名称: ' . $channel_info->lottery->title . PHP_EOL .
                    '奖品数量: ' . $channel_info->lottery->number . PHP_EOL .
                    '开奖条件: ' . $conditions[$channel_info->lottery->conditions] . PHP_EOL .
                    $condition_text . PHP_EOL .
                    '具体参与方式请在群内发送『怎么抽奖』进行查询。' . PHP_EOL . PHP_EOL .
                    '以上信息来自 @' . $channel_username . ' 频道',
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
            ],
            'reply_markup' => new InlineKeyboard($keyboard_buttons),
        ];
        // trace($list , 'info');

        // trace($data , 'info');
        $results = Request::answerInlineQuery([
            'inline_query_id' => $this->getUpdate()->getInlineQuery()->getId(),
            'cache_time' => 3600 * 12,
            'results'=> $data,
            //'next_offset'=> $page+1,
        ]);
        // trace($results , 'info');
        return $results;
    }
}