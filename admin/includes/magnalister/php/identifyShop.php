<?php
/**
 * 888888ba                 dP  .88888.                    dP                
 * 88    `8b                88 d8'   `88                   88                
 * 88aaaa8P' .d8888b. .d888b88 88        .d8888b. .d8888b. 88  .dP  .d8888b. 
 * 88   `8b. 88ooood8 88'  `88 88   YP88 88ooood8 88'  `"" 88888"   88'  `88 
 * 88     88 88.  ... 88.  .88 Y8.   .88 88.  ... 88.  ... 88  `8b. 88.  .88 
 * dP     dP `88888P' `88888P8  `88888'  `88888P' `88888P' dP   `YP `88888P' 
 *
 *                          m a g n a l i s t e r
 *                                      boost your Online-Shop
 *
 * -----------------------------------------------------------------------------
 * $Id: identifyShop.php 4468 2014-08-29 08:45:09Z derpapst $
 *
 * (c) 2010 RedGecko GmbH -- http://www.redgecko.de
 *     Released under the MIT License (Expat)
 * -----------------------------------------------------------------------------
 */

function identShopSystem() {
	$content = file_get_contents('includes/application_top.php', 0, null, -1, 1500);
	if (defined('_GM_VALID_CALL') || (stripos($content, 'gambio') !== false)) {
		define('SHOPSYSTEM', 'gambio');
	} else if (defined('PROJECT_VERSION') && (stripos(PROJECT_VERSION, 'modified') !== false)) {
		define('SHOPSYSTEM', 'xtcmodified');
	} else if (defined('PROJECT_VERSION') && (stripos(PROJECT_VERSION, 'xt:commerce') !== false)) {
		define('SHOPSYSTEM', 'xtcommerce');
	} else if (stripos($content, 'xt-commerce') !== false) {
		define('SHOPSYSTEM', 'xtcommerce');
	} else if (stripos($content, 'xt:Commerce') !== false) {
		define('SHOPSYSTEM', 'xtcommerce');
	} else if (stripos($content, 'oscommerce') !== false) {
		define('SHOPSYSTEM', 'oscommerce');
	} else if (stripos(PROJECT_VERSION, 'deLuxe') !== false) {
		define('SHOPSYSTEM', 'xonsoft');
	} else {
		/* Shop unbekannt, aber mindestens ein osC fork */
		define('SHOPSYSTEM', 'oscommerce');
	}
}

identShopSystem();
