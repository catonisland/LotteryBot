<?php
namespace app\crontab\model;

use think\Model;

/**
 * 定时任务日志模型
 */
class CrontabLog extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getCidLinkAttr($value, $data)
    {
        $url = url('crontab/index/index', ['search_field'=>'id', 'keyword'=>$data['cid']]);
        return '<a href="'.$url.'">'.$data['cid'].'</a>';
    }

    public function getTitleLinkAttr($value, $data)
    {
        $url = url('crontab/index/index', ['search_field'=>'id', 'keyword'=>$data['cid']]);
        return '<a href="'.$url.'">'.$data['title'].'</a>';
    }

    public function getStatusTextAttr($value, $data)
    {
        if ($data['status']==1){
            return '成功';
        }else{
            return '失败';
        }
    }

    public function getExecuteTimeAttr($value, $data)
    {
        if (empty($data['create_time'])){
            return null;
        }
        return date('Y-m-d H:i:s', $data['create_time']);
    }
}