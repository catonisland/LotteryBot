<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\SystemCommand;
use app\tgbot\telegram\Conversation;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = '处理通用消息';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * 执行命令
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $type = $message->getChat()->getType();

        if ( $type == 'private' ){
            //如果存在会话，则执行命令会话
            $conversation = new Conversation(
                $this->getMessage()->getFrom()->getId(),
                $this->getMessage()->getChat()->getId()
            );

            //如果会话命令存在则获取并执行它
            if ($conversation->exists() && ($command = $conversation->getCommand())) {
                return $this->telegram->executeCommand($command);
            }
        }

        return Request::emptyResponse();
    }
}
