<?php
/**
 * 模块信息
 */
return [
    // 模块名[必填]
    'name'        => 'tgbot',
    // 模块标题[必填]
    'title'       => '抽奖机器人',
    // 模块唯一标识[必填]，格式：模块名.开发者标识.module
    'identifier'  => 'ottery.tgbot.module',
    // 模块图标[选填]
    'icon'        => 'fa fa-fw fa-telegram',
    // 开发者[必填]
    'author'      => 'TingV',
    'author_url' => 'https://github.com/tingv/LotteryBot',
    // 版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
    'version'     => '1.0.0',
    // 模块描述[必填]
    'description' => 'Telegram 抽奖机器人模块',
    'tables' => [
        'tgbot_chat',
        'tgbot_conversation',
        'tgbot_lottery',
        'tgbot_lottery_channel',
        'tgbot_lottery_prize',
        'tgbot_lottery_user',
    ],
    'database_prefix' => 'dp_',
    'config' => [
        [
            'text',
            'bot_webhook_password',
            'Webhook URL 密码',
            'Webhook URL 为：<code>'. request()->domain() .'/tgbot/index/index/password/[密码]</code>，如果地址泄露，请移除 Webhook ，然后更换密码，再设置 Webhook。',
            '',
        ],
        [
            'number',
            'bot_webhook_max_connections',
            '最大连接数',
            'Telegram 向 Webhook 发起的最大连接数，范围在 1-100 之间，默认为 40。较低的值可以降低机器人在服务器上的负载，较高的值可以增加机器人的吞吐量。更新此配置后需要移除并重新设置 Webhook 才能生效。',
            '40',
            '0',
            '100',
        ],
        [
            'text',
            'bot_name',
            '机器人的名称',
            '',
            '',
        ],
        [
            'text',
            'bot_token',
            '机器人令牌',
            '通过 @BotFather 创建并获取',
            '',
        ],
        [
            'text',
            'bot_username',
            '机器人用户名',
            '不包含前面的 @',
            '',
        ],
        [
            'text',
            'bot_id',
            '机器人 ID',
            '',
            '',
        ],
        [
            'textarea',
            'admin_users_ids',
            '机器人管理员 ID',
            '每行一个 ID',
            '',
        ],
        [
            'text',
            'channel_id',
            '频道 ID',
            '',
            '',
        ],
        [
            'text',
            'channel_title',
            '频道名称',
            '',
            '',
        ],
        [
            'text',
            'channel_username',
            '频道用户名',
            '不含前面的 @',
            '',
        ],
        [
            'text',
            'channel_URL',
            '频道 URL',
            '要求 https://t.me/xxx 格式的 URL',
            '',
        ],
        [
            'radio',
            'channel_push_review',
            '是否需要审核',
            '推送到频道的抽奖信息是否需要机器人管理员审核',
            [1 => '是', 0 => '否'],
            1,
        ],
    ],
];