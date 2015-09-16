<?php
class user_processCmdmap_postProcess {
    
    public function processCmdmap_postProcess($command, $table, $node_uid, $parent_uid, $pObj)
    {
        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => "$command, $table, $node_uid, $parent_uid", 'crdate' => time()));
	if($table=='pages') {
	    switch ($command) {
		case 'move':
                    if(substr($parent_uid, 0,1) === '-') {
                        $parent_uid = substr($parent_uid, 1);
                        $this->moveNodeAfter($node_uid, $parent_uid);
                    } else {
                        $this->moveNodeDirectUnderParent($node_uid, $parent_uid);
                    }
		    break;
                case 'copy':
                    if(substr($parent_uid, 0,1) === '-') {
                        $parent_uid = substr($parent_uid, 1);
                        $this->copyNodeAfter($node_uid, $parent_uid);
                    } else {
                        $this->copyNodeDirectUnderParent($node_uid, $parent_uid);
                    }
		    break;
		case 'delete':
		    $this->deleteNode($node_uid);
		    break;
	    }
	}
    }
    
    
    private function moveNodeDirectUnderParent($itemId, $newSiblingId)
    {
        $sql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);  
        
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
        
        //////$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $st, 'crdate' => time()));
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
    }
    
    
    private function copyNodeDirectUnderParent($oldItemId, $newSiblingId)
    {        
        $sql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $itemId = $this->getClonedNode($oldItemId);
        
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
    }
     
    
    private function moveNodeAfter($itemId, $newSiblingId)
    {
        $sql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);        
        
        $sql = "SELECT pid AS newParentId, root FROM pages WHERE uid = $newSiblingId";
        $st = $sql;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $newParentId = $row['newParentId'];
        $newRoot = $row['root'];

        $sql = "SELECT lft AS oldLeft, rgt AS oldRight FROM pages WHERE uid = $itemId LIMIT 1";
        $st .= $sql;
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if(isset($row['oldLeft']) and isset($row['oldRight'])) {

            $oldLeft = $row['oldLeft'];
            $oldRight = $row['oldRight'];
            $itemWidth = ($oldRight - $oldLeft) + 1;
            
            $sql = "UPDATE pages SET rgt=rgt*-1, lft=lft*-1 WHERE lft BETWEEN $oldLeft AND $oldRight";
            $st .= $sql;
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            //if($command=='move') {
                //Update right
                $sql = "UPDATE pages SET rgt = rgt - $itemWidth WHERE rgt > $oldRight";
                $st .= $sql;
                $GLOBALS['TYPO3_DB'] -> sql_query($sql);

                //Update left
                $sql = "UPDATE pages SET lft = lft - $itemWidth WHERE lft > $oldRight";
                $st .= $sql;
                $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            //}

            $sql = "SELECT (rgt+1) AS newLeft FROM pages  WHERE uid =$newSiblingId LIMIT 1";
                
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            if(isset($row['newLeft'])) {
                $newLeft = $row['newLeft'];
            } else if ($newLeft == 0 and $newParentId != 0) {
                $sql = "SELECT rgt AS newLeft FROM pages WHERE uid=$newParentId LIMIT 1";
                $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                $newLeft = $row['newLeft'];
            }

            //If no previous sibling or parent, set to first item in tree
            if ($newLeft===0 or $newLeft===null) {
                $newLeft=1;
            }

            //Update right
            $sql = "UPDATE pages SET rgt = rgt + $itemWidth WHERE rgt >= $newLeft";
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);

            //Update left
            $sql = "UPDATE pages SET lft = lft + $itemWidth WHERE lft >= $newLeft";
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
             //echo $newLeft;

            $moveBy = $oldLeft - $newLeft;
            $sql = "UPDATE pages SET rgt=(rgt* -1)-$moveBy, lft=(lft* -1)-$moveBy, root = $newRoot WHERE lft < 0";
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $sql = "UPDATE pages SET pid = $newParentId WHERE uid = $itemId";
            $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            
        } /*else {
            $sql = "SELECT rgt FROM pages WHERE uid = $newSiblingId";
            $st = $sql;
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            if(isset($row['rgt'])) {
                $rgt = $row['rgt'];
                $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > $rgt";
                $st .= $sql;
                $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > $rgt";
                $st .= $sql;
                $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                $sql = "UPDATE pages SET lft = $rgt + 1, rgt = $rgt + 2, root = $newRoot, pid = $newParentId WHERE uid = $itemId";
                //UPDATE pages SET lft = , rgt = 143 + 2 WHERE uid=1416
                $st .= $sql;
                $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            }
        }*/
      
        $sql = "UNLOCK TABLES";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $get_menuObj = new get_menu_functions;
        $get_menuObj->clearVarnishCacheForDomain($itemId);
            
        //Clear hamburger cache
        if($newRoot) {
            $get_menuObj->clearMenuCache($newRoot);
        }
        
        //////$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $st, 'crdate' => time()));
    }
    
    
    private function copyNodeAfter($oldItemId, $newSiblingId)
    {
        $sql = "LOCK TABLE pages WRITE";
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $itemId = $this->getClonedNode($oldItemId);
        
        $sql = "SELECT rgt, pid, root FROM pages WHERE uid = $newSiblingId";
        $st = $itemId.$sql;
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
            
            $sql = "UPDATE pages SET lft = $newRight + 1, rgt = $newRight + 2, root = $newRoot, pid = $newPid WHERE uid = $itemId";
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

            //////$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $st, 'crdate' => time()));
        }
        /*
         * SELECT @myRight := rgt FROM nested_category
WHERE name = 'TELEVISIONS';

UPDATE nested_category SET rgt = rgt + 2 WHERE rgt > @myRight;
UPDATE nested_category SET lft = lft + 2 WHERE lft > @myRight;

INSERT INTO nested_category(name, lft, rgt) VALUES('GAME CONSOLES', @myRight + 1, @myRight + 2);

         */
    }

    private function getClonedNode($node_uid)
    {
        $sql = "SELECT lft,rgt FROM pages WHERE uid=$node_uid";
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if(isset($row['lft']) and isset($row['rgt'])) {
            $lft = $row['lft'];
            $rgt = $row['rgt'];
            $sql = "SELECT uid FROM pages WHERE lft = $lft AND rgt = $rgt ORDER BY uid DESC LIMIT 1";
            $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            if(isset($row['uid'])) {
                return $row['uid'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    private function deleteNode($node_uid)
    {
        $unix_timestamp = time();
        
        //$varnishClean = false;
        
        $sql = "LOCK TABLE pages WRITE";
	$GLOBALS['TYPO3_DB'] -> sql_query($sql);
	
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
                /*$sql = "SELECT uid FROM pages WHERE rgt > $rgt OR lft > $rgt";
                $varnishClean = true;*/
	    }
	}
	
        $sql = "UNLOCK TABLES";
	$GLOBALS['TYPO3_DB'] -> sql_query($sql);
        
        $get_menuObj = new get_menu_functions;
        $get_menuObj->clearVarnishCacheForDomain($node_uid);
            
        //Clear hamburger cache
        $rootId = $get_menuObj->getRootId($parent_uid);
        if($rootId) {
            $get_menuObj->clearMenuCache($rootId);
        }
    }
    
    
    /*private function getTargetPage($parent_uid, $pid)
    {
        $unix_timestamp = time();
	//$limit = '0,1';
	if(substr($parent_uid, 0,1) === '-') {
	    $retVal = 'after_node';
	    $parent_uid = substr($parent_uid, 1);
	} else {
	    $retVal = 'before_node';
	    //$limit = '1,1';
	}
        
	$sql = "SELECT node.uid AS node_uid, node.lft AS node_lft, node.rgt AS node_rgt, parent.uid AS parent_uid, parent.lft AS parent_lft, 
            parent.rgt AS parent_rgt 
            FROM pages AS node JOIN pages AS parent ON node.uid = parent.pid AND node.hidden=0 AND node.deleted=0 
            AND node.hidden=0 AND node.starttime<=$unix_timestamp AND
            (node.endtime=0 OR node.endtime>$unix_timestamp) 
            AND NOT node.t3ver_state>0 AND node.doktype<200 AND (node.fe_group='' 
            OR node.fe_group IS NULL OR node.fe_group='0' OR FIND_IN_SET('0',node.fe_group) OR FIND_IN_SET('-1',node.fe_group))
            WHERE parent.uid=$parent_uid ORDER BY parent.sorting";
        
	$res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	if(isset($row['node_uid'])) {
	    if($retVal == 'before_node') {
		return array($row['parent_uid'], $row['parent_lft'], $row['parent_rgt'], $retVal);
	    } else {
		return array($row['node_uid'], $row['node_lft'], $row['node_rgt'], $retVal);
	    }
	} else {
	    return false;
	}
    }*/
}