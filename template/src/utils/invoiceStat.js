/**
 * 进项发票统计口径转换
 * 后端 /expense/invoice/my 与 /expense/invoice/finance 返回 snake_case 汇总，
 * 页面统计卡片统一使用 camelCase，这里做一次收敛，避免两处口径不一致。
 * @param {Object} stat 后端返回的 stat 对象
 * @returns {Object} 页面可直接渲染的统计对象
 */
const toNumber = (val) => {
  const num = parseFloat(val)
  return isNaN(num) ? 0 : num
}

export const mapInvoiceStat = (stat = {}) => ({
  totalCount: toNumber(stat.total_count),
  totalAmount: toNumber(stat.total_amount),
  unboundCount: toNumber(stat.unbound_count),
  unboundAmount: toNumber(stat.unbound_amount),
  bindingCount: toNumber(stat.binding_count),
  bindingAmount: toNumber(stat.binding_amount),
  reimbursedCount: toNumber(stat.reimbursed_count),
  reimbursedAmount: toNumber(stat.reimbursed_amount),
  abnormalCount: toNumber(stat.abnormal_count),
  abnormalAmount: toNumber(stat.abnormal_amount)
})
