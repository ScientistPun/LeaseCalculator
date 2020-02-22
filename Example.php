<?php
require_once "./vendor/autoload.php";

if (isset($_GET['compute'])) {
    $renttime = $_GET['renttime'];
    $rent = $_GET['rent'];
    $mode = $_GET['mode'];
    $forward = $_GET['forward'];
    $rentday = $_GET['rentday'];

    list($start, $end) = explode(' ~ ', $renttime);

    $comupte = new \Lease\Compute(['mode' => $mode]);
    $options = [
        'starttime'     => strtotime($start),   // 开始时间 
        'endtime'       => strtotime($end),     // 结束时间
        'rent'          => $rent,               // 租金
    ];

    if ($mode == 1 && $forward = 1) {
        // 提前收租日
        $options['forwardday'] = $forwardday;
    } else {
        // 固定收租日
        $options['rentday'] = $rentday;
    }

    $comupte->setOptions($options);
    $data = $comupte->getComputeCycle();
    foreach ($data as &$d) {
        $d['start'] = date('Y-m-d', $d['start']);
        $d['end'] = date('Y-m-d', $d['end']);
        $d['rent'] = bcadd($d['rent'], 0, 2);
    }
    unset($d);

    echo json_encode(['code' => 0, 'msg' => 'ok', 'count' => count($data), 'data' => $data]);
    exit;
}

echo json_encode(['code'=>1, 'msg'=>'error']);