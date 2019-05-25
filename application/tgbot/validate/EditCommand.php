<?php
/**
 * Created by PhpStorm.
 * User: wjq
 * Date: 2018/10/17
 * Time: 22:57
 */

namespace app\tgbot\validate;

use think\Validate;

class EditCommand extends Validate
{
    // 定义验证规则
    protected $rule = [
        'field|修改的字段' => [
            'in:奖品名称,开奖时间,开奖人数,放弃修改',
        ],
        'title|奖品名称' => [
            'length:1,200',
        ],
        'condition_time|开奖条件:时间' => [
            'date',
            'dateFormat:Y-m-d H:i',
            'checkDate:',
        ],
        'condition_hot|开奖条件:人数' => [
            'number',
            'checkNumber:',
        ],
    ];

    protected $message = [
        'field.in' => '请通过键盘来选择要修改的内容',

        'title.length' => '奖品名称不能大于 200 个字符',

        'condition_time.date' => '时间无效',
        'condition_time.dateFormat' => '时间格式错误',
        'condition_time.checkDate' => '时间不能小于当前时间',

        'condition_hot.number' => '请告诉我一个数字',
        'condition_hot.checkNumber' => '开奖人数必须大于已参与的人数',
    ];

    // 自定义验证规则
    protected function checkDate($value)
    {
        return strtotime($value) > time() ? true : false;
    }

    protected function checkNumber($value, $rule, $data)
    {
        // trace($data,'info');
        return $value > $data['hot'] ? true : false;
    }
}