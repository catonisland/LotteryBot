<?php
namespace app\tgbot\model;

use think\Model as ThinkModel;

class Lottery extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__TGBOT_LOTTERY__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 类型转换
    protected $type = [
        'condition_time'  =>  'timestamp:Y-m-d H:i',
        'time'  =>  'timestamp:Y-m-d H:i',
    ];

    // 一对多关联奖品
    public function prizes()
    {
        return $this->hasMany('LotteryPrize','lottery_id');
    }
}