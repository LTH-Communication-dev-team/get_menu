<?php
class user_processCmdmap_postProcess {
    /*public function processCmdmap_afterFinish($pObj)
    {
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, queue, command', 'pages', 'queue IS NOT NULL AND command != \'\'');
        while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
            $node_uid = $row['uid'];
            $parent_uid = $row['queue'];
            $command = $row['command'];
            $this->moveNode($node_uid, $parent_uid, $command);
        }
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', '', array('queue' => NULL));
    }*/
    
    public function processCmdmap_postProcess($command, $table, $node_uid, $parent_uid, $pObj)
    {
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => "$command, $table, $node_uid, $parent_uid", 'crdate' => time()));
//move, pages, 150, -182
        if($table=='pages') {
	    switch ($command) {
		case 'move':
                case 'copy':
                    /*if($parent_uid < 1) {
                        //$this->moveNodeAfter($node_uid, abs($parent_uid));
                        $this->storeUpdate($node_uid, abs($parent_uid));
                    } else {
                        $this->storeUpdate($node_uid, $parent_uid);
                    }*/
                    //$this->storeUpdate($node_uid, $parent_uid, $command);
		    break;
                /*case 'copy':
                    if(substr($parent_uid, 0,1) === '-') {
                        $parent_uid = substr($parent_uid, 1);
                        $this->copyNodeAfter($node_uid, $parent_uid);
                    } else {
                        $this->copyNodeDirectUnderParent($node_uid, $parent_uid);
                    }
		    break;
		case 'delete':
		    $this->deleteNode($node_uid);
		    break;*/
	    }
	}
    }
    
    
    private function storeUpdate($node_uid, $parent_uid, $command)
    {
        if($node_uid && $parent_uid) {
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid='.intval($node_uid), array('queue' => $parent_uid, 'command' => $command));
        }
    }
    
    
        
    private function moveNode($node_id, $parent_id, $command)
    {                
        //$this->debug("$node_id, $parent_id, $command");
        $sql = "SELECT lft, rgt, root, pid FROM pages WHERE uid = " . intval($node_id);
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
        $node_pos_left = $row['lft'];
        $node_pos_right = $row['rgt'];
        $node_root = $row['root'];
        $node_pid = $row['pid'];
        
        if($parent_uid < 1) {
            $sql = "SELECT lft, rgt, root FROM pages WHERE uid = (SELECT pid FROM pages WHERE uid = " . abs($parent_id) . ")";
        } else {
            $sql = "SELECT lft, rgt, root FROM pages WHERE uid = " . intval($parent_id);
        }
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $parent_pos_left = $row['lft'];
        $parent_pos_right = $row['rgt'];
        $parent_root = $row['root'];
        
        if(!$node_pos_left) {
            //New page!
            return $this->newPage($node_id, $node_pos_right, $node_root, $parent_id, $parent_pos_right, $parent_root);
        }
        
        $node_size = intval($node_pos_right) - intval($node_pos_left) + 1; // 'size' of moving node (including all it's sub nodes)
        
        if($node_root != $parent_root) {
            if($command === 'move') {
                $this->deletePage($node_pos_left, $node_pos_right, $node_size, $node_root);
            }
            return $this->newPage($node_id, $node_pos_right, $node_root, $parent_id, $parent_pos_right, $parent_root);
        }
        
        if($command==='copy') {
            //Get new id
            $sql = "SELECT GROUP_CONCAT(node.uid) AS uids FROM pages node JOIN pages parent ON node.lft BETWEEN parent.lft AND parent.rgt WHERE parent.uid = (" .
                    "SELECT uid FROM pages WHERE lft = " . $node_pos_left . " AND rgt = " . $node_pos_right . " AND root = " . $node_root . " ORDER BY uid DESC LIMIT 1" .
                ")";
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            $uids = $row['uids'];
            if($uids) {
                $uidsArray = explode(',', $uids);
                $len = count($uidsArray);
                
                $firstHalf = array_slice($uidsArray, 0, $len / 2);
                $secondHalf = array_slice($uidsArray, $len / 2);
                $uids = implode(',', $secondHalf);
                $copyUids = "";// OR uid IN(" . implode(',', $firstHalf) . ") ";
            }
            $sql = "UPDATE pages SET lft = 0-(lft), rgt = 0-(rgt) WHERE uid IN(" . $uids . ")";
        } else {
            $sql = "UPDATE pages SET lft = 0-(lft), rgt = 0-(rgt) WHERE lft >= $node_pos_left AND rgt <= " . $node_pos_right . " AND root = " . $parent_root;
        }
        
        $lsql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($lsql);
        
        //step 1: temporary "remove" moving node
        //WHERE `pos_left` >= @node_pos_left AND `pos_right` <= @node_pos_right;
        
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        //echo $sql;
           
        //# step 2: decrease left and/or right position values of currently 'lower' items (and parents)
        //$updateArray = array('lft' => 'lft - ' . $node_size);
        if($command==='move') {
            $sql = "UPDATE pages SET lft = lft - " . $node_size . " WHERE lft > " . $node_pos_right . " AND root = " . $parent_root;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            //$this->debug($sql);

            //$updateArray = array('rgt' => 'rgt - ' . $node_size);
            $sql = "UPDATE pages SET rgt = rgt - " . $node_size . " WHERE rgt > " . $node_pos_right . " AND root = " . $parent_root;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            //echo $sql;
        }
        
        //step 3: increase left and/or right position values of future 'lower' items (and parents)
        //$updateArray = array('lft' => lft + $node_size);
        if($parent_pos_right > $node_pos_right) {
            //$updateWhere = lft >= strval(intval($parent_pos_right) - intval($node_size));
            $sPos = intval($parent_pos_right) - intval($node_size);
            $sql = "UPDATE pages SET lft = lft + " . $node_size . " WHERE lft >= " . $sPos . " AND root = " . $parent_root;
        } else {
            //$updateWhere = lft >= (string)$parent_pos_right;
            $sql = "UPDATE pages SET lft = lft + " . $node_size . " WHERE lft >= " . $parent_pos_right . " AND root = " . $parent_root;
        }
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        //$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', (string)$updateWhere, $updateArray);
        //echo $sPos.';'.$sql;

        //$updateArray = array('rgt' => rgt + $node_size);
        if($parent_pos_right > $node_pos_right) {
            //$updateWhere = lft >= strval(intval($parent_pos_right) - intval($node_size));
            $sPos = intval($parent_pos_right) - intval($node_size);
            $sql = "UPDATE pages SET rgt = rgt + " . $node_size . " WHERE lft >= " . $sPos . " AND root = " . $parent_root;
        } else {
            //$updateWhere = lft >= (string)$parent_pos_right;
            $sql = "UPDATE pages SET rgt = rgt + " . $node_size . " WHERE lft >= " . $parent_pos_right . " AND root = " . $parent_root;
        } 
        //$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', (string)$updateWhere, $updateArray);
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        //echo $sql;

        //step 4: move node (and it's subnodes)
        if($parent_pos_right > $node_pos_right) {
            $newPos = intval($parent_pos_right) - intval($node_pos_right) - 1;
        } else {
            $newPos = intval($parent_pos_right) - intval($node_pos_right) - 1 + intval($node_size);
        }
        if($command==='copy') {
            $sql = "UPDATE pages SET lft = 0-(lft) + " . $newPos . ", rgt = 0-(rgt) + " . $newPos . ", root = " . $parent_root . " WHERE uid IN(" . $uids . ")";
        } else {
            $sql = "UPDATE pages SET lft = 0-(lft) + " . $newPos . ", rgt = 0-(rgt) + " . $newPos . ", root = " . $parent_root . " WHERE lft <= 0-".$node_pos_left." AND rgt >= 0-" . $node_pos_right . " AND root = " . $parent_root;
        }
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

        //$updateArray = array('lft' => 0-(lft)+$newPosLeft, 'rgt' => 0-(rgt)+$newPosRight, 'root' => $parent_root);
        //$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'lft <= 0-'.(string)$node_pos_left . ' AND rgt >= 0-' . (string)$node_pos_right, $updateArray);
        //echo $sql;
        
        $lsql = "UNLOCK TABLES";
        $GLOBALS['TYPO3_DB'] -> sql_query($lsql);
        
        //$sql = "UNLOCK TABLES";
        //$GLOBALS['TYPO3_DB'] -> sql_query($sql);
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $st, 'crdate' => time()));
        $get_menuObj = new get_menu_functions;
        $get_menuObj->clearVarnishCacheForDomain($node_id);
            
        //Clear hamburger cache
        if($newRoot) {
            $get_menuObj->clearMenuCache($parent_root);
        }
        
    }
    
    
    private function newPage($node_id, $node_pos_right, $node_root, $parent_id, $parent_pos_right, $parent_root)
    {
        $lsql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($lsql);
        
        if($node_pid > 0) {
            $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > " . $parent_pos_right . " AND root = " . $parent_root;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > " . $parent_pos_right . " AND root = " . $parent_root;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            $sql = "UPDATE pages SET lft = " . ($parent_pos_right + 1) . ", rgt = " . ($parent_pos_right + 2) . ", root = " . $parent_root . " WHERE uid = " . $node_id;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        } else {
            //Top level! Get page above
            $sql = "SELECT lft, rgt, root FROM pages WHERE uid = " . abs($parent_id);
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            $node_pos_right = $row['rgt'];
            $node_root = $row['root'];

            $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > " . $node_pos_right . " AND root = " . $parent_root;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > " . $node_pos_right . " AND root = " . $parent_root;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            $sql = "UPDATE pages SET lft = " . ($node_pos_right + 1) . ", rgt = " . ($node_pos_right + 2) . ", root = " . $node_root . " WHERE uid = " . $node_id;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        }
        $lsql = "UNLOCK TABLES";
        $GLOBALS['TYPO3_DB'] -> sql_query($lsql);
        
        return 200;
    }
    
    
    private function deletePage($node_pos_left, $node_pos_right, $node_size, $node_root)
    {
        $sql = "LOCK TABLE pages WRITE";
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

        $sql = "UPDATE pages SET rgt = rgt - " . $node_size . " WHERE rgt > " . $node_pos_right . " AND root = " . $node_root;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

        $sql = "UPDATE pages SET lft = lft - " . $node_size . " WHERE lft > " . $node_pos_right . " AND root = " . $node_root;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

        $sql = "UNLOCK TABLES";
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        return 200;
    }
    
    
  /**  private function moveNodeDirectUnderParent($itemId, $newSiblingId)
    {
       // $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => "37:$itemId, $newSiblingId", 'crdate' => time()));
        $sql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        //$sql = "SELECT lft AS oldLeft, rgt AS oldRight FROM pages WHERE uid = $itemId LIMIT 1";
        $sql = "SELECT GROUP_CONCAT(node.uid) AS nodes, parent.lft AS oldLft
            FROM pages AS node
            JOIN pages AS parent ON node.lft BETWEEN parent.lft AND parent.rgt
            WHERE parent.uid = " . intval($itemId);
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if(isset($row['oldLeft']) and isset($row['nodes'])) {
            $oldLft = $row['oldLft'];
            //$sql = "UPDATE pages SET rgt=rgt*-1, lft=lft*-1 WHERE lft BETWEEN $oldLeft AND $oldRight";
            $sql = "UPDATE pages SET rgt=rgt*-1, lft=lft*-1 WHERE uid IN($nodes)";
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        }
        
        $sql = "SELECT lft, root FROM pages WHERE uid = " . intval($newSiblingId);
        $st = $sql;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if(isset($row['lft'])) {
            $lft = $row['lft'];
            $root =  $row['root'];
            $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > $lft";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > $lft";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            
            $moveBy = $oldLft - $lft;
            //$sql = "UPDATE pages SET rgt=(rgt* -1)-$moveBy, lft=(lft* -1)-$moveBy, root = $newRoot WHERE lft < 0";
            $sql = "UPDATE pages SET rgt=(rgt* -1)-$moveBy, lft=(lft* -1)-$moveBy, root = $root WHERE uid in($nodes)";
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            //$sql = "UPDATE pages SET pid = $newSiblingId, root = $newRoot WHERE uid = $itemId";
            //$GLOBALS['TYPO3_DB'] -> sql_query($sql);
            
            //$sql = "UPDATE pages SET lft = $lft + 1, rgt = $lft + 2, root = $newRoot, pid = $newSiblingId WHERE uid = $itemId";
            //$st .= $sql;
            //$GLOBALS['TYPO3_DB'] -> sql_query($sql);
        }
        
        $sql = "UNLOCK TABLES";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $get_menuObj = new get_menu_functions;
        $get_menuObj->clearVarnishCacheForDomain($itemId);
            
        //Clear hamburger cache
        if($newRoot) {
            $get_menuObj->clearMenuCache($newRoot);
        }
        
        //////$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $st, 'crdate' => time()));
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
    }*/
    
    
    /*private function copyNodeDirectUnderParent($itemId, $newSiblingId)
    {        
        $sql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $itemIdArray = $this->getClonedNodes($itemId);
        
        $sql = "SELECT lft, root FROM pages WHERE uid = $newSiblingId";
        $st = $sql;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if(isset($row['lft'])) {
            $lft = $row['lft'];
            $newRoot =  $row['root'];
            $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > $lft";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > $lft";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $sql = "UPDATE pages SET lft = $lft + 1, rgt = $lft + 2, root = $newRoot, pid = $newSiblingId WHERE uid = $itemId";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        }
        
        $sql = "UNLOCK TABLES";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $get_menuObj = new get_menu_functions;
        $get_menuObj->clearVarnishCacheForDomain($itemId);
            
        //Clear hamburger cache
        if($newRoot) {
            $get_menuObj->clearMenuCache($newRoot);
        }
        
        ////$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $st, 'crdate' => time()));
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
     * 
    }*/
    
    
    private function debug($msg)
    {
        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $msg, 'crdate' => time()));
    }
    
    
    /*private function copyNode($node_id, $parent_id)
    {
        $sql = "SELECT rgt, root FROM pages WHERE uid = " . $parent_id;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $rgt = $row['rgt'];
        $root = $row['root'];

        $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > " . $rgt;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > " . $rgt;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);

        $sql = "UPDATE pages SET lft = " . $rgt + 1 . ",rgt = " . $rgt + 2 . ", root = " . $root;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
    }*/
    
    
   /* private function copyNodeAfter($oldItemId, $newSiblingId)
    {
        //68,77
        $sql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $itemIds = $this->getClonedNode($oldItemId);
        
        
        $sql = "SELECT rgt, pid, root FROM pages WHERE uid = " . intval($newSiblingId);
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if(isset($row['rgt'])) {
            $newRight = $row['rgt'];
            $newRoot = $row['root'];
            $newPid = $row['pid'];
            
            //Update right
            $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > $newRight";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            //Update left
            $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > $newRight";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            
            $sql = "UPDATE pages SET lft = $newRight + 1, rgt = $newRight + 2, root = $newRoot, pid = $newPid WHERE uid = " . $itemIds[0];
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            
            $sql = "UNLOCK TABLES";
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            $get_menuObj = new get_menu_functions;
            $get_menuObj->clearVarnishCacheForDomain($itemId);

            //Clear hamburger cache
            if($newRoot) {
                $get_menuObj->clearMenuCache($newRoot);
            }

            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $st, 'crdate' => time()));
        }
    }

    private function getClonedNodes($node_uid)
    {
        $uidArray = array();
        $sql = "SELECT p1.uid AS uid1, p2.uid AS uid2, p3.uid AS uid3, p4.uid AS uid4, p5.uid AS uid5, p6.uid AS uid6, p7.uid AS uid7, p8.uid AS uid8
        FROM pages p1 
        LEFT JOIN pages p2 ON p1.uid = p2.pid
        LEFT JOIN pages p3 ON p2.uid = p3.pid
        LEFT JOIN pages p4 ON p3.uid = p4.pid
        LEFT JOIN pages p5 ON p4.uid = p5.pid
        LEFT JOIN pages p6 ON p5.uid = p6.pid
        LEFT JOIN pages p7 ON p6.uid = p7.pid
        LEFT JOIN pages p8 ON p7.uid = p8.pid
        WHERE p1.uid = " . intval($node_uid);
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
            if($row['uid1']) $uidArray[] = $row['uid1'];
            if($row['uid2']) $uidArray[] = $row['uid2'];
            if($row['uid3']) $uidArray[] = $row['uid3'];
            if($row['uid4']) $uidArray[] = $row['uid4'];
            if($row['uid5']) $uidArray[] = $row['uid5'];
            if($row['uid6']) $uidArray[] = $row['uid6'];
            if($row['uid7']) $uidArray[] = $row['uid7'];
            if($row['uid8']) $uidArray[] = $row['uid8'];
        }
        $uidArray = array_unique($uidArray);
        return $uidArray;
    }
    
    private function deleteNode($node_uid)
    {
        $unix_timestamp = time();
        
        //$varnishClean = false;
        
        //$sql = "LOCK TABLE pages WRITE";
	//$GLOBALS['TYPO3_DB'] -> sql_query($sql);
	
	$sql = "SELECT uid
        FROM pages AS node join pages AS parent
        ON node.lft BETWEEN parent.lft AND parent.rgt AND parent.uid = $node_uid AND node.hidden=0 AND node.deleted=0 
            AND node.hidden=0 AND node.starttime<=$unix_timestamp AND
            (node.endtime=0 OR node.endtime>$unix_timestamp) 
            AND NOT node.t3ver_state>0 AND node.doktype<200 AND (node.fe_group='' 
            OR node.fe_group IS NULL OR node.fe_group='0' OR FIND_IN_SET('0',node.fe_group) OR FIND_IN_SET('-1',node.fe_group))
        GROUP BY node.label
        ORDER BY node.lft";
	$res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
	$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	
	if(count($row) > 1) {
	    $sql = "SELECT lft, rgt, (rgt - lft) + 1 AS my_width FROM pages WHERE uid = $node_uid";
	    $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
	    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	    if(isset($row['rgt'])) {
		$rgt = $row['rgt'];
		$lft = $row['lft'];
		$my_width = $row['my_width'];
		$sql = "UPDATE pages SET rgt = rgt - 1, lft = lft - 1 WHERE lft BETWEEN $lft AND $rgt";
		$GLOBALS['TYPO3_DB'] -> sql_query($sql);
		$sql = "UPDATE pages SET rgt = rgt - 2 WHERE rgt > $rgt";
		$GLOBALS['TYPO3_DB'] -> sql_query($sql);
		$sql = "UPDATE pages SET lft = lft - 2 WHERE lft > $rgt";
		$GLOBALS['TYPO3_DB'] -> sql_query($sql);
                //$varnishClean = true;
	    }
	} else {
	    $sql = "SELECT lft, rgt, (rgt - lft) + 1 AS my_width FROM pages WHERE uid = $node_uid";
	    $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
	    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	    if(isset($row['rgt'])) {
		$rgt = $row['rgt'];
		$lft = $row['lft'];
		$my_width = $row['my_width'];
		$sql = "UPDATE pages SET rgt = rgt - $my_width WHERE rgt > $rgt";
		$GLOBALS['TYPO3_DB'] -> sql_query($sql);
		$sql = "UPDATE pages SET lft = lft - $my_width WHERE lft > $rgt";
		$GLOBALS['TYPO3_DB'] -> sql_query($sql);
                //$sql = "SELECT uid FROM pages WHERE rgt > $rgt OR lft > $rgt";
                //$varnishClean = true;
	    }
	}
	
        //$sql = "UNLOCK TABLES";
	//$GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $get_menuObj = new get_menu_functions;
        $get_menuObj->clearVarnishCacheForDomain($node_uid);
            
        //Clear hamburger cache
        $rootId = $get_menuObj->getRootId($parent_uid);
        if($rootId) {
            $get_menuObj->clearMenuCache($rootId);
        }
    }*/
}