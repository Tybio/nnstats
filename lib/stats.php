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

	private function getSQL() {
		// Postprocessing Queue queries
		// SQL queries based on categories
		$NNQ['ppq']['count']['movie'] = 'SELECT COUNT(id) FROM releases USE INDEX (ix_releases_categoryID) WHERE imdbID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_MOVIE.')';
		$NNQ['ppq']['count']['music'] = 'SELECT COUNT(id) FROM releases USE INDEX (ix_releases_categoryID) WHERE musicinfoID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_MUSIC.")";
		$NNQ['ppq']['count']['anime'] = 'SELECT COUNT(id) FROM releases WHERE anidbID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE categoryID = '.Category::CAT_TV_ANIME.")";
		$NNQ['ppq']['count']['tvrage'] = 'SELECT COUNT(id) FROM releases WHERE rageID = -1 AND categoryID in ( select ID from category WHERE parentID = '.Category::CAT_PARENT_TV.")";
		$NNQ['ppq']['count']['tvdb'] = 'SELECT COUNT(id) FROM releases WHERE episodeinfoID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_TV.")";
		$NNQ['ppq']['count']['game'] = 'SELECT COUNT(id) FROM releases USE INDEX (ix_releases_categoryID) WHERE consoleinfoID IS NULL AND categoryID IN ( SELECT ID FROM category WHERE parentID = '.Category::CAT_PARENT_GAME.")";
		// SQL queries not based on categories
		$NNQ['ppq']['count']['movie'] = 'SELECT COUNT(id) FROM `releasenfo` WHERE `nfo` IS NULL AND `attempts` <= 3';

		// Table Size
		$NNQ['tablerows']['count']['movie'] = 'SELECT COUNT(id) FROM `releases`';
		$NNQ['tablerows']['count']['movie'] = 'SELECT COUNT(id) FROM `parts`';
		$NNQ['tablerows']['count']['movie'] = 'SELECT COUNT(id) FROM `binaries`';
	}

	public function getStats($type, $data, $id = 'ALL' ) {
		if ( $id === 'ALL') { 
			return $this->getType($type, $data);
		} else {
			return $this->getID($type, $data, $id);
		}
	}

	private function getID($type, $data, $id) {
			$sql = $NNQ[$type][$data][$id];
			$db = new DB();
			$dbo = $db->query($sql);
			return $dbo['0']['COUNT(id)'];
		} else {
			return false;
		}
	}

	private function getType($type, $data) {
		$list = $NNQ[$type][$data];
		$db = new DB();
		foreach ( $list as $key => $i) {
			$dbo = $db->query($i);
		}
		$out = $this->cleanArray($dump);
		return $out;
	}

	private function cleanArray($array) {
		foreach ( $array as $key => $i) {
			$newarray[$key] = $array[$key]['0']['COUNT(id)'];
		}
		return $newarray;
	}
	
}
?>