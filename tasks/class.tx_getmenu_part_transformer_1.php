<?php
/**
     * @class   tree_transformer
     * @author  Paul Houle, Matthew Toledo
     * @created 2008-11-04
     * @url     http://gen5.info/q/2008/11/04/nested-sets-php-verb-objects-and-noun-objects/
     */
    class tx_getmenu_part_transformer_1 extends tx_scheduler_Task {
	private $i_count;
        private $a_link;
	
	public function traverse($i_id)
        {
            //echo $i_id . '; ';
            $i_lft = $this->i_count;
            $this->i_count++;

            $a_kid = $this->get_children($i_id);

            if ($a_kid) {
                foreach($a_kid as $a_child) {
                    $this->traverse($a_child);
                }
            }
            $i_rgt = $this->i_count;
            $this->i_count++;
            $this->write($i_lft, $i_rgt, $i_id);
        }   
	
	function execute($input_uid = 0)
	{
            //set_time_limit(0);
	    $executionSucceeded = FALSE;
            tslib_eidtools::connectDB();
            
            $sql = "SELECT DISTINCT CONCAT(file_path, file_name) as filetocheck FROM tx_dam 
WHERE uid IN(144767,13905,13905,201075,21979,21987,21977,21945,21935,21961,21963,127515,46511,181603,174487,171253,174613,171253,15537,15531,174917,172775,
15499,15459,15463,15517,15489,109399,109407,118957,118961,126671,126673,78255,146433,178391,178393,176119,176121,176123,176115,176117,125417,37571,42009,
187537,187539,78805,78805,76763,76769,76739,76781,76755,76773,76751,76759,112465,76767,76771,76757,102007,126917,126919,76785,76789,112151,181587,184725,
185405,185407,118279,118267,121385,124029,116601,118291,118289,120609,119581,118267,168941,138585,138595,3747,3751,3757,3771,194289,191973,191861,191701,
191705,191065,190641,186267,185873,185875,185873,185875,184879,184349,181563,181033,180595,180239,178717,178719,180219,180221,180575,180577,180927,180929,
180937,180941,181337,182201,182199,181975,149649,182477,182595,183725,183495,184005,184347,184877,184881,186771,186773,187585,187587,188883,188885,187885,
187887,188887,189669,151877,151891,151879,151881,151887,151893,151885,174027,194609,50755,141059,143921,143919,121935,50755,141059,148251,190409,227241,
181513,181591,171125)";
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
                $filetocheck = $row['filetocheck'];
                if(is_file('/var/www/html/typo3/'.$filetocheck)) {
                    echo "<p>$filetocheck ok</p>";
                } else {
                    echo "<p>$filetocheck NOT ok</p>";
                }
            }
	    $GLOBALS['TYPO3_DB']->sql_free_result($res);
            /*$sql = "SELECT uid, name FROM sys_file WHERE identifier LIKE '/_temp_/%'";
            $nameArray = array();
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
                $nameArray[$row['uid']] = $row['name'];
            }
            foreach($nameArray as $key => $value) {
                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tx_dam',"file_name='".$value."'");
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                $damuid = $row['uid'];
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file', 'uid='.intval($key), array('_migrateddamuid' => $damuid, 'damUid' => $damuid));
            }

            $GLOBALS['TYPO3_DB']->sql_free_result($res);

*/

/* $sql = "SELECT
  tt_news.uid,
  CONCAT('/_migrated/pics/',SUBSTRING_INDEX(SUBSTRING_INDEX(tt_news.image, ',', numbers.n), ',', -1)) fullname, SUBSTRING_INDEX(SUBSTRING_INDEX(tt_news.image, ',', numbers.n), ',', -1) filename
FROM
  (SELECT 1 n UNION ALL SELECT 2
   UNION ALL SELECT 3 UNION ALL SELECT 4) numbers INNER JOIN tt_news
  ON CHAR_LENGTH(tt_news.image)
     -CHAR_LENGTH(REPLACE(tt_news.image, ',', ''))>=numbers.n-1
     HAVING filename != ''";

            $ttnewsArray = array();
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
                $ttnewsArray[$row['filename']] = array($row['fullname'], $row['uid']);
            }

            $sql = "SELECT uid, name as filename FROM sys_file";
            $sysfileArray = array();
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
                $sysfileArray[$row['filename']] = $row['uid'];
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            $insertArray = array();
            foreach($ttnewsArray as $key => $value) {
                $suid = $sysfileArray[$key];
                $name = $key;
                $tuid = $value[1];
                $insertArray = array('pid' => 73417, 'l10n_diffsource' => '', 
                    'uid_local' => $tuid, 
                    'uid_foreign' => $suid, 'tablenames' => 'tt_news',
                    'field_name' => 'tx_falttnews_fal_images',
                    'sorting_foreign' => 1, 
                    'table_local' => 'sys_file',
                    'tstamp' => time());

                $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'sys_file_reference',
				array(
					'uid_local'   => $suid,
					'uid_foreign' => $tuid,
					'tablenames'  => 'tt_news',
					'fieldname'   => 'tx_falttnews_fal_images',
					'table_local' => 'sys_file',
				)
			);
                
            }
            
            //$this->addRootId();
	    */
	    $executionSucceeded = TRUE;
	    
	    return $executionSucceeded;
        }
        
        
        private function getPages($root)
        {
// build a complete copy of the adjacency table in ram
            $a_link = array();
            $this->i_count = 0;
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, pid', 'pages', 'doktype < 254 AND root = ' . $root);
            while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
                //$a_rows[] = $row;
                $i_father_id = $row['pid'];
		$i_child_id = $row['uid'];
		if (!array_key_exists($i_father_id, $a_link)) {
		    $a_link[$i_father_id] = array();
		}
		$a_link[$i_father_id][] = $i_child_id;
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
	    
	    if(!is_array($a_link)) {
                throw new Exception("First parameter should be an array. Instead, it was type '".gettype($a_link)."'");
            }
            $this->i_count = 1;
            $this->a_link = $a_link;
	    $this->traverse(0);
        }

        
        private function get_children($i_id) 
        {
	    if ( ! isset($this->a_link[$i_id])) {
		$this->a_link[$i_id] = null;
	    }

            return $this->a_link[$i_id];
        }

        
        private function write($i_lft, $i_rgt, $i_id) 
        {
            //echo "$i_lft,$i_rgt,$i_id;";
            // fetch the source column
	    $unix_timestamp = time();
            //$s_query = "SELECT * FROM pages WHERE uid=$i_id";
            /*$a_source = array();
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid='. $i_id);
            $a_source = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);*/
                

            /*if (!$i_result = mysql_query($s_query))
            {
                echo "<pre>$s_query</pre>\n";
                throw new Exception(mysql_error());  
            }
            $a_source = array();
            if (mysql_num_rows($i_result))
            {
                $a_source = mysql_fetch_assoc($i_result);
            }*/

            // root node?  label it unless already labeled in source table
            /*if (1 == $i_lft && empty($a_source['title']))
            {
                $a_source['title'] = 'ROOT';
            }*/

            // insert into the new nested tree table
            // use mysql_real_escape_string because one value "CD's"  has a single '
            /*$s_query = "
                INSERT INTO `nested_table`
                (`id`,`lft`,`rgt`,`title`)
                VALUES (
                    '".$i_id."',
                    '".$i_lft."',
                    '".$i_rgt."',
                    '".mysql_real_escape_string($a_source['title'])."'
                )
            ";*/
	    //$u_query = "UPDATE pages SET lft = $i_lft,rgt=$i_rgt WHERE uid = $i_id";
	    try {
                //echo $i_id . '; ';
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid='.intval($i_id), array('lft' => intval($i_lft), 'rgt' => intval($i_rgt), 'tstamp' => $unix_timestamp));
		
                //$i_result = mysql_query($u_query);
                //$GLOBALS['TYPO3_DB']->sql_free_result($i_result);
	    } catch(Exception $e) {
                die($e);
	    }
        }
        
        function addRootId()
        {
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', '', '', 'sorting');
            while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
                $rootid = $this->getRoot($row['uid']);
                if($rootid!==false) {
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid='.$row['uid'], array('root' => $rootid, 'tstamp' => time()));
                } else {
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid='.$row['uid'], array('root' => 0, 'tstamp' => time()));
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
        }
        
        function getRoot($uid)
        {
            /*$sql = "SELECT SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1) AS rootid
            FROM pages AS node
            JOIN pages AS parent ON node.lft BETWEEN parent.lft AND parent.rgt AND parent.deleted = 0
            LEFT JOIN sys_template template ON parent.uid=template.pid AND template.root=1
            WHERE node.uid = $uid
            ORDER BY node.lft";*/
            $res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery("SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1) AS rootid", 
                    "pages AS node JOIN pages AS parent ON node.lft BETWEEN parent.lft AND parent.rgt AND parent.deleted = 0 LEFT JOIN sys_template AS template ON parent.uid=template.pid AND template.root=1 AND template.deleted = 0 AND template.hidden = 0", 
                    "node.uid = " . $uid, "", "node.lft");
            //$res1 = $GLOBALS['TYPO3_DB']->sql_query($sql);
            $row1 = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res1);
            //Secho $row1['rootid'] . '; ';
            if(isset($row1['rootid'])){
                return $row1['rootid'];
            } else {
                return false;
            }
        }
        //	    LEFT JOIN pages p2 ON p1.uid=p2.pid AND p2.deleted=0 AND p2.hidden=0 AND p2.nav_hide=0 
        //	    AND p2.starttime<=$unix_timestamp AND (p2.endtime=0 OR p2.endtime>$unix_timestamp) 
        //	    AND NOT p2.t3ver_state>0 AND p2.doktype<200 AND (p2.fe_group='' 
        //	    OR p2.fe_group IS NULL OR p2.fe_group='0' OR FIND_IN_SET('0',p2.fe_group) OR FIND_IN_SET('-1',p2.fe_group))

    }