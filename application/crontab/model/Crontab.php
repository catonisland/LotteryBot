<?php
namespace app\crontab\model;

use think\Model;
use Cron\CronExpression;

/**
 * 定时任务模型
 */
class Crontab extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CRONTAB__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 获取下次执行时间
    public function getNextTimeAttr($value, $data){
        $cron = CronExpression::factory($data['schedule']);
        return $cron->getNextRunDate()->format('Y-m-d H:i');
    }

    public function getMaximumsTextAttr($value, $data){
        if ($data['maximums']>0){
            return $data['maximums'];
        }else{
            return '无限制';
        }
    }

    public function setBeginTimeAttr($value)
    {
        return strtotime($value);
    }

    public function setEndTimeAttr($value)
    {
        return strtotime($value);
    }

    public function getBeginTimeAttr($value)
    {
        if (empty($value)){
            return null;
        }
        return date('Y-m-d H:i', $value);
    }

    public function getEndTimeAttr($value)
    {
        if (empty($value)){
            return null;
        }
        return date('Y-m-d H:i', $value);
    }

    public function getExecuteTimeAttr($value)
    {
        if (empty($value)){
            return null;
        }
        return date('Y-m-d H:i', $value);
    }

}