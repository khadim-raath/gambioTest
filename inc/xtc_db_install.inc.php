<?php
/* --------------------------------------------------------------
  xtc_db_install.inc.php 2015-04-04 gm
  Gambio GmbH
  http://www.gambio.de
  Copyright (c) 2015 Gambio GmbH
  Released under the GNU General Public License (Version 2)
  [http://www.gnu.org/licenses/gpl-2.0.html]
  --------------------------------------------------------------


  based on:
  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
  (c) 2002-2003 osCommerce(database.php,v 1.2 2002/03/02); www.oscommerce.com
  (c) 2003	 nextcommerce (xtc_db_install.inc.php,v 1.3 2003/08/13); www.nextcommerce.org
  (c) 2003 XT-Commerce - community made shopping http://www.xt-commerce.com ($Id: xtc_db_install.inc.php 899 2005-04-29 02:40:57Z hhgag $)

  Released under the GNU General Public License
  --------------------------------------------------------------------------------------- */

function xtc_db_install($database, $sql_file)
{
	global $db_error;

	$db_error = false;

	if(!@xtc_db_select_db($database))
	{
		if(@xtc_db_query_installer('create database ' . $database))
		{
			xtc_db_select_db($database);
		}
		else
		{
			$db_error = ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
		}
	}

	if(!$db_error)
	{
		if(file_exists($sql_file))
		{
			$fd = fopen($sql_file, 'rb');
			$restore_query = fread($fd, filesize($sql_file));
			fclose($fd);
			$restore_query .= "\n";
		}
		else
		{
			$db_error = 'SQL file does not exist: ' . $sql_file;
			return false;
		}

		$sql_array = array();
		$sql_length = strlen($restore_query);
		$pos = strpos($restore_query, ';');
		
		for($i = $pos; $i < $sql_length; $i++)
		{
			if($restore_query[0] == '#')
			{
				$restore_query = ltrim(substr($restore_query, strpos($restore_query, "\n")));
				$sql_length = strlen($restore_query);
				$i = strpos($restore_query, ';') - 1;
				continue;
			}
			
			if($restore_query[($i + 1)] == "\n")
			{
				for($j = ($i + 2); $j < $sql_length; $j++)
				{
					if(trim($restore_query[$j]) != '')
					{
						$next = substr($restore_query, $j, 7);
						
						if($next[0] == '#')
						{
							// find out where the break position is so we can remove this line (#comment line)
							for($k = $j; $k < $sql_length; $k++)
							{
								if($restore_query[$k] == "\n")
									break;
							}
							
							$query = substr($restore_query, 0, $i + 1);
							$restore_query = substr($restore_query, $k);
							// join the query before the comment appeared, with the rest of the dump
							$restore_query = $query . $restore_query;
							$sql_length = strlen($restore_query);
							$i = strpos($restore_query, ';') - 1;
							continue 2;
						}
						break;
					}
				}
				
				if($next == '')
				{ // get the last insert query
					$next = 'insert';
				}
				
				if((preg_match('/create/i', $next)) || (preg_match('/insert/i', $next)) || (preg_match('/drop ta/i', $next)) || (preg_match('/alter t/i', $next)))
				{
					$next = '';
					$sql_array[] = substr($restore_query, 0, $i);
					$restore_query = ltrim(substr($restore_query, $i + 1));
					$sql_length = strlen($restore_query);
					$i = strpos($restore_query, ';') - 1;
				}
			}
		}

		for($i = 0; $i < sizeof($sql_array); $i++)
		{
			xtc_db_query_installer($sql_array[$i]);
			$GLOBALS['total_executed_queries'] += 1;
		}
	}
	else
	{
		return false;
	}
}
