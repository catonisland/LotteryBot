<?php
/**
 * Created by LotteryBot.
 * User: TingV
 * Date: 2019-05-25
 * Time: 01:50
 */

namespace app\tgbot\admin;

use app\admin\controller\Admin;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;

/**
 * TG 机器人管理控制器
 * @package app\tgbot\admin
 */
class Index extends Admin
{
    public function index()
    {
        return $this->moduleConfig();
    }

    public function setWebhook()
    {

        $config = module_config('tgbot.bot_token,bot_username,bot_webhook_password,bot_webhook_max_connections');

        try {
            // Create Telegram API object
            $telegram = new Telegram($config['bot_token'], $config['bot_username']);

            // Set webhook
            $hook_url = request()->domain() . '/tgbot/index/index/password/' . $config['bot_webhook_password'];
            $result = $telegram->setWebhook($hook_url, ['max_connections'=>$config['bot_webhook_max_connections']]);

            // To use a self-signed certificate, use this line instead
            //$result = $telegram->setWebhook($hook_url, ['certificate' => $certificate_path]);

            if ($result->isOk()) {
                // echo $result->getDescription();
                $this->success('设置成功!');
            }
        } catch (TelegramException $e) {
            $this->error('设置失败：' . $e->getMessage());
        }
    }

    public function removeWebhook()
    {
        $config = module_config('tgbot.bot_token,bot_username');

        try {
            // Create Telegram API object
            $telegram = new Telegram($config['bot_token'], $config['bot_username']);

            // Delete webhook
            $result = $telegram->deleteWebhook();

            if ($result->isOk()) {
                // echo $result->getDescription();
                $this->success('移除成功!');
            }
        } catch (TelegramException $e) {
            $this->error('移除失败：' . $e->getMessage());
        }
    }

}