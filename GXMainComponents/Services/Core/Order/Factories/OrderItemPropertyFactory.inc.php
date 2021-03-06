<?php

/* --------------------------------------------------------------
   OrderItemPropertyFactory.inc.php 2015-11-05 gm
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2015 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

MainFactory::load_class('OrderItemAttributeFactoryInterface');

/**
 * Class OrderItemPropertyFactory
 *
 * @category   System
 * @package    Order
 * @subpackage Factories
 */
class OrderItemPropertyFactory implements OrderItemAttributeFactoryInterface
{
	/**
	 * Creates an order item attribute instance.
	 *
	 * @param StringType $name  Name of the property.
	 * @param StringType $value Value of the property.
	 *
	 * @return OrderItemProperty New order item attribute instance.
	 */
	public function createOrderItemAttribute(StringType $name, StringType $value)
	{
		return MainFactory::create('OrderItemProperty', $name, $value);
	}
	
	
	/**
	 * Creates a stored order item property instance.
	 *
	 * @param IdType $orderItemAttributeId Database ID of the stored property instance.
	 *
	 * @return StoredOrderItemProperty New order item attribute instance.
	 */
	public function createStoredOrderItemAttribute(IdType $orderItemAttributeId)
	{
		return MainFactory::create('StoredOrderItemProperty', $orderItemAttributeId);
	}
}