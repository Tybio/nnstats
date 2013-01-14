<?php
/*

	Used to get stats about various newznab processes/status.

	Supported types are:
		count
		delta

	*** Untested: To get a single value, call $stats->getStats(<type>, <data>, <id>);
			eg: $stats->getStats(ppq, count, movie);
	To get an array with all stats for one type, call $stats->getStats(<type>, <data>);
			eg: $stats->getStats(ppq, count)

	To format the output, call smallTable with the array:
			$foo = $stats->getStats(ppq, count);
			$table = $stats->smallTable($foo);

	This library can reside anywhere, but the script calling it MUST include 
	  newznabs config.php.
*/
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");


class Stats {

	private function getSQL() {
		// Postprocessing Queue queries
		// SQL queries based on categories
		$NNQ['ppq']['count']['movie'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE imdbID IS NULL 
							AND categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_MOVIE);
		$NNQ['cat']['count']['movie'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_MOVIE);
		$NNQ['ppq']['count']['music'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE musicinfoID IS NULL 
							AND categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_MUSIC);
		$NNQ['cat']['count']['music'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_MUSIC);
		$NNQ['ppq']['count']['ebook'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE bookinfoID IS NULL 
							AND categoryID = %d 
							", Category::CAT_MISC_EBOOK);
		$NNQ['cat']['count']['ebook'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID = %d 
							", Category::CAT_MISC_EBOOK);
		$NNQ['ppq']['count']['tv'] = sprintf(" 
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE episodeinfoID < 0
							AND categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_TV);
		$NNQ['cat']['count']['tv'] = sprintf(" 
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_TV);
		$NNQ['ppq']['count']['game'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE consoleinfoID IS NULL 
							AND categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_GAME);
		$NNQ['cat']['count']['game'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_GAME);
		// SQL queries not based on categories
		$NNQ['ppq']['count']['nfo'] = sprintf("
							SELECT COUNT(id) 
							FROM releasenfo 
								WHERE nfo IS NULL 
								AND attempts <= 3
							");

		// Table Size
		$NNQ['trs']['count']['releases'] = 'SELECT COUNT(id) FROM `releases`';
		$NNQ['trs']['count']['parts'] = 'SELECT COUNT(id) FROM `parts`';

		return $NNQ;
	}

	public function getAllStats() {
		$NNQ = $this->getSQL();
		foreach ( $NNQ as $type => $data ) {
			$typeName = $type;
			foreach ( $data as $item => $value ) {
				$dataName = key($data);
				$array[$type][$dataName] = $this->getType($type, $dataName);
			}
		}
		return $array;
	}

	public function getStats($type, $data, $id = 'ALL' ) {
		if ( $id === 'ALL') { 
			return $this->getType($type, $data);
		} else {
			return $this->getID($type, $data, $id);
		}
	}

	public function saveStats($array) {
		foreach($array['cat']['count'] as $item => $value) {
			switch ( $item ) {
				case 'movie':
					$cat = Category::CAT_PARENT_MOVIE;
					break;
				case 'music':
					$cat = Category::CAT_PARENT_MUSIC;
					break;
				case 'ebook':
					$cat = Category::CAT_MISC_EBOOK;
					break;
				case 'tv':
					$cat = Category::CAT_PARENT_TV;
					break;
				case 'game':
					$cat = Category::CAT_PARENT_GAME;
					break;
			}
			$queue = $array['ppq']['count'][$item];
			printf("Name: $item ID: $cat Total: $value Queue: $queue\n");
			$sql = sprintf("
				INSERT INTO stats
				( categoryID, categoryname, total, queue )
				VALUES
				( '$cat', '$item', '$value', '$queue' )
			");
			$sdb = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, 'nnstats', DB_PORT);
			if ( $sdb->connect_error) {
				die('Connection Error: ('.$mysqli->connect_errno.') '.$mysqli->connect_error);
			}
			if( $sdb->query($sql) === TRUE ) {
				printf("DB Insert successful\n");
			} else {
				printf("Error: %s\n", $sdb->error);
			}
			$sdb->close();
		}	
		foreach($array['trs']['count'] as $item => $value) {
			switch ( $item ) {
				case 'releases':
					$cat = '10';
					break;
				case 'parts':
					$cat = '20';
					break;
			}
			printf("Name: $item ID: $cat Total: $value Queue: 0\n");
			$sql = sprintf("
				INSERT INTO stats
				( categoryID, categoryname, total )
				VALUES
				( '$cat', '$item', '$value' )
			");
			$sdb = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, 'nnstats', DB_PORT);
			if ( $sdb->connect_error) {
				die('Connection Error: ('.$mysqli->connect_errno.') '.$mysqli->connect_error);
			}
			if( $sdb->query($sql) === TRUE ) {
				printf("DB Insert successful\n");
			} else {
				printf("Error: %s\n", $sdb->error);
			}
			$sdb->close();
		}
		
	}

	private function getID($type, $data, $id) {
		$NNQ = $this->getSQL();
		$sql = $NNQ[$type][$data][$id];
		if ( empty($sql) ) { return false; }
		$db = new DB();
		$dbo = $db->query($sql);
		return $dbo['0']['COUNT(id)'];
	}

	private function getType($type, $data) {
		$NNQ = $this->getSQL();
		$list = $NNQ[$type][$data];
		foreach ( $list as $key => $i) {
			$db = new DB();
			if ( !$db ) { echo "DB Connection Failure"; } 
			$dbo = $db->query($i);
			$dump[$key] = $dbo;
		}
		$out = $this->cleanArray($dump);
		unset($dump);
		return $out;
	}

	private function cleanArray($array) {
		foreach ( $array as $key => $i) {
			$newarray[$key] = $array[$key]['0']['COUNT(id)'];
		}
		return $newarray;
	}
	public function statTable($type, $array) {
		$line = "+-----------------+-----------------+-----------------+-----------------+";
		$format = "| %-15s | %-15s | %-15s | %15s |";
		$h = array('Item', 'Total', 'Last', 'Delta');
		$content[] = $line;
		$content[] = sprintf($format, $h[0], $h[1], $h[2], $h[3]);
		$content[] = $line;
		foreach ($array[$type]['count'] as $key => $value) {
			$n[] = (isset($a['last'][$key]) ? number_format($array['last'][$key]) : 'na'); 
			$n[] = (isset($a['delta'][$key]) ? number_format($array['delta'][$key]) : 'na'); 
			$content[] = sprintf($format, $key, $value, $n[0], $n[1]);
		}
		$content[] = $line;
		return $content;
	}
}
?>
