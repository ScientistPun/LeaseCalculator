<?php
// +----------------------------------------------------------------------
// | 计算租赁周期
// +----------------------------------------------------------------------
// | Author: panzq
// +----------------------------------------------------------------------
// | Last Modify Time: 2019-09-27
// +----------------------------------------------------------------------
namespace Lease;
use Carbon\Carbon;

class Compute
{
    /**
     * @var array 相关配置
     */
    private $_config = [
        'mode'      => 0,            // 模式 0固定月头月尾账期 1动态账期
        'forward'   => 0,            // 0固定日 1提前N天，提前方式只能动态账期才设置有效
        'cutdays'   => 15,           // 分割日
        'maxday'    => 0,           // 大于0：超过多少天算一个月，0：按照当月的多少天算一个月
        'decimal'   => 2,            // 保留小数位数
    ];

    /**
     * @var int 租赁开始时间
     */
    private $starttime = 0;
    /**
     * @var int 租赁结束时间
     */
    private $endtime = 0;
    /**
     * @var int 固定收租日
     */
    private $rentday = 0;
    /**
     * @var int 提前N天收租
     */
    private $forwardday = 0;
    /**
     * @var float 租金
     */
    private $rent = 0;

    /**
     * @var array 设置结果
     */
    private $cycles = [];


    public function __construct($_cfg = [])
    {
        $_cfg['mode'] = !isset($_cfg['mode']) ? $this->_config['mode'] : $_cfg['mode'];
        $_cfg['forward'] = !isset($_cfg['forward']) ? $this->_config['forward'] : $_cfg['forward'];
        $_cfg['cutdays'] = !isset($_cfg['cutdays']) ? $this->_config['cutdays'] : $_cfg['cutdays'];
        $_cfg['maxday'] = !isset($_cfg['maxday']) ? $this->_config['maxday'] : $_cfg['maxday'];
        $_cfg['decimal'] = !isset($_cfg['decimal']) ? $this->_config['decimal'] : $_cfg['decimal'];

        $this->_config = $_cfg;
    }

    /**
     * 设置属性
     * @param float $_options['rent'] 租金
     * @param float $_options['starttime'] 租赁开始
     * @param float $_options['endtime'] 租赁结束
     */
    public function setOptions($_options = [])
    {
        $this->rent = !isset($_options['rent']) ? $this->rent : $_options['rent'];
        $this->forwardday = !isset($_options['forwardday']) ? $this->forwardday : $_options['forwardday'];
        $this->rentday = !isset($_options['rentday']) ? $this->rentday : $_options['rentday'];
        $this->endtime = !isset($_options['endtime']) ? $this->endtime : $_options['endtime'];
        $this->starttime = !isset($_options['starttime']) ? $this->starttime : $_options['starttime'];
    }

    /**
     * 获取计算后的结果
     */
    public function getComputeCycle()
    {
        if ($this->rent < 1) {
            $this->setError('请填写正确的租金金额');
            return false;
        }

        if (date('Ym', $this->starttime) == date('Ym', $this->endtime) || $this->starttime >= $this->endtime) {
            $this->setError('请填写正确的租赁日期');
            return false;
        }

        // 固定月头月尾账期
        if ($this->_config['mode'] == 0) {
            if ($this->rentday < 1 || $this->rentday > 28) {
                $this->setError('收租日请填写在1-28号之间');
                return false;
            }
            $this->computeFixedMonth();

            // 动态账期
        } else {
            // 提前
            if ($this->_config['forward'] == 1) {
                if ($this->forwardday < 0 || $this->forwardday > 28) {
                    $this->setError('提前天数请填写在1-28之间');
                    return false;
                }
                $this->computeForwardDay();

                // 固定
            } else {
                if ($this->rentday < 1 || $this->rentday > 28) {
                    $this->setError('收租日请填写在1-28号之间');
                    return false;
                }
                $this->computeFixedDay();
            }
        }

        return $this->cycles;
    }

