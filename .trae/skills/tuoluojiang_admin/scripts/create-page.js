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

const pageName = options.name
const pageDir = options.dir || 'src/views'
const templateType = options.template || 'default'

// 转换命名
const kebabCaseName = pageName.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase()
const camelCaseName = pageName.replace(/^[A-Z]/, (letter) => letter.toLowerCase())

// 确保目录存在
if (!fs.existsSync(pageDir)) {
  fs.mkdirSync(pageDir, { recursive: true })
  console.log(`Created directory: ${pageDir}`)
}

// 读取模板文件
const templatePath = path.join(__dirname, '../assets/PageTemplate.vue')
let templateContent

try {
  templateContent = fs.readFileSync(templatePath, 'utf8')
} catch (error) {
  console.error(`Error reading template file: ${error.message}`)
  process.exit(1)
}

// 替换模板变量
const pageContent = templateContent
  .replace(/\{\{ PageName \}\}/g, pageName)
  .replace(/\{\{ pageName \}\}/g, camelCaseName)

// 写入页面文件
const pagePath = path.join(pageDir, `${kebabCaseName}.vue`)

try {
  fs.writeFileSync(pagePath, pageContent)
  console.log(`Created page: ${pagePath}`)
} catch (error) {
  console.error(`Error creating page file: ${error.message}`)
  process.exit(1)
}

console.log('Page created successfully!')
