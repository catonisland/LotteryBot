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

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

/**
 * Start command
 *
 * @todo Remove due to deprecation!
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = '开始命令';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();            // Get Message object

        $chat_id = $message->getChat()->getId();   // Get the current Chat ID

        $text    = trim($message->getText(true));

        Request::sendChatAction(['chat_id' => $chat_id, 'action'=>'typing']);

        // 默认回复信息
        $data = [
            'chat_id' => $chat_id,
            'text'    =>
                '· 如果你是活动参与者，中奖后需到我这里领取奖品。' . PHP_EOL .
                '· 如果你是群组管理员，邀请我进你所管理的群，身份为管理员，并给与删除消息和置顶消息的权限。通过 _/create_ 命令可以在群里发起抽奖活动。' . PHP_EOL . PHP_EOL .

                '你也可以使用下面的命令来控制我：' . PHP_EOL .PHP_EOL .

                '*参与者*' . PHP_EOL .
                '/list - 已参与的活动' . PHP_EOL .
                '/wait - 待开奖的活动' . PHP_EOL .
                '/winlist - 领取奖品' . PHP_EOL . PHP_EOL .

                '*发起者*' . PHP_EOL .
                '/create - 在群组中使用此命令来创建一个抽奖活动' . PHP_EOL .
                '/released - 查询已发布的抽奖活动' . PHP_EOL .
                '/edit - 命令后面加上活动 ID 可以修改已发布的活动 ( ID 通过 _/released_ 命令查询)' . PHP_EOL .
                '/close - 命令后面加上活动 ID 可以关闭正在进行中的活动 ( ID 通过 _/released_ 命令查询)' . PHP_EOL .
                '/leave - 让抽奖助手自己离开你的群组' . PHP_EOL . PHP_EOL .

                '*其他命令*' . PHP_EOL .
                '/cancel - 取消当前会话 ( 例如：取消当前正在创建的抽奖活动 )',
            'parse_mode' => 'Markdown',
            'disable_notification'=>true,
            'disable_web_page_preview'=>true,
        ];

        // 没有参数
        if (empty($text)){
            return Request::sendMessage($data);
        }

        // 解析参数
        $param = explode('-', $text);

        // 参数不对
        if (is_array($param) == false || count($param) != 2){
            return Request::sendMessage($data);
        }

        $action = $param[0];    // 操作

        // 执行操作
        $this->telegram->executeCommand($action);
    }
}
