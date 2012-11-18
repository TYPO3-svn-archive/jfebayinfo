<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Juergen Furrer <juergen.furrer@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Plugin 'ebay articles' for the 'jfebayinfo' extension.
 *
 * @author	Juergen Furrer <juergen.furrer@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_jfebayinfo
 */
class tx_jfebayinfo_pi1 extends tslib_pibase
{
	public $prefixId	  = 'tx_jfebayinfo_pi1';
	public $scriptRelPath = 'pi1/class.tx_jfebayinfo_pi1.php';
	public $extKey		= 'jfebayinfo';
	public $pi_checkCHash = TRUE;

	/**
	 * The main method of the Plugin.
	 *
	 * @param string $content The Plugin content
	 * @param array $conf The Plugin configuration
	 * @return string The content that is displayed on the website
	 */
	public function main($content, array $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$cacheFile = PATH_site . $this->conf['cacheFolder'] . $this->conf['cacheFile'];

		$url = $this->cObj->cObjGetSingle($this->conf['requestUrl'], $this->conf['requestUrl.']);

		if (file_exists($cacheFile) && (filemtime($cacheFile) + $this->conf['cacheTime']) > time()) {
			// take the cache-File
		} else {
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse']) {
				// Open url by curl
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER , FALSE);
				curl_setopt($ch, CURLOPT_POST, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->conf['timeout']);
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']) {
					curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']);
				}
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
					curl_setopt($ch, CURLOPT_PROXY, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);
				}
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']) {
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']);
				}
				$apicall = curl_exec($ch);
				curl_close($ch);
			} else {
				// Open url by fopen
				set_time_limit($this->conf['timeout']);
				$apicall = file_get_contents($url);
			}
			// write the file to the cache...
			$file = fopen($cacheFile, "w");
			fwrite($file, $apicall);
			fclose($file);
		}

		$content = NULL;

		$resp = simplexml_load_file($cacheFile);
		if ($resp->ack == "Success") {
			$results = '';
			// If the response was loaded, parse it and build links
			foreach($resp->searchResult->item as $ikey => $item) {
				foreach ($item as $fkey => $field) {
					if (gettype(key($field)) == 'integer') {
						$this->cObj->data['item_'.$fkey] = $field;
					} else {
						foreach ($field as $skey => $subField) {
							$this->cObj->data['item_'.$fkey.'_'.$skey] = $subField;
						}
					}
				}
				$content .= $this->cObj->cObjGetSingle($this->conf['content'], $this->conf['content.']);
			}
		} else {
			// if not Success, the cache-file will be removed...
			unlink($cacheFile);
			$content = "No success request! Please try again later";
		}

		$content = $this->cObj->stdWrap($content, $this->conf['stdWrap.']);

		return $this->pi_wrapInBaseClass($content);
	}
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/jfebayinfo/pi1/class.tx_jfebayinfo_pi1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/jfebayinfo/pi1/class.tx_jfebayinfo_pi1.php']);
}

?>