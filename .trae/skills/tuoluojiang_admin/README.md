# 陀螺匠管理系统 Skill 文档

## 1. 项目概述

陀螺匠管理系统是一个基于 Vue.js 2.6 开发的企业级管理系统模板，集成了丰富的功能模块和组件，旨在为企业提供高效、便捷的管理解决方案。

- **项目名称**：tuoluojiang
- **版本**：1.1.0
- **描述**：赋能开发者，助力企业发展,提高企业管理水平，提升企业核心竞争力，实现企业利润最大化。
- **作者**：Tuoluojiang Team <admin@tuoluojiang.com>
- **许可证**：MIT

## 2. 目录结构

项目采用清晰的目录结构，便于开发和维护：

```
template/
├── patches/              # 补丁文件
├── public/               # 静态资源
│   ├── image/            # 图片资源
│   ├── luckysheet/       # 电子表格组件
│   ├── favicon.ico       # 网站图标
│   ├── index.html        # 入口 HTML
│   └── manifest.json     # 应用清单
├── src/                  # 源代码
│   ├── api/              # API 接口
│   ├── assets/           # 静态资源
│   ├── components/       # 组件
│   ├── config/           # 配置
│   ├── directive/        # 指令
│   ├── filters/          # 过滤器
│   ├── icons/            # 图标
│   └── App.vue           # 根组件
├── .eslintignore         # ESLint 忽略文件
├── .eslintrc.js          # ESLint 配置
├── README.md             # 项目说明
├── babel.config.js       # Babel 配置
├── docker-compose.yml    # Docker 配置
├── index.js              # 入口文件
├── jest.config.js        # Jest 配置
├── jsconfig.json         # JS 配置
├── package.json          # 项目依赖
└── postcss.config.js     # PostCSS 配置
```

### 2.1 核心目录说明

