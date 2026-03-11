# 陀螺匠生产环境 Docker 配置说明

本文档提供陀螺匠生产环境的 Docker 配置和使用指南，基于腾讯云容器镜像服务。

## 1. 目录结构

```
helper/docker-compose/
├── php/         # PHP 容器配置
│   └── log/         # 日志目录
├── mysql/       # MySQL 容器配置
│   ├── data/        # 数据目录
│   └── log/         # 日志目录
├── redis/       # Redis 容器配置
│   ├── data/        # 数据目录
│   └── log/         # 日志目录
├── nginx/       # Nginx 容器配置
│   └── log/         # 日志目录
├── docker-compose.yml # 正式环境容器编排文件
└── README.md        # 本说明文档
```

## 2. 镜像配置

本配置使用腾讯云容器镜像服务中的镜像：

- **PHP 镜像**：ccr.ccs.tencentyun.com/tuoluojiang/php:latest
- **MySQL 镜像**：ccr.ccs.tencentyun.com/tuoluojiang/mysql:latest
- **Redis 镜像**：ccr.ccs.tencentyun.com/tuoluojiang/redis:latest
- **Nginx 镜像**：ccr.ccs.tencentyun.com/tuoluojiang/nginx:latest

## 3. 服务配置

### 3.1 MySQL 服务
- **容器名称**：tuoluojiang_mysql
- **端口映射**：33061:3306
- **环境变量**：
  - TZ: Asia/Shanghai
  - MYSQL_ROOT_PASSWORD: 123456
  - MYSQL_USER: tuoluojiang
  - MYSQL_PASSWORD: 123456
  - MYSQL_DATABASE: tuoluojiang
- **数据卷**：
  - ./mysql/data:/var/lib/mysql
  - ./mysql/log:/var/log/mysql
- **网络地址**：192.168.12.11

### 3.2 Redis 服务
- **容器名称**：tuoluojiang_redis
- **端口映射**：6379:6379
- **环境变量**：
  - TZ: Asia/Shanghai
- **数据卷**：
  - ./redis/data:/data
  - ./redis/log:/var/log/redis
- **网络地址**：192.168.12.10

### 3.3 PHP 服务
- **容器名称**：tuoluojiang_php
- **端口映射**：9000:9000, 20200:20200, 20002:20002, 20003:20003
- **环境变量**：
  - TZ: Asia/Shanghai
  - MYSQL_HOST_IP: tuoluojiang_mysql
  - MYSQL_PORT: "3306"
  - MYSQL_USER: tuoluojiang
  - MYSQL_PASSWORD: 123456
  - MYSQL_DATABASE: tuoluojiang
  - REDIS_HOST_IP: tuoluojiang_redis
  - REDIS_PORT: "6379"
  - REDIS_DATABASE: "0"
  - REDIS_PASSWORD: "123456"
  - APP_DEBUG: true
  - DEFAULT_TIMEZONE: Asia/Shanghai
- **数据卷**：
  - ../../:/var/www
  - ../../runtime:/var/www/runtime
  - ../../storage:/var/www/storage
  - ./php/log:/var/log/php
- **网络地址**：192.168.12.90
- **启动命令**：bin/laravels start --ignore

### 3.4 Nginx 服务
- **容器名称**：tuoluojiang_nginx
- **端口映射**：8022:80
- **环境变量**：
  - TZ: Asia/Shanghai
- **数据卷**：
  - 继承自 phpfpm 服务
  - ./nginx/log:/var/log/nginx
- **网络地址**：192.168.12.80

## 4. 网络配置

使用自定义网络 `app_net`，IP 段为 `192.168.12.0/24`，各服务的 IP 地址：
- MySQL：192.168.12.11
- Redis：192.168.12.10
- PHP-FPM：192.168.12.90
- Nginx：192.168.12.80

## 5. 使用方法

### 5.1 启动服务

