#!/usr/bin/env node

const { execSync } = require('child_process')

// 解析命令行参数
const args = process.argv.slice(2)
const options = {
  fix: false,
  path: 'src'
}

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--fix') {
    options.fix = true
  } else if (args[i] === '--path' && args[i + 1]) {
    options.path = args[i + 1]
    i++
  }
}

// 构建 ESLint 命令
let eslintCommand = `npx eslint ${options.path}`
if (options.fix) {
  eslintCommand += ' --fix'
}

console.log(`Running ESLint on ${options.path}...`)

try {
  const output = execSync(eslintCommand, { encoding: 'utf8' })
  console.log('ESLint check completed successfully!')
  if (output) {
    console.log(output)
  }
} catch (error) {
  console.error('ESLint check failed:')
  console.error(error.stdout || error.message)
  process.exit(1)
}
