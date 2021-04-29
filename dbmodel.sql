
ALTER TABLE `player` ADD `player_person_score` INT UNSIGNED NOT NULL DEFAULT '0',
ADD `player_person_score_order` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
ADD `player_play_order` SMALLINT UNSIGNED NULL DEFAULT NULL,
ADD `player_action_choice` SMALLINT UNSIGNED NULL DEFAULT NULL ,
ADD `player_yuan` SMALLINT UNSIGNED NOT NULL DEFAULT '6',
ADD `player_fireworks` SMALLINT UNSIGNED NOT NULL ,
ADD `player_rice` SMALLINT UNSIGNED NOT NULL ,
ADD `player_favor` SMALLINT UNSIGNED NOT NULL ;

CREATE TABLE IF NOT EXISTS `action` (
  `action_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `action_type` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`action_id`)
) ENGINE=InnoDB ;


CREATE TABLE IF NOT EXISTS `palace` (
  `palace_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `palace_player` int(10) unsigned NOT NULL,
  `palace_size` smallint(5) unsigned NOT NULL,
  `palace_drought_affected` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`palace_id`),
  KEY `palace_player` (`palace_player`)
) ENGINE=InnoDB ;


CREATE TABLE IF NOT EXISTS `palace_person` (
  `palace_person_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `palace_person_palace_id` int(10) unsigned NOT NULL,
  `palace_person_type` smallint(5) unsigned NOT NULL,
  `palace_person_level` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`palace_person_id`),
  KEY `palace_character_palace_id` (`palace_person_palace_id`)
) ENGINE=InnoDB ;


CREATE TABLE IF NOT EXISTS `personcard` (
  `personcard_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `personcard_player` int(10) unsigned NOT NULL,
  `personcard_type` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`personcard_id`),
  KEY `charactercard_player` (`personcard_player`)
) ENGINE=InnoDB ;


CREATE TABLE IF NOT EXISTS `personpool` (
  `personpool_type` int(10) unsigned NOT NULL,
  `personpool_level` tinyint(3) unsigned NOT NULL,
  `personpool_nbr` int(10) unsigned NOT NULL,
  PRIMARY KEY (`personpool_type`,`personpool_level`)
) ENGINE=InnoDB ;


CREATE TABLE IF NOT EXISTS `year` (
  `year_id` smallint(5) unsigned NOT NULL,
  `year_event` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`year_id`)
) ENGINE=InnoDB ;

CREATE TABLE IF NOT EXISTS `WALL` ( 
  `id` TINYINT unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL COMMENT 'player_id',
  `bonus` TINYINT NULL COMMENT 'wall_tile index',
  `location` TINYINT NOT NULL COMMENT '0 or wall position',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
