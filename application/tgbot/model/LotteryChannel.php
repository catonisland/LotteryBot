<?php
namespace app\tgbot\model;

use think\Model as ThinkModel;

class LotteryChannel extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__TGBOT_LOTTERY_CHANNEL__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 一对一关联
    public function lottery()
    {
        return $this->hasOne('Lottery', 'id','lottery_id');
    }
}