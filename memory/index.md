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

- **2026-11-01**:
  1. 开发模式收敛为 sit 单写副本：创建 `worktree/sit/tuoluojiang`（分支 `sit`，起点 `feature/nx-monorepo-separated` 的 HEAD），推送 `origin/sit`。
  2. 更新分离模式目录约定（`workspace.json` 的 `localRepos`/`branchRepoRoot` → `worktree/...`）与 lane 注册表（`repo_overrides` + `dev` 权限类），新增仓库根 `AGENTS.md`。详见第 5 节。

## 5. 开发模式（sit 单写副本）
- **唯一开发副本**: `worktree/sit/tuoluojiang`（分支 `sit`），由 `feature/nx-monorepo-separated` 当前 HEAD 创建的 git worktree。
- **不可写位置**: 仓库根检出与 `master`、`main`（base）对业务代码只读，不承接开发提交；不得另建第二份开发副本。
- **lane 命名**: `sit-<module>-<layer>`，`<module>` ∈ {`oa-backend`, `oa-frontend`, `edge-proxy`}，`<layer>` 沿用各模块 `workspace-lanes.yaml#modules.<module>.layers`。
- **静态声明**: `workspace-lanes.yaml#repo_overrides.tuoluojiang`（`only_worktree: worktree/sit/tuoluojiang`、`branch: sit`）与 `permissions.dev`（`code_write: allowed`、`concurrent_writers: 1`）。
- **集成与发布**: sit 开发完成后走受控 `release-<module>-integration`（`permissions.integration`），再按 `zhk-git-aoneflow` 提 CR 并人工确认合入 base；不在 base 分支直接开发。
- **构建与部署**: 统一在 sit 副本内执行（`npx nx <target> <project>`）；根检出只保留控制面文件与治理账本。
