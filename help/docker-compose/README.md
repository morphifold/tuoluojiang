# 陀螺匠 Docker 配置说明

本文档提供陀螺匠项目的 Docker 配置和使用指南。

## 1. 目录结构

```
tuoluojiang/
├── code/                          # 项目代码目录
│   ├── app/                       # 应用代码
│   ├── config/                    # 配置文件
│   ├── public/                    # 公共资源
│   ├── storage/                   # 存储目录
│   └── ...
├── help/                          # 帮助文档和配置
│   ├── docker-compose/            # Docker 配置文件
│   │   ├── mysql/                 # MySQL 配置
│   │   │   ├── my.cnf             # MySQL 配置文件
│   │   │   ├── data/              # MySQL 数据目录
│   │   │   └── log/               # MySQL 日志目录
│   │   ├── redis/                 # Redis 配置
│   │   │   ├── redis.conf         # Redis 配置文件
│   │   │   └── data/              # Redis 数据目录
│   │   ├── php/                   # PHP 配置
│   │   │   ├── Dockerfile         # PHP 镜像构建文件
│   │   │   └── php-ini-overrides.ini # PHP 配置文件
│   │   ├── nginx/                 # Nginx 配置
│   │   │   ├── nginx.conf         # Nginx 配置文件
│   │   │   ├── conf.d/            # Nginx 站点配置
│   │   │   └── log/               # Nginx 日志目录
│   │   └── README.md              # 本说明文档
│   ├── resource/                  # 图片资源
│   ├── README.md                  # Docker 快速指南
│   └── RUN_README.md              # 运行说明
├── docker-compose.yml             # Docker Compose 主配置文件
├── run.sh                         # Docker 管理脚本
├── Dockerfile                     # 一体化镜像构建文件
└── README.md                      # 项目说明文档
```

## 2. 快速开始

### 2.1 使用管理脚本（推荐）

```bash
# 启动所有服务
./run.sh start

# 停止所有服务
./run.sh stop

# 重启所有服务
./run.sh restart

# 查看服务状态
./run.sh status

# 查看服务日志
./run.sh logs mysql
./run.sh logs redis
./run.sh logs tuoluojiang
./run.sh logs nginx

# 清理数据并重新安装
./run.sh install

# 重新构建PHP镜像
./run.sh build

# 显示帮助信息
./run.sh help
```

### 2.2 使用 Docker Compose

```bash
# 启动所有服务
docker-compose up -d

# 停止所有服务
docker-compose down

# 重启所有服务
docker-compose restart

# 查看服务状态
docker-compose ps

# 查看服务日志
docker-compose logs -f
```

## 3. 服务配置

### 3.1 MySQL 服务
- **容器名称**：mysql_tuoluojiang
- **镜像**：mysql:8.0
- **端口映射**：3376:3306
- **环境变量**：
  - TZ: Asia/Shanghai
  - MYSQL_ROOT_PASSWORD: 123456
  - MYSQL_DATABASE: tuoluojiang
  - MYSQL_USER: tuoluojiang
  - MYSQL_PASSWORD: 123456
- **数据卷**：
  - ./help/docker-compose/mysql/my.cnf:/etc/mysql/my.cnf
  - ./help/docker-compose/mysql/data:/var/lib/mysql
  - ./help/docker-compose/mysql/log:/var/log/mysql
- **网络地址**：192.168.10.14

### 3.2 Redis 服务
- **容器名称**：redis_tuoluojiang
- **镜像**：redis:latest
- **端口映射**：6389:6379
- **配置文件**：./help/docker-compose/redis/redis.conf
- **数据卷**：
  - ./help/docker-compose/redis/data:/data
  - ./help/docker-compose/redis/redis.conf:/usr/local/etc/redis/redis.conf
- **网络地址**：192.168.10.15

### 3.3 PHP 服务
- **容器名称**：tuoluojiang
- **镜像**：tuoluojiang（本地构建）
- **构建路径**：./help/docker-compose/php
- **端口映射**：20300:20200
- **环境变量**：
  - TZ: Asia/Shanghai
  - MYSQL_HOST_IP: mysql_tuoluojiang
  - MYSQL_PORT: "3306"
  - MYSQL_USER: tuoluojiang
  - MYSQL_PASSWORD: 123456
  - MYSQL_DATABASE: tuoluojiang
  - REDIS_HOST_IP: redis_tuoluojiang
  - REDIS_PORT: "6379"
  - REDIS_DATABASE: "0"
  - REDIS_PASSWORD: "123456"
  - APP_DEBUG: true
  - DEFAULT_TIMEZONE: Asia/Shanghai
- **数据卷**：
  - ./code:/var/www
  - ./code/storage:/var/www/temp
  - ./help/docker-compose/php/php-ini-overrides.ini:/etc/php/8.0/fpm/conf.d/99-overrides.ini
- **网络地址**：192.168.10.16
- **启动命令**：bin/laravels start --ignore

