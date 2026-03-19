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
echo -e "${CYAN}正在拉取最新代码…${RESET}"
git -C "$GIT_ROOT" pull origin main 2>/dev/null || git -C "$GIT_ROOT" pull origin master 2>/dev/null || {
    echo -e "${YELLOW}⚠  git pull 失败，请检查网络或手动更新${RESET}"
    exit 1
}

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

echo ""
echo -e "${BOLD}════════════════════════════════════════════${RESET}"
echo -e "${GREEN}${BOLD}  ✅ 更新完成${RESET}"
echo -e "${BOLD}════════════════════════════════════════════${RESET}"
echo ""
echo -e "  访问信息不变，查阅方式：${CYAN}cat DEPLOY_INFO.txt${RESET}"
echo ""
