<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

/**
 * 菜单信息
 */
return [
  [
    'title' => '定时任务',
    'icon' => 'glyphicon glyphicon-time',
    'url_type' => 'module_admin',
    'url_value' => 'crontab/index/index',
    'url_target' => '_self',
    'online_hide' => 0,
    'sort' => 8,
    'status' => 1,
    'child' => [
      [
        'title' => '任务列表',
        'icon' => 'fa fa-fw fa-list',
        'url_type' => 'module_admin',
        'url_value' => 'crontab/index/index',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 100,
        'status' => 1,
        'child' => [
          [
            'title' => '禁用',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/index/disable',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => '编辑',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/index/edit',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => '删除',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/index/delete',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => '添加',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/index/add',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => '启用',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/index/enable',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => '检查Crontab格式',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/index/checkschedule',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => '获取未来N次的时间',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/index/getschedulefuture',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
        ],
      ],
      [
        'title' => '执行日志',
        'icon' => 'fa fa-fw fa-play',
        'url_type' => 'module_admin',
        'url_value' => 'crontab/log/index',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 100,
        'status' => 1,
        'child' => [
          [
            'title' => '编辑',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/log/edit',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => '清空日志',
            'icon' => '',
            'url_type' => 'module_admin',
            'url_value' => 'crontab/log/clear',
            'url_target' => '_self',
            'online_hide' => 1,
            'sort' => 100,
            'status' => 1,
          ],
        ],
      ],
    ],
  ],
];
