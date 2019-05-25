<?php
/**
 * Created by LotteryBot.
 * User: TingV
 * Date: 2019-05-25
 * Time: 18:18
 */
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use app\tgbot\telegram\Conversation;

/**
 * User "/review" command
 *
 * 审核推送信息
 */

class ReviewCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'review';

    /**
     * @var string
     */
    protected $description = '审核推送信息';

    /**
     * @var string
     */
    protected $usage = '/review';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = false;

    /**
     * If this command is enabled
     *
     * @var boolean
     */
    protected $enabled = true;

    /**
     * @var bool
     */
    protected $need_mysql = false;

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
        $callback_query = $this->getUpdate()->getCallbackQuery();

        // 此命令只能在 callbackquery 中生效
        if (!$callback_query) {
            return Request::emptyResponse();
        }

        $user_id = $callback_query->getFrom()->getId();
        $message = $callback_query->getMessage();
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $message_id = $message->getMessageId();
        $text = $callback_query->getData();

        $param = explode('-', $text);

        if (isset($param[1]) == false || isset($param[2]) == false){
            return false;
        }

        if (intval($param[2]) < 1){
            return false;
        }

        // 取消会话
        $conversation = new Conversation( $user_id, $user_id );
        if ( $conversation->exists() ) {
            $conversation->cancel();
        }

        $channel_info = \app\tgbot\model\LotteryChannel::get( $param[2] );

        // 判断活动是否存在
        if ( !$channel_info ){
            return Request::answerCallbackQuery([
                'callback_query_id' => $callback_query->getId(),
                'text' => '待审核的活动不存在',
            ]);
        }

        // 判断活动是已被审核过
        if ($channel_info->status != 0){
            return Request::answerCallbackQuery([
                'callback_query_id' => $callback_query->getId(),
                'text' => '此活动已审核过了',
            ]);
        }

        if ( !$channel_info->lottery ){
            return Request::answerCallbackQuery([
                'callback_query_id' => $callback_query->getId(),
                'text' => '活动不存在',
            ]);
        }

        // 判断活动是否已结束
        if ( $channel_info->lottery->status == 0 ){
            return Request::answerCallbackQuery([
                'callback_query_id' => $callback_query->getId(),
                'text' => '活动已开奖',
            ]);
        }

        // 判断活动是否已关闭
        if ( $channel_info->lottery->status == -1 ){
            return Request::answerCallbackQuery([
                'callback_query_id' => $callback_query->getId(),
                'text' => '活动已关闭',
            ]);
        }

        // 通过
        if ($param[1] == 'ratify'){
            // 推送到频道
            $push_channel = \Longman\TelegramBot\Commands\UserCommands\CreateCommand::push_channel( $param[2] );
            if ($push_channel){ // 推送成功
                return Request::answerCallbackQuery([
                    'callback_query_id' => $callback_query->getId(),
                    'text' => '审核已通过',
                ]);
            }else{
                return Request::answerCallbackQuery([
                    'callback_query_id' => $callback_query->getId(),
                    'text' => '推送到频道失败',
                ]);
            }
        }

        // 拒绝
        if ($param[1] == 'reject'){
            $channel_info->status    = -1;
            $channel_info->save();
            return Request::answerCallbackQuery([
                'callback_query_id' => $callback_query->getId(),
                'text' => '审核已拒绝',
            ]);
        }


    }

}