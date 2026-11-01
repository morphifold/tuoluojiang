# 陀螺匠 Docker 管理脚本 (run.sh)

优化后的 `run.sh` 脚本提供了完整的 Docker Compose 管理功能，与 `docker-compose.yml` 配合使用。
以下命令均需在开发副本 `worktree/sit/tuoluojiang/` 目录内执行；文中表格的容器名与网络 IP 属多容器分离布局，当前 `docker-compose.yml` 只定义单容器 `tuoluojiang-oa`，端口映射以 `docker-compose.yml` 为准。

## 主要优化内容

### 1. 增强的命令支持
- **start**: 启动所有服务（默认命令）
- **stop**: 停止所有服务
- **restart**: 重启所有服务
- **status**: 查看服务状态和资源使用
- **logs**: 查看服务日志（支持指定服务）
- **install**: 清理数据并重新安装
- **build**: 重新构建PHP镜像
- **help**: 显示帮助信息

### 2. 完善的清理逻辑
清理操作现在包含：
- Storage 缓存目录
- Redis 数据目录
- MySQL 数据目录
- Nginx 日志目录
- MySQL 日志目录
- 安装锁文件
- Docker 构建缓存

### 3. 详细的服务信息
启动/重启后会显示：
- 应用访问地址
- Web界面地址
- 数据库连接信息
- Redis连接信息
- 容器网络配置

## 使用示例

```bash
# 查看帮助
./run.sh help

# 启动服务（默认）
./run.sh start

# 查看服务状态
./run.sh status

# 查看PHP容器日志
./run.sh logs php

# 重启所有服务
./run.sh restart

# 清理并重新安装
./run.sh install

# 重新构建PHP镜像
./run.sh build

# 停止所有服务
./run.sh stop
```

## 服务配置对应关系

脚本与 `docker-compose.yml` 的对应配置：

| 服务 | 容器名 | 端口映射 | 网络IP |
|------|--------|----------|--------|
| MySQL | mysql_tuoluojiang | 3376:3306 | 192.168.10.14 |
| Redis | redis_tuoluojiang | 6389:6379 | 192.168.10.15 |
| PHP应用 | tuoluojiang | 20300:20200 | 192.168.10.16 |
| Nginx | nginx_tuoluojiang | 80:80, 443:443 | 192.168.10.17 |

## 访问地址

- **应用接口**: http://localhost:20300
- **Web界面**: http://localhost:80
- **MySQL管理**: localhost:3376 (root/123456)
- **Redis连接**: localhost:6389

## 故障排查

### 查看特定服务日志
```bash
./run.sh logs mysql    # MySQL日志
./run.sh logs redis    # Redis日志
./run.sh logs php      # PHP应用日志
./run.sh logs nginx    # Nginx日志
```

### 重新构建镜像
如果PHP镜像有问题：
```bash
./run.sh build
./run.sh restart
```

### 完全重新安装
```bash
./run.sh install
```

## 注意事项

1. 确保 Docker 和 Docker Compose 已安装
2. 首次运行建议使用 `./run.sh install`
3. PHP镜像构建需要网络连接下载依赖
4. 清理操作会删除所有数据，请先备份重要数据

## 脚本特点

- **彩色输出**: 不同级别信息使用不同颜色
- **错误处理**: 完善的错误检查和退出机制
- **健康检查**: 自动检查Docker和Docker Compose状态
- **资源监控**: 显示容器资源使用情况
- **网络信息**: 显示详细的网络配置信息