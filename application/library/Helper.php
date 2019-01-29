<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2018/4/20
 * Time: 10:46
 */

namespace app\library;


class Helper
{
    //将时间段（秒）装换为中文格式
    public static function convertTimeSlot($timeSlot)
    {
        if(empty($timeSlot)){
            $timeSlot=0;
        }
        $day=0;
        $hour=0;
        $minute=0;
        $second=0;
        $surplus=0; //剩余时间

        $day= floor($timeSlot/(3600*24));
        $surplus= floor($timeSlot%(3600*24));

        $hour=floor($surplus/(3600));
        $surplus= floor($surplus%(3600));

        $minute=floor($surplus/(60));
        $surplus= floor($surplus%(60));

        $second=$surplus;
        return $day.'天 '.$hour.'时'.$minute.'分'.$second.'秒';

    }
}