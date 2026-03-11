# 陀螺匠 Docker 一键运行指南

本文档提供使用 Docker 一键运行陀螺匠项目的详细步骤。

## 1. 使用腾讯云镜像

直接使用腾讯云容器镜像服务中的完整镜像：

```bash
# 运行容器，使用腾讯云镜像
docker run -d -p 8022:80 --name tuoluojiang ccr.ccs.tencentyun.com/zbkj/tuoluojiang:v2.0
```

## 2. 文件挂载说明

如果需要将本地项目文件挂载到容器中，以便实时修改和调试，可以使用以下命令：

```bash
# 运行容器并挂载本地项目文件
docker run -d \
  -p 8022:80 -p 3306:3306 -p 6379:6379 \
  -v /path/to/your/project:/var/www \
  --name tuoluojiang \
  ccr.ccs.tencentyun.com/zbkj/tuoluojiang:v2.0
```

**注意**：将 `/path/to/your/project` 替换为你的本地项目目录路径。



## 3. 访问应用

容器启动后，可以通过以下地址访问应用：

```
http://localhost:8022
```

## 4. 连接数据库

### 4.1 连接 MySQL

```bash
# 连接 MySQL 数据库
mysql -h 127.0.0.1 -P 3306 -u tuoluojiang -p123456

# 查看数据库
mysql> SHOW DATABASES;
```

### 4.2 连接 Redis

```bash
# 连接 Redis
redis-cli -h 127.0.0.1 -p 6379 -a 123456

# 测试连接
127.0.0.1:6379> PING
```

## 5. 查看容器状态

```bash
# 查看容器状态
docker ps | grep tuoluojiang

# 查看容器日志
docker logs tuoluojiang
```

## 6. 停止和删除容器

```bash
# 停止容器
docker stop tuoluojiang

# 删除容器
docker rm tuoluojiang
```

## 7. 注意事项

- 容器启动时会自动初始化数据库，创建 `tuoluojiang` 数据库和用户
- 数据库连接信息：
  - 用户名：tuoluojiang
  - 密码：123456
  - 数据库名：tuoluojiang
- Redis 连接信息：
  - 密码：123456
- 应用默认运行在端口 8022
- 首次访问会进入安装向导，按照提示完成配置

## 8. 故障排查

如果遇到问题，可以查看容器日志：

```bash
# 查看容器详细日志
docker logs -f tuoluojiang
```

常见问题：
- 端口冲突：修改映射端口
- 权限问题：确保项目目录有正确的权限
- 数据库连接失败：检查数据库服务是否正常启动
