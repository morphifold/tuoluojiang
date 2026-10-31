#!/bin/bash

# 陀螺匠 Docker 启动脚本

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 配置参数
COMPOSE_FILE="docker-compose.yml"

# 从 docker-compose.yml 中读取配置
load_config_from_compose() {
    # 获取应用名称（容器名）
    APP_NAME=$(grep "container_name: tuoluojiang" "$COMPOSE_FILE" | awk '{print $2}')
    
    # 获取端口配置
    # MySQL端口
    MYSQL_PORT=$(awk '/mysql_tuoluojiang:/{found=1} found && /:3306/{print; exit}' "$COMPOSE_FILE" | awk -F'[-:]' '{gsub(/^[ \t]+|[ \t]+$/, "", $2); print $2}')
    
    # Redis端口（处理引号）
    REDIS_PORT=$(awk '/redis_tuoluojiang:/{found=1} found && /:6379/{print; exit}' "$COMPOSE_FILE" | tr -d '"' | awk -F'[-:]' '{gsub(/^[ \t]+|[ \t]+$/, "", $2); print $2}')
    
    # 应用端口（PHP容器）
    APP_PORT=$(awk '/container_name: tuoluojiang/{found=1} found && /:20200/{print; exit}' "$COMPOSE_FILE" | awk -F'[-:]' '{gsub(/^[ \t]+|[ \t]+$/, "", $2); print $2}')
    
    # Nginx端口（HTTP端口，取第一个）
    NGINX_PORT=$(awk '/nginx_tuoluojiang:/{found=1} found && /:80/{print; exit}' "$COMPOSE_FILE" | tr -d '"' | awk -F'[-:]' '{gsub(/^[ \t]+|[ \t]+$/, "", $2); print $2}')
    
    # 如果解析失败，使用默认值
    APP_NAME=${APP_NAME:-"tuoluojiang"}
    APP_PORT=${APP_PORT:-"20300"}
    MYSQL_PORT=${MYSQL_PORT:-"3376"}
    REDIS_PORT=${REDIS_PORT:-"6389"}
    NGINX_PORT=${NGINX_PORT:-"80"}
}

# 加载配置
load_config_from_compose

# 日志函数
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 显示帮助信息
show_help() {
    echo -e "${CYAN}陀螺匠 Docker 管理脚本${NC}"
    echo ""
    echo "用法: ./run.sh [命令]"
    echo ""
    echo "命令:"
    echo "  start          启动所有服务（默认）"
    echo "  stop           停止所有服务"
    echo "  restart        重启所有服务"
    echo "  status         查看服务状态"
    echo "  logs [服务名]  查看服务日志"
    echo "  install        清理数据并重新安装"
    echo "  build          重新构建PHP镜像"
    echo "  delete         删除容器和临时文件（保留数据）"
    echo "  help           显示帮助信息"
    echo ""
    echo "可用服务名:"
    echo "  mysql          MySQL数据库"
    echo "  redis          Redis缓存"
    echo "  tuoluojiang    PHP应用"
    echo "  nginx          Nginx服务器"
    echo ""
    echo "示例:"
    echo "  ./run.sh start"
    echo "  ./run.sh stop"
    echo "  ./run.sh logs mysql"
    echo "  ./run.sh logs tuoluojiang"
    echo "  ./run.sh install"
}

# 检查Docker Compose是否安装
check_docker_compose() {
    if ! command -v docker-compose &> /dev/null; then
        log_error "docker-compose 未安装，请先安装 docker-compose"
        exit 1
    fi
}

# 检查Docker是否运行
check_docker() {
    if ! docker info &> /dev/null; then
        log_error "Docker 守护进程未运行，请先启动 Docker"
        exit 1
    fi
}

# 启动服务
start_services() {
    log_info "启动 Docker 容器..."
    docker-compose -f "$COMPOSE_FILE" up -d
    
    if [ $? -eq 0 ]; then
        log_success "Docker 容器启动成功！"
        show_service_info
    else
        log_error "Docker 容器启动失败！"
        exit 1
    fi
}

# 停止服务
stop_services() {
    log_info "停止 Docker 容器..."
    docker-compose -f "$COMPOSE_FILE" down
    
    if [ $? -eq 0 ]; then
        log_success "Docker 容器停止成功！"
    else
        log_error "Docker 容器停止失败！"
        exit 1
    fi
}

