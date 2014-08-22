DROP TABLE IF EXISTS `article`;
CREATE TABLE `article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '文章标题',
  `categoryId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '栏目',
  `tagIds` tinytext COMMENT 'tag标签',
  `source` varchar(1024) DEFAULT NULL COMMENT '来源',
  `sourceUrl` varchar(1024) DEFAULT NULL COMMENT '来源URL',
  `publishedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `body` text COMMENT '内容主体',
  `thumb` varchar(255) NOT NULL DEFAULT '',
  `originalThumb` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图原图',
  `picture` varchar(255) NOT NULL DEFAULT '' COMMENT '文章添加/编辑时，如文章中有图片保存',
  `status` enum('published','unpublished','trash') NOT NULL DEFAULT 'unpublished' COMMENT '状态',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `featured` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '头条',
  `promoted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '推荐',
  `sticky` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文章资讯表';

DROP TABLE IF EXISTS `article_category`;
CREATE TABLE `article_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '栏目名称',
  `code` varchar(64) NOT NULL COMMENT 'URL目录名称',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT '权重',
  `publishArticle` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许发布文章',
  `seoTitle` varchar(1024) NOT NULL DEFAULT '' COMMENT '栏目标题',
  `seoKeyword` varchar(1024) NOT NULL DEFAULT '' COMMENT 'SEO 关键字',
  `seoDesc` varchar(1024) NOT NULL DEFAULT '' COMMENT '栏目描述（SEO）',
  `published` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（1：启用 0：停用)',
  `parentId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级目录id',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uri` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文章资讯类型表';

DROP TABLE IF EXISTS `block`;
CREATE TABLE `block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT '用户Id',
  `title` varchar(255) NOT NULL COMMENT '编辑时的题目',
  `content` text COMMENT '编辑区的内容',
  `code` varchar(255) NOT NULL DEFAULT '',
  `tips` text COMMENT '提示',
  `createdTime` int(11) unsigned NOT NULL COMMENT '创建时间',
  `updateTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='图片切换主题表';

DROP TABLE IF EXISTS `block_history`;
CREATE TABLE `block_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `blockId` int(11) NOT NULL COMMENT '主题id',
  `content` text COMMENT '历史内容',
  `userId` int(11) NOT NULL COMMENT '用户id',
  `createdTime` int(11) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='图片切换主题历史表';

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '缓存名称',
  `data` longblob COMMENT '缓存的数据',
  `serialized` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '序列化',
  `expiredTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期的时间',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `expiredTime` (`expiredTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统缓存表';

DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '类别code',
  `name` varchar(255) NOT NULL COMMENT '类别名称',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '类别路径',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT '类别权重',
  `groupId` int(10) unsigned NOT NULL COMMENT '类别组',
  `parentId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父类别',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uri` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品类别表';

DROP TABLE IF EXISTS `category_group`;
CREATE TABLE `category_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT '类别组名',
  `depth` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品类别组';

DROP TABLE IF EXISTS `cloud_app`;
CREATE TABLE `cloud_app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '名称',
  `code` varchar(255) NOT NULL COMMENT '编码',
  `description` text NOT NULL COMMENT '描述',
  `icon` varchar(255) NOT NULL COMMENT '图标',
  `version` varchar(32) NOT NULL COMMENT '当前版本',
  `fromVersion` varchar(32) NOT NULL DEFAULT '0.0.0' COMMENT '更新前版本',
  `developerId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户ID',
  `developerName` varchar(255) NOT NULL DEFAULT '' COMMENT '开发者名称',
  `installedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装时间',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='已安装的应用';

