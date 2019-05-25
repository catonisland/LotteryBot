<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use app\tgbot\model\Lottery;
use app\tgbot\telegram\Conversation;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Keyboard;

class EditCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'edit';

    /**
     * @var string
     */
    protected $description = '修改抽奖活动';

    /**
     * @var string
     */
    protected $usage = '/edit';

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

    private $field = [
        'title' => '奖品名称',
        'condition_time' => '开奖时间',
        'condition_hot' => '开奖人数',
        'cancel' => '放弃修改',
    ];

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
        $text    = trim($message->getText(true));

        $conversation = new Conversation($user_id, $chat_id, $this->getName());
        $notes = $conversation->notes;

        if (empty($notes) && is_numeric($text)){
            $lottery_id = intval($text);
        }else{
            $lottery_id = $notes['lottery_id'];
        }

        // 检查 ID
        if (empty($notes) && $lottery_id<1 ){
            $data = [
                'chat_id' => $chat_id,
                'text'    => '请先通过 /released 命令查询活动 ID，然后再使用 <i>/edit ID</i> 命令修改活动信息。',
                'parse_mode' => 'html',
                'disable_notification'=> true,
                'disable_web_page_preview'=> true,
            ];
            return Request::sendMessage($data);
        }

        // 查询活动
        $lottery_info = Lottery::get(['user_id'=>$user_id, 'id'=>$lottery_id]);

        // 活动不存在
        if ( !$lottery_info ){
            $data = [
                'chat_id' => $chat_id,
                'text'    => '此活动活动不存在。',
                'disable_notification'=> true,
                'disable_web_page_preview'=> true,
            ];
            return Request::sendMessage($data);
        }

        // 活动已结束，不能修改
        if ( $lottery_info->status != 1 ){
            $data = [
                'chat_id' => $chat_id,
                'text'    => '此活动已结束，不能修改。',
                'disable_notification'=> true,
                'disable_web_page_preview'=> true,
            ];
            return Request::sendMessage($data);
        }

        // 第一次发命令来的时候要求用户选择要修改的字段
        if (empty($notes)){
            switch ( $lottery_info->conditions ){
                case  1 :
                    unset($this->field['condition_hot']);
                    $condition_text = '开奖时间：' . $lottery_info->condition_time;
                    break;
                case  2 :
                    unset($this->field['condition_time']);
                    $condition_text = '开奖人数：' . $lottery_info->condition_hot;
                    break;
            }

            $text =    '群组：' . $lottery_info->chat_title . PHP_EOL .
                '奖品名称：' . $lottery_info->title . PHP_EOL .
                $condition_text . PHP_EOL .
                '参与人数：' . $lottery_info->hot . PHP_EOL . PHP_EOL .
                '请选择要修改的内容';
            $data = [
                'chat_id' => $chat_id,
                'text'    => $text,
                'reply_markup' =>  new Keyboard([
                    'resize_keyboard'   => true,
                    'one_time_keyboard' => true,
                    'selective'         => true,
                    'keyboard'          => [$this->field],
                ]),
                'parse_mode' => 'Markdown',
                'disable_notification'=> true,
                'disable_web_page_preview'=> true,
            ];

            // 记录当前操作
            $conversation->notes = [
                'lottery_id' => $lottery_info->id,
                'field' => '',
            ];
            $conversation->update();

            return Request::sendMessage($data);
        }

        // 获取用户要修改的字段
        if (isset($notes['field']) && empty($notes['field']) && empty($text) == false){
            $validate = validate('EditCommand');
            if(!$validate->check(['field'=>$text])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $field_name = array_search($text, $this->field);

            if ($field_name  == 'cancel'){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => '已放弃 <b>' . $lottery_info->title . '</b> 抽奖活动的修改。',
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => Keyboard::remove(['selective' => true]),
                ];
                $conversation->cancel(); // 取消会话
                return Request::sendMessage($data);
            }


            $notes['field'] = $field_name;
            $conversation->notes = $notes;
            $conversation->update();

            $data = [
                'chat_id' => $chat_id,
                'text'    => '请输入新的' . $text . '：',
                'reply_markup' => Keyboard::remove(['selective' => true]),
                'parse_mode' => 'html',
            ];
            return Request::sendMessage($data);
        }

        // 修改字段
        if (isset($notes['field']) && empty($notes['field']) == false && empty($text) == false){

            $validate = validate('EditCommand');
            if(!$validate->check([$notes['field'] => $text, 'hot'=>$lottery_info->hot])){
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $validate->getError(),
                ];
                return Request::sendMessage($data);
            }

            $field = $notes['field'];
            $lottery_info->$field = $text;

            if($lottery_info->save() !== false){
                $msg = '已修改';
            }else{
                $msg = '修改失败';
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => $this->field[$field] . $msg,
                'parse_mode' => 'html',
            ];
            $conversation->cancel(); // 取消会话
            return Request::sendMessage($data);
        }

    }
}