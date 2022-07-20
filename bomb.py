# 并发32个进程  多进程执行for循环
import time
import multiprocessing
import pymysql.cursors

def index_pool():
    connection =  pymysql.connect(host='127.0.0.1',port=3333,user='admin',password='hechunyang',database='test',charset='utf8')
    with connection:
      with connection.cursor() as cursor:
        sql = 'call bomb(@a)'
        cursor.execute(sql)
    #time.sleep(1)

if __name__ == "__main__":
    pool = multiprocessing.Pool(processes=32)
    for i in range(32):
        pool.apply_async(index_pool)
    pool.close()
    pool.join()