### 3.4 Nginx 服务
- **容器名称**：nginx_tuoluojiang
- **镜像**：nginx:latest
- **端口映射**：80:80, 443:443
- **数据卷**：
  - ./code:/var/www
  - ./help/docker-compose/nginx/nginx.conf:/etc/nginx/nginx.conf
  - ./help/docker-compose/nginx/conf.d:/etc/nginx/conf.d
  - ./help/docker-compose/nginx/log:/var/log/nginx
- **网络地址**：192.168.10.17

## 4. 网络配置

使用自定义网络 `app_net`，IP 段为 `192.168.10.0/24`，各服务的 IP 地址：
- MySQL：192.168.10.14
- Redis：192.168.10.15
- PHP应用：192.168.10.16
- Nginx：192.168.10.17

## 5. 访问应用

服务启动后，可以通过以下地址访问应用：

```
Web界面：http://localhost:80
应用端口：http://localhost:20300
```

## 6. 连接数据库

### 6.1 连接 MySQL

```bash
# 从宿主机连接 MySQL
mysql -h 127.0.0.1 -P 3376 -u tuoluojiang -p123456

# 查看数据库
mysql> SHOW DATABASES;

# 使用 tuoluojiang 数据库
mysql> USE tuoluojiang;
```

### 6.2 连接 Redis

```bash
# 从宿主机连接 Redis
redis-cli -h 127.0.0.1 -p 6389

# 如果设置了密码
redis-cli -h 127.0.0.1 -p 6389 -a 123456

# 测试连接
127.0.0.1:6389> PING
```

## 7. 配置修改

### 7.1 修改数据库配置

编辑 `docker-compose.yml` 文件中的 MySQL 环境变量：

```yaml
mysql_tuoluojiang:
  environment:
    MYSQL_ROOT_PASSWORD: 新密码
    MYSQL_USER: 新用户名
    MYSQL_PASSWORD: 新密码
    MYSQL_DATABASE: 新数据库名
```

### 7.2 修改 Redis 配置

编辑 `help/docker-compose/redis/redis.conf` 文件：

```conf
# 设置密码
requirepass 你的密码

# 其他配置...
```

### 7.3 修改端口映射

编辑 `docker-compose.yml` 文件中的端口映射：

```yaml
mysql_tuoluojiang:
  ports:
    - "3376:3306" # 修改为其他端口

nginx_tuoluojiang:
  ports:
    - "80:80" # 修改为其他端口
```

## 8. 数据持久化

所有重要数据都挂载到本地目录：

- **MySQL 数据**：`./help/docker-compose/mysql/data/`
- **MySQL 日志**：`./help/docker-compose/mysql/log/`
- **Redis 数据**：`./help/docker-compose/redis/data/`
- **Nginx 日志**：`./help/docker-compose/nginx/log/`
- **应用存储**：`./code/storage/`

建议定期备份这些目录中的数据。

## 10. 常见问题处理

### 10.1 端口冲突

如果遇到端口冲突，可以修改 `docker-compose.yml` 文件中的端口映射。

### 10.2 权限问题

如果遇到权限问题，可以在主机上设置正确的权限：

```bash
# 设置数据目录权限
chmod -R 755 ./help/docker-compose/mysql/data
chmod -R 755 ./help/docker-compose/redis/data
chmod -R 755 ./code/storage
```

### 10.3 服务启动失败

如果服务启动失败，可以查看日志：

```bash
# 使用管理脚本查看日志
./run.sh logs

# 或使用 docker-compose
docker-compose logs

# 查看特定服务日志
./run.sh logs mysql
./run.sh logs php
```

### 10.4 MySQL 用户权限问题

如果遇到 MySQL 用户权限问题，可以进入容器手动创建用户：

```bash
# 进入 MySQL 容器
docker exec -it mysql_tuoluojiang bash

# 登录 MySQL
mysql -uroot -p123456

# 创建用户并授权
CREATE USER 'tuoluojiang'@'%' IDENTIFIED BY '123456';
GRANT ALL PRIVILEGES ON tuoluojiang.* TO 'tuoluojiang'@'%';
FLUSH PRIVILEGES;
```

## 11. 最佳实践

- **定期备份数据**：定期备份 MySQL 数据和重要文件
- **使用固定版本镜像**：在生产环境中，建议使用固定版本的镜像
- **定期更新镜像**：定期更新镜像，修复安全漏洞
- **配置资源限制**：根据实际需求配置合理的资源限制
- **监控日志**：定期查看日志，及时发现和解决问题

## 11. 开发环境 vs 生产环境

- **开发环境**：使用 `docker-compose.yml`，支持代码实时挂载和调试
- **生产环境**：建议使用独立的 MySQL、Redis 服务，或使用项目根目录的 `Dockerfile` 构建包含所有服务的镜像进行部署

## 13. 参考链接

- [Docker 官方文档](https://docs.docker.com/)
- [Docker Compose 官方文档](https://docs.docker.com/compose/)
- [Laravel 官方文档](https://laravel.com/docs)
- [Swoole 官方文档](https://www.swoole.co.uk/)
- [陀螺匠官方文档](https://doc.crmeb.com/tuoluojiang)

---

**最后更新时间**：2025-03-17  
**适用版本**：陀螺匠企业助手 v2.0+
