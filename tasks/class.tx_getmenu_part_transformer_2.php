<?php
/**
     * @class   tree_transformer
     * @author  Paul Houle, Matthew Toledo
     * @created 2008-11-04
     * @url     http://gen5.info/q/2008/11/04/nested-sets-php-verb-objects-and-noun-objects/
     */
    class tx_getmenu_part_transformer_2 extends tx_scheduler_Task {
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
            
            $sql = "SELECT DISTINCT root FROM pages WHERE root > 0 AND deleted = 0 LIMIT 100,200";
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
                $this->getPages($row['root']);
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            
            //$this->addRootId();
	    
	    $executionSucceeded = TRUE;
	    
	    return $executionSucceeded;
        }
        
        
        private function getPages($root)
        {
// build a complete copy of the adjacency table in ram
            $a_link = array();
            $i_count = 0;
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