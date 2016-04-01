CREATE TABLE `yx_lhj_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '姓名',
  `tel` varchar(50) NOT NULL DEFAULT '' COMMENT '电话',
  `hit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '中了几等奖',
  `time` int(11) NOT NULL DEFAULT '0' COMMENT '中奖事件',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8;