- **src/api/**：包含所有 API 接口定义，如 administration.js、business.js、chart.js 等
- **src/components/**：包含丰富的组件，如 form-designer、form-render、workFlow 等
- **src/assets/**：包含图片、音频、字体等静态资源
- **src/directive/**：包含自定义指令，如 clipboard、permission、waves 等
- **src/filters/**：包含过滤器
- **src/icons/**：包含 SVG 图标和 iconfont

## 3. 核心功能

### 3.1 表单设计与渲染
- **form-designer**：可视化表单设计器
- **form-render**：表单渲染组件
- **form-common**：表单通用组件

### 3.2 工作流管理
- **workFlow**：工作流节点管理
- **processSetting**：流程设置

### 3.3 文档处理
- **openFile**：支持多种文件格式的预览和编辑
  - 文档：docx、txt、md
  - 表格：xlsx
  - 图片：png、jpg 等
  - 视频：mp4
  - 音频：mp3
  - PDF：pdf
  - 思维导图：xmind

### 3.4 数据可视化
- **scEcharts**：基于 ECharts 的图表组件
- **echarts**：图表容器组件

### 3.5 权限管理
- **systemAuth**：系统权限管理
- **permission**：权限指令

### 3.6 其他功能
- **ThemePicker**：主题选择器
- **code-editor**：代码编辑器
- **simpleTable**：简化表格组件
- **xmind-editor**：思维导图编辑器
- **holidaySetting**：假期设置
- **invoice**：发票管理

## 4. 技术栈

### 4.1 前端框架
- **Vue.js**：2.6.10
- **Vue Router**：3.0.2
- **Vuex**：3.1.0
- **Element UI**：2.15.14

### 4.2 构建工具
- **Vue CLI**：3.5.3
- **Webpack**：4.44.2
- **Babel**：7.13.0
- **ESLint**：7.15.0

### 4.3 第三方库
- **ECharts**：4.2.1
- **FullCalendar**：6.1.4
- **WangEditor**：4.7.15 / 5.1.18
- **LuckyExcel**：1.0.1
- **Simple Mind Map**：0.12.1
- **Vue Office**：1.6.2
- **Axios**：1.6.0
- **Moment**：2.29.4

## 5. 依赖包

### 5.1 核心依赖

| 依赖 | 版本 | 用途 |
|------|------|------|
| vue | 2.6.10 | 前端框架 |
| vue-router | 3.0.2 | 路由管理 |
| vuex | 3.1.0 | 状态管理 |
| element-ui | 2.15.14 | UI 组件库 |
| axios | 1.6.0 | HTTP 客户端 |
| echarts | 4.2.1 | 数据可视化 |
| moment | 2.29.4 | 时间处理 |
| @form-create/element-ui | 2.5.27 | 表单生成器 |
| @fullcalendar/vue | 6.1.4 | 日历组件 |
| @wangeditor/editor | 5.1.18 | 富文本编辑器 |
| luckyexcel | 1.0.1 | Excel 处理 |
| simple-mind-map | 0.12.1 | 思维导图 |
| @vue-office/docx | 1.6.2 | Office 文档预览 |

### 5.2 开发依赖

| 依赖 | 版本 | 用途 |
|------|------|------|
| @vue/cli-service | 3.5.3 | Vue CLI 服务 |
| @vue/cli-plugin-babel | 3.5.3 | Babel 插件 |
| @vue/cli-plugin-eslint | 3.9.1 | ESLint 插件 |
| @vue/cli-plugin-unit-jest | 3.5.3 | Jest 插件 |
| eslint | 7.15.0 | 代码检查 |
| prettier | 2.4.1 | 代码格式化 |
| less | 4.1.1 | CSS 预处理器 |
| sass-loader | 7.3.1 | SASS 加载器 |
| patch-package | 6.2.2 | 补丁管理 |

## 6. 快速开始

### 6.1 环境要求
- Node.js：>= 8.9
- npm：>= 3.0.0

### 6.2 安装依赖

```bash
# 进入 template 目录
cd /Users/xurongyao/工作/陀螺匠/kaiyuan/tuoluojiang/template

# 安装依赖
npm install
```

### 6.3 启动开发服务器

```bash
# 启动开发服务器
npm run dev

# 构建生产版本
npm run build:prod

# 构建并发布
npm run build

# 预览构建结果
npm run preview

# 代码检查
npm run lint

# 代码格式化
npm run prettier
```

## 7. 配置说明

### 7.1 项目配置
- **jsconfig.json**：JS 项目配置
- **babel.config.js**：Babel 配置
- **postcss.config.js**：PostCSS 配置
- **eslintrc.js**：ESLint 配置
- **jest.config.js**：Jest 测试配置

### 7.2 API 配置
- **src/api/request.js**：API 请求配置
- **src/api/**：各个模块的 API 接口

### 7.3 指令配置
- **src/directive/permission/**：权限指令配置
- **src/directive/clipboard/**：剪贴板指令
- **src/directive/waves/**：波浪效果指令

## 8. 部署指南

### 8.1 Docker 部署
项目提供了 Docker 配置文件，可以使用 Docker 进行部署：

```bash
# 进入 template 目录
cd /Users/xurongyao/工作/陀螺匠/kaiyuan/tuoluojiang/template

# 使用 Docker Compose 启动服务
docker-compose up -d
```

### 8.2 常规部署
1. 构建生产版本：`npm run build:prod`
2. 将 `dist` 目录部署到 Web 服务器
3. 配置 Web 服务器（如 Nginx）指向 `dist` 目录

## 9. 开发文档

### 9.1 组件开发
- **组件目录**：`src/components/`
- **组件命名**：使用 PascalCase 命名法
- **组件文档**：每个组件目录下可添加 README.md 文件

### 9.2 API 开发
- **API 目录**：`src/api/`
- **API 命名**：按功能模块分类
- **请求配置**：统一在 `request.js` 中配置

### 9.3 样式开发
- **样式文件**：可