# imprison_rg 囚禁慢SQL

数据库90%的性能问题由于SQL引起，线上SQL的执行快慢，直接影响着系统的稳定性。

如果你刚入职一家公司，线上数据库CPU被慢SQL给打爆，而你又不敢直接将慢SQL杀死，万一出点事自己负连带责任。

退而求其次，利用MySQL 8.0资源组该功能，有效解决慢SQL引发CPU告警。

资源组的作用是资源隔离（你可以理解为开通云主机时勾选的硬件配置），将线上的慢SQL线程id分配给CPU一个核，让它慢慢跑，从而不影响CPU整体性能。

这里我封装了一个PHP脚本，简化了DBA输入相关资源组命令操作，直接在SHELL里运行即可。

该工具默认把执行时间超过10秒的慢SQL（SELECT|INSERT|UPDATE|DELETE|ALTER），捆绑在CPU最后一个核。

--------------------------------------------------------------------------------------------

环境准备

shell> yum install -y php php-mysql

--------------------------------------------------------------------------------------------

修改配置文件

######下面的配置信息修改成你自己的！！！######

shell> vim imprison_rg.php

$hostip='127.0.0.1';

$username='admin';

$password='hechunyang';

$dbname='test';

$dbport=3306;

$long_time=10; //执行时间10秒

--------------------------------------------------------------------------------------------
部署在主库运行：

# php imprison_rg.php

关闭并删除资源组：

# php imprison_rg.php --stop


--------------------------------------------------------------------------------------------
会在工具目录下生成slowlog.txt文件保存慢SQL。

shell> cat slowlog.txt

2021-07-21 18:22:30

用户名：root

来源IP：localhost

数据库名：test

SQL语句：select sleep(3600)

资源组：slowsql_rg

执行时间：13 秒

----------------------------------------------------------
注：资源组启动需开启CAP_SYS_NICE功能，并重启mysqld进程生效。

开启步骤如下：

shell> setcap cap_sys_nice+ep /usr/local/mysql/bin/mysqld

shell> getcap /usr/local/mysql/bin/mysqld

/usr/local/mysql/bin/mysqld = cap_sys_nice+ep（出现此信息表示已经开启CAP_SYS_NICE功能）

shell> systemctl restart mysqld.service

![image](https://s4.51cto.com/images/blog/202107/22/64b0f34597b7c95f4eab1c1fc74061fe.jpg?x-oss-process=image/watermark,size_14,text_QDUxQ1RP5Y2a5a6i,color_FFFFFF,t_100,g_se,x_10,y_10,shadow_20,type_ZmFuZ3poZW5naGVpdGk=)

----------------------------------------------------
3、验证：

使用top命令查看CPU状态信息，发现慢SQL已经绑定在CPU最后一核上运行。对于复杂、执行时间长、消耗资源多的慢SQL，我们可以将其设置特定的资源组，限制SQL查询的使用资源，避免导致其它正常查询不被响应，甚至导致MySQL直接hang住。

![image](https://s4.51cto.com/images/blog/202107/22/b35929dd5b13df07d4f31b5d9917592a.jpg?x-oss-process=image/watermark,size_14,text_QDUxQ1RP5Y2a5a6i,color_FFFFFF,t_100,g_se,x_10,y_10,shadow_20,type_ZmFuZ3poZW5naGVpdGk=)

----------------------------------------------------
测试用例：
'''
DROP PROCEDURE bomb;
DELIMITER // 
CREATE PROCEDURE bomb(OUT ot BIGINT)  
BEGIN  
    DECLARE cnt BIGINT DEFAULT 0;  
    SET @FUTURE = (SELECT NOW() + INTERVAL 1800 SECOND);  
    WHILE NOW() < @FUTURE 
    DO  
        SET cnt = (SELECT cnt + 1);  
    END WHILE;  
    SELECT cnt INTO ot;  
END  //
DELIMITER ;
'''

''' call  bomb(@a);'''

