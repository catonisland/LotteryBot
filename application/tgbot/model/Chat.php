<?php
namespace app\tgbot\model;

use think\Model as ThinkModel;

class Chat extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__TGBOT_CHAT__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 数据完成
    protected $insert = ['public_channel' => 1, 'status' => 1];
}