# 重启服务
restart_services() {
    log_info "重启 Docker 容器..."
    docker-compose -f "$COMPOSE_FILE" restart
    
    if [ $? -eq 0 ]; then
        log_success "Docker 容器重启成功！"
        show_service_info
    else
        log_error "Docker 容器重启失败！"
        exit 1
    fi
}

# 查看服务状态
show_status() {
    log_info "Docker 容器状态："
    docker-compose -f "$COMPOSE_FILE" ps
    
    echo ""
    log_info "容器资源使用情况："
    docker stats --no-stream 2>/dev/null || echo "无法获取资源使用情况"
}

# 查看日志
show_logs() {
    local service_name="$1"
    
    # 服务名称映射（简写到完整名称）
    case "$service_name" in
        mysql)
            service_name="mysql_tuoluojiang"
            ;;
        redis)
            service_name="redis_tuoluojiang"
            ;;
        php|tuoluojiang)
            service_name="tuoluojiang"
            ;;
        nginx)
            service_name="nginx_tuoluojiang"
            ;;
    esac
    
    if [ -z "$service_name" ]; then
        log_info "显示所有容器日志 (Ctrl+C 退出)..."
        docker-compose -f "$COMPOSE_FILE" logs -f
    else
        log_info "显示 $service_name 容器日志 (Ctrl+C 退出)..."
        docker-compose -f "$COMPOSE_FILE" logs -f "$service_name"
    fi
}

