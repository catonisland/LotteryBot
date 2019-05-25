<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

// 为方便系统核心升级，二次开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件

/*
* 生成一定数量的不重复随机数
* $min 和 $max: 指定随机数的范围
* $num: 指定生成数量
*/
if (!function_exists('unique_rand')) {
    function unique_rand($min, $max, $num){
        $count = 0;
        $return_arr = array();
        while($count < $num){
            $return_arr[] = mt_rand($min, $max);
            $return_arr = array_flip( array_flip( $return_arr ) );
            $count = count( $return_arr );
        }
        shuffle( $return_arr );
        return $return_arr;
    }
}