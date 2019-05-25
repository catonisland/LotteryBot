<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * User "/leave" command
 *
 * 此命令可以让机器人主动退出群组或频道
 */
class LeaveCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'leave';

    /**
     * @var string
     */
    protected $description = '退出群组或频道';

    /**
     * @var string
     */
    protected $usage = '/leave';

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
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(){
        $message = $this->getMessage();
        $chat = $message->getChat();
        $type = $chat->getType();
        $chat_id = $chat->getId();
        $message_id = $message->getMessageId();
        $user_id = $message->getFrom()->getId();

        // 此命令只能在群组/频道中使用
        if ($type == 'private'){
            $data = [
                'chat_id' => $chat_id,
                'reply_to_message_id' => $message_id,
                'text'    => '如果你是群组管理员，请在群里使用此命令',
            ];
            return Request::sendMessage($data);
        }

        // 获取群组管理员
        $administrator_ids = cache('chat_asmin_ids_' . $chat_id);
        if ( !$administrator_ids ){
            $result = Request::getChatAdministrators(['chat_id' => $chat_id]);
            if ($result->isOk()){
                $data = $result->getResult();
                $administrator_ids = [];
                foreach ($data as $key => $user){
                    $administrator_ids[] = $data[$key]->user['id'];
                }
                cache('chat_asmin_ids_' . $chat_id, $administrator_ids, 3600);
            }
        }

        // 判断命令执行者是否为群组管理员
        if ($this->telegram->isAdmin($user_id) != true && in_array($user_id, $administrator_ids) != true){
            $data = [
                'chat_id' => $chat_id,
                'reply_to_message_id' => $message_id,
                'text'    => '非群组管理员不能使用此命令',
            ];
            return Request::sendMessage($data);
        }

        return Request::leaveChat(['chat_id'=>$chat_id]);
    }
}