# 清理安装数据
# 参数: $1 - 是否删除容器和镜像 (true/false, 默认false)
clean_install_data() {
    local remove_containers="${1:-false}"
    
    if [ "$remove_containers" = "true" ]; then
        log_warning "删除容器和数据卷（保留镜像，仅保留代码和配置文件）..."
        
        # 停止并删除容器和数据卷（保留镜像）
        log_info "停止并删除容器和数据卷..."
        docker-compose -f "$COMPOSE_FILE" down -v
    else
        log_warning "清理数据（保留容器和镜像，仅删除数据文件）..."
        
        # 仅停止容器
        log_info "停止所有容器..."
        docker-compose -f "$COMPOSE_FILE" stop
    fi
    
    # 删除 MySQL 数据
    if [ -d "./help/docker-compose/mysql/data" ]; then
        log_info "删除 MySQL 数据..."
        rm -rf ./help/docker-compose/mysql/data/*
        log_success "MySQL 数据已删除"
    fi
    
    # 删除 MySQL 日志
    if [ -d "./help/docker-compose/mysql/log" ]; then
        log_info "删除 MySQL 日志..."
        rm -rf ./help/docker-compose/mysql/log/*
        log_success "MySQL 日志已删除"
    fi
    
    # 删除 Redis 数据
    if [ -d "./help/docker-compose/redis/data" ]; then
        log_info "删除 Redis 数据..."
        rm -rf ./help/docker-compose/redis/data/*
        log_success "Redis 数据已删除"
    fi
    
    # 删除 Nginx 日志
    if [ -d "./help/docker-compose/nginx/log" ]; then
        log_info "删除 Nginx 日志..."
        rm -rf ./help/docker-compose/nginx/log/*
        log_success "Nginx 日志已删除"
    fi
    
    # 清理 storage 目录（保留结构）
    if [ -d "./apps/oa-backend/storage" ]; then
        log_info "清理 storage 目录..."
        # 清理 framework 缓存
        rm -rf ./apps/oa-backend/storage/framework/cache/* 2>/dev/null
        rm -rf ./apps/oa-backend/storage/framework/sessions/* 2>/dev/null
        rm -rf ./apps/oa-backend/storage/framework/views/* 2>/dev/null
        # 清理日志
        rm -rf ./apps/oa-backend/storage/logs/*.log 2>/dev/null
        # 删除上传的文件
        rm -rf ./apps/oa-backend/storage/app/public/* 2>/dev/null
        log_success "storage 目录已清理"
    fi
    
    # 删除用户上传文件
    if [ -d "./apps/oa-backend/public/uploads" ]; then
        log_info "删除用户上传文件..."
        rm -rf ./apps/oa-backend/public/uploads/*
        log_success "用户上传文件已删除"
    fi
    
    # 清理 bootstrap 缓存
    if [ -d "./apps/oa-backend/bootstrap/cache" ]; then
        log_info "清理 bootstrap 缓存..."
        rm -rf ./apps/oa-backend/bootstrap/cache/*.php 2>/dev/null
        log_success "bootstrap 缓存已清理"
    fi
    
    # 删除安装锁文件
    if [ -f "./apps/oa-backend/public/install/install.lock" ]; then
        log_info "删除安装锁文件..."
        rm -f ./apps/oa-backend/public/install/install.lock
        log_success "安装锁文件已删除"
    fi
    
    log_success "清理完成！"
    log_info "保留的文件："
    echo "  ✓ 项目代码: ./apps/oa-backend/"
    echo "  ✓ 配置文件: ./apps/oa-backend/.env"
    echo "  ✓ Docker 配置: ./help/docker-compose/"
    echo "  ✓ docker-compose.yml"
    echo ""
    log_warning "以下数据已被删除："
    echo "  ✗ MySQL 数据库"
    echo "  ✗ Redis 数据"
    echo "  ✗ 用户上传文件"
    echo "  ✗ 应用日志和缓存"
}

# 构建PHP镜像
build_php_image() {
    log_info "重新构建PHP镜像..."
    
    # 检查Dockerfile是否存在
    if [ ! -f "./help/docker-compose/php/Dockerfile" ]; then
        log_error "PHP Dockerfile 不存在: ./help/docker-compose/php/Dockerfile"
        exit 1
    fi
    
    # 停止PHP服务
    log_info "停止PHP服务..."
    docker-compose -f "$COMPOSE_FILE" stop tuoluojiang
    
    # 删除旧镜像
    log_info "删除旧镜像..."
    docker rmi -f tuoluojiang 2>/dev/null || true
    
    # 构建新镜像
    log_info "构建新镜像..."
    docker-compose -f "$COMPOSE_FILE" build tuoluojiang
    
    if [ $? -eq 0 ]; then
        log_success "PHP镜像构建成功！"
        log_info "使用 './run.sh restart' 重启服务以应用新镜像"
    else
        log_error "PHP镜像构建失败！"
        exit 1
    fi
}

# 删除容器和临时文件（保留镜像）
delete_containers() {
    log_warning "删除容器和数据卷（保留镜像）..."
    clean_install_data "true"
}

# 显示服务信息
show_service_info() {
    echo ""
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${MAGENTA}                    陀螺匠服务信息                        ${NC}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "${GREEN}✓ 应用访问地址:${NC} http://localhost:$APP_PORT"
    echo -e "${GREEN}✓ Web界面地址:${NC} http://localhost:$NGINX_PORT"
    echo ""
    echo -e "${YELLOW}数据库连接信息:${NC}"
    echo -e "  MySQL地址: localhost:$MYSQL_PORT"
    echo -e "  用户名: root"
    echo -e "  密码: 123456"
    echo -e "  数据库: tuoluojiang"
    echo ""
    echo -e "${YELLOW}Redis连接信息:${NC}"
    echo -e "  Redis地址: localhost:$REDIS_PORT"
    echo -e "  密码: (无)"
    echo ""
    echo -e "${YELLOW}容器网络信息:${NC}"
    echo -e "  网络名称: app_net"
    echo -e "  子网: 192.168.10.0/24"
    echo -e "  MySQL IP: 192.168.10.14"
    echo -e "  Redis IP: 192.168.10.15"
    echo -e "  PHP应用 IP: 192.168.10.16"
    echo -e "  Nginx IP: 192.168.10.17"
    echo ""
    echo -e "${YELLOW}日志查看命令:${NC}"
    echo -e "  ./run.sh logs mysql      查看MySQL日志"
    echo -e "  ./run.sh logs redis      查看Redis日志"
    echo -e "  ./run.sh logs tuoluojiang 查看PHP应用日志"
    echo -e "  ./run.sh logs nginx      查看Nginx日志"
    echo ""
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}使用 './run.sh status' 查看容器状态${NC}"
    echo -e "${BLUE}使用 './run.sh help' 查看完整帮助${NC}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
}

# 主函数
main() {
    # 检查Docker和Docker Compose
    check_docker
    check_docker_compose
    
    # 解析参数
    local command="${1:-start}"
    
    case "$command" in
        start)
            start_services
            ;;
        stop)
            stop_services
            ;;
        restart)
            restart_services
            ;;
        status)
            show_status
            ;;
        logs)
            show_logs "$2"
            ;;
        install)
            clean_install_data "true"
            start_services
            ;;
        build)
            build_php_image
            ;;
        delete)
            delete_containers
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            log_error "未知命令: $command"
            show_help
            exit 1
            ;;
    esac
}

# 执行主函数
main "$@"
