<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use app\tgbot\telegram\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Keyboard;

/**
 * User "/cancel" command
 *
 * 此命令可以取消当前活动的会话并返回一条消息，让用户知道它是哪个会话。 如果没有激活会话，则返回的消息会显示。
 */
class CancelCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cancel';

    /**
     * @var string
     */
    protected $description = '清空会话状态';

    /**
     * @var string
     */
    protected $usage = '/cancel';

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
        $text = '没有活跃的会话，无需清理。';

        // 取消当前会话（如果有）
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();
            $text = '_' . $conversation_command . '_ 命令的会话已取消。';
        }

        return $this->removeKeyboard($text);
    }

    /**
     * 删除键盘并输出文本
     *
     * @param string $text
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    private function removeKeyboard($text)
    {
        return Request::sendMessage([
            'reply_markup' => Keyboard::remove(['selective' => true]),
            'chat_id'      => $this->getMessage()->getChat()->getId(),
            'text'         => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}