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
 * $Id$
 *
 * (c) 2010 - 2014 RedGecko GmbH -- http://www.redgecko.de
 *     Released under the MIT License (Expat)
 * -----------------------------------------------------------------------------
 */
require_once DIR_MAGNALISTER_INCLUDES.'lib/classes/ProductList/Dependency/MLProductListDependency.php';

class MLProductListDependencyStatusFilter extends MLProductListDependency {
	public function manipulateQuery() {
		if(
			   (MagnaDB::gi()->columnExistsInTable('products_status', TABLE_PRODUCTS))
			&& getDBConfigValue(
					array($this->getMagnaSession('currentPlatform').'.'.$this->getConfig('selectionname').'.status', 'val'),
					$this->getMagnaSession('mpID'),
					false
			   )
		) {
			$this->getQuery()->where('p.`products_status` <> 0');
		}
		return $this;
	}
	protected function getDefaultConfig() {
		return array(
			'selectionname' => 'general',
		);
	}

}