    /**
     * 计算固定月头月尾的周期，首月和尾月的租金按天算
     */
    private function computeFixedMonth()
    {
        // 保留小数位数
        $dec = $this->_config['decimal'];
        // 租赁时长
        $starttime = $this->starttime;
        $endtime = $this->endtime;
        // 收租日
        $rentday = $this->rentday;
        // 租金
        $rent = $this->rent;
        // 分割日
        $cutdays = $this->_config['cutdays'];


        // 是否继续计算
        $break = false;

        $_pay = $_start = $starttime;

        $_index = 1;
        $cycles = [];

        do {
            $_end = Carbon::createFromTimestamp($starttime);
            // 如果为第一期直接选择当月最后一天
            if ($_index == 1) {
                $_end = $_end->lastOfMonth()->timestamp;
            } else {
                // 结束时间 = 本月结束
                $_end = $_end->firstOfMonth()->month($_end->month + ($_index - 1))->lastOfMonth()->timestamp;
            }

            // 超过多少天算一个月
            $maxday = $this->getMonthDays($_start);
            // 每天租金
            $everyrent = $maxday == 0 ? 0 : bcdiv($rent, $maxday, $dec + 2);

            // 历经天数
            $_days = Carbon::createFromTimestamp($_start)->subDay()->diffInDays(Carbon::createFromTimestamp($_end));

            // 如果年份和月份和租赁结束时间一期一样，结束日期为租赁结束日
            // 首期 或 尾期
            if ($_index == 1 || date('Ym', $_end) == date('Ym', $endtime)) {
                // 如果是尾期
                if (date('Ym', $_end) == date('Ym', $endtime)) {
                    $break = true;
                    $_end = $endtime;
                }

                $_days = Carbon::createFromTimestamp($_start)->subDay()->diffInDays(Carbon::createFromTimestamp($_end));

                // 如果最後一期的租日为一个月
                if ($_days >= $maxday) {
                    $_rent = $rent;
                    // 如果最後一期的租日为不止一个月
                    $_rent = bcadd($_rent, bcmul($everyrent, ($_days - $maxday), $dec), $dec);
                } else {
                    $_rent = bcmul($everyrent, $_days, $dec);
                }
            } else {
                $_rent = $rent;
            }

            // 塞入周期栈
            $cycles[] = ['index' => $_index, 'pay' => $_pay, 'start' => $_start, 'end' => $_end, 'rent' => $_rent, 'days' => $_days];
            $_index++;

            if ($_index == 2 && date('d', $starttime) >= $cutdays) {
                $_pay = $_pay;
            } else {
                $_pay = Carbon::create(date('Y', $starttime), date('m', $starttime), $rentday);
                // 如果开始时间小于等于收租日
                if ($rentday >= $cutdays) {
                    $_pay = $_pay->month($_pay->month + ($_index - 2))->timestamp;
                } else {
                    $_pay = $_pay->month($_pay->month + ($_index - 1))->timestamp;
                }
            }

            // 开始时间 = 租赁开始当月的头天 + （周期-1）月
            $_start = Carbon::createFromTimestamp($starttime);
            $_start = $_start->firstOfMonth()->month($_start->month + ($_index - 1))->timestamp;
        } while (!$break);

        $this->cycles = $cycles;
    }

    /**
     * 动态账期设置提前天数
     */
    private function computeForwardDay()
    {
        // 保留小数位数
        $dec = $this->_config['decimal'];
        // 租赁时长
        $starttime = $this->starttime;
        $endtime = $this->endtime;
        // 租金
        $rent = $this->rent;
        // 提前N天
        $forwardday = $this->forwardday;

        // 是否继续计算
        $break = false;
        $_pay = $_start = $starttime;
        $_index = 1;
        $cycles = [];
        do {
            // 如果该日期为2月的账期 并且 账期开始时间在29-31号
            $_endmonth = Carbon::createFromTimestamp($starttime);
            $_endmonth = $_endmonth->month($_endmonth->month + ($_index - 1))->timestamp;
            if (date('d', $starttime) >= 29 && date('d', $starttime) <= 31 && date('m', $_endmonth) == 1) {
                // 2月的最后一天
                $_end = Carbon::create(date('Y', $_endmonth), 2, 1)->lastOfMonth()->timestamp;
            } else {
                // 结束时间 = 本月结束（开始时间 + 周期-1个月 - 1天）
                $_end = Carbon::createFromTimestamp($starttime);
                $_end = $_end->month($_end->month + $_index)->day($_end->day - 1)->timestamp;
            }

            // 超过多少天算一个月
            $maxday = $this->getMonthDays($_start);
            // 每天租金
            $everyrent = $maxday == 0 ? 0 : bcdiv($rent, $maxday, $dec + 2);

            // 历经天数
            $_days = Carbon::createFromTimestamp($_start)->subDay()->diffInDays(Carbon::createFromTimestamp($_end));

            // 如果年份和月份和租赁结束时间一期一样，结束日期为租赁结束日
            // 尾期
            if (date('Ym', $_end) == date('Ym', $endtime)) {
                $break = true;
                $_end = $endtime;
                $_days = Carbon::createFromTimestamp($_start)->subDay()->diffInDays(Carbon::createFromTimestamp($_end));

                // 如果最後一期的租日为一个月
                if ($_days >= $maxday) {
                    $_rent = $rent;
                    // 如果最後一期的租日为不止一个月
                    $_rent = bcadd($_rent, bcmul($everyrent, ($_days - $maxday), $dec), $dec);
                } else {
                    $_rent = bcmul($everyrent, $_days, $dec);
                }
            } else {
                $_rent = $rent;
            }

            // 塞入周期栈
            $cycles[] = ['index' => $_index, 'pay' => $_pay, 'start' => $_start, 'end' => $_end, 'rent' => $_rent, 'days' => $_days];
            $_index++;

            // 缴费时间
            $_pay = Carbon::createFromTimestamp($starttime);
            $_pay = $_pay->month($_pay->month + ($_index - 1))->day($_pay->day - $forwardday)->timestamp;

            // 如果结束时间是2月最后一天的
            $_endday = Carbon::create(date('Y', $_end), 2, 1)->lastOfMonth()->timestamp;
            if ($_end == $_endday) {
                $_start = Carbon::create(date('Y', $_endmonth), 3, 1)->timestamp;
            } else {
                // 开始时间 = 租赁开始 + (周期 - 1) 个月
                $_start = Carbon::createFromTimestamp($starttime);
                $_start = $_start->month($_start->month + ($_index - 1))->timestamp;
            }
        } while (!$break);

        $this->cycles = $cycles;
    }

