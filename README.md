# LeaseCalculator
租赁计算器

**功能描述：**
1.允许选择动态账期和固定账期
2.可以选择是否提前收租（当固定账期是只能选择固定日收租）
3.可设置分割日，分割日前提前收租
4.可规定超过一定天数后需要缴纳全月的租金
5.可设置月内每天的租金（默认是按照租金除以当月的日数作为日租金）

**实现说明：**
1.日期计算方面使用Carbon类计算日期

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