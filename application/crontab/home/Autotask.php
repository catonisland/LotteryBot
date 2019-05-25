<?php
namespace app\crontab\home;

use think\Controller;
use think\Exception;
use app\crontab\model\Crontab as CrontabModel;
use app\crontab\model\CrontabLog as CrontabLogModel;
use Cron\CronExpression;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use think\facade\Env;

/**
 * 定时任务接口
 * 以Crontab方式每分钟定时执行,且只可以Cli方式运行 * * * * * /usr/bin/php /your-web-dir/public/index.php /crontab/autotask
 * @internal
 */
class Autotask extends Controller
{
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        config('app_trace', false); // 关闭 app_trace
        //config('log', ['type'  => 'test']);// 临时关闭TP日志写入，不然可能会出错

        // 只可以以cli方式执行
        if (!$this->request->isCli()){
            $this->error('Autotask script only work at client!');
        }

        parent::_initialize();

    }

    /**
     * 执行定时任务
     */
    public function index()
    {
        // 筛选未过期且未完成的任务
        $crontab_list = CrontabModel::where(['status' => 'normal'])->field(true)->order('weigh desc,id desc')->select();
        if (!$crontab_list) {
            return null;
        }

        $time = time();

        foreach ($crontab_list as $key => $crontab) {
            $value = $crontab->getData();
            $update = [];
            $execute = false;   // 是否执行

            if ($time < $value['begin_time']) {   //任务未开始
                continue;
            }

            if ($value['maximums'] && $value['executes'] > $value['maximums']) {  //任务已超过最大执行次数
                $update['status'] = 'completed';
            } else if ($value['end_time'] > 0 && $time > $value['end_time']) {     //任务已过期
                $update['status'] = 'expired';
            } else {
                $cron = CronExpression::factory($value['schedule']);
                /*
                 * 根据当前时间判断是否该应该执行
                 * 这个判断和秒数无关，其最小单位为分
                 * 也就是说，如果处于该执行的这个分钟内如果多次调用都会判定为真
                 * 所以我们在服务器上设置的定时任务最小单位应该是分
                 */
                if ($cron->isDue()) {
                    // 允许执行
                    $execute = true;
                    // 允许执行的时候更新状态
                    $update['execute_time'] = $time;
                    $update['executes'] = $value['executes'] + 1;
                    $update['status'] = ($value['maximums'] > 0 && $update['executes'] >= $value['maximums']) ? 'completed' : 'normal';
                } else {    //如果未到执行时间则跳过本任务去判断下一个任务
                    continue;
                }
            }

            // 更新状态
            $crontab->save($update);

            // 如果不允许执行，只是从当前开始已过期或者已超过最大执行次数的任务，只是更新状态就行了，不执行
            if (!$execute) {
                continue;
            }

            try {
                // 分类执行任务
                switch ($value['type']) {
                    case 'url':
                        if (substr($crontab['content'], 0, 1) == "/") {// 本地项目URL
                            $request = shell_exec('php ' . Env::get('root_path') . 'public/index.php ' . $crontab['content'] . ' 2>&1');
                            $this->saveLog('url', $value['id'], $value['title'], 1, $request);
                        } else {// 远程URL
                            try {
                                $client = new Client();
                                $response = $client->request('GET', $crontab['content']);
                                $this->saveLog('url', $value['id'], $value['title'], 1, $crontab['content'] . ' 请求成功，HTTP状态码: ' . $response->getStatusCode());
                            } catch (RequestException $e) {
                                $this->saveLog('url', $value['id'], $value['title'], 0, $crontab['content'] . ' 请求成功失败: ' . $e->getMessage());
                            }
                        }
                        break;
                    case 'sql':
                        /* 注释中的方法可以一次执行所有SQL语句
                         * $connect = \think\Db::connect([], true);
                        $connect->execute("select 1");
                        // 执行SQL
                        $count  = $connect->getPdo()->exec($crontab['content']);
                        dump($count );*/

                        // 解析成一条条的sql语句
                        $sqls = str_replace("\r", "\n", $crontab['content']);
                        $sqls = explode(";\n", $sqls);
                        $connect = \think\Db::connect([], true);
                        $remark = '';
                        $status = 1;
                        foreach ($sqls as $sql) {
                            $sql = trim($sql);
                            if (empty($sql)) continue;
                            if (substr($sql, 0, 2) == '--') continue;   // SQL注释
                            // 执行SQL并记录执行结果
                            if (false !== $connect->execute($sql)) {
                                $remark .= '执行成功: ' . $sql . "\r\n\r\n";
                            } else {
                                $remark .= '执行失败: ' . $sql . "\r\n\r\n";
                                $status = 0;
                            }
                        }
                        $this->saveLog('sql', $value['id'], $value['title'], $status, $remark);
                        break;
                    case 'shell':
                        $request = shell_exec($crontab['content'] . ' 2>&1');
                        $this->saveLog('shell', $value['id'], $value['title'], 1, $request);
                        break;
                }
            }
            catch (Exception $e)
            {
                $this->saveLog($value['type'], $value['id'], $value['title'], 0, "执行的内容发生异常:\r\n" . $e->getMessage());
            }
        }

    }

    // 保存运行日志
    private function saveLog($type, $cid, $title, $status, $remark = '')
    {
        return true;
        /*CrontabLogModel::create([
            'type'  =>  $type,
            'cid' =>  $cid,
            'title' =>  $title,
            'status' =>  $status,
            'remark' =>  $remark,
        ]);*/
    }

}