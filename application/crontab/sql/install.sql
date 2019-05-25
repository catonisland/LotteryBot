-- -----------------------------
-- 导出时间 `2019-05-25 21:31:13`
-- -----------------------------

-- -----------------------------
-- 表结构 `dp_crontab`
-- -----------------------------
DROP TABLE IF EXISTS `dp_crontab`;
CREATE TABLE `dp_crontab` (
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='定时任务表';

-- -----------------------------
-- 表数据 `dp_crontab`
-- -----------------------------
INSERT INTO `dp_crontab` VALUES ('1', 'url', '自动开奖', '/tgbot/lottery', '* * * * *', '0', '0', '81', '1558785742', '1558791061', '1558713600', '1735660800', '1558791061', '0', 'normal');
INSERT INTO `dp_crontab` VALUES ('2', 'sql', '定期清理会话', 'DELETE FROM `dp_tgbot_conversation` WHERE `status` <1', '0 4 * * 1', '0', '0', '0', '1558785788', '1558785788', '1558713600', '1735660800', '', '0', 'normal');

-- -----------------------------
-- 表结构 `dp_crontab_log`
-- -----------------------------
DROP TABLE IF EXISTS `dp_crontab_log`;
CREATE TABLE `dp_crontab_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(10) NOT NULL COMMENT '类型',
  `cid` int(10) unsigned NOT NULL COMMENT '任务的ID',
  `title` varchar(150) NOT NULL COMMENT '标题',
  `remark` mediumtext COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL COMMENT '执行时间',
  `status` tinyint(1) NOT NULL COMMENT '状态 0:失败 1:成功',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='定时任务日志表';