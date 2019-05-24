<?php
namespace app\tgbot\telegram;

use app\tgbot\model\Conversation as ConversationModel;

/**
 * Telegram 会话
 */
class Conversation
{
    /**
     * 模型的对象实例
     *
     * @var array|null
     */
    protected $conversation;

    /**
     * 受保护的会话内容
     *
     * @var mixed
     */
    protected $protected_notes;

    /**
     * 会话内容
     *
     * @var mixed
     */
    public $notes;

    /**
     * Telegram user id
     *
     * @var int
     */
    protected $user_id;

    /**
     * Telegram chat id
     *
     * @var int
     */
    protected $chat_id;

    /**
     * 如果会话处于活动状态，则执行命令
     *
     * @var string
     */
    protected $command;

    /**
     * 会话初始化
     *
     * @param int    $user_id
     * @param int    $chat_id
     * @param string $command
     */
    public function __construct($user_id, $chat_id, $command = null)
    {
        $this->user_id = $user_id;
        $this->chat_id = $chat_id;
        $this->command = $command;

        //尝试加载已存在的会话
        if (!$this->load() && $command !== null) {
            //创建一个新的会话
            $this->start();
        }
    }

    /**
     * 清除所有会话变量
     *
     * @return bool 始终返回true，为了在 if 语句中允许此方法。
     */
    protected function clear()
    {
        $this->conversation    = null;
        $this->protected_notes = null;
        $this->notes           = null;

        return true;
    }

    /**
     * 从数据库中加载会话
     *
     * @return bool
     */
    protected function load()
    {
        //获取一个活动的会话
        $conversation = ConversationModel::get(['user_id'=>$this->user_id, 'chat_id'=>$this->chat_id, 'status'=>1]);
        if ($conversation) {
            $this->conversation = $conversation;

            //如果尚未传递命令，则从原会话中加载命令
            $this->command = $this->command ?: $this->conversation->command;

            if ($this->command !== $this->conversation->command) {
                $this->cancel();
                return false;
            }

            //加载会话备注
            $this->protected_notes = $this->conversation->notes;
            $this->notes           = $this->protected_notes;
        }

        return $this->exists();
    }

    /**
     * 检查会话是否已存在
     *
     * @return bool
     */
    public function exists()
    {
        return ($this->conversation !== null);
    }

    /**
     * 如果命令存在，且当前没有活动的会话，则创建一个新的会话
     *
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    protected function start()
    {
        if ($this->command
            && !$this->exists()
            &&ConversationModel::create([
                'user_id'  =>  $this->user_id,
                'chat_id'  =>  $this->chat_id,
                'command'  =>  $this->command,
                'status'  =>  1,
                'notes'  =>  null,
            ])
        ) {
            return $this->load();
        }

        return false;
    }

    /**
     * 软删除当前会话
     *
     * @return bool
     */
    public function stop()
    {
        return ($this->updateStatus(-1) && $this->clear());
    }

    /**
     * 取消当前会话
     *
     * @return bool
     */
    public function cancel()
    {
        return ($this->updateStatus(0) && $this->clear());
    }

    /**
     * 更新当前会话的状态
     *
     * @param string $status
     *
     * @return bool
     */
    protected function updateStatus($status)
    {
        if ($this->exists()) {
            $conversation = new ConversationModel;
            $result = $conversation->save([
                'status'  => $status,
            ],[
                'id'      => $this->conversation->id,
                'status'  => 1,
                'user_id' => $this->user_id,
                'chat_id' => $this->chat_id,
            ]);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * 序列化存储 notes 的内容
     *
     * @return bool
     */
    public function update()
    {
        if ($this->exists()) {
            $conversation = new ConversationModel;
            $result = $conversation->save(['notes' => $this->notes],['id' => $this->conversation->id]);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * 从对话中获取要执行的命令
     *
     * @return string|null
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * 获取字段
     *
     * @return string|null
     */
    public function getAttr( $field )
    {
        return $this->conversation->$field;
    }
}