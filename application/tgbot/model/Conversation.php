<?php
namespace app\tgbot\model;

use think\Model as ThinkModel;

class Conversation extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__TGBOT_CONVERSATION__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 类型转换
    protected $type = [
        'notes'    =>  'serialize',
    ];
}