DROP TABLE IF EXISTS `cloud_app_logs`;
CREATE TABLE `cloud_app_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT '应用编码',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '应用名称',
  `fromVersion` varchar(32) DEFAULT '' COMMENT '升级前版本',
  `toVersion` varchar(32) NOT NULL DEFAULT '' COMMENT '升级后版本',
  `type` enum('install','upgrade') NOT NULL DEFAULT 'install' COMMENT '升级类型',
  `dbBackupPath` varchar(255) NOT NULL DEFAULT '' COMMENT '数据库备份文件',
  `sourceBackupPath` varchar(255) NOT NULL DEFAULT '' COMMENT '源文件备份地址',
  `status` varchar(32) NOT NULL COMMENT '升级状态(ROLLBACK,ERROR,SUCCESS,RECOVERED)',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `ip` varchar(32) NOT NULL DEFAULT '' COMMENT '升级时的IP',
  `message` text COMMENT '失败原因',
  `createdTime` int(10) unsigned NOT NULL COMMENT '日志记录时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='应用升级日志';

DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `objectType` varchar(32) NOT NULL COMMENT '对象类型',
  `objectId` int(10) unsigned NOT NULL COMMENT '对象id',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `content` text NOT NULL COMMENT '评论内容',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `objectType` (`objectType`,`objectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='评论表';

DROP TABLE IF EXISTS `content`;
CREATE TABLE `content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '页面标题',
  `editor` enum('richeditor','none') NOT NULL DEFAULT 'richeditor' COMMENT '编辑器选择类型字段',
  `type` varchar(255) NOT NULL COMMENT '页面类型',
  `alias` varchar(255) NOT NULL DEFAULT '' COMMENT '别名',
  `summary` text COMMENT '摘要',
  `body` text COMMENT '主体',
  `picture` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `template` varchar(255) NOT NULL DEFAULT '' COMMENT '模板',
  `status` enum('published','unpublished','trash') NOT NULL COMMENT '状态',
  `categoryId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '类别id',
  `tagIds` tinytext COMMENT '标签id',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '头条',
  `featured` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '特色',
  `promoted` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '在列表中是否显示该条目。',
  `sticky` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶',
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `field1` text,
  `field2` text,
  `field3` text,
  `field4` text,
  `field5` text,
  `field6` text,
  `field7` text,
  `field8` text,
  `field9` text,
  `field10` text,
  `publishedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公开时间',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='单页面内容表';

DROP TABLE IF EXISTS `file`;
CREATE TABLE `file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `groupId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件组名',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `uri` varchar(255) NOT NULL,
  `mime` varchar(255) NOT NULL,
  `size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '大小',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文件表';

DROP TABLE IF EXISTS `file_group`;
CREATE TABLE `file_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '组名',
  `code` varchar(255) NOT NULL,
  `public` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文件组';

DROP TABLE IF EXISTS `friend`;
CREATE TABLE `friend` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fromId` int(10) unsigned NOT NULL COMMENT '关注者',
  `toId` int(10) unsigned NOT NULL COMMENT '被关注者',
  `createdTime` int(10) unsigned NOT NULL COMMENT '关注时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='粉丝';

DROP TABLE IF EXISTS `installed_packages`;
CREATE TABLE `installed_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ename` varchar(255) NOT NULL COMMENT '包名称',
  `cname` varchar(255) NOT NULL,
  `version` varchar(255) NOT NULL COMMENT 'version',
  `installTime` int(11) NOT NULL COMMENT '安装时间',
  `fromVersion` varchar(255) NOT NULL DEFAULT '' COMMENT '来源',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cname` (`ename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='已安装包';

