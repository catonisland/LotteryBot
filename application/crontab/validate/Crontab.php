<?php
namespace app\crontab\validate;

use think\Validate;
use Cron\CronExpression;

/*
 * 定时任务验证器
 */
class Crontab extends Validate
{
    // 定义验证规则
    protected $rule = [
        'title|任务标题' => 'require',
        'type|任务类型' => 'require|in:url,sql,shell',
        'content|内容' => 'require',
        'schedule|执行周期' => 'checkSchedule:',
        'maximums|最大执行次数' => 'require|number|min:0',
        'begin_time|开始时间' => 'require|date',
        'end_time|结束时间' => 'require|date',
        'weigh|权重' => 'require|number',
        'status|状态' => 'require|in:disable,normal,completed,expired',
    ];

    // 定义验证提示
    protected $message = [
        'title.require' => '任务标题不能为空',
        'type.require' => '类型不能为空',
        'type.in' => '类型错误',
        'content.require' => '内容不能为空',
        'maximums.require' => '最大执行次数不能为空',
        'maximums.number' => '最大执行次数必须是数字',
        'maximums.min' => '最大执行次数必须大于等于0',
        'begin_time.require' => '开始时间不能为空',
        'begin_time.date' => '开始时间格式错误',
        'end_time.require' => '结束时间不能为空',
        'end_time.date' => '结束时间格式错误',
        'weigh.require' => '权重不能为空',
        'weigh.number' => '权重必须是数字',
        'status.require' => '状态不能为空',
        'status.in' => '状态错误',
    ];

    // 自定义验证规则
    protected function checkSchedule($value, $rule, $data)
    {
        if (empty($value)){
            return '执行周期不能为空';
        }
        if (!CronExpression::isValidExpression($value)){
            return '执行周期 Cron 表达式错误';
        }
        return true;
    }

    // 定义验证场景
    protected $scene = [
        'schedule' => ['schedule'],
    ];

}