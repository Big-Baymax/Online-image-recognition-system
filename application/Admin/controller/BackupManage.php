<?php/** * Created by PhpStorm. * User: Baymax * Date: 2017/12/23 * Time: 18:44 */namespace app\Admin\controller;use think\Request;use think\Db;use think\Config;use app\Admin\model\Backup;use app\Common\Controller\AuthAdminInterManage;Class BackupManage extends AuthAdminInterManage{    //执行符号    private $ds = "\n\r\n\r";    //备份路径    private $dir = "./../backup/";    /**     * 保存备份文件     *     * @param string $table     * @return array     */    private function write_file($sql, $filename) {        $dir = $this->dir;        $filename .='.sql';        $result  = array('status' => true, 'msg' => "");        // 创建目录        if (! is_dir ( $dir )) {            mkdir ( $dir, 0777, true );        }        if (! @$fp = fopen ( $dir . $filename, "w+" )) {            $result  = array('status' => false, 'msg' => "打开文件失败！");        }        if (! @fwrite ( $fp, $sql )) {            $result  = array('status' => false, 'msg' => "写入文件失败，请文件是否可写!");        }        if (! @fclose ( $fp )) {            $result  = array('status' => false, 'msg' => "关闭文件失败！");        }        return $result;    }    /**     * 插入语句构造     *     * @param string $table     * @return string     */    private function insert_record($table) {        // sql字段逗号分割        $mysql ='';        //获取表的执行语句        $sql = '';        $sql .= "--" . $this->ds;        $sql .= "-- 表的结构" . $table .$this->ds."--" .$this->ds;        // 如果存在则删除表        $sql .= "DROP TABLE IF EXISTS `" . $table . '`' . ';' . $this->ds;        // 获取详细表信息        $res = Db::query ( 'show create table ' . $table);        $res = $res[0]['Create Table'] .= ';'.$this->ds;        $insert = $sql.$res;        // 加上        $insert .= $this->ds;        $insert .= "--" . $this->ds;        $insert .= "-- 转存表中的数据 " . $table . $this->ds;        $insert .= "--" . $this->ds;        $insert .= $this->ds;        //var_dump($res[0]['Create Table']);        //获取数据库的配置        $con = Config::get('database');        $con=mysqli_connect($con["hostname"],$con["username"],$con["password"],$con["database"]);        $all = Db::query ( 'select * FROM `' . $table . '`' );        //生成每个记录执行语句        foreach ($all as $key){            $comma = 0;            $insert .= "INSERT INTO `" . $table . "` VALUES(";            //var_dump($all);            //$insert.= ( "'" . implode(',',$key) . "'");            foreach ($key as $v){                $insert.=$comma == 0 ? "" : ",";                $value = mysqli_escape_string($con,$v);                if(empty($value)){                    if($value =='0'){                        $value = '0';                        $insert.= $value;                    }else{                        $value = 'null';                        $insert.= $value;                    }                }else{                    $insert.= ( "'" . $value . "'");                }                $comma++;            };            $insert .= ");" . $this->ds;        };        $filename = $table.'_'.intval(time());        $result = $this->write_file($insert,$filename);        if ($result['status']){            $backup_time = date('Y-m-d H:i:s');            $backup = Backup::create([                'table_name'  =>  $table,                'file_name' =>  $filename.'.sql',                'backup_time' => $backup_time,                'download_count' => 0,                'status' =>1,                'reset_count' =>0,            ]);            if($backup){                return array('status' => 1, 'msg' => "导出备份成功！");            }else{                return array('status' => 0, 'msg' => "存库失败！");            }        }else{            return array('status' => 0, 'msg' => $result['msg']);        }    }    /**     * 将sql导入到数据库（普通导入）     *     * @param string $sqlfile     * @return boolean     */    private function resetsql($sqlfile) {        // sql文件包含的sql语句数组        $f = fopen ( $sqlfile, "rb" );        // 创建表缓冲变量        $create_table = '';        while ( ! feof ( $f ) ) {            // 读取每一行sql            $line = fgets ( $f );            // 这一步为了将创建表合成完整的sql语句            // 如果结尾没有包含';'(即为一个完整的sql语句，这里是插入语句)，并且不包含'ENGINE='(即创建表的最后一句)            if (! preg_match ( '/;/', $line ) || preg_match ( '/ENGINE=/', $line )) {                // 将本次sql语句与创建表sql连接存起来                $create_table .= $line;                // 如果包含了创建表的最后一句                if (preg_match ( '/ENGINE=/', $create_table)) {                    //执行sql语句创建表                     Db::query ( $create_table);                    // 清空当前，准备下一个表的创建                    $create_table = '';                }                // 跳过本次                continue;            }            //执行sql语句            Db::query ($line);        }        fclose ( $f );        return array('status' => true, 'msg' => "还原成功！");    }    /**     * 备份表格函数     *     * @param array $uid     * @return json ['status'] ,['msg']     */    public function backup_tables(){        $all = Request::instance()->post();        $result   = array('status' => false, 'msg' => "");        $tables = $all['table_name'];        $error_table = array();        foreach ($tables as $key){            $list = $this->insert_record($key);            if($list['status']){            }else{                array_push($error_table,$key);            }        }        if(sizeof($error_table)){            $result['status'] = 0;            $result['msg'] = '表【'.implode(',',$error_table).'】备份失败！';            return json($result);        }else{            $result['status'] = 1;            $result['msg'] = '全部备份成功！';            return json($result);        }    }    /**     * 还原表格函数     *     * @param array $uid     * @return json ['status'] ,['msg']     */    public function reset_tables(){        $all = Request::instance()->post();        $result   = array('status' => false, 'msg' => "");        $uid = $all['uid'];        $Backup = model('Backup');        $error_table = array();        foreach ($uid as $key){            //获取数据库信息判断            $Backup_list = Backup::get(['uid' => $key,'status' => 1]);            //执行还原函数            $list = $this->resetsql($this->dir.$Backup_list['file_name']);            if($list['status']){                $Backup->where('uid', $key)->setInc('reset_count', 1);                $Backup -> where('uid',$key) -> setField('reset_time',date('Y-m-d H:i:s'));            }else{                array_push($error_table,$Backup['table_name']);            }        }        if(sizeof($error_table)){            $result['status'] = 0;            $result['msg'] = '表【'.implode(',',$error_table).'】还原失败！';            return json($result);        }else{            $result['status'] = 1;            $result['msg'] = '全部还原成功！';            return json($result);        }    }    /**     * 优化表接口     *     * @param array $uid     * @return json ['status'] ,['msg']     */    public function optimize_tables(){        $all = Request::instance()->post();        $result   = array('status' => false, 'msg' => "");        $uid = $all['table_name'];        $error_table = array();        foreach ($uid as $key){            try{                Db::query (  'OPTIMIZE TABLE`' . $key . '`' );            }catch(Exception $e){                array_push($error_table,$key);            }        }        if(sizeof($error_table)){            $result['status'] = 0;            $result['msg'] = '表【'.implode(',',$error_table).'】优化失败！';            return json($result);        }else{            $result['status'] = 1;            $result['msg'] = '全部优化成功！';            return json($result);        }    }    /**     * 优化表接口     *     * @param array $uid     * @return json ['status'] ,['msg']     */    public function repair_tables(){        $all = Request::instance()->post();        $result   = array('status' => false, 'msg' => "");        $uid = $all['table_name'];        $error_table = array();        foreach ($uid as $key){            try{                Db::query (  'REPAIR TABLE`' . $key . '`' );            }catch(Exception $e){                array_push($error_table,$key);            }        }        if(sizeof($error_table)){            $result['status'] = 0;            $result['msg'] = '表【'.implode(',',$error_table).'】还修复失败！';            return json($result);        }else{            $result['status'] = 1;            $result['msg'] = '全部修复成功！';            return json($result);        }    }    /**     * 删除备份记录接口     *     * @param array $uid     * @return json ['status'] ,['msg']     */    public function del_backup(){        $all = Request::instance()->post();        $result   = array('status' => false, 'msg' => "");        $uid = $all['uid'];        $error_table = array();        $Backup = model('Backup');        foreach ($uid as $key){            //获取数据库信息判断            $list = $Backup -> where('uid='.$key) -> setField('status',0);            $Backup_list = Backup::get(['uid' => $key]);            if($list){                //执行删除文件函数                if(is_file($this->dir.$Backup_list['file_name'])){                    if(!unlink($this->dir.$Backup_list['file_name'])){                        array_push($error_table,$Backup_list['table_name']);                    }                }            }else{                array_push($error_table,$Backup_list['table_name']);            }        }        if(sizeof($error_table)){            $result['status'] = 0;            $result['msg'] = '表【'.implode(',',$error_table).'】删除失败！';            return json($result);        }else{            $result['status'] = 1;            $result['msg'] = '全部删除成功！';            return json($result);        }    }    /**     * 下载备份记录接口     *     * @param array $uid     * @return json ['status'] ,['msg'] |     */    public function download_backup(){        $all = Request::instance()->post();        $result   = array('status' => false, 'msg' => "");        $uid = $all['uid'];        $Backup = model('Backup');        $download_file = array();        $i = 0;        foreach ($uid as $key){            //获取数据库信息判断            $Backup_list = Backup::get(['uid' => $key]);            // 把所有的文件加入队列            $download_file[$i++] = $Backup_list['file_name'];            $Backup->where('uid', $key)->setInc('download_count', 1);        }        //打包        $result_zip = tozip('download_backup_',$this->dir,$download_file);        if($result_zip['status']){            $result['status'] = 1;            $result['msg'] = $result_zip['msg'];            return json($result);        }else{            $result['status'] = 0;            $result['msg'] = '表【'.implode(',',$result_zip['msg']).'】打包失败！';            return json($result);        }    }    /**     * 下载文件(zip格式)     *     * @param $file_path string     * return $filename file || false (文件不存在！)     */    public function download_file($file){        $result = echo_file($this->dir.$file,'数据库备份文件');        if($result){            unlink($this->dir.$file); //下载完成后要进行删除        }else{            echo "<script>alert('对不起,您要下载的文件不存在');</script>";        }    }}