```bash
# 进入 helper/docker-compose 目录
cd /Users/xurongyao/工作/陀螺匠/kaiyuan/tuoluojiang/code/helper/docker-compose

# 启动服务
docker-compose up -d
```

### 5.2 查看服务状态

```bash
# 查看容器状态
docker-compose ps

# 查看服务日志
docker-compose logs -f
```

### 5.3 停止服务

```bash
# 停止服务
docker-compose down

# 停止并删除数据卷
docker-compose down -v
```

## 6. 访问应用

服务启动后，可以通过以下地址访问应用：

```
http://localhost:8022
```

## 7. 测试连接

### 7.1 测试数据库连接

#### 7.1.1 从宿主机连接

```bash
# 从宿主机连接 MySQL 容器
mysql -h 127.0.0.1 -P 33061 -u tuoluojiang -p123456

# 查看数据库
mysql> SHOW DATABASES;
```

#### 7.1.2 从容器内部连接

```bash
# 进入 MySQL 容器
docker exec -it tuoluojiang_mysql mysql -u tuoluojiang -p123456

# 查看数据库
mysql> SHOW DATABASES;
```

### 7.2 测试 Redis 连接

#### 7.2.1 从宿主机连接

```bash
# 从宿主机连接 Redis 容器
redis-cli -h 127.0.0.1 -p 6379 -a 123456

# 测试连接
127.0.0.1:6379> PING
```

#### 7.2.2 从容器内部连接

```bash
# 进入 Redis 容器
docker exec -it tuoluojiang_redis redis-cli -a 123456

# 测试连接
127.0.0.1:6379> PING
```

### 7.3 测试 PHP 服务

```bash
# 进入 PHP 容器
docker exec -it tuoluojiang_php bash

# 查看 PHP 版本
php -v

# 查看 Swoole 扩展
php -m | grep swoole
```

## 8. 配置修改

### 8.1 修改数据库配置

编辑 `docker-compose.yml` 文件中的 MySQL 环境变量：

```yaml
mysql:
  environment:
    MYSQL_ROOT_PASSWORD: 新密码
    MYSQL_USER: 新用户名
    MYSQL_PASSWORD: 新密码
    MYSQL_DATABASE: 新数据库名
```

### 8.2 修改 Redis 配置

修改 `docker-compose.yml` 文件中的 Redis 环境变量：

```yaml
phpfpm:
  environment:
    REDIS_PASSWORD: "新密码"
```

### 8.3 修改端口映射

编辑 `docker-compose.yml` 文件中的端口映射：

```yaml
mysql:
  ports:
    - "33061:3306" # 修改为其他端口

nginx:
  ports:
    - "8022:80" # 修改为其他端口
```

## 9. 常见问题处理

### 9.1 端口冲突

如果遇到端口冲突，可以修改 `docker-compose.yml` 文件中的端口映射。

### 9.2 权限问题

如果遇到权限问题，可以在主机上设置正确的权限：

```bash
# 设置数据目录权限
chmod -R 777 ./mysql/data
```

### 9.3 服务启动失败

如果服务启动失败，可以查看日志：

```bash
# 查看服务日志
docker-compose logs
```

## 10. 最佳实践

- **使用固定版本镜像**：在生产环境中，建议使用固定版本的镜像，而不是 `latest` 标签
- **定期更新镜像**：定期更新镜像，修复安全漏洞
- **配置资源限制**：根据实际需求配置合理的资源限制
- **备份数据**：定期备份数据库和重要数据

## 11. 参考链接

- [Docker 官方文档](https://docs.docker.com/)
- [Docker Compose 官方文档](https://docs.docker.com/compose/)
- [腾讯云容器镜像服务文档](https://cloud.tencent.com/document/product/1141)
- [陀螺匠官方文档](https://doc.crmeb.com/tuoluojiang/tuoluojiang22/31958)

---

**最后更新时间**：2026-03-08
**适用版本**：陀螺匠企业助手