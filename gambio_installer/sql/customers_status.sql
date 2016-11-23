DROP TABLE IF EXISTS `customers_status`;
CREATE TABLE `customers_status` (
  `customers_status_id` int(11) NOT NULL DEFAULT '0',
  `language_id` int(11) NOT NULL DEFAULT '1',
  `customers_status_name` varchar(32) NOT NULL DEFAULT '',
  `customers_status_public` int(1) NOT NULL DEFAULT '1',
  `customers_status_min_order` decimal(15,2) DEFAULT NULL,
  `customers_status_max_order` decimal(15,2) DEFAULT NULL,
  `customers_status_image` varchar(64) DEFAULT NULL,
  `customers_status_discount` decimal(5,2) DEFAULT '0.00',
  `customers_status_ot_discount_flag` char(1) NOT NULL DEFAULT '0',
  `customers_status_ot_discount` decimal(5,2) DEFAULT '0.00',
  `customers_status_graduated_prices` char(1) NOT NULL DEFAULT '0',
  `customers_status_show_price` int(1) NOT NULL DEFAULT '1',
  `customers_status_show_price_tax` int(1) NOT NULL DEFAULT '1',
  `customers_status_add_tax_ot` int(1) NOT NULL DEFAULT '0',
  `customers_status_payment_unallowed` varchar(255) NOT NULL DEFAULT '',
  `customers_status_shipping_unallowed` varchar(255) NOT NULL DEFAULT '',
  `customers_status_discount_attributes` int(1) NOT NULL DEFAULT '0',
  `customers_fsk18` int(1) NOT NULL DEFAULT '1',
  `customers_fsk18_display` int(1) NOT NULL DEFAULT '1',
  `customers_status_write_reviews` int(1) NOT NULL DEFAULT '1',
  `customers_status_read_reviews` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`customers_status_id`,`language_id`),
  KEY `idx_orders_status_name` (`customers_status_name`),
  KEY `language_id` (`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;