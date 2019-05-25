<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * User "/id" command
 *
 * 获取 id
 */
class IdCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'id';

    /**
     * @var string
     */
    protected $description = '清空会话状态';

    /**
     * @var string
     */
    protected $usage = '/id';

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
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $reply_to_message = $message->getReplyToMessage();

        if ( isset($reply_to_message) == false ){
            $data = [
                'chat_id' => $chat_id,
                'text' => '请先转发一条消息给我，然后再用 /id 命令回复这条被转发的消息来查询 ID',
            ];
            return Request::sendMessage($data);
        }

        $forward_from_chat = $reply_to_message->getForwardFromChat();
        if ( isset($forward_from_chat) == false ){
            $forward_from = $reply_to_message->getForwardFrom();
            if (isset($forward_from)){
                $data = [
                    'chat_id' => $chat_id,
                    'text' => ' ID： <code>' . $forward_from->getId() .'</code>',
                    'parse_mode' => 'html',
                ];
                return Request::sendMessage($data);
            }

            $data = [
                'chat_id' => $chat_id,
                'text' => '此消息的 ID 无法获取',
            ];
            return Request::sendMessage($data);
        }

        $id = $forward_from_chat->getId();

        $data = [
            'chat_id' => $chat_id,
            'text' => ' ID： <code>' . $id .'</code>',
            'parse_mode' => 'html',
        ];
        return Request::sendMessage($data);
    }
}