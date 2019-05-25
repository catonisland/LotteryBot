<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * User "/help" command
 *
 * Command that lists all available commands and displays them in User and Admin sections.
 */
class HelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'help';

    /**
     * @var string
     */
    protected $description = '显示机器人帮助信息';

    /**
     * @var string
     */
    protected $usage = '/help 或 /help <command>';

    /**
     * @var string
     */
    protected $version = '1.3.0';

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
     * Show in Help
     *
     * @var bool
     */
    protected $show_in_help = false;

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->telegram->executeCommand('start');
    }
}
