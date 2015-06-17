<?php 
class user_get_menu { 
    function makeMenuArray($uid) 
    {
        //ToDo: language overlay!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $lvlOld = 0;
        $uidOld = 0;
        $levelIndex = 0;
        $treeArray = array();
        $branchArray = array();
        $nodeArray = array();
        $ii=0;
        $json = '{ "hmenu":[';
        $new = array();
        $link = mysql_connect('localhost', 'typo3', 'night95Tunisia') or die('Could not connect: ' . mysql_error());
mysql_select_db('t3_4720') or die('Could not select database');
        //$uid = intval($GLOBALS['TSFE']->id);
        $sql = "SELECT parent.uid AS parent_uid, node.uid, node.pid,
            CASE 
                WHEN TRIM(node.nav_title) = '' THEN node.title
                ELSE node.nav_title
            END AS title, COUNT(parent.uid) AS lvl
            FROM pages AS node JOIN pages AS parent ON node.lft BETWEEN parent.lft AND parent.rgt and parent.deleted=0 AND node.deleted=0
            GROUP BY node.uid
            HAVING parent.uid=(SELECT SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1)
            FROM pages AS node
            JOIN pages AS parent
            LEFT JOIN sys_template template ON parent.uid=template.pid AND template.root = 1
            WHERE node.lft BETWEEN parent.lft AND parent.rgt
            AND node.uid = $uid
            ORDER BY node.lft)
            ORDER BY node.lft";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
            if($ii>0) {
                $parent_uid = $row['parent_uid'];
                $uid = $row['uid'];
                $pid = $row['pid'];
                $title = trim($row['title']);
                $lvl = $row['lvl'];
                
                $tmpJson = "{\"title\":\"$title\",\"_OVERRIDE_HREF\":\"index.php?id=$uid\"}";

                if($lvl > $lvlOld && $ii>1) {
                    //Sublevel
                    $json = substr($json,0,-1);
                    $json .= ", \"_SUB_MENU\":[{\"title\":\"$title\",\"_OVERRIDE_HREF\":\"index.php?id=$uid\"}";
                    $levelIndex++;
                } else if($lvl < $lvlOld) {
                    for($i==0;$levelIndex;$i++){
                        $json .= "]}";
                        $levelIndex--;
                    }
                } else {
                    //same level
                    if(substr($json,-1)=='}') {
                        $json .= ",";
                    }
                    $json .= "{\"title\":\"$title\",\"_OVERRIDE_HREF\":\"index.php?id=$uid\"}";
                }
                $lvlOld = $lvl;
                $uidOld = $uid;
            }
            $ii++;
        }
        mysql_free_result($res);
        mysql_close($link);
        $json .= "]}";

        $output = json_decode($json, true);
        echo '<pre>';
        print_r($output['hmenu']);
         echo '</pre>';
         echo json_last_error();
          /* die();
"employees":[
    {"firstName":"John", "lastName":"Doe"}, 
    {"firstName":"Anna", "lastName":"Smith"}, 
    {"firstName":"Peter","lastName":"Jones"}
]         
var foo = {
    "logged_in":true,
    "town":"Dublin",
    "state":"Ohio",
    "country":"USA",
    "products":
    [
        {
            "pic_id":"1500",
            "description":"Picture of a computer",
            "localion":"img.cloudimages.us/2012/06/02/computer.jpg",
            "type":"jpg",
            "childrenimages":
            [
                {
                    "pic_id":"15011",
                    "description":"Picture of a cpu",
                    "localion":"img.cloudimages.us/2012/06/02/mycpu.png",
                    "type":"png"
                },
                {
                    "pic_id":"15012",
                    "description":"Picture of a cpu two",
                    "localion":"img.cloudimages.us/2012/06/02/thiscpu.png",
                    "type":"png"
                }
            ]
        },
        {
            "pic_id":"1501",
            "description":"Picture of a cpu",
            "localion":"img.cloudimages.us/2012/06/02/cpu.png",
            "type":"png"
        }
    ],
};         */

        /*return array(
            array(
                'title' => 'Contact',
                '_OVERRIDE_HREF' => 'index.php?id=10',
                '_SUB_MENU' => array(
                    array(
                        'title' => 'Offices',
                        '_OVERRIDE_HREF' => 'index.php?id=11',
                       'ITEM_STATE' => 'ACT',
                       '_SUB_MENU' => array(
                           array(
                               'title' => 'Copenhagen Office',
                               '_OVERRIDE_HREF' => 'index.php?id=11&officeId=cph',
                           ),
                           array(
                               'title' => 'Paris Office',
                               '_OVERRIDE_HREF' => 'index.php?id=11&officeId=paris',
                           ),
                           array(
                               'title' => 'New York Office',
                               '_OVERRIDE_HREF' => 'http://www.example.com',
                               '_OVERRIDE_TARGET' => '_blank',
                           )
                       )
                   ),
                   array(
                       'title' => 'Form',
                       '_OVERRIDE_HREF' => 'index.php?id=10&cmd=showform',
                   ),
                   array(
                       'title' => 'Thank you',
                       '_OVERRIDE_HREF' => 'index.php?id=10&cmd=thankyou',
                   ),
               ),
           ),
           array(
               'title' => 'Products',
               '_OVERRIDE_HREF' => 'index.php?id=14',
           )
         );*/
    }
    
    function createTree($list, $parent){
        $tree = array();
        foreach ($parent as $k=>$l){
            if(isset($list[$l['uid']])){
                $l['children'] = $this->createTree($list, $list[$l['uid']]);
            }
            $tree[] = $l;
        } 
        return $tree;
    }
}

$treeObj = new user_get_menu;
$treeObj->makeMenuArray('213');

