<?php         

$hostip='127.0.0.1';
$username='admin';
$password='hechunyang';
$dbname='test';
$dbport=3306;
$long_time=10;


//*****************************下面的代码不用修改************************************************//

ini_set('date.timezone','Asia/Shanghai');
error_reporting(7);


$longopts  = array(
    "stop"
);

$options = getopt($shortopts, $longopts);

$check_support_rg="/usr/sbin/getcap $(which mysqld)";

exec("$check_support_rg",$output_rg,$return_rg);

if($return_rg!=0){
    echo "检测到mysqld没有开启CAP_SYS_NICE功能。请使用下面的命令进行开启设置：". PHP_EOL. PHP_EOL;
    echo "setcap cap_sys_nice+ep /usr/local/mysql/bin/mysqld". PHP_EOL. PHP_EOL;
    echo "并需要重启mysqld服务生效". PHP_EOL. PHP_EOL;
    exit;
} else {
    echo "mysqld已经开启CAP_SYS_NICE功能". PHP_EOL;
    echo $output_rg[0]. PHP_EOL. PHP_EOL;
}

$vcpu = exec("cat /proc/cpuinfo  | grep processor | tail -n 1 | awk -F': ' '{print \$NF}'");

$conn = mysqli_connect($hostip,$username,$password,$dbname,$dbport) or die("数据库链接错误".mysqli_error($conn));
mysqli_query($conn,"set names utf8");  

$check_rg_exist = "SELECT * FROM information_schema.resource_groups WHERE RESOURCE_GROUP_NAME = 'slowsql_rg'";

mysqli_query($conn,$check_rg_exist);

if(mysqli_affected_rows($conn)>0){
        echo "检测已存在资源组slowsql_rg". PHP_EOL. PHP_EOL;
} else {
	echo "系统检测不存在资源组slowsql_rg，开启创建create resource group slowsql_rg". PHP_EOL. PHP_EOL;
	$create_rg = "create resource group slowsql_rg type=user vcpu=" . $vcpu ." thread_priority=19 enable";
	mysqli_query($conn,$create_rg);
}

$find_slowsql = "SELECT THREAD_ID,PROCESSLIST_INFO,RESOURCE_GROUP,PROCESSLIST_TIME FROM performance_schema.threads 
WHERE PROCESSLIST_INFO REGEXP 'SELECT|INSERT|UPDATE|DELETE|ALTER' AND PROCESSLIST_TIME >  ".$long_time;

$query_slowsql = mysqli_query($conn,$find_slowsql);

if(mysqli_affected_rows($conn)==0){
	echo "\e[38;5;10m".date('Y-m-d H:i:s')."	未检测出当前执行中的卡顿慢SQL。\e[0m" .PHP_EOL;
        if(file_exists(dirname(__FILE__)."/slowlog.txt")){
		rename(dirname(__FILE__)."/slowlog.txt","slowlog_".date('Y-m-d_H:i:s')."_history.txt");
        }
	exit;
} 

while($row_slowsql = mysqli_fetch_array($query_slowsql)) {
	$set_rg = "SET resource group slowsql_rg for ".$row_slowsql[0];
	mysqli_query($conn,$set_rg);
}

$show_slowsql = "SELECT PROCESSLIST_USER,PROCESSLIST_HOST,PROCESSLIST_DB,PROCESSLIST_INFO,RESOURCE_GROUP,PROCESSLIST_TIME FROM performance_schema.threads 
WHERE PROCESSLIST_INFO REGEXP 'SELECT|INSERT|UPDATE|DELETE|REPLACE|ALTER' AND PROCESSLIST_TIME >  ".$long_time;

$query_rg_slowsql = mysqli_query($conn,$show_slowsql);


while($row_rg_slowsql = mysqli_fetch_array($query_rg_slowsql)) {
	echo "\e[38;5;196m".date('Y-m-d H:i:s')."	警告！出现卡顿慢SQL，请及时排查问题。\e[0m" .PHP_EOL;

	file_put_contents(dirname(__FILE__).'/slowlog.txt', date('Y-m-d H:i:s')."\n".
        "用户名：".$row_rg_slowsql['0'].PHP_EOL.
        "来源IP：".$row_rg_slowsql['1'].PHP_EOL.
        "数据库名：".$row_rg_slowsql['2'].PHP_EOL.
        "SQL语句：".$row_rg_slowsql[3] .PHP_EOL.
        "资源组：" . $row_rg_slowsql[4] .PHP_EOL. 
        "执行时间：" . $row_rg_slowsql[5]." 秒".PHP_EOL.
	"-----------------------------------------------------------
        ".PHP_EOL,FILE_APPEND);

	echo PHP_EOL;

	if(isset($options['stop'])){
		echo "\e[38;5;11m关闭并且删除资源组slowsql_rg\e[0m" .PHP_EOL;
		$disable_rg = "ALTER RESOURCE GROUP slowsql_rg DISABLE FORCE";
		mysqli_query($conn,$disable_rg);
		$drop_rg = "drop resource group slowsql_rg";
                mysqli_query($conn,$drop_rg);
	}	
}


?> 
