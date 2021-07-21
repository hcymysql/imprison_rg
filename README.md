# imprison_rg 囚禁慢SQL

数据库90%的性能问题由于SQL引起，线上SQL的执行快慢，直接影响着系统的稳定性。

如果你刚入职一家公司，线上数据库CPU被慢SQL给打爆，而你又不敢直接将慢SQL杀死，万一出点事自己负连带责任。

退而求其次，利用MySQL 8.0资源组该功能，有效解决慢SQL引发CPU告警。

资源组的作用是资源隔离（你可以理解为开通云主机时勾选的硬件配置），将线上的慢SQL线程id分配给CPU一个核，让它慢慢跑，从而不影响CPU整体性能。

默认把执行时间超过10秒的慢SQL，捆绑在CPU最后一个核。

运行：

# php imprison_rg.php

关闭：

# php imprison_rg.php --stop


注：资源组启动需开启CAP_SYS_NICE功能

开启步骤如下：

# setcap cap_sys_nice+ep /usr/local/mysql/bin/mysqld

# getcap /usr/local/mysql/bin/mysqld
/usr/local/mysql/bin/mysqld = cap_sys_nice+ep