    /**
     * 动态账期设置固定日
     */
    private function computeFixedDay()
    {
        // 保留小数位数
        $dec = $this->_config['decimal'];
        // 租赁时长
        $starttime = $this->starttime;
        $endtime = $this->endtime;
        // 租金
        $rent = $this->rent;
        // 收租日
        $rentday = $this->rentday;
        // 分割日
        $cutdays = $this->_config['cutdays'];


        // 是否继续计算
        $break = false;
        $_pay = $_start = $starttime;
        $_index = 1;
        $cycles = [];
        do {
            // 如果该日期为2月的账期 并且 账期开始时间在29-31号
            $_endmonth = Carbon::createFromTimestamp($starttime);
            $_endmonth = $_endmonth->month($_endmonth->month + ($_index - 1))->timestamp;
            if (date('d', $starttime) >= 29 && date('d', $starttime) <= 31 && date('m', $_endmonth) == 1) {
                // 2月的最后一天
                $_end = Carbon::create(date('Y', $_endmonth), 2, 1)->lastOfMonth()->timestamp;
            } else {
                // 结束时间 = 本月结束（开始时间 + 周期-1个月 - 1天）
                $_end = Carbon::createFromTimestamp($starttime);
                $_end = $_end->month($_end->month + $_index)->day($_end->day - 1)->timestamp;
            }

            // 超过多少天算一个月
            $maxday = $this->getMonthDays($_start);
            // 每天租金
            $everyrent = $maxday == 0 ? 0 : bcdiv($rent, $maxday, $dec + 2);

            // 历经天数
            $_days = Carbon::createFromTimestamp($_start)->subDay()->diffInDays(Carbon::createFromTimestamp($_end));

            // 如果年份和月份和租赁结束时间一期一样，结束日期为租赁结束日
            // 尾期
            if (date('Ym', $_end) == date('Ym', $endtime)) {
                $break = true;
                $_end = $endtime;
                $_days = Carbon::createFromTimestamp($_start)->subDay()->diffInDays(Carbon::createFromTimestamp($_end));

                // 如果最後一期的租日为一个月
                if ($_days >= $maxday) {
                    $_rent = $rent;
                    // 如果最後一期的租日为不止一个月
                    $_rent = bcadd($_rent, bcmul($everyrent, ($_days - $maxday), $dec), $dec);
                } else {
                    $_rent = bcmul($everyrent, $_days, $dec);
                }
            } else {
                $_rent = $rent;
            }

            // 塞入周期栈
            $cycles[] = ['index' => $_index, 'pay' => $_pay, 'start' => $_start, 'end' => $_end, 'rent' => $_rent, 'days' => $_days];
            $_index++;

            // 如果开始时间小于等于收租日
            if (date('d', $starttime) < $rentday) {
                $_pay = Carbon::create(date('Y', $starttime), date('m', $starttime), $rentday);
                // 如果收租日小于分割日
                if ($rentday >= $cutdays) {
                    $_pay = $_pay->month($_pay->month + ($_index - 2))->timestamp;
                } else {
                    $_pay = $_pay->month($_pay->month + ($_index - 1))->timestamp;
                }
            } else {
                $_pay = Carbon::create(date('Y', $starttime), date('m', $starttime), $rentday);
                $_pay = $_pay->month($_pay->month + ($_index - 1))->timestamp;
            }

            // 如果结束时间是2月最后一天的
            $_endday = Carbon::create(date('Y', $_end), 2, 1)->lastOfMonth()->timestamp;
            if ($_end == $_endday) {
                $_start = Carbon::create(date('Y', $_endmonth), 3, 1)->timestamp;
            } else {
                // 开始时间 = 租赁开始 + (周期 - 1) 个月
                $_start = Carbon::createFromTimestamp($starttime);
                $_start = $_start->month($_start->month + ($_index - 1))->timestamp;
            }
        } while (!$break);

        $this->cycles = $cycles;
    }

    /**
     * 计算一个月有多少天
     * @param string $datetime 当前时间
     */
    public function getMonthDays($datetime = '')
    {
        $maxday = $this->_config['maxday'];
        if ($maxday <= 0) {
            $date = Carbon::createFromTimestamp($datetime);
            $date->firstOfMonth();
            $maxday = $date->diffInDays($date->copy()->lastOfMonth());
            $maxday++;
        }
        return $maxday;
    }

    /**
     * 设置错误信息
     * @param string $msg 错误信息
     */
    public function setError($msg)
    {
        $this->errmsg = $msg;
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->errmsg;
    }
}
