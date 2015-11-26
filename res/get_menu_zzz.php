<?php 
class user_get_menu {
    
    function makeMenuArray($content, $conf) 
    {
        //ToDo: language overlay!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $uid = intval($GLOBALS['TSFE']->id);
        $menuArr = $this->getPages($uid);
        return '<script language="javascript">var hamburger_array = ' . json_encode($menuArr[0]) . '; var nestedType = "' . $menuArr[1] . '";</script>'; 
    }
    
    
    function getPages($uid)	
    {
        $ii=0;
        $lvlOld = 0;
        $uidOld = 0;
        $unix_timestamp = time();

        $sql = "SELECT DISTINCT parent.uid AS parent_uid, node.uid AS node_uid, node.pid AS node_pid,
        CASE 
        WHEN TRIM(node.nav_title) = '' THEN node.title 
        ELSE node.nav_title 
        END AS title, PC.pagepath
FROM pages AS node JOIN
        pages AS parent
ON node.lft BETWEEN parent.lft AND parent.rgt 
        AND parent.uid = (
        SELECT SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1) 
        FROM pages AS node 
        JOIN pages AS parent 
        LEFT JOIN sys_template AS template ON parent.uid=template.pid AND template.root = 1 AND template.deleted = 0 AND template.hidden = 0
        WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.uid = $uid
        ORDER BY node.lft
        ) AND node.root = (
        SELECT SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1) 
        FROM pages AS node 
        JOIN pages AS parent 
        LEFT JOIN sys_template AS template ON parent.uid=template.pid AND template.root = 1 AND template.deleted = 0 AND template.hidden = 0
        WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.uid = $uid 
        ORDER BY node.lft
        )  AND node.deleted=0 AND node.hidden=0 AND node.nav_hide = 0 AND node.starttime <= $unix_timestamp AND 
        (node.endtime=0 OR node.endtime > $unix_timestamp ) 
        AND NOT node.t3ver_state>0 AND node.doktype < 199 AND (node.fe_group='' 
        OR node.fe_group IS NULL OR node.fe_group='0' OR FIND_IN_SET('0',node.fe_group) OR FIND_IN_SET('-1',node.fe_group)) 
        LEFT JOIN tx_realurl_pathcache AS PC ON PC.page_id = node.uid
        ORDER BY node.lft";
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $refs = array();
        $list = array();
        $ulist = "";
        while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
            $source[$row['node_uid']] = $row;
        }
        
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => print_r($source,true), 'crdate' => time()));
        $list = $this->makeNested($source);
        return array($list[0], $list[1]);
    }
    
    function makeNested($source) {
        $i = 0;
        $nestedType = 'one';
        $nestedFlag = true;
        try {
            $nested = array();
            foreach ( $source as &$s ) {
                if ( $s['node_uid'] == $s['parent_uid']) {
                    // no parent_id so we put it in the root of the array
                    $nested[] = &$s;
                    $nestedFlag = false;
                } else if( ($s['parent_uid'] == $s['node_pid']) && $nestedFlag === true ) {
                    $nested[] = &$s;
                    $nestedType = 'two';
                } else {
                    $pid = $s['node_pid'];
                    if ( isset($source[$pid]) ) {
                        // If the parent ID exists in the source array
                        // we add it to the 'children' array of the parent after initializing it.

                        if ( !isset($source[$pid]['_SUB_MENU']) ) {
                            $source[$pid]['_SUB_MENU'] = array();
                        }

                        $source[$pid]['_SUB_MENU'][] = &$s;
                    }
                }
                $i++;
            }
            //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => count($nested), 'crdate' => time()));
            /*if(count($nested)===0) {
                $nested[] = &$s;
            }*/
            return array($nested, $nestedType);
        } catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
        }
    }
    
    
    function convertToTree(array $flat, $idField = 'id',
                        $parentIdField = 'parentId',
                        $childNodesField = 'childNodes') {
    
    // first pass - get the array indexed by the primary id  
    foreach ($flat as $row) {
        $indexed[$row[$idField]] = $row;
        $indexed[$row[$idField]][$childNodesField] = array();
    }

    //second pass  
    $root = null;
    foreach ($indexed as $id => $row) {
        $indexed[$row[$parentIdField]][$childNodesField][$id] =& $indexed[$id];
        if (!$row[$parentIdField]) {
            $root = $id;
        }
    }

    return array($root => $indexed[$root]);
}
    
    
    function buildTree(array $elements, $parentId = 0) {
    $branch = array();

    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }

    return $branch;
}
    
    /**
    * Creates fakemenu array for use in HMENU
    *
    * @param	array		array of category root pids
    * @return	array		final menuarray
    */
    function createMenuArray($pagesArray){
        //foreach($rootLine as $k => $v){
            //if(in_array($v, $this->conf['excludeUidList'])) continue;
            $menuArray[$k]=$this->catArr[$v];
            $menuArray[$k]['ITEM_STATE'] = $menuArray[$k]['ITEM_STATE'] ? $menuArray[$k]['ITEM_STATE'] : 'NO';
            $menuArray[$k]['_SAFE'] = TRUE;
            $menuArray[$k]['_level'] = 0;
            $this->setHref($menuArray[$k]);
            $this->uid2cid($menuArray[$k]);
            $this->makeSubMenu($menuArray[$k],1);
        //}
        //debug($menuArray, 'menuArray', __LINE__, __FILE__,5);
        return $menuArray;
    }
    /**
     * Creates submenu records
     *
     * @param	array		menuArray
     * @return	void
     */
    function makeSubMenu(&$menuArray,$level){
        $subCount = FALSE;
        foreach($this->catArr as $v){
            if(in_array($v['uid'], $this->conf['excludeUidList'])) continue;
            if($menuArray['cid']==$v[$this->conf['parentEntry']]){
                $v['ITEM_STATE'] = $v['ITEM_STATE'] ? $v['ITEM_STATE'] : 'NO';
                $v['_SAFE'] = TRUE;
                $v['_level'] = $level;
                if (in_array('IFSUB', $this->conf['states'])) {
                    switch ($menuArray['ITEM_STATE']) {
                        case 'NO' :
                            $menuArray['ITEM_STATE'] ='IFSUB';
                        break;
                        case 'CUR' :
                        case 'ACT' :
                            $menuArray['ITEM_STATE'].='IFSUB';
                        break;
                    }
                }
                $this->setHref($v);
                $this->uid2cid($v);
                $this->makeSubMenu($v,$level+1);
                if ($this->conf['expAll'] || in_array($menuArray['cid'],$this->activeRootline)) {
                    $menuArray['_SUB_MENU'][]=$v;
                }
                if (!$subCount && $this->conf['subShortcut']){
                    $subCount = TRUE;
                    $menuArray['_OVERRIDE_HREF'] = $v['_OVERRIDE_HREF'];
                }
            }
        }
    }  
}
