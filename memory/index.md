# Workspace Memory Index

## 1. 架构总览
- **工作区模式**: 分离模式（`workspaceMode: separated`），项目类型为控制仓（`projectType: control_repo`）。
- **任务编排**: Nx Monorepo 统一任务编排体系（`apps/` 与 `libs/`）。
- **静态治理**: `workspace-lanes.yaml` 定义模块词表、层与写者权限类。

## 2. 目录拓扑与应用划分
### Apps (`apps/`)
- `apps/oa-backend`: Laravel 8 + Swoole (LaravelS) 后端核心服务，包含已验收进项发票防重与报销闭环系统。
  - Targets: `serve`, `migrate`, `test`
- `apps/oa-frontend`: Vue 2 + Element UI 后端管理前端 SPA。
  - Targets: `build`, `serve`, `lint`
- `apps/edge-proxy`: Cloudflare Worker 边缘反向代理 (`oa.funopc.dev`)。
  - Targets: `deploy`

### Libs (`libs/`)
- `libs/shared-invoice`: 发票校验规则与跨端共享契约模型（占位）。

## 3. 运维与容器映射
- `docker-compose.yml`: `./apps/oa-backend:/var/www`（容器内运行 LaravelS 与 Nginx 反代）。
- `nginx_default.conf`: 静态资源直出 `/var/www/public`，动态请求代理至 LaravelS (20200)。
- `run.sh`: 运维脚本，已适配 `apps/oa-backend` 缓存、日志与临时目录清理。

## 4. 重构演进历史
- **2026-10-31**:
  1. 在 `master` 分支提交发票系统与全套配置变更：`feat(invoice): 进项发票与费用报销防重闭环系统完整实现`。
  2. 签出 `feature/nx-monorepo-separated` 分支，执行 `git mv` 平滑迁移：
     - `code/` -> `apps/oa-backend/`
     - `template/` -> `apps/oa-frontend/`
     - `cloudflare-worker/` -> `apps/edge-proxy/`
  3. 配置 Nx monorepo 体系（`package.json`, `nx.json`, 各项目 `project.json`），`npx nx show projects` 验证通过。
  4. 适配 `docker-compose.yml` 卷映射与 `run.sh` 脚本路径。
  5. 落地分离模式控制仓治理规范（`workspace.json`, `workspace-lanes.yaml`）。
