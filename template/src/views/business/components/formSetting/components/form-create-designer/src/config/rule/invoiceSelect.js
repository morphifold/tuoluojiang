import uniqueId from '@form-create/utils/lib/unique'
import { makeRequiredRule } from '../../utils'

const label = '发票明细'
const name = 'invoiceSelect'

export default {
  icon: 'iconfont iconfapiao',
  label,
  name,
  drag: true,
  inside: false,
  rule() {
    return {
      type: name,
      field: uniqueId(),
      title: label,
      info: '选择或上传报销发票明细',
      symbol: 'invoiceSelect',
      props: {},
      value: [],
      // 设计器对 drag 为 true 的规则会向 rule.children 塞入拖拽容器，缺省会抛 TypeError 导致落拖静默失败
      children: []
    }
  },
  props() {
    return [makeRequiredRule()]
  },
  basic() {}
}
