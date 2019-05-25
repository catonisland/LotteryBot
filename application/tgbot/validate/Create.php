<?php
/**
 * Created by LotteryBot.
 * User: TingV
 * Date: 2019-05-25
 * Time: 14:50
 */

namespace app\tgbot\validate;

use think\Validate;

class Create extends Validate
{
    // 定义验证规则
    protected $rule = [
        'title|奖品名称' => [
            'length:1,200',
        ],
        'number|奖品数量' => [
            'number',
            'egt:1',
            'elt:30',
        ],
        'prize|奖品内容' => [
            'length:1,200',
        ],
        'keyword|关键词' => [
            'length:1,50',
        ],
        'conditions|开奖条件' => [
            'in:按时间自动开奖,按人数自动开奖',
        ],
        'condition_time|开奖条件:时间' => [
            'date',
            'dateFormat:Y-m-d H:i',
            'checkDate:',
        ],
        'condition_hot|开奖条件:人数' => [
            'number',
            'egt:1',
        ],
        'notification|开奖通知' => [
            'in:是,否',
        ],
        'join_type|参与方式' => [
            'in:群内发送关键词参与抽奖,私聊机器人参与抽奖',
        ],
        'is_push_channel|是否推送活动到频道' => [
            'in:是，我同意推送,不，谢谢',
        ],
        'chat_url|群组链接' => [
            'checkChatUrl:',
        ],
        'submit|确定或取消' => [
            'in:✅ 确定,🚫 取消',
        ],
    ];

    protected $message = [
        'title.length' => '奖品名称不能大于 200 个字符',
        'prize.length' => '奖品内容不能大于 200 个字符',
        'keyword.length' => '关键词不能大于 50 个字符',
        'number.number' => '请告诉我一个数字',
        'number.egt' => '奖品数量必须大于 0',
        'number.elt' => '奖品数量不能大于 30',

        'conditions.in' => '请通过键盘来选择开奖条件',

        'condition_time.date' => '时间无效',
        'condition_time.dateFormat' => '时间格式错误',
        'condition_time.checkDate' => '时间不能小于当前时间',

        'condition_hot.number' => '请告诉我一个数字',
        'condition_hot.egt' => '自动开奖人数必须大于 0',

        'notification.in' => '请通过键盘来选择是否发送开奖通知给所有人',

        'join_type.in' => '请通过键盘来选择用户参与方式',

        'public_channel.in' => '请通过键盘来选择是否推送活动到频道',

        'chat_url.checkChatUrl' => '请使用标准的 <i>https://t.me/xxxx</i> 类群组邀请链接',

        'submit.in' => '请通过键盘来选择是否发布抽奖活动',
    ];

    // 自定义验证规则
    protected function checkDate($value)
    {
        return strtotime($value) > time() ? true : false;
    }

    protected function checkChatUrl($value)
    {
        return preg_match('/^https:\/\/t\.me\/.+/i', $value) ? true : false;
    }
}