# Shared Invoice Library (libs/shared-invoice)

## 概述
`libs/shared-invoice` 是发票系统与费用报销防重闭环的跨端共享库占位目录。

## 规划职责
1. **共享契约与类型定义**：前后端统一的发票数据模型、增值税发票结构定义、OCR 识别响应结构体。
2. **报销校验规则**：发票代码/号码唯一性规则、价税合计校验、发票状态生命周期定义。
3. **通用工具库**：发票税额计算、统计聚合辅助方法。

## 依赖边界
- 本库遵循 Monorepo 单向依赖规范（`libs/` 不得依赖 `apps/`）。
- 供 `apps/oa-backend` 与 `apps/oa-frontend` 引入消费。