DROP TABLE IF EXISTS `location`;
CREATE TABLE `location` (
  `id` bigint(20) unsigned NOT NULL,
  `parentId` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `pinyin` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户表',
  `module` varchar(32) NOT NULL COMMENT '日志模型',
  `action` varchar(32) NOT NULL COMMENT '动作',
  `message` text NOT NULL COMMENT '信息',
  `data` text COMMENT '数据',
  `ip` varchar(255) NOT NULL COMMENT 'ip',
  `createdTime` int(10) unsigned NOT NULL,
  `level` char(10) NOT NULL COMMENT '水平',
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统日志表';

DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '私信Id',
  `fromId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发信人Id',
  `toId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收信人Id',
  `content` text NOT NULL COMMENT '消息内容',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息表';

DROP TABLE IF EXISTS `message_conversation`;
CREATE TABLE `message_conversation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '会话Id',
  `fromId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发信人Id',
  `toId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收信人Id',
  `messageNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '此对话的信息条数',
  `latestMessageUserId` int(10) unsigned DEFAULT NULL COMMENT '最后一条信息，用Json显示',
  `latestMessageTime` int(10) unsigned NOT NULL COMMENT '最后信息的时间',
  `latestMessageContent` text NOT NULL COMMENT '最后信息内容',
  `unreadNum` int(10) unsigned NOT NULL COMMENT '未读数字',
  `createdTime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息对话表';

DROP TABLE IF EXISTS `message_relation`;
CREATE TABLE `message_relation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversationId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '对话id',
  `messageId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消息Id',
  `isRead` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0表示未读',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息的关系表';

DROP TABLE IF EXISTS `migration_versions`;
CREATE TABLE `migration_versions` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='迁移版本表';

DROP TABLE IF EXISTS `navigation`;
CREATE TABLE `navigation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) NOT NULL COMMENT '产品文案',
  `url` varchar(300) NOT NULL COMMENT 'URL',
  `sequence` tinyint(4) unsigned NOT NULL COMMENT '显示顺序,数字替代',
  `createdTime` int(11) NOT NULL,
  `updateTime` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(30) NOT NULL COMMENT '类型',
  `isOpen` tinyint(2) NOT NULL DEFAULT '1' COMMENT '默认1，为开启',
  `isNewWin` tinyint(2) NOT NULL DEFAULT '1' COMMENT '默认为1,另开窗口',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='导航数据表';

DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `type` varchar(64) NOT NULL DEFAULT 'default' COMMENT '类型',
  `content` text COMMENT '通知内容',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  `isRead` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统通知表';

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sn` varchar(32) NOT NULL COMMENT '订单编号',
  `status` enum('created','paid','refunding','refunded','cancelled') NOT NULL COMMENT '状态',
  `title` varchar(255) NOT NULL COMMENT '订单标题',
  `targetType` varchar(64) NOT NULL DEFAULT '' COMMENT '归属的类型',
  `targetId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '归属id',
  `amount` float(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单实付金额',
  `isGift` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否是礼物',
  `giftTo` varchar(64) NOT NULL DEFAULT '' COMMENT '给谁的',
  `refundId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后一次退款操作记录的ID',
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `coupon` varchar(255) NOT NULL DEFAULT '' COMMENT '优惠券',
  `couponDiscount` float(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '优惠的折扣',
  `payment` enum('none','alipay','tenpay') NOT NULL DEFAULT 'none' COMMENT '是否付款',
  `bank` varchar(32) NOT NULL DEFAULT '' COMMENT '银行编号',
  `paidTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `note` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `data` text COMMENT '订单业务数据',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sn` (`sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单表';

DROP TABLE IF EXISTS `order_log`;
CREATE TABLE `order_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `orderId` int(10) unsigned NOT NULL COMMENT '订单id',
  `type` varchar(32) NOT NULL COMMENT '订单类型',
  `message` text COMMENT '订单消息',
  `data` text COMMENT '订单数据',
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `ip` varchar(255) NOT NULL COMMENT 'id',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `orderId` (`orderId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单日志表';

DROP TABLE IF EXISTS `order_refund`;
CREATE TABLE `order_refund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `orderId` int(10) unsigned NOT NULL COMMENT '订单id',
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `targetType` varchar(64) NOT NULL DEFAULT '' COMMENT '所属类型',
  `targetId` int(10) unsigned NOT NULL COMMENT '所属类型id',
  `status` enum('created','success','failed','cancelled') NOT NULL DEFAULT 'created' COMMENT '状态',
  `expectedAmount` float(10,2) unsigned DEFAULT '0.00' COMMENT '期望退款的金额，NULL代表未知，0代表不需要退款',
  `actualAmount` float(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际退款金额，0代表无退款',
  `reasonType` varchar(64) NOT NULL DEFAULT '' COMMENT '退款原因类型',
  `reasonNote` varchar(1024) NOT NULL DEFAULT '' COMMENT '原因备注',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单退款表';

DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(1024) NOT NULL COMMENT '产品名称',
  `subtitle` varchar(1024) NOT NULL DEFAULT '' COMMENT '副标题',
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft' COMMENT '产品状态',
  `price` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `expiryDay` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '到期时间',
  `showStudentNumType` enum('opened','closed') NOT NULL DEFAULT 'opened' COMMENT '显示关注者数量',
  `serializeMode` enum('none','serialize','finished') NOT NULL DEFAULT 'none' COMMENT '序列化方式',
  `income` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '产品销售总收入',
  `lessonNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品说明书数量',
  `rating` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排行数值',
  `ratingNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '投票人数',
  `vipLevelId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '可以免费试用的会员等级',
  `categoryId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '类别id',
  `tags` text COMMENT '标签',
  `smallPicture` varchar(255) NOT NULL DEFAULT '' COMMENT '产品小图片',
  `middlePicture` varchar(255) NOT NULL DEFAULT '' COMMENT '产品中图片',
  `largePicture` varchar(255) NOT NULL DEFAULT '' COMMENT '产品大图片',
  `about` text COMMENT '关于',
  `teacherIds` text COMMENT '享客',
  `goals` text COMMENT '产品市场',
  `audiences` text COMMENT '访客',
  `recommended` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否为推荐产品',
  `recommendedSeq` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推荐seq',
  `recommendedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推荐时间',
  `locationId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品产地ID',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `studentNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关注者数量',
  `hitNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '查看次数',
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品表';

DROP TABLE IF EXISTS `product_announcement`;
CREATE TABLE `product_announcement` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(10) NOT NULL COMMENT '用户id',
  `courseId` int(10) NOT NULL COMMENT '产品id',
  `content` text NOT NULL COMMENT '公告内容',
  `createdTime` int(10) NOT NULL COMMENT '创建时间',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品公告表';

DROP TABLE IF EXISTS `product_chapter`;
CREATE TABLE `product_chapter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '产品介绍章节ID',
  `courseId` int(10) unsigned NOT NULL COMMENT '章节所属产品ID',
  `type` enum('chapter','unit') NOT NULL DEFAULT 'chapter' COMMENT '介绍章节类型：chapter为章节，unit为单元。',
  `parentId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'parentId大于０时为单元',
  `number` int(10) unsigned NOT NULL COMMENT '章节编号',
  `seq` int(10) unsigned NOT NULL COMMENT '章节序号',
  `title` varchar(255) NOT NULL COMMENT '章节名称',
  `createdTime` int(10) unsigned NOT NULL COMMENT '章节创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品使用说明书章节表';

DROP TABLE IF EXISTS `product_favorite`;
CREATE TABLE `product_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '收藏的id',
  `courseId` int(10) unsigned NOT NULL COMMENT '收藏产品的Id',
  `userId` int(10) unsigned NOT NULL COMMENT '收藏人的Id',
  `createdTime` int(10) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户的产品收藏数据表';

DROP TABLE IF EXISTS `product_specification`;
CREATE TABLE `product_lesson` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `courseId` int(10) unsigned NOT NULL COMMENT '产品',
  `chapterId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '说明书章节id',
  `number` int(10) unsigned NOT NULL COMMENT '说明书数量',
  `seq` int(10) unsigned NOT NULL,
  `free` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否免费',
  `status` enum('unpublished','published') NOT NULL DEFAULT 'published' COMMENT '状态',
  `title` varchar(255) NOT NULL COMMENT '说明书标题',
  `summary` text COMMENT '说明收摘要',
  `tags` text COMMENT '标签',
  `type` varchar(64) NOT NULL DEFAULT 'text' COMMENT '类型',
  `content` text COMMENT '说明书内容',
  `mediaId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '媒体文件ID(user_disk_file.id)',
  `mediaSource` varchar(32) NOT NULL DEFAULT '' COMMENT '媒体文件来源(self:本站上传,youku:优酷)',
  `mediaName` varchar(255) NOT NULL DEFAULT '' COMMENT '媒体文件名称',
  `mediaUri` varchar(1024) NOT NULL DEFAULT '' COMMENT '媒体文件资源名',
  `length` int(11) unsigned DEFAULT NULL COMMENT '长度',
  `materialNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传的产品资料数量',
  `quizNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品问卷测验题目数量',
  `learnedNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关注的数量',
  `viewedNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数量',
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品使用说明书';

DROP TABLE IF EXISTS `product_specification_focus`;
CREATE TABLE `product_lesson_learn` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `courseId` int(10) unsigned NOT NULL COMMENT '产品id',
  `lessonId` int(10) unsigned NOT NULL COMMENT '说明书id',
  `status` enum('learning','finished') NOT NULL COMMENT '状态',
  `startTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开发时间',
  `finishedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId_lessonId` (`userId`,`lessonId`),
  KEY `userId_courseId` (`userId`,`courseId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品使用说明书关注表';

DROP TABLE IF EXISTS `product_material`;
CREATE TABLE `product_material` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '产品资料ID',
  `courseId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品资料所属产品ID',
  `lessonId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品资料所属产品ID',
  `title` varchar(1024) NOT NULL COMMENT '产品资料标题',
  `description` text COMMENT '产品资料描述',
  `link` varchar(1024) NOT NULL DEFAULT '' COMMENT '产品外部链接地址',
  `fileId` int(10) unsigned NOT NULL COMMENT '产品资料文件ID',
  `fileUri` varchar(255) NOT NULL DEFAULT '' COMMENT '产品资料文件URI',
  `fileMime` varchar(255) NOT NULL DEFAULT '' COMMENT '产品资料文件MIME',
  `fileSize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品资料文件大小',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品资料创建人ID',
  `createdTime` int(10) unsigned NOT NULL COMMENT '产品资料创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品材料表';

DROP TABLE IF EXISTS `product_member`;
CREATE TABLE `product_member` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `courseId` int(10) unsigned NOT NULL COMMENT '产品id',
  `userId` int(10) unsigned NOT NULL COMMENT '用户id',
  `orderId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员购买产品时的订单ID',
  `deadline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后期限',
  `levelId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户以会员的方式查看产品相关信息时的会员ID',
  `learnedNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关注次数',
  `noteNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '备忘数目',
  `noteLastUpdateTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最新的备忘更新时间',
  `isLearned` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否在关注中',
  `seq` int(10) unsigned NOT NULL DEFAULT '0',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `isVisible` tinyint(2) NOT NULL DEFAULT '1' COMMENT '可见与否，默认为可见',
  `role` enum('student','teacher') NOT NULL DEFAULT 'student' COMMENT '角色',
  `locked` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否冻结',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `courseId` (`courseId`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品关注的用户信息表';

DROP TABLE IF EXISTS `product_note`;
CREATE TABLE `product_note` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(10) NOT NULL COMMENT '备忘作者ID',
  `courseId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品ID',
  `lessonId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品查看页数ID',
  `content` text NOT NULL COMMENT '备忘内容',
  `length` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '备忘内容的字数',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '备忘状态：0:私有, 1:公开',
  `createdTime` int(10) NOT NULL COMMENT '备忘创建时间',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '备忘更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户关注产品的备忘表';

DROP TABLE IF EXISTS `product_review`;
CREATE TABLE `product_review` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论用户',
  `courseId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '评论title',
  `content` text NOT NULL COMMENT '评论内容',
  `rating` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评级',
  `createdTime` int(10) unsigned NOT NULL COMMENT '评价创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品评论表';

DROP TABLE IF EXISTS `product_thread`;
CREATE TABLE `product_thread` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `courseId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品id',
  `lessonId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品使用说明书id',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `type` enum('discussion','question') NOT NULL DEFAULT 'discussion' COMMENT '问题讨论类型',
  `isStick` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶',
  `isElite` tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '是否为精华',
  `isClosed` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '是否关闭',
  `title` varchar(255) NOT NULL COMMENT '问题讨论标题',
  `content` text COMMENT '问题讨论内容',
  `postNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问题讨论回复数量',
  `hitNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击查看的次数',
  `followNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '跟踪数字',
  `latestPostUserId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后回复用户的id',
  `latestPostTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后回复时间',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品的问题讨论区';

DROP TABLE IF EXISTS `product_thread_post`;
CREATE TABLE `product_thread_post` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `courseId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品id',
  `lessonId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用说明书id',
  `threadId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '线程id',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `isElite` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否可以编辑',
  `content` text NOT NULL COMMENT '正文',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品问题讨论区回复';

DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL DEFAULT '' COMMENT '题目类型',
  `stem` text COMMENT '产品问卷题干',
  `score` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '分数',
  `answer` text COMMENT '问卷参考答案',
  `analysis` text COMMENT '解析',
  `metas` text COMMENT '题目元信息',
  `categoryId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '类别',
  `difficulty` varchar(64) NOT NULL DEFAULT 'normal' COMMENT '困难程度',
  `target` varchar(255) NOT NULL DEFAULT '' COMMENT '从属于',
  `parentId` int(10) unsigned DEFAULT '0' COMMENT '材料父ID',
  `subCount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子题数量',
  `finishedTimes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成次数',
  `passedTimes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品问卷题目表';

DROP TABLE IF EXISTS `question_category`;
CREATE TABLE `question_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '类别名称',
  `target` varchar(255) NOT NULL DEFAULT '' COMMENT '从属于',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作用户',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `seq` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品问卷题库类别表';

DROP TABLE IF EXISTS `question_favorite`;
CREATE TABLE `question_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `questionId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问卷题目id',
  `target` varchar(255) NOT NULL DEFAULT '' COMMENT '从属于',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品问卷题目收藏表';

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `session_id` varchar(255) NOT NULL COMMENT 'sessionid',
  `session_value` text NOT NULL COMMENT 'session值',
  `session_time` int(11) NOT NULL COMMENT 'session时间',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='session表';

DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '设置项',
  `value` longblob COMMENT '设置值',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='应用设置表';

DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '标签名称',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='标签表';

DROP TABLE IF EXISTS `testpaper`;
CREATE TABLE `testpaper` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '产品问卷名称',
  `description` text COMMENT '问卷说明',
  `limitedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '限时(单位：秒)',
  `pattern` varchar(255) NOT NULL DEFAULT '' COMMENT '问卷生成/显示模式',
  `target` varchar(255) NOT NULL DEFAULT '' COMMENT '目标',
  `status` varchar(32) NOT NULL DEFAULT 'draft' COMMENT '问卷状态：draft,open,closed',
  `score` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '总分',
  `itemCount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '题目数量',
  `createdUserId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatedUserId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改人',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `metas` text COMMENT '问卷题型排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='问卷测试表';

DROP TABLE IF EXISTS `testpaper_item`;
CREATE TABLE `testpaper_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '问卷题目',
  `testId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属问卷',
  `seq` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问卷题目顺序',
  `questionId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问卷题目id',
  `questionType` varchar(64) NOT NULL DEFAULT '' COMMENT '问卷题目类别',
  `parentId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父类别id',
  `score` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '分值',
  `missScore` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '失去分数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='问卷测试项目表';

DROP TABLE IF EXISTS `testpaper_item_result`;
CREATE TABLE `testpaper_item_result` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `itemId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问卷题目id',
  `testId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '测试Id',
  `testPaperResultId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问卷结果Id',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户Id',
  `questionId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问题Id',
  `status` enum('none','right','partRight','wrong','noAnswer') NOT NULL DEFAULT 'none' COMMENT '状态',
  `score` float(10,1) NOT NULL DEFAULT '0.0' COMMENT '分数',
  `answer` text COMMENT '回答',
  `teacherSay` text COMMENT '审批备注',
  PRIMARY KEY (`id`),
  KEY `testPaperResultId` (`testPaperResultId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='问卷测试项目结果';

DROP TABLE IF EXISTS `testpaper_result`;
CREATE TABLE `testpaper_result` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `paperName` varchar(255) NOT NULL DEFAULT '' COMMENT '问卷名称',
  `testId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'testId',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'UserId',
  `score` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '分数',
  `objectiveScore` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '目标分数',
  `subjectiveScore` float(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '主观评分',
  `teacherSay` text COMMENT '审批意见',
  `rightItemCount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '正确的数量',
  `limitedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '问卷限制时间(秒)',
  `beginTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `endTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `updateTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `active` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '活跃',
  `status` enum('doing','paused','reviewing','finished') NOT NULL COMMENT '状态',
  `target` varchar(255) NOT NULL DEFAULT '' COMMENT '目标',
  `checkTeacherId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审批者id',
  `checkedTime` int(11) NOT NULL DEFAULT '0' COMMENT '审批时间',
  `usedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='问卷测试结果表';

DROP TABLE IF EXISTS `upgrade_logs`;
CREATE TABLE `upgrade_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `remoteId` int(11) NOT NULL COMMENT 'packageId',
  `installedId` int(11) DEFAULT NULL COMMENT '本地已安装id',
  `ename` varchar(32) NOT NULL COMMENT '名称',
  `cname` varchar(32) NOT NULL COMMENT '中文名称',
  `fromv` varchar(32) DEFAULT NULL COMMENT '初始版本',
  `tov` varchar(32) NOT NULL COMMENT '目标版本',
  `type` smallint(6) NOT NULL COMMENT '升级类型',
  `dbBackPath` text COMMENT '数据库备份文件',
  `srcBackPath` text COMMENT '源文件备份地址',
  `status` varchar(32) NOT NULL COMMENT '状态(ROLLBACK,ERROR,SUCCESS,RECOVERED)',
  `logtime` int(11) NOT NULL COMMENT '升级时间',
  `uid` int(10) unsigned NOT NULL COMMENT 'uid',
  `ip` varchar(32) DEFAULT NULL COMMENT 'ip',
  `reason` text COMMENT '失败原因',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='本地升级日志表';

DROP TABLE IF EXISTS `upload_files`;
CREATE TABLE `upload_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hashId` varchar(128) NOT NULL DEFAULT '' COMMENT '文件的HashID',
  `targetId` int(11) NOT NULL COMMENT '所存目标id',
  `targetType` varchar(64) NOT NULL DEFAULT '' COMMENT '目标类型',
  `filename` varchar(1024) NOT NULL DEFAULT '' COMMENT '文件名称',
  `ext` varchar(12) NOT NULL DEFAULT '' COMMENT '文件后缀',
  `size` bigint(20) NOT NULL DEFAULT '0' COMMENT '文件大小',
  `etag` varchar(256) NOT NULL DEFAULT '' COMMENT '电子标签',
  `convertHash` varchar(256) NOT NULL DEFAULT '' COMMENT '文件转换时的查询转换进度用的Hash值',
  `convertStatus` enum('none','waiting','doing','success','error') NOT NULL DEFAULT 'none' COMMENT '转换状态',
  `metas` text COMMENT '搜索',
  `metas2` text COMMENT '搜索2',
  `type` enum('document','video','audio','image','other') NOT NULL DEFAULT 'other' COMMENT '类型',
  `storage` enum('local','cloud') NOT NULL COMMENT '存储',
  `isPublic` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否公开文件',
  `canDownload` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否可下载',
  `updatedUserId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新用户名',
  `updatedTime` int(10) unsigned DEFAULT '0' COMMENT '更新时间',
  `createdUserId` int(10) unsigned NOT NULL COMMENT '创建的用户id',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hashId` (`hashId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='上传文件表';

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(128) NOT NULL COMMENT '邮箱',
  `password` varchar(64) NOT NULL COMMENT '密码',
  `salt` varchar(32) NOT NULL COMMENT '盐',
  `uri` varchar(64) NOT NULL DEFAULT '' COMMENT 'uri',
  `userName` varchar(64) NOT NULL COMMENT '昵称',
  `title` varchar(255) DEFAULT NULL COMMENT '头衔',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `type` varchar(32) NOT NULL COMMENT 'default默认为网站注册, weibo新浪微薄登录',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '点',
  `coin` int(11) NOT NULL DEFAULT '0' COMMENT '币数',
  `smallAvatar` varchar(255) NOT NULL DEFAULT '' COMMENT '小尺寸头像',
  `mediumAvatar` varchar(255) NOT NULL DEFAULT '' COMMENT '中尺寸头像',
  `largeAvatar` varchar(255) NOT NULL DEFAULT '' COMMENT '大尺寸头像',
  `emailVerified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '电子邮件验证，0为未验证，1为验证',
  `setup` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否初始化设置的，未初始化的可以设置邮箱、注册手机。',
  `roles` varchar(255) NOT NULL COMMENT '角色',
  `promoted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否为推荐',
  `promotedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推荐时间',
  `locked` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '冻结用户',
  `loginTime` int(11) NOT NULL DEFAULT '0' COMMENT '登录时间',
  `loginIp` varchar(64) NOT NULL DEFAULT '' COMMENT '登录ip',
  `loginSessionId` varchar(255) NOT NULL DEFAULT '' COMMENT '登录的session id',
  `approvalTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审批时间',
  `approvalStatus` enum('unapprove','approving','approved','approve_fail') NOT NULL DEFAULT 'unapprove' COMMENT '审批状态',
  `newMessageNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '新消息数量',
  `newNotificationNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '新通知数量',
  `createdIp` varchar(64) NOT NULL DEFAULT '' COMMENT '注册ip',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `userName` (`userName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

DROP TABLE IF EXISTS `user_approval`;
CREATE TABLE `user_approval` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(10) NOT NULL COMMENT '用户ID',
  `idcard` varchar(24) NOT NULL DEFAULT '' COMMENT '身份证号',
  `faceImg` varchar(500) NOT NULL DEFAULT '' COMMENT '身份证正面',
  `backImg` varchar(500) NOT NULL DEFAULT '' COMMENT '身份证背面',
  `truename` varchar(255) DEFAULT NULL COMMENT '真实姓名',
  `note` text COMMENT '认证审批信息',
  `status` enum('unapprove','approving','approved','approve_fail') NOT NULL COMMENT '是否通过：1是 0否',
  `operatorId` int(10) unsigned DEFAULT NULL COMMENT '审核人',
  `createdTime` int(10) NOT NULL DEFAULT '0' COMMENT '申请时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户认证表';

DROP TABLE IF EXISTS `user_bind`;
CREATE TABLE `user_bind` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL COMMENT '类型',
  `fromId` varchar(32) NOT NULL COMMENT '来路id',
  `toId` int(10) unsigned NOT NULL COMMENT '绑定的用户ID',
  `token` varchar(255) NOT NULL DEFAULT '' COMMENT '令牌',
  `refreshToken` varchar(255) NOT NULL DEFAULT '' COMMENT '刷新令牌',
  `expiredTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '令牌过期时间',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`,`fromId`),
  UNIQUE KEY `type_2` (`type`,`toId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户绑定表';

DROP TABLE IF EXISTS `user_fortune_log`;
CREATE TABLE `user_fortune_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL COMMENT '用户id',
  `number` int(10) NOT NULL COMMENT '数量',
  `action` varchar(20) NOT NULL COMMENT '动作',
  `note` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createdTime` int(11) NOT NULL COMMENT '创建时间',
  `type` varchar(20) NOT NULL COMMENT '类型',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户财富日志';

DROP TABLE IF EXISTS `user_profile`;
CREATE TABLE `user_profile` (
  `id` int(10) unsigned NOT NULL COMMENT '用户id',
  `truename` varchar(255) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `idcard` varchar(24) NOT NULL DEFAULT '0' COMMENT '身份证号码',
  `gender` enum('male','female','secret') NOT NULL DEFAULT 'secret' COMMENT '性别',
  `iam` varchar(255) NOT NULL DEFAULT '' COMMENT '我是谁',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `city` varchar(64) NOT NULL DEFAULT '' COMMENT '城市',
  `mobile` varchar(32) NOT NULL DEFAULT '' COMMENT '手机号',
  `qq` varchar(32) NOT NULL DEFAULT '' COMMENT 'qq号',
  `signature` text COMMENT '签名',
  `about` text COMMENT '自我介绍',
  `company` varchar(255) NOT NULL DEFAULT '' COMMENT '公司',
  `job` varchar(255) NOT NULL DEFAULT '' COMMENT '职业',
  `school` varchar(255) NOT NULL DEFAULT '' COMMENT '学校',
  `class` varchar(255) NOT NULL DEFAULT '' COMMENT '班级',
  `weibo` varchar(255) NOT NULL DEFAULT '' COMMENT '微博',
  `weixin` varchar(255) NOT NULL DEFAULT '' COMMENT '微信',
  `site` varchar(255) NOT NULL DEFAULT '' COMMENT '网站',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户帐号配置文件';

DROP TABLE IF EXISTS `user_token`;
CREATE TABLE `user_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL COMMENT '令牌',
  `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `type` varchar(255) NOT NULL COMMENT '类型',
  `data` text NOT NULL COMMENT '数据',
  `expiredTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期的时间',
  `createdTime` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`(6))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户令牌表';