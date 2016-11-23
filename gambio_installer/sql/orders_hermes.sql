DROP TABLE IF EXISTS `orders_hermes`;
CREATE TABLE `orders_hermes` (
  `orderno` varchar(255) NOT NULL DEFAULT '',
  `order_type` enum('props','prips') NOT NULL DEFAULT 'props',
  `orders_id` int(11) NOT NULL DEFAULT '0',
  `receiver_firstname` varchar(25) DEFAULT NULL,
  `receiver_lastname` varchar(25) NOT NULL DEFAULT '',
  `receiver_street` varchar(27) NOT NULL DEFAULT '',
  `receiver_housenumber` varchar(5) DEFAULT NULL,
  `receiver_addressadd` varchar(25) DEFAULT NULL,
  `receiver_postcode` varchar(25) NOT NULL DEFAULT '',
  `receiver_city` varchar(30) NOT NULL DEFAULT '',
  `receiver_district` varchar(25) DEFAULT NULL,
  `receiver_countrycode` varchar(3) NOT NULL DEFAULT '',
  `receiver_email` varchar(255) DEFAULT NULL,
  `receiver_telephonenumber` varchar(32) DEFAULT NULL,
  `receiver_telephoneprefix` varchar(25) DEFAULT NULL,
  `clientreferencenumber` varchar(255) DEFAULT NULL,
  `parcelclass` varchar(255) DEFAULT NULL,
  `amountcashondeliveryeurocent` int(11) NOT NULL DEFAULT '0',
  `state` enum('not_sent','sent','printed') NOT NULL DEFAULT 'not_sent',
  `shipping_id` varchar(255) DEFAULT NULL,
  `paket_shop_id` varchar(255) DEFAULT NULL,
  `hand_over_mode` varchar(255) DEFAULT NULL,
  `collection_desired_date` datetime DEFAULT NULL,
  PRIMARY KEY (`orderno`),
  KEY `order_id` (`orders_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;