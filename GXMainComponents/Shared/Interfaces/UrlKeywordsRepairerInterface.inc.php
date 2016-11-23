<?php
/* --------------------------------------------------------------
   UrlKeywordsRepairerInterface.inc.php 2016-07-13
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2016 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Interface UrlKeywordsRepairerInterface
 *
 * @category   System
 * @package    Shared
 * @subpackage Interfaces
 */
interface UrlKeywordsRepairerInterface
{
	/**
	 * @param string $type all|products|categories|contents
	 *
	 * @return null
	 */
	public function repair($type = 'all');
}