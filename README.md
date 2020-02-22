# LeaseCalculator
租赁计算器

提供三种方法进行计算每个月的租赁信息（租赁周期，租赁周期时间，每个周期的租金）
注：周期开始时间和结束时间返回时间戳

### 例子
```
// 固定账期
$comupte = new \Lease\ComputeRentCycle();
$comupte->setOptions([
    'starttime'     => $start,              // 开始时间 
    'endtime'       => $end,                // 结束时间
    'rent'          => $rent,               // 租金
    'rentday'       => $rentday,            // 固定收租日
    ]);
        
// 动态账期，提前日
$comupte = new \Lease\ComputeRentCycle(['mode'=>1, 'forward'=>1]);
$comupte->setOptions([
    'starttime'     => $start,              // 开始时间
    'endtime'       => $end,                // 结束时间
    'rent'          => $rent,               // 租金
    'forwardday'    => $forwardday,         // 提前天数
    ]);

// 动态账期，固定日
$comupte = new \Lease\ComputeRentCycle(['mode'=>1]);
$comupte->setOptions([
    'starttime'     => $start,              // 开始时间
    'endtime'       => $end,                // 结束时间
    'rent'          => $rent,               // 租金
    'rentday'       => $rentday,            // 固定收租日
    ]);
```