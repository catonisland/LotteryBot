<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use app\tgbot\model\Lottery;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

class CloseCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'close';

    /**
     * @var string
     */
    protected $description = '关闭正则进行中的抽奖活动';

    /**
     * @var string
     */
    protected $usage = '/close';

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
        $message = $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $text    = intval(trim($message->getText(true)));

        // 检查 ID
        if ( empty($text) ){
            $data = [
                'chat_id' => $chat_id,
                'text'    => '请先通过 /released 命令查询活动 ID，然后再使用 <i>/close ID</i> 命令来关闭指定的抽奖活动。',
                'parse_mode' => 'html',
                'disable_notification'=> true,
                'disable_web_page_preview'=> true,
            ];
            return Request::sendMessage($data);
        }

        // 查询活动
        $lottery_info = Lottery::get(['user_id'=>$user_id, 'id'=>$text]);

        // 活动不存在
        if ( !$lottery_info ){
            $data = [
                'chat_id' => $chat_id,
                'text'    => '此活动不存在。',
                'disable_notification'=> true,
                'disable_web_page_preview'=> true,
            ];
            return Request::sendMessage($data);
        }

        // 活动已结束
        if ( $lottery_info->status != 1 ){
            $data = [
                'chat_id' => $chat_id,
                'text'    => '此活动已结束，无法关闭。',
                'disable_notification'=> true,
                'disable_web_page_preview'=> true,
            ];
            return Request::sendMessage($data);
        }

        $lottery_info->status = -1;

        if($lottery_info->save() !== false){
            $msg = '已关闭。';
        }else{
            $msg = '关闭失败。';
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => '<b>' . $lottery_info->title . '</b> 抽奖活动'  . $msg,
            'parse_mode' => 'html',
        ];
        return Request::sendMessage($data);
    }
}