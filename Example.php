<?php
require_once "./vendor/autoload.php";

if (isset($_GET['compute'])) {
    $renttime = $_GET['renttime'];
    $rent = $_GET['rent'];
    $mode = $_GET['mode'];
    $forward = $_GET['forward'];
    $forwardday = $_GET['forwardday'];
    $rentday = $_GET['rentday'];

    list($start, $end) = explode(' ~ ', $renttime);

    $comupte = new \Lease\Compute(['mode' => $mode, 'forward'=> $forward]);
    $options = [
        'starttime'     => strtotime($start),   // 开始时间 
        'endtime'       => strtotime($end),     // 结束时间
        'rent'          => $rent,               // 租金
        'rentday'       => $rentday,               // 固定收租日
    ];

    if ($mode == 1 && $forward) {
        // 提前收租日
        $options['forwardday'] = $forwardday;
    }

    $comupte->setOptions($options);
    $data = $comupte->getComputeCycle();
    if (!is_array($data)) {
        echo json_encode(['code' => 2, 'msg' => $comupte->getError()]);
        exit;
    }

    foreach ($data as &$d) {
        $d['pay'] = date('Y-m-d', $d['pay']);
        $d['start'] = date('Y-m-d', $d['start']);
        $d['end'] = date('Y-m-d', $d['end']);
        $d['rent'] = bcadd($d['rent'], 0, 2);
    }
    unset($d);


    echo json_encode(['code' => 0, 'msg' => 'ok', 'count' => count($data), 'data' => $data, 'options'=>$options]);
    exit;
}

echo json_encode(['code'=>1, 'msg'=>'error']);