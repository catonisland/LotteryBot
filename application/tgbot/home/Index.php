<?php
namespace app\tgbot\home;

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\Commands\SystemCommands\CallbackqueryCommand;
use Longman\TelegramBot\Entities\CallbackQuery;

/**
 * 机器人 Webhook 控制器
 */
class Index
{
    /**
     * Webhook 入口
     */
    public function index()
    {
        $config = module_config('tgbot.bot_token,bot_username,bot_webhook_password,admin_users_ids');
        $bot_api_key  = $config['bot_token'];
        $bot_username = $config['bot_username'];
        $bot_webhook_password = $config['bot_webhook_password'];
        $admin_users_ids = $config['admin_users_ids'];

        $password = input('password', '');
        if ($password !== $bot_webhook_password){
            abort(403, '密码错误');
            return false;
        }

        // Define all paths for your custom commands in this array (leave as empty array if not used)
        $commands_paths = [
            APP_PATH . request()->module() . '/commands/',
        ];

        // Define all IDs of admin users in this array (leave as empty array if not used)
        if (empty($admin_users_ids) == false){
            $admin_users = explode(PHP_EOL, $admin_users_ids);
            array_walk($admin_users, function (&$value){
                $value = (int)$value;   // ID 必须是整数类型
            });
        }else{
            $admin_users = [];
        }

        try {
            // Create Telegram API object
            $telegram = new Telegram($bot_api_key, $bot_username);

            // Add commands paths containing your custom commands
            $telegram->addCommandsPaths($commands_paths);

            // Enable admin users
            $telegram->enableAdmins($admin_users);

            // 在命令里处理 callbackquery
            CallbackqueryCommand::addCallbackHandler(function (CallbackQuery $query) use ($telegram) {
                $data = $query->getData();
                $param = explode('-', $data);
                $command = isset($param[0]) ? $param[0] : $data;
                $telegram->executeCommand($command);
            });

            // Handle telegram webhook request
            $telegram->handle();

        } catch (TelegramException $e) {
            // Silence is golden!
            // log telegram errors
            // echo $e->getMessage();
        } catch (TelegramLogException $e) {
            // Silence is golden!
            // Uncomment this to catch log initialisation errors
            //echo $e;
        }
    }
}