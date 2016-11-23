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
 * $Id: get_hitmeister_orders.php 1575 2012-03-18 23:00:00Z MaW $
 *
 * (c) 2010 RedGecko GmbH -- http://www.redgecko.de
 *     Released under the MIT License (Expat)
 * -----------------------------------------------------------------------------
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');


function magnaImportHitmeisterOrders($mpID) {
    global $magnaConfig, $_magnaLanguage, $_modules;
    $mp = 'hitmeister';

    #require_once(DIR_MAGNALISTER_MODULES.'hitmeister/hitmeisterFunctions.php');
    require_once(DIR_MAGNALISTER_INCLUDES.'lib/classes/SimplePrice.php');
    if (   ('oscommerce' == SHOPSYSTEM)
		&& (!function_exists('tep_encrypt_password'))
	    && ( file_exists(DIR_WS_FUNCTIONS.'password_funcs.php'))) {
        require_once(DIR_WS_FUNCTIONS.'password_funcs.php');
    }

    /*
    require_once(DIR_MAGNALISTER_INCLUDES . 'lib/MagnaTestDB.php');
    $MagnaDB = MagnaTestDB::gi();
    /*/
    $MagnaDB = MagnaDB::gi();
    //*/

    $character_set_client = MagnaDB::gi()->mysqlVariableValue('character_set_client');
    if (('utf8mb3' == $character_set_client) || ('utf8mb4' == $character_set_client)) {
	# means the same for us
		$character_set_client = 'utf8';
	}

    $verbose = (MAGNA_CALLBACK_MODE == 'STANDALONE') && (get_class($MagnaDB) == 'MagnaTestDB');

    $simplePrice = new SimplePrice();

    $ShopInfo = array(
        'CustomerGroup' => getDBConfigValue($mp.'.CustomerGroup', $mpID),
        'OrderStatusOpen' => getDBConfigValue($mp.'.orderstatus.open', $mpID),
        'FallbackCountryID' => MagnaDB::gi()->fetchOne('SELECT countries_id FROM '.TABLE_COUNTRIES.' LIMIT 1'),
    );

    $defaultShippingAndPaymentMethod = 'hitmeister';
    $ShopInfo['ShippingMethod'] = getDBConfigValue($mp.'.orderimport.shippingmethod', $mpID, 'textfield');
    if ($ShopInfo['ShippingMethod'] == 'textfield') {
        $ShopInfo['ShippingMethod'] = trim(getDBConfigValue($mp.'.orderimport.shippingmethod.name', $mpID, $defaultShippingAndPaymentMethod));
    }
    if (empty($ShopInfo['ShippingMethod'])) {
        $ShopInfo['ShippingMethod'] = $defaultShippingAndPaymentMethod;
    }
    $ShopInfo['PaymentMethod'] = getDBConfigValue($mp.'.orderimport.paymentmethod', $mpID, 'matching');
    if ($ShopInfo['PaymentMethod'] == 'textfield') {
        $ShopInfo['PaymentMethod'] = trim(getDBConfigValue($mp.'.orderimport.paymentmethod.name', $mpID, $defaultShippingAndPaymentMethod));
    }
    if (empty($ShopInfo['PaymentMethod'])) {
        $ShopInfo['PaymentMethod'] = $defaultShippingAndPaymentMethod;
    }

    $updateExchangeRate = getDBConfigValue(array($mp.'.exchangerate', 'update'), $mpID, false);

    $lastImport = getDBConfigValue($mp.'.orderimport.lastrun', $mpID, 0);
    if (preg_match('
            /^([1-2][0-9]{3})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s'.
            '([0-1][0-9]|2[0-4]):([0-5][0-9]):([0-5][0-9])$/',
            $lastImport
    )) {
        $lastImport = strtotime($lastImport.' +0000');
    } else {
        $lastImport = 0;
    }

    $begin = strtotime(getDBConfigValue($mp.'.preimport.start', $mpID, '1970-01-01'));
    if ($begin > time()) {
        if ($verbose) echo "Date in the future --> no import\n";
        return;
    }
    if ($begin < MAGNA_ORDERS_DATERANGE_BEGIN) {
        if ($verbose) echo "Date in the past --> fix date\n";
        $begin = MAGNA_ORDERS_DATERANGE_BEGIN;
    }

    if ( ($lastImport > 0) && ($begin < $lastImport) ) {
        $begin = $lastImport;
    }
    #$begin -= 60 * 60 * 24 * 12;

    $break = false;
    $offset = array (
        'COUNT' => 200,
        'START' => 0,
    );

    $lastOrder = '';
    $allCurrencies = array();
    # Bestelldaten abfragen.
    while (!$break) {
        @set_time_limit(60);
        $request = array(
            'ACTION' => 'GetOrdersForDateRange',
            'SUBSYSTEM' => 'Hitmeister',
            'MARKETPLACEID' => $mpID,
            'BEGIN' => gmdate('Y-m-d H:i:s', $begin),
            'OFFSET' => $offset,
        );
#DEBUG
#        echo print_m($request, 'request');
        try {
            $res = MagnaConnector::gi()->submitRequest($request);
        } catch (MagnaException $e) {
            $res = array();
            if (MAGNA_CALLBACK_MODE == 'STANDALONE') {
                echo print_m($e->getErrorArray(), 'Error: '.$e->getMessage(), true);
            }
            if (MAGNA_DEBUG && ($e->getMessage() == ML_INTERNAL_API_TIMEOUT)) {
                $e->setCriticalStatus(false);
            }
            $break = true;
        }
#DEBUG
#        echo print_m($res, 'res');

        if (!array_key_exists('DATA', $res) || empty($res['DATA'])) {
            return false;
        }

        $break = !$res['HASNEXT'];
        $offset['START'] += $offset['COUNT'];

        $orders = $res['DATA'];
        #echo print_m($orders, 'orders');
        # ggf. Zeichensatz korrigieren
        if ('utf8' != $character_set_client) {
            arrayEntitiesToLatin1($orders);
        }
		
		#check for gambio gm_send_order_status
		$existsGmSendOrderStatus = MagnaDB::gi()->columnExistsInTable('gm_send_order_status', TABLE_ORDERS);

        $syncBatch = array();

        # Bestelldaten durchgehen.
        foreach ($orders as $order) {
            if (!array_key_exists($order['order']['currency'], $allCurrencies)) {
                # Gibts die Waehrung auch im Shop?
                if (!$simplePrice->currencyExists($order['order']['currency'])) {
                    continue;
                }

                $simplePrice->setCurrency($order['order']['currency']);
                if ($updateExchangeRate) {
                    $simplePrice->updateCurrencyByService();
                }
                $currencyValue = $simplePrice->getCurrencyValue();
                if ((float)$currencyValue <= 0.0) {
                    continue;
                }
                $allCurrencies[$order['order']['currency']] = $currencyValue;
            }

            $simplePrice->setCurrency($order['order']['currency']);
            $order['order']['currency_value'] = $allCurrencies[$order['order']['currency']];

            #Land herausfinden
            if (!empty($order['orderInfo']['BillingCountryISO'])) {
                $countryRes = MagnaDB::gi()->fetchRow('
                    SELECT countries_id, countries_name, countries_iso_code_2
                      FROM '.TABLE_COUNTRIES.'
                     WHERE countries_iso_code_2=\''.$order['orderInfo']['BillingCountryISO'].'\' 
                     LIMIT 1
                ');
            } else {
                $countryRes = false;
            }
            if ($countryRes !== false) {
                $countryId   = $countryRes['countries_id'];
                $countryName = $countryRes['countries_name'];
            } else {
                $countryId   = $ShopInfo['FallbackCountryID'];
                $countryName = $order['order']['billing_country'];
            }

            if (MagnaDB::gi()->columnExistsInTable('customers_cid',TABLE_CUSTOMERS)) {
                $tmp_customer_id = MagnaDB::gi()->fetchRow('
                    SELECT customers_id, customers_cid FROM '.TABLE_CUSTOMERS.' 
                    WHERE customers_email_address=\''.$order['customer']['customers_email_address'].'\' 
                    LIMIT 1
                ');
                if (!$tmp_customer_id)
                    $customersId = false;
                else {
                    $customersId = $tmp_customer_id['customers_id'];
                    $customersCId = $tmp_customer_id['customers_cid'];
                }
            } else {
                $customersId = MagnaDB::gi()->fetchOne('
                    SELECT customers_id FROM '.TABLE_CUSTOMERS.'
                    WHERE customers_email_address=\''.$order['customer']['customers_email_address'].'\' LIMIT 1
                ');
            }

            # Muss ein neuer Kunde angelegt werden?
            if ($customersId === false) {
                $customers_password = randomString(10);
                $order['customer']['customers_password'] = md5($customers_password);

                if (SHOPSYSTEM != 'oscommerce') {
                    $order['customer']['customers_status'] = $ShopInfo['CustomerGroup'];
                    $order['customer']['account_type'] = '0';
				} else if (function_exists('tep_encrypt_password')) {
                    $order['customer']['customers_password'] = tep_encrypt_password($customers_password);
                }
                $MagnaDB->insert(TABLE_CUSTOMERS, $order['customer']);

                # Kunden-ID herausfinden
                $customersId = $MagnaDB->getLastInsertID();
                # customers_cid bestimmen
                if (MagnaDB::gi()->columnExistsInTable('customers_cid',TABLE_CUSTOMERS)) {
                    switch (getDBConfigValue('customers_cid.assignment', '0', 'none')) {
                        case 'sequential':
                            $customersCId = MagnaDB::gi()->fetchOne('
                            SELECT MAX(CAST(IFNULL(customers_cid,0) AS SIGNED))+1
                            FROM '.TABLE_CUSTOMERS);
                            break;
                        case 'customers_id':
                            $customersCId = $customersId;
                            break;
                        case 'none':
                        default:
                            if (isset($customersCId)) unset($customersCId);
                            break;
                    }
                    if (isset($customersCId))
                        MagnaDB::gi()->update(TABLE_CUSTOMERS, array('customers_cid' => $customersCId), array('customers_id' => $customersId));
                }

                # Infodatensatz erzeugen
				$MagnaDB->insert(TABLE_CUSTOMERS_INFO, array(
					'customers_info_id' => $customersId,
					'customers_info_number_of_logons' => 0,
					'customers_info_date_account_created' => date('Y-m-d H:i:s', strtotime($order['order']['date_purchased']) - 1),
					'customers_info_date_account_last_modified' => date('Y-m-d H:i:s'),
				));
                //echo 'DELETE FROM '.TABLE_CUSTOMERS_INFO.' WHERE customers_info_id=\''.$customersId.'\';'."\n\n";

                # Adressbuchdatensatz ergaenzen.
                $order['adress']['customers_id'] = $customersId;
                $order['adress']['entry_country_id'] = $countryId;

                $MagnaDB->insert(TABLE_ADDRESS_BOOK, $order['adress']);
                //echo 'DELETE FROM '.TABLE_ADDRESS_BOOK.' WHERE customers_id=\''.$customersId.'\';'."\n\n";

                # Adressbuchdatensatz-Id herausfinden.
                $abId = $MagnaDB->getLastInsertID();

                # Kundendatensatz updaten.
                $MagnaDB->update(TABLE_CUSTOMERS, array(
                    'customers_default_address_id' => $abId
                ), array (
                    'customers_id' => $customersId
                ));
            } else {
				# Adressbuchdatensatz erneuern
					$customer = $order['customer'];
					unset($customer['customers_date_added']);
					unset($customer['account_type']);
					$MagnaDB->update(TABLE_CUSTOMERS, $customer, array (
						'customers_id' => $customersId,
					));
					
					if (isset($order['adress']['address_date_added'])) {
						unset($order['adress']['address_date_added']);
					}
					$order['adress']['entry_country_id'] = $billingCountry['countries_id'];
					$MagnaDB->update(TABLE_ADDRESS_BOOK, $order['adress'], array (
						'customers_id' => $customersId,
					));
					
                # Falls Altkunde, haben wir kein Password, brauchen geeigneten Platzhalter
                switch($countryISO) {
                    case('AT'):
                    case('DE'): $customers_password = '(wie bekannt)';
                        break;
                    default:    $customers_password = '(as known)';
                        break;
                }
            }

            if (MagnaDB::gi()->recordExists(TABLE_MAGNA_ORDERS, array(
                'mpID' => $mpID,
                'special' => $order['orderInfo']['MOrderID']))
            ) {
                # Bestellung existiert bereits.
                continue;
            }

            # Bestellung anlegen.
            # Hauptdatensatz in Tabelle "orders".
            $order['order']['customers_id']            = $customersId;
            $order['order']['customers_country']       = $countryName;
            $order['order']['customers_address_format_id'] = magnaGetAddressFormatID($countryId);

            $order['order']['billing_country']         = $countryName;
            $order['order']['billing_address_format_id']   = magnaGetAddressFormatID($countryId);

            if (!empty($order['orderInfo']['ShippingCountryISO'])) {
                $countryRes = MagnaDB::gi()->fetchRow('
                    SELECT countries_id, countries_name, countries_iso_code_2
                      FROM '.TABLE_COUNTRIES.'
                     WHERE countries_iso_code_2=\''.$order['orderInfo']['ShippingCountryISO'].'\' 
                     LIMIT 1
                ');
            } else {
                $countryRes = false;
            }
            if ($countryRes !== false) {
                $countryIdShiping   = $countryRes['countries_id'];
                $countryNameShiping = $countryRes['countries_name'];
            } else {
                $countryIdShiping   = $ShopInfo['FallbackCountryID'];
                $countryNameShiping = $order['order']['delivery_country'];
            }

            $order['order']['delivery_country']           = $countryNameShiping;
            $order['order']['delivery_address_format_id'] = magnaGetAddressFormatID($countryIdShiping);

            # Status der Bestellung
            $order['order']['orders_status'] = $ShopInfo['OrderStatusOpen'];
            if ($ShopInfo['PaymentMethod'] == 'matching') {
                $order['order']['payment_method'] = $order['orderInfo']['PaymentMethod'];
            } else {
                $order['order']['payment_method'] = $ShopInfo['PaymentMethod'];
            }

            if (SHOPSYSTEM != 'oscommerce') {
                $order['order']['customers_cid'] = $customersCId;
                $order['order']['payment_class'] = $order['order']['payment_method'];
                $order['order']['shipping_class'] = $order['order']['shipping_method'] = $ShopInfo['ShippingMethod'];

                $order['order']['customers_status'] = $ShopInfo['CustomerGroup'];

                $order['order']['billing_country_iso_code_2'] = $order['orderInfo']['BillingCountryISO'];
                $order['order']['delivery_country_iso_code_2'] = $order['orderInfo']['ShippingCountryISO'];

                $order['order']['language'] = $_magnaLanguage;

                $order['order']['comments'] = trim(
                    sprintf(ML_GENERIC_AUTOMATIC_ORDER_MP_SHORT, $_modules['hitmeister']['title'])."\n".
                    ML_HITMEISTER_LABEL_ORDER_ID.': '.$order['orderInfo']['MOrderID']."\n\n".
                    $order['order']['comments']
                );
            }
			
			# $existsGmSendOrderStatus check for exsisting table colum gm_send_order_status
			if ($existsGmSendOrderStatus) {
				$order['order']['gm_send_order_status'] = 1;
			}
			
#DEBUG
#   echo "Bestell-Datensatz: ";print_r($order['order']);

            # Bestelldatensatz abspeichern.
            $MagnaDB->insert(TABLE_ORDERS, $order['order']);
            $ordersId = $MagnaDB->getLastInsertID();
            //echo 'DELETE FROM '.TABLE_ORDERS.' WHERE orders_id=\''.$ordersId.'\';'."\n\n";

            # Datensatz in magnalister_orders anlegen
#DEBUG
#   echo 'orderInfo ==';print_r($order['orderInfo']);
            $MagnaDB->insert(TABLE_MAGNA_ORDERS, array (
                'mpID' => $mpID,
                'orders_id' => $ordersId,
                'orders_status' => $order['order']['orders_status'],
                'data' => serialize($order['magnaOrders']),
                'special' => $order['orderInfo']['MOrderID'],
                'platform' => 'hitmeister'

            ));
            //echo 'DELETE FROM '.TABLE_MAGNA_ORDERS.' WHERE orders_id=\''.$ordersId.'\';'."\n\n";

            # Statuseintrag fuer Historie vornehmen.
            $order['orderStatus']['orders_id'] = $ordersId;
            $order['orderStatus']['orders_status_id'] = $order['order']['orders_status'];
            $order['orderStatus']['comments'] = trim(
                sprintf(ML_GENERIC_AUTOMATIC_ORDER_MP, $_modules['hitmeister']['title'])."\n".
                ML_HITMEISTER_LABEL_ORDER_ID.': '.$order['orderInfo']['MOrderID']."\n\n".
                $order['orderStatus']['comments']
            );

            $MagnaDB->insert(TABLE_ORDERS_STATUS_HISTORY, $order['orderStatus']);
            //echo 'DELETE FROM '.TABLE_ORDERS_STATUS_HISTORY.' WHERE orders_id=\''.$ordersId.'\';'."\n\n";

            foreach ($order['orderTotal'] as $key => &$entry) {
                $entry['orders_id'] = $ordersId;
                if (defined($entry['title'])) {
                    $entry['title'] = constant($entry['title']);
                }
                $entry['text'] = $simplePrice->setPrice($entry['value'])->format();
                $MagnaDB->insert(TABLE_ORDERS_TOTAL, $entry);
            }
            //echo 'DELETE FROM '.TABLE_ORDERS_TOTAL.' WHERE orders_id=\''.$ordersId.'\';'."\n\n";

            $mailOrderSummary = array();
            $taxes = array();
            foreach ($order['products'] as &$prodOrderData) {
				$productFound = false;
				if (isset($prodOrderData['products_model'])) {
					$prodOrderData['products_id'] = magnaSKU2pID($prodOrderData['products_model'], true);
					$productFound = (boolean)$prodOrderData['products_id'];
				} else if (isset($prodOrderData['products_id']) {
					$prodOrderData['products_id'] = magnaSKU2pID($prodOrderData['products_id'], true);
					$prodOrderData['products_model'] = MagnaDB::gi()->fetchOne('
						SELECT products_model FROM '.TABLE_PRODUCTS.'
						 WHERE products_id = '.$prodOrderData['products_id']
					);
					$productFound = (boolean)$prodOrderData['products_id'];
				} else if (isset($prodOrderData['products_ean'])) {
                    $pim = MagnaDB::gi()->fetchRow('
                        SELECT products_id, products_model FROM '.TABLE_PRODUCTS.'
                         WHERE products_ean = \''.$prodOrderData['products_ean'].'\'');
                    if (false !== $pim) {
                        $prodOrderData['products_id'] = $pim['products_id'];
                        $prodOrderData['products_model'] = $pim['products_model'];
                        unset($pim);
					}
					$productFound = (boolean)$prodOrderData['products_id'];
                }

				if (!$productFound) {
                    # nix gefunden -> shopfremdes Produkt
					if (!isset($prodOrderData['products_name'])) {
						if (isset($prodOrderData['products_model']) {
							$prodOrderData['products_name'] = $prodOrderData['products_model'];
						} else if (isset($prodOrderData['products_id']) {
							$prodOrderData['products_name'] = $prodOrderData['products_id'];
						} else if (isset($prodOrderData['products_ean']) {
							$prodOrderData['products_name'] = $prodOrderData['products_ean'];
						}
					}
                    $prodOrderData['products_id'] = 0;
                    $prodOrderData['products_model'] = '';
                }
                if ($productFound && (!isset($prodOrderData['products_name']))) {
                    $prodOrderData['products_name'] = MagnaDB::gi()->fetchOne('
                    SELECT pd.products_name
                    FROM '.TABLE_PRODUCTS_DESCRIPTION.'pd, '.TABLE_LANGUAGES.' l
                    WHERE pd.products_id = \''.$prodOrderData['products_id'].'\'
                    AND pd.language_id = l.languages_id
                    AND l.code = \''.strtolower($order['order']['customers_country']).'\'');
                    if (false == $prodOrderData['products_name']) {
                    # Fallback for default language
                        $languageId = MagnaDB::gi()->fetchOne('
                            SELECT languages_id
                                FROM '.TABLE_LANGUAGES.' l, '.TABLE_CONFIGURATION.' c
                                WHERE c.configuration_key = \'DEFAULT_LANGUAGE\'
                                AND c.configuration_value = l.code
                        ');
                        $prodOrderData['products_name'] = MagnaDB::gi()->fetchOne('
                            SELECT products_name FROM '.TABLE_PRODUCTS_DESCRIPTION.'
                            WHERE pd.products_id = \''.$prodOrderData['products_id'].'\'
                            AND language_id = '.$languageId
                        );
                    }
                }
                $mailOrderSummary[] = array(
                    'quantity' => $prodOrderData['products_quantity'],
                    'name' => $prodOrderData['products_name'],
                    'price' => $simplePrice->setPrice(
                        ($prodOrderData['final_price'] / $prodOrderData['products_quantity'])
                    )->format(),
                    'finalprice' => $simplePrice->setPrice($prodOrderData['final_price'])->format(),
                );

                if (array_key_exists($prodOrderData['products_id'], $syncBatch)) {
                    $syncBatch[$prodOrderData['products_id']]['NewQuantity']['Value'] += (int)$prodOrderData['products_quantity'];
                } else {
                    $syncBatch[$prodOrderData['products_id']] = array (
                        'SKU' => $prodOrderData['products_id'],
                        'NewQuantity' => array (
                            'Mode' => 'SUB',
                            'Value' => (int)$prodOrderData['products_quantity']
                        ),
                    );
                }

                # Produkt anlegen
                $prodOrderData['orders_id'] = $ordersId;

                #$attrValues = magnaSKU2pOpt($prodOrderData['SKU'], $order['orderInfo']['ShippingCountryISO']);
                #$prodOrderData['products_id'] = magnaSKU2pID($prodOrderData['SKU']);

                # Zusaetzliche Daten abrufen.
                $pData = MagnaDB::gi()->fetchRow('
                    SELECT * 
                      FROM '.TABLE_PRODUCTS.'
                     WHERE products_id=\''.$prodOrderData['products_id'].'\'
                     LIMIT 1
                ');

                if ($pData === false) {
                    $prodOrderData['products_model'] = $prodOrderData['products_model'];
                    $prodOrderData['products_id'] = 0;
                    $prodOrderData['products_tax'] = (float)getDBConfigValue($mp.'.mwstfallback', $mpID, 19);

                } else {
                    if (array_key_exists('products_shippingtime', $pData)) {
                        $prodOrderData['products_shipping_time'] = $pData['products_shippingtime'];
                    }
                    $prodOrderData['products_model'] = $pData['products_model'];
                    $prodOrderData['products_tax'] = SimplePrice::getTaxByClassID((int)$pData['products_tax_class_id'], (int)$countryId);

                    /* Lagerbestand reduzieren */
                    if (getDBConfigValue($mp.'.stocksync.frommarketplace', $mpID, 'no') != 'no') {
                        $MagnaDB->query('
                            UPDATE '.TABLE_PRODUCTS.' SET products_quantity = products_quantity - '.(int)$prodOrderData['products_quantity'].'
                             WHERE products_id='.(int)$pData['products_id']
                        );
                        /* Varianten-Bestand reduzieren, falls Produkt mit Varianten (gibt es bei osCommerce nicht) */
                        /*if (!empty($attrValues['options_name'])
                            && (SHOPSYSTEM != 'oscommerce')
                        ) {
                            $MagnaDB->query('
                                UPDATE '.TABLE_PRODUCTS_ATTRIBUTES.' SET attributes_stock = attributes_stock - '.(int)$prodOrderData['products_quantity'].'
                                 WHERE products_id='.(int)$prodOrderData['products_id'].' 
                                       AND options_id='.(int)$attrValues['options_id'].' 
                                       AND options_values_id='.(int)$attrValues['options_values_id'].'
                            ');
                        }*/
                    }
                }

                #unset($prodOrderData['SKU']);

                $prodOrderData['products_tax'] = (float)$prodOrderData['products_tax'];
                $priceWOTax = $simplePrice->setPrice($prodOrderData['products_price'])->removeTax($prodOrderData['products_tax'])->getPrice();

                if (!isset($taxes[$prodOrderData['products_tax']])) {
                    $taxes[$prodOrderData['products_tax']] = 0.0;
                }
                $taxes[$prodOrderData['products_tax']] += (float)($priceWOTax * (int)$prodOrderData['products_quantity']);

                if (SHOPSYSTEM == 'oscommerce') { /* osC speichert Preise netto ab */
                    $prodOrderData['products_price'] = $priceWOTax;
                    $prodOrderData['final_price'] = $prodOrderData['products_price'];
                }

                unset($prodOrderData['products_ean']);
                $MagnaDB->insert(TABLE_ORDERS_PRODUCTS, $prodOrderData);
                // orders_products_attributes:
                $prodOrderAttrData = array(
                    'orders_id' => $prodOrderData['orders_id'],
                    'orders_products_id' => $ordersProductsId,
                    'products_options' => $attrValues['options_name'],
                    'products_options_values' => $attrValues['options_values_name'],
                    'options_values_price' => 0.0,
                    'price_prefix' => ''
                );
                if (!empty($attrValues['options_name'])) {
                	if (MagnaDB::gi()->columnExistsInTable('products_options_id', TABLE_ORDERS_PRODUCTS_ATTRIBUTES)) {
						$prodOrderAttrData['products_options_id'] = $attrValues['options_id'];
						$prodOrderAttrData['products_options_values_id'] = $attrValues['options_values_id'];
					}
                    $MagnaDB->insert(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $prodOrderAttrData);
                }
            }
            //echo 'DELETE FROM '.TABLE_ORDERS_PRODUCTS.' WHERE orders_id=\''.$ordersId.'\';'."\n\n";               

            /* MwSt Versandkosten */
            if (array_key_exists('Shipping', $order['orderTotal'])) {
                $shippingTax = (float)getDBConfigValue($mp.'.mwst.shipping', $mpID);
                if ($shippingTax > 0) {
                    if (!isset($taxes[$shippingTax])) {
                        $taxes[$shippingTax] = 0.0;
                    }
                    $taxes[$shippingTax] += $simplePrice->setPrice($order['orderTotal']['Shipping']['value'])->removeTax($shippingTax)->getPrice();
                }
            }

            /* MwSt speichern */
            if (defined('MODULE_ORDER_TOTAL_TAX_STATUS') && (MODULE_ORDER_TOTAL_TAX_STATUS == 'true')) {
                $otc = 60;
                foreach ($taxes as $key => $sumprice) {
                    $taxEntry = array(
                        'orders_id' => $ordersId,
                        'title' => ML_LABEL_INCL.' '.round($key, 2).'% '.MAGNA_LABEL_ORDERS_TAX,
                        'value' => $simplePrice->setPrice($sumprice)->getTaxValue($key),
                        'class' => 'ot_tax',
                        'sort_order' => $otc
                    );
                    $taxEntry['text'] = $simplePrice->setPrice($taxEntry['value'])->format();
                    ++$otc;

                    $MagnaDB->insert(TABLE_ORDERS_TOTAL, $taxEntry);
                }
            }

                        if (getDBConfigValue($mp.'.mail.send', $mpID, 'false') == 'true') {
                sendSaleConfirmationMail(
                    $mpID,
                    $order['customer']['customers_email_address'],
                    array(
                        '#FIRSTNAME#' => $order['customer']['customers_firstname'],
                        '#LASTNAME#' => $order['customer']['customers_lastname'],
                        '#ORDERSUMMARY#' => $mailOrderSummary,
                        '#MARKETPLACE#' => $_modules['hitmeister']['title'],
                        '#SHOPURL#' => HTTP_SERVER.DIR_WS_CATALOG,
                    )
                );
            }
            $lastOrder = $order['order']['date_purchased'];
        }

        if (get_class($MagnaDB) != 'MagnaTestDB') {
        #    require_once(DIR_MAGNALISTER_CALLBACK.'inventoryUpdate.php');
        #    magnaInventoryUpdateByOrderImport(array_values($syncBatch), $mpID);
            if ($lastOrder !== '') {
                setDBConfigValue($mp.'.orderimport.lastrun', $mpID, $lastOrder, true);
            }
        }
    }
}
