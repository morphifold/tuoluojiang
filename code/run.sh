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
APP_NAME="tuoluojiang"
APP_PORT="20300"
MYSQL_PORT="3376"
REDIS_PORT="6389"
NGINX_PORT="80"

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
clean_install_data() {
    log_warning "执行安装模式，清理缓存数据..."
    
    # 停止服务
    stop_services
    
    # 清理 storage 缓存目录
    if [ -d "./storage" ]; then
        log_info "清理 ./storage 缓存目录..."
        find ./storage -type f -delete 2>/dev/null
        log_success "storage 缓存目录已清理"
    fi
    
    # 清理 redis 数据目录
    if [ -d "./helper/docker-compose/redis/data" ]; then
        log_info "清理 ./helper/docker-compose/redis/data..."
        rm -rf ./helper/docker-compose/redis/data/*
        log_success "redis 数据目录已清理"
    fi
    
    # 清理 mysql 数据目录
    if [ -d "./helper/docker-compose/mysql/data" ]; then
        log_info "清理 ./helper/docker-compose/mysql/data..."
        rm -rf ./helper/docker-compose/mysql/data/*
        log_success "mysql 数据目录已清理"
    fi
    
    # 清理 nginx 日志目录
    if [ -d "./helper/docker-compose/nginx/log" ]; then
        log_info "清理 ./helper/docker-compose/nginx/log..."
        rm -rf ./helper/docker-compose/nginx/log/*
        log_success "nginx 日志目录已清理"
    fi
    
    # 清理 mysql 日志目录
    if [ -d "./helper/docker-compose/mysql/log" ]; then
        log_info "清理 ./helper/docker-compose/mysql/log..."
        rm -rf ./helper/docker-compose/mysql/log/*
        log_success "mysql 日志目录已清理"
    fi
    
    # 删除安装锁文件
    if [ -f "./public/install/install.lock" ]; then
        log_info "删除 ./public/install/install.lock..."
        rm -f ./public/install/install.lock
        log_success "安装锁文件已删除"
    fi
    
    # 清理Docker构建缓存
    log_info "清理Docker构建缓存..."
    docker system prune -f --volumes
    
    log_success "所有缓存和数据已清理完成！"
}

# 构建PHP镜像
build_php_image() {
    log_info "重新构建PHP镜像..."
    
    # 检查Dockerfile是否存在
    if [ ! -f "./helper/docker-compose/php/Dockerfile" ]; then
        log_error "PHP Dockerfile 不存在: ./helper/docker-compose/php/Dockerfile"
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
            clean_install_data
            start_services
            ;;
        build)
            build_php_image
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
