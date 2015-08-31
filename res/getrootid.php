<?php
class user_getrootid {
    function main($content, $conf) {
        
	tslib_eidtools::connectDB();
        $sql = "SELECT SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1) AS rootid
        FROM pages AS node
        JOIN pages AS parent
        LEFT JOIN sys_template template ON parent.uid=template.pid
        WHERE node.lft BETWEEN parent.lft AND parent.rgt
        AND node.uid = " . $GLOBALS['TSFE']->id . "
        ORDER BY node.lft;";
	$res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
	$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        if(isset($row['rootid'])) {
            return $row['rootid'];
        } else {
            return $GLOBALS['TSFE']->id;
        }
    }
}