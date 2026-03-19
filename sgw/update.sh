#!/bin/bash
# =============================================================
# update.sh — 更新脚本
# 拉取最新代码并重新构建容器，.env 不受影响
# =============================================================

set -euo pipefail
cd "$(dirname "$0")"

BOLD='\033[1m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; RESET='\033[0m'

echo -e "${BOLD}SubSieve — 更新${RESET}"
echo "────────────────────────────────────────"

# 检查 .env 文件是否存在，缺失则中止（重建容器会导致密钥等配置丢失）
if [[ ! -f .env ]]; then
    echo -e "${RED}❌ 未找到 .env 文件${RESET}"
    echo ""
    echo "   .env 包含管理密码、Secret Path 等关键配置，缺失后重建容器会导致："
    echo "     • 管理后台无法访问（Secret Path 丢失）"
    echo "     • 订阅网关无法启动（V2B_BACKEND 未设置）"
    echo ""
    echo "   解决方法："
    echo "     1. 如有备份，将 .env 文件恢复到 $(pwd)/.env"
    echo "     2. 如无备份，查阅 DEPLOY_INFO.txt 获取配置信息，"
    echo "        然后重新运行 ./setup.sh 重新部署"
    echo ""
    if [[ -f DEPLOY_INFO.txt ]]; then
        echo -e "${CYAN}当前 DEPLOY_INFO.txt 内容：${RESET}"
        cat DEPLOY_INFO.txt
        echo ""
    fi
    exit 1
fi

# 检查 git 仓库
if [[ ! -d .git ]] && [[ ! -d ../.git ]]; then
    echo -e "${YELLOW}⚠  当前目录不是 git 仓库，无法自动拉取更新${RESET}"
    echo "   请手动下载最新版本后运行 docker compose up -d --build"
    exit 1
fi

# 备份 .env
if [[ -f .env ]]; then
    cp .env .env.bak
    echo -e "${CYAN}已备份 .env → .env.bak${RESET}"
fi

# 拉取最新代码（从仓库根目录执行）
GIT_ROOT=$(git -C "$(dirname "$0")" rev-parse --show-toplevel 2>/dev/null || echo "$(dirname "$0")")

# 自动检测远程默认分支（main / master 均可）
REMOTE_BRANCH=$(git -C "$GIT_ROOT" remote show origin 2>/dev/null \
    | grep 'HEAD branch' | awk '{print $NF}')
REMOTE_BRANCH=${REMOTE_BRANCH:-main}

echo -e "${CYAN}正在拉取最新代码…${RESET}"
if ! git -C "$GIT_ROOT" pull origin "$REMOTE_BRANCH"; then
    # 还原 .env 再退出
    if [[ -f .env.bak ]]; then
        mv .env.bak .env
        echo -e "${CYAN}已还原 .env${RESET}"
    fi
    echo -e "${YELLOW}⚠  git pull 失败，请检查网络或手动更新${RESET}"
    exit 1
fi

# 还原 .env（git pull 不会覆盖未追踪文件，但以防万一）
if [[ -f .env.bak ]]; then
    mv .env.bak .env
    echo -e "${CYAN}已还原 .env${RESET}"
fi

# 重新构建并重启容器
echo ""
echo -e "${CYAN}正在重新构建容器…${RESET}"
docker compose up -d --build

# 清理旧镜像
echo -e "${CYAN}清理旧镜像…${RESET}"
docker image prune -f --filter "dangling=true" > /dev/null 2>&1 || true

# 验证容器健康状态
echo ""
echo -e "${CYAN}验证容器状态…${RESET}"
sleep 5
HEALTH_OK=true
for NAME in subscribe-gateway subscribe-admin; do
    STATUS=$(docker inspect --format '{{.State.Status}}' "$NAME" 2>/dev/null || echo "missing")
    if [[ "$STATUS" == "running" ]]; then
        echo -e "  ✅ $NAME 运行中"
    else
        echo -e "  ❌ $NAME 状态异常（$STATUS）"
        echo -e "${YELLOW}  最近日志：${RESET}"
        docker logs --tail 30 "$NAME" 2>&1 | sed 's/^/    /' || true
        HEALTH_OK=false
    fi
done

# 检查管理后台端口是否可达
ADMIN_PORT=64444
if (echo > /dev/tcp/localhost/$ADMIN_PORT) 2>/dev/null; then
    echo -e "  ✅ 管理后台端口 ${ADMIN_PORT} 可达"
else
    echo -e "  ❌ 管理后台端口 ${ADMIN_PORT} 不可达"
    echo -e "${YELLOW}  nginx 可能未正常启动，请查看日志：${RESET}"
    docker logs --tail 20 subscribe-admin 2>&1 | sed 's/^/    /' || true
    HEALTH_OK=false
fi

echo ""
if [[ "$HEALTH_OK" == "true" ]]; then
    echo -e "${BOLD}════════════════════════════════════════════${RESET}"
    echo -e "${GREEN}${BOLD}  ✅ 更新完成${RESET}"
    echo -e "${BOLD}════════════════════════════════════════════${RESET}"
    echo ""
    echo -e "  访问信息不变，查阅方式：${CYAN}cat DEPLOY_INFO.txt${RESET}"
else
    echo -e "${BOLD}════════════════════════════════════════════${RESET}"
    echo -e "${YELLOW}${BOLD}  ⚠️  更新完成，但部分容器异常${RESET}"
    echo -e "${BOLD}════════════════════════════════════════════${RESET}"
    echo ""
    echo -e "  排查命令："
    echo -e "    ${CYAN}docker ps -a${RESET}               查看所有容器状态"
    echo -e "    ${CYAN}docker logs subscribe-admin${RESET}  查看管理后台日志"
    echo -e "    ${CYAN}docker logs subscribe-gateway${RESET} 查看网关日志"
    echo -e "    ${CYAN}docker compose up -d${RESET}         尝试重新启动"
fi
echo ""
