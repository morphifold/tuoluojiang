import request from './request'

/**
 * 员工端 - 我的发票夹列表
 * @param {Object} data { page, limit, status, invoice_type, start_date, end_date, min_amount, max_amount, keyword }
 */
export function getExpenseInvoiceMyListApi(data) {
  return request.get('expense/invoice/my', data)
}

/**
 * 员工端 - 手动录入/保存发票
 * @param {Object} data { invoice_type, invoice_code, invoice_number, amount, tax_amount, total_amount, invoice_date, seller_name, buyer_name, mark, file_path, file_name, file_type }
 */
export function addExpenseInvoiceMyApi(data) {
  return request.post('expense/invoice/my', data)
}

/**
 * 员工端 - 获取发票详情
 * @param {Number|String} id
 */
export function getExpenseInvoiceMyDetailApi(id) {
  return request.get(`expense/invoice/my/${id}`)
}

/**
 * 员工端 - 更新发票信息
 * @param {Number|String} id
 * @param {Object} data
 */
export function updateExpenseInvoiceMyApi(id, data) {
  return request.put(`expense/invoice/my/${id}`, data)
}

/**
 * 员工端 - 删除发票（未绑定状态）
 * @param {Number|String} id
 */
export function deleteExpenseInvoiceMyApi(id) {
  return request.delete(`expense/invoice/my/${id}`)
}

/**
 * 发票解析（上传PDF电子发票或图片自动解析）
 * @param {FormData|Object} data 包含 file 或 file_path
 */
export function parseExpenseInvoiceApi(data) {
  return request.post('expense/invoice/my/parse', data)
}

/**
 * 发票查重接口（代码+号码查重）
 * @param {Object} data { invoice_code, invoice_number, exclude_id }
 */
export function checkExpenseInvoiceDuplicateApi(data) {
  return request.post('expense/invoice/check', data)
}

/**
 * 财务端 - 全局进项发票台账列表
 * @param {Object} data { page, limit, uid, dep_id, status, abnormal_status, invoice_type, start_date, end_date, keyword }
 */
export function getExpenseInvoiceFinanceListApi(data) {
  return request.get('expense/invoice/finance', data)
}

/**
 * 财务端 - 标记发票异常状态
 * @param {Object} data { id, abnormal_status (0-正常, 1-作废, 2-红冲, 3-假票), abnormal_reason }
 */
export function markExpenseInvoiceAbnormalApi(data) {
  return request.post('expense/invoice/finance/abnormal', data)
}

/**
 * 财务端 - 导出进项发票台账 Excel
 * @param {Object} data 筛选条件
 */
export function exportExpenseInvoiceFinanceApi(data) {
  return request.get('expense/invoice/finance/export', data)
}

/**
 * 报销单绑定发票
 * @param {Object} data { apply_id, invoice_ids: [] }
 */
export function bindExpenseInvoiceApi(data) {
  return request.post('expense/invoice/bind', data)
}

/**
 * 报销单解绑发票
 * @param {Object} data { apply_id, invoice_ids: [] }
 */
export function unbindExpenseInvoiceApi(data) {
  return request.post('expense/invoice/unbind', data)
}
