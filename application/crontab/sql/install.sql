-- -----------------------------
-- 导出时间 `2017-10-27 20:09:37`
-- -----------------------------

-- -----------------------------
-- 表结构 `msx_crontab`
-- -----------------------------
DROP TABLE IF EXISTS `msx_crontab`;
CREATE TABLE `msx_crontab` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(10) NOT NULL COMMENT '类型',
  `title` varchar(150) NOT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `schedule` varchar(100) NOT NULL COMMENT 'Cron 表达式',
  `sleep` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '延迟秒数执行',
  `maximums` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最大执行次数 0为不限',
  `executes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已经执行的次数',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `begin_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `execute_time` int(10) unsigned DEFAULT NULL COMMENT '最后执行时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` enum('completed','expired','disable','normal') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='定时任务表';


-- -----------------------------
-- 表结构 `msx_crontab_log`
-- -----------------------------
DROP TABLE IF EXISTS `msx_crontab_log`;
CREATE TABLE `msx_crontab_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(10) NOT NULL COMMENT '类型',
  `cid` int(10) unsigned NOT NULL COMMENT '任务的ID',
  `title` varchar(150) NOT NULL COMMENT '标题',
  `remark` mediumtext COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL COMMENT '执行时间',
  `status` tinyint(1) NOT NULL COMMENT '状态 0:失败 1:成功',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='定时任务日志表';

