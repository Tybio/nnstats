<?php
/*

	Used to get stats about various newznab processes/status.

	Not all Categories have processing queues, to get a list of supported requests use $stats->getList(<type>);

	Supported types are:
		count
		delta

	To get a single value, call $stats->getstats(<type>, <id>);
	To get an array with all stats for one type, call $stats->getStats(<type>, ALL);

	Stats should always return a single numaric value

	This library can reside anywhere, but the script calling it MUST include 
	  newznabs config.php.
*/
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");


class Stats {

	public function __construct() {
		// SQL queries based on categories
		define('C_MOVIE', 'SELECT COUNT(id) FROM releases USE INDEX (ix_releases_categoryID) WHERE imdbID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_MOVIE.')');
		define('C_MUSIC', 'SELECT COUNT(id) FROM releases USE INDEX (ix_releases_categoryID) WHERE musicinfoID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_MUSIC.")");
		define('C_ANIME', 'SELECT COUNT(id) FROM releases WHERE anidbID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE categoryID = '.Category::CAT_TV_ANIME.")");
		define('C_TVRAGE', 'SELECT COUNT(id) FROM releases WHERE rageID = -1 AND categoryID in ( select ID from category WHERE parentID = '.Category::CAT_PARENT_TV.")");
		define('C_TVDB', 'SELECT COUNT(id) FROM releases WHERE episodeinfoID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_TV.")");
		define('C_GAME', 'SELECT COUNT(id) FROM releases USE INDEX (ix_releases_categoryID) WHERE consoleinfoID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_GAME.")");
		// SQL queries not based on categories
		define('C_NFO', 'SELECT COUNT(id) FROM `releasenfo` WHERE `nfo` IS NULL AND `attempts` <= 3');
		define('C_PART', 'SELECT COUNT(id) FROM `parts`');
		define('C_BIN', 'SELECT COUNT(id) FROM `binaries`');
	}

	public function getStats($type, $id = 'ALL' ) {
		if ( $id === 'ALL') { 
			return $this->getType($type);
		} else {
			return $this->getID($type, $id);
		}
	}

	public function getList($type) {
		$prefix = $this->getPrefix($type);
		// Returns a list of all valid IDs
		$list = get_defined_constants(true);
		foreach ($list['user'] as $key=>$value) {
			$match = '/^'.$prefix.'/';
			if ( preg_match($match, $key) ) {
				$trim = str_replace($prefix, '', $key);
   	 	    	$dump[$trim] = $value; 
       		}
        }
    	return $dump;
	}

	private function getID($type, $id) {
		$prefix = $this->getPrefix($type);
		if ( constant($prefix.$id) ) {
			$sql = constant($prefix."$id");
			$db = new DB();
			$dbo = $db->query($sql);
			return $dbo['0']['COUNT(id)'];
		} else {
			return false;
		}
	}

	private function getType($type) {
		$prefix = $this->getPrefix($type);
		foreach ( $this->getList($type) as $k => $i ) {
			$sql = constant($prefix.$k);
			$db = new DB();
			$dbo = $db->query($sql);
			$dump[$k] = $dbo;
		}
		$out = $this->cleanArray($dump);
		return $out;
	}

	private function getPrefix($type) {
		if ( $type === 'count' ) { return 'C_'; }
		if ( $type === 'delta' ) { return 'D_'; }
	}

	private function cleanArray($array) {
		foreach ( $array as $key => $i) {
			$newarray[$key] = $array[$key]['0']['COUNT(id)'];
		}
		return $newarray;
	}
	
}
?>