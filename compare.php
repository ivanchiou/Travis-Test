<?php

require_once("config.php");
require_once 'MDB2.php';

$dsn = array(
    'phptype'  => 'mysql',
    'username' => $db['username'],
    'password' => $db['password'],
    'hostspec' => $db['hostname'],
    'database' => $db['database'],
);

$mdb2 =& MDB2::connect($dsn);
if (PEAR::isError($mdb2)) {
    die($mdb2->getMessage());
}


function get_by_union($mdb2) {
    $begin_time = microtime(true);

    $month_counts = 36;
    $date_union_string = "";
    for ($i = 0; $i <= $month_counts; $i++) {
        $date = date('Y-m', strtotime("-$i month"));
        $date_union_string .= "SELECT '".$date."' AS Date";
        if($i < $month_counts){
            $date_union_string .= " UNION ";
        }
    }
    $sql = "SELECT tmpTable2.Date, IF(count IS NULL, 0, count) AS Downloads FROM (SELECT DATE_FORMAT(`date`, '%Y-%m') AS Date , COUNT(*) AS count FROM records WHERE model='DV-700' GROUP BY DATE_FORMAT(date, '%Y%m') ORDER BY Date) tmpTable1 RIGHT JOIN ($date_union_string) tmpTable2 ON tmpTable1.Date = tmpTable2.Date";

    $res = $mdb2->query($sql);
    $mdb2->disconnect();
    $i = 0;
    while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $data[$i]['Date'] = $row['date'];
        $data[$i]['Downloads'] = $row['downloads'];
        $i++;
    }

    $result = json_encode($data);

    $total_time = microtime(true) - $begin_time;

    return $total_time;
}

function get_by_array($mdb2) {
    $begin_time = microtime(true);

    $sql = "select DATE_FORMAT(`date`, '%Y-%m') AS Date, COUNT(*) AS Downloads from records where model = 'DV-700' group by DATE_FORMAT(date, '%Y%m')";

    $data = $mdb2->queryAll($sql);
    $mdb2->disconnect();

    // Create Array and Fille 0 in empty month
    $data = array();
    $end_time = date('Y-m');
    for ($i=0; $i<=36; $i++) {
        $date = date('Y-m', strtotime("-$i month"));
        $downloads = (isset($result_data[$date])) ? $result_data[$date] : "0";

        $data[$i]["Date"] = $date;
        $data[$i]["Downloads"] = $downloads;
    }

    $result = json_encode($data);

    $total_time = microtime(true) - $begin_time;

    return $total_time;

}

echo get_by_union($mdb2);
echo "\n";
echo get_by_array($mdb2);
