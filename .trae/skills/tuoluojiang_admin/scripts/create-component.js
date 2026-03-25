#!/usr/bin/env node

const fs = require('fs')
const path = require('path')

// 解析命令行参数
const args = process.argv.slice(2)
const options = {}

for (let i = 0; i < args.length; i++) {
  if (args[i].startsWith('--')) {
    const key = args[i].substring(2)
    options[key] = args[i + 1]
    i++
  }
}

// 检查必要参数
if (!options.name) {
  console.error('Error: --name parameter is required')
  process.exit(1)
}

const componentName = options.name
const componentDir = options.dir || 'src/components/common'
const templateType = options.template || 'default'

// 转换命名
const kebabCaseName = componentName.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase()
const camelCaseName = componentName.replace(/^[A-Z]/, (letter) => letter.toLowerCase())

// 确保目录存在
if (!fs.existsSync(componentDir)) {
  fs.mkdirSync(componentDir, { recursive: true })
  console.log(`Created directory: ${componentDir}`)
}

// 读取模板文件
const templatePath = path.join(__dirname, '../assets/ComponentTemplate.vue')
let templateContent

try {
  templateContent = fs.readFileSync(templatePath, 'utf8')
} catch (error) {
  console.error(`Error reading template file: ${error.message}`)
  process.exit(1)
}

// 替换模板变量
const componentContent = templateContent
  .replace(/\{\{ ComponentName \}\}/g, componentName)
  .replace(/\{\{ componentName \}\}/g, camelCaseName)

// 写入组件文件
const componentPath = path.join(componentDir, `${kebabCaseName}.vue`)

try {
  fs.writeFileSync(componentPath, componentContent)
  console.log(`Created component: ${componentPath}`)
} catch (error) {
  console.error(`Error creating component file: ${error.message}`)
  process.exit(1)
}

console.log('Component created successfully!')
