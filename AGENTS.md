---
workspace:
  id: "fde_oa"
  projectType: "control_repo"
  workspaceMode: "separated"
  laneRegistryFile: "workspace-lanes.yaml"
  dev:
    worktree: "worktree/sit/tuoluojiang"
    branch: "sit"
    laneTemplate: "sit-<module>-<layer>"
---

# fde_oa 工作区约定

陀螺匠 OA 分离模式工作区：控制面文件在仓库根，业务代码在 `apps/`（`libs/` 为共享库），容器仓库为 `tuoluojiang`。

## 唯一开发副本

- 本仓唯一可写开发副本是 `worktree/sit/tuoluojiang`，分支 `sit`；其余位置对业务代码只读。
- 仓库根检出与 `master`、`main`（base）不得直接开发业务代码，也不得向其合入或推送开发提交。
- lane 命名固定为 `sit-<module>-<layer>`：`<module>` ∈ `oa-backend` / `oa-frontend` / `edge-proxy`，`<layer>` 取 `workspace-lanes.yaml#modules.<module>.layers`。
- 静态声明以 `workspace-lanes.yaml` 为准：`repo_overrides.tuoluojiang`（`only_worktree` + `branch`）、`permissions.dev`（sit 开发，`code_write: allowed`）、`permissions.master|main`（base，`code_write: forbidden`）。

## 读写与集成

- 构建、运行、部署、调试一律在 sit 副本内执行（`npx nx <target> <project>`）；根检出只承载控制面文件与治理账本。
- `apps/**`、`libs/**` 的业务改动只提交到 `sit`，不得在根检出或 base 分支提交。
- 集成走受控 `release-<module>-integration`，再按 `zhk-git-aoneflow` 提 CR 并人工确认合入 base；不得在 base 分支直接开发。
- 不得为业务开发另建第二份副本，禁止 `push --force`。
