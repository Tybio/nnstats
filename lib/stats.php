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
		$NNQ['ppq']['last']['movie'] = sprintf("SELECT queue FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_MOVIE); 	
		$NNQ['cat']['count']['movie'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_MOVIE);
		$NNQ['cat']['last']['movie'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_MOVIE); 	
		$NNQ['ppq']['count']['music'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE musicinfoID IS NULL 
							AND categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_MUSIC);
		$NNQ['ppq']['last']['music'] = sprintf("SELECT queue FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_MUSIC); 	
		$NNQ['cat']['count']['music'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_MUSIC);
		$NNQ['cat']['last']['music'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_MUSIC); 	
		$NNQ['ppq']['count']['ebook'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE bookinfoID IS NULL 
							AND categoryID = %d 
							", Category::CAT_MISC_EBOOK);
		$NNQ['ppq']['last']['ebook'] = sprintf("SELECT queue FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_MISC_EBOOK); 	
		$NNQ['cat']['count']['ebook'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID = %d 
							", Category::CAT_MISC_EBOOK);
		$NNQ['cat']['last']['ebook'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_MISC_EBOOK); 	
		$NNQ['ppq']['count']['tv'] = sprintf(" 
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE episodeinfoID < 0
							AND categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_TV);
		$NNQ['ppq']['last']['tv'] = sprintf("SELECT queue FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_TV); 	
		$NNQ['cat']['count']['tv'] = sprintf(" 
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_TV);
		$NNQ['cat']['last']['tv'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_TV); 	
		$NNQ['ppq']['count']['game'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE consoleinfoID IS NULL 
							AND categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_GAME);
		$NNQ['ppq']['last']['game'] = sprintf("SELECT queue FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_GAME); 	
		$NNQ['cat']['count']['game'] = sprintf("
							SELECT COUNT(id) 
							FROM releases 
								USE INDEX (ix_releases_categoryID) 
							WHERE categoryID IN 
								( SELECT ID FROM category WHERE parentID = %d )
							", Category::CAT_PARENT_GAME);
		$NNQ['cat']['last']['game'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", Category::CAT_PARENT_GAME); 	
		// SQL queries not based on categories
		$NNQ['ppq']['count']['nfo'] = sprintf("
							SELECT COUNT(id) 
							FROM releasenfo 
								WHERE nfo IS NULL 
								AND attempts <= 3
							");
		$NNQ['ppq']['last']['nfo'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", '30'); 	

		// Table Size (rows)
		$NNQ['trs']['count']['releases'] = 'SELECT COUNT(id) FROM `releases`';
		$NNQ['trs']['last']['releases'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", '10'); 	
		$NNQ['trs']['count']['parts'] = 'SELECT COUNT(id) FROM `parts`';
		$NNQ['trs']['last']['parts'] = sprintf("SELECT total FROM stats WHERE categoryID = %d order by updatedate desc limit 1", '20'); 	

		//Various Stats, not easy to bucket in NN terms
		//$NNQ['var']['count']['dbsize'] = sprintf("
		//					SELECT CONCAT(sum(ROUND(((DATA_LENGTH + INDEX_LENGTH - DATA_FREE) / 1024 / 1024),2)),\" MB\") 
		//					AS Size FROM INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA like %s", DB_NAME);

		return $NNQ;
	}

	public function getAllStats() {
		$NNQ = $this->getSQL();
		foreach ( $NNQ as $head => $data ) {
			foreach ( $data as $bucket => $item) {
				foreach ( $item as $element => $sql ) {
					if ( $bucket === 'count' ) {
						$array[$head][$bucket][$element] = $this->getDB($sql, 'nndb');
					} else {
						$array[$head][$bucket][$element] = $this->getDB($sql, 'sdb');
					}
				}
			}
		}
		return $array;
	}

	private function getCatID($name) {
		switch ( $name ) {
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
			case 'nfo':
				$cat = '30';
				break;
			case 'releases':
				$cat = '10';
				break;
			case 'parts':
				$cat = '20';
				break;
		}
		return $cat;
	}
	

	public function saveStats() {
		$array = $this->getAllStats();
		foreach($array['cat']['count'] as $item => $value) {
			$cat = $this->getCatID($item);
			$queue = $array['ppq']['count'][$item];
			$sql = sprintf("
				INSERT INTO stats
				( categoryID, categoryname, total, queue )
				VALUES
				( '$cat', '$item', '$value', '$queue' )
			");
			$sdb = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, 'nnstats', DB_PORT);
			$sdbo = $sdb->query($sql);
			$sdb->close();
		}	
		foreach($array['trs']['count'] as $item => $value) {
			if ( $item === 'releases' ) { $cat = '10'; }
			if ( $item === 'parts' ) { $cat = '20'; }
			$sql = sprintf("
				INSERT INTO stats
				( categoryID, categoryname, total )
				VALUES
				( '$cat', '$item', '$value' )
			");
			$sdb = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, 'nnstats', DB_PORT);
			$sdbo = $sdb->query($sql);
			$sdb->close();
		}
		$this->cleanStats();
		
	}
	
	private function cleanStats() {
		$sql = sprintf("DELETE FROM stats WHERE updatedate < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
		$sdb = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, 'nnstats', DB_PORT);
		$sdbo = $sdb->query($sql);
		$sdb->close();
	}
		

	private function getDB($sql, $loc) {
		switch ( $loc ) {
			case 'sdb':		
				$sdb = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, 'nnstats', DB_PORT);
				if ( !$sdb ) { die('Connect Error: ' . mysqli_connect_error()); }
				$sdbo = $sdb->query($sql);
				$rs = $sdbo->fetch_row();
				$sdb->close();
				return $rs[0];
			case 'nndb':
				$db = new DB();
				$dbo = $db->query($sql);
				if ( !$dbo ) { die('DB Connection Failure'); } 
				$rs = $dbo['0']['COUNT(id)'];
				return $rs;
			default:
				die('Bad call to getDB: '.$loc.' unknown');
		}
	}

	private function cleanArray($array) {
		foreach ( $array as $key => $i) {
			$newarray[$key] = $array[$key]['0']['COUNT(id)'];
		}
		return $newarray;
	}
	public function statTable($array) {
		$NNQ = $this->getSQL();
		$line = "+-----------------+-----------------+-----------------+-----------------+";
		$format = "| %-15s | %-15s | %-15s | %15s |";
		$hdrfmt = "| %-69s |";
		$h = array('Item', 'Total', 'Last', 'Delta');
		$content[] = $line;
		$content[] = sprintf($format, $h[0], $h[1], $h[2], $h[3]);
		foreach ( $NNQ as $head => $data ) {
			if ( $head === 'ppq' ) { $title = 'Postprocessing Queue'; }
			if ( $head === 'cat' ) { $title = 'Category Count'; }
			if ( $head === 'trs' ) { $title = 'Table Rows'; }
			$content[] = $line;
			$content[] = sprintf($hdrfmt, $title);
			$content[] = $line;
			foreach ( $data as $bucket => $item) {
				foreach ( $item as $key => $value ) {
					if ( $bucket === 'count' ) { 
						$tot = $array[$head][$bucket][$key];
						$las = $array[$head]['last'][$key];
						$delta = $tot - $las;
						$content[] = sprintf($format, $key, $tot, $las, $delta);
					}
				}
			}
		}
		$content[] = $line;
		return $content;
	}
}
?>
