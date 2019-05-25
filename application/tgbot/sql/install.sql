-- -----------------------------
-- 导出时间 `2019-05-25 21:31:06`
-- -----------------------------

-- -----------------------------
-- 表结构 `dp_tgbot_chat`
-- -----------------------------
DROP TABLE IF EXISTS `dp_tgbot_chat`;
CREATE TABLE `dp_tgbot_chat` (
  `id` bigint(20) NOT NULL COMMENT 'Unique user or chat identifier',
  `type` enum('private','group','supergroup','channel') NOT NULL COMMENT 'Chat type, either private, group, supergroup or channel',
  `title` char(255) DEFAULT NULL COMMENT 'Chat (group) title, is null if chat type is private',
  `username` char(255) DEFAULT NULL COMMENT 'Username, for private chats, supergroups and channels if available',
  `public_channel` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否允许推送到频道 0:否 1:是',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态 0: 禁用 1: 启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='使用机器人的用户表';

-- -----------------------------
-- 表数据 `dp_tgbot_chat`
-- -----------------------------

-- -----------------------------
-- 表结构 `dp_tgbot_conversation`
-- -----------------------------
DROP TABLE IF EXISTS `dp_tgbot_conversation`;
CREATE TABLE `dp_tgbot_conversation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint(20) NOT NULL COMMENT 'Unique user identifier',
  `chat_id` bigint(20) NOT NULL COMMENT 'Unique user or chat identifier',
  `command` varchar(160) DEFAULT NULL COMMENT 'Default command to execute',
  `notes` text COMMENT 'Data stored from command',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态 -1: 删除 0: 取消 1: 活跃',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回话表';

-- -----------------------------
-- 表数据 `dp_tgbot_conversation`
-- -----------------------------

-- -----------------------------
-- 表结构 `dp_tgbot_lottery`
-- -----------------------------
DROP TABLE IF EXISTS `dp_tgbot_lottery`;
CREATE TABLE `dp_tgbot_lottery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint(20) NOT NULL COMMENT '发起人ID',
  `chat_id` bigint(20) NOT NULL COMMENT '活动群ID',
  `chat_type` enum('group','supergroup') NOT NULL COMMENT '群组类型',
  `chat_title` varchar(255) DEFAULT NULL COMMENT '群组名称',
  `chat_url` varchar(255) DEFAULT NULL COMMENT '群组链接',
  `title` varchar(255) NOT NULL COMMENT '奖品名称',
  `number` smallint(1) unsigned NOT NULL DEFAULT '0' COMMENT '奖品数量',
  `conditions` tinyint(2) NOT NULL COMMENT '开奖条件 1:按时间自动开奖 2:按人数自动开奖',
  `condition_time` int(11) unsigned DEFAULT NULL COMMENT '开奖条件:时间',
  `condition_hot` int(10) unsigned DEFAULT NULL COMMENT '开奖条件:人数',
  `join_type` smallint(2) unsigned NOT NULL DEFAULT '1' COMMENT '参与方式 1:群聊 2:私聊',
  `time` int(11) unsigned DEFAULT NULL COMMENT '实际开奖时间',
  `keyword` varchar(250) DEFAULT NULL COMMENT '参与活动关键词',
  `hot` int(10) NOT NULL DEFAULT '0' COMMENT '参与人数',
  `notification` tinyint(2) NOT NULL COMMENT '开奖结果推送通知 0:否 1:是',
  `is_push_channel` tinyint(2) NOT NULL COMMENT '是否推送活动到频道 1:是 0:否',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态 -1: 已删除 0: 已开奖 1: 待开奖',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抽奖活动表';

-- -----------------------------
-- 表数据 `dp_tgbot_lottery`
-- -----------------------------

-- -----------------------------
-- 表结构 `dp_tgbot_lottery_channel`
-- -----------------------------
DROP TABLE IF EXISTS `dp_tgbot_lottery_channel`;
CREATE TABLE `dp_tgbot_lottery_channel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lottery_id` int(10) unsigned NOT NULL COMMENT '活动ID',
  `message_id` int(10) NOT NULL DEFAULT '0' COMMENT '消息ID',
  `status` tinyint(2) NOT NULL COMMENT '状态 -1:拒绝 0:待审 1:通过',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `lottery_id` (`lottery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抽奖公共频道';

-- -----------------------------
-- 表数据 `dp_tgbot_lottery_channel`
-- -----------------------------

-- -----------------------------
-- 表结构 `dp_tgbot_lottery_prize`
-- -----------------------------
DROP TABLE IF EXISTS `dp_tgbot_lottery_prize`;
CREATE TABLE `dp_tgbot_lottery_prize` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lottery_id` int(10) unsigned NOT NULL COMMENT '抽奖活动ID',
  `prize` text NOT NULL COMMENT '奖品内容',
  `user_id` bigint(20) DEFAULT NULL COMMENT '中奖者ID',
  `first_name` varchar(255) DEFAULT NULL COMMENT '中奖者 first name',
  `last_name` varchar(255) DEFAULT NULL COMMENT '中奖者 last name',
  `username` varchar(255) DEFAULT NULL COMMENT '中奖者 username',
  `time` int(11) unsigned DEFAULT NULL COMMENT '中奖时间',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态 -1: 取消资格 0: 未发放 1: 已发放',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `lottery_id` (`lottery_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='奖品表';

-- -----------------------------
-- 表数据 `dp_tgbot_lottery_prize`
-- -----------------------------

-- -----------------------------
-- 表结构 `dp_tgbot_lottery_user`
-- -----------------------------
DROP TABLE IF EXISTS `dp_tgbot_lottery_user`;
CREATE TABLE `dp_tgbot_lottery_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint(20) NOT NULL COMMENT '用户ID',
  `lottery_id` int(10) unsigned NOT NULL COMMENT '抽奖活动ID',
  `first_name` varchar(255) NOT NULL COMMENT 'User''s first name',
  `last_name` varchar(255) DEFAULT NULL COMMENT 'User''s last name',
  `username` varchar(255) DEFAULT NULL COMMENT 'User''s username',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `lottery_id` (`lottery_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户参与抽奖记录表';

-- -----------------------------
-- 表数据 `dp_tgbot_lottery_user`
-- -----------------------------
