<template>
  <div>
    <!-- 编辑审批 -->
    <el-drawer
      :size="drawerSize"
      :visible.sync="drawer"
      :show-close="true"
      :direction="direction"
      :before-close="close"
      :append-to-body="true"
      :wrapper-closable="false"
    >
      <div slot="title">
        <div v-if="configData" class="headerBox acea-row row-middle row-between">
          <div v-if="!isEdit" class="acea-row row-middle">
            <div
              class="portrait mr10"
              :style="{ backgroundColor: configData.approve ? configData.approve.color : configData.color }"
            >
              <i class="iconfont" :class="configData.approve ? configData.approve.icon : configData.icon"></i>
            </div>
            <div class="nameBox">
              <span class="st1">{{ configData.approve ? configData.approve.name : configData.name }}</span>
            </div>
          </div>
          <div v-else class="acea-row row-middle">
            <div
              class="portrait mr10"
              :style="{ backgroundColor: configData.approve ? configData.approve.color : configData.color }"
            >
              <i class="iconfont" :class="configData.approve ? configData.approve.icon : configData.icon"></i>
            </div>
            <div class="nameBox">
              <span class="st1">{{ configData.approve ? configData.approve.name : configData.name }}</span>
            </div>
          </div>
        </div>
      </div>
      <div v-if="isReimburseForm" class="dd-tabs-nav">
        <div class="dd-tab" :class="{ active: ddTab === 'submit' }" @click="ddTab = 'submit'">发起提交</div>
        <div class="dd-tab" :class="{ active: ddTab === 'history' }" @click="switchHistory">历史记录</div>
      </div>
      <div class="ex-content" v-show="ddTab === 'submit'">
        <el-scrollbar style="height: 100%">
          <div class="ex-content-con">
            <div v-if="draftHint" class="dd-draft-hint">
              <span>已为您恢复上次暂存的草稿</span>
              <button type="button" class="dd-draft-clear" @click="clearDraftForm">清空重填</button>
            </div>
            <div v-if="!isReimburseForm" class="acea-row mb20">
              <div class="shu mr10"></div>
              <span class="title">提交审批</span>
            </div>
            <form-create
              class="edit-examine-form"
              ref="fc"
              v-model="fapi"
              :option="option"
              size="small"
              :rule="rules"
              @change="handleChange"
            />
            <process-from
              v-if="configData && configData.examine != 0"
              :examine-data="examineData"
              :edit-user="editUser"
            ></process-from>
          </div>
        </el-scrollbar>
      </div>
      <div class="ex-content" v-show="isReimburseForm && ddTab === 'history'">
        <el-scrollbar style="height: 100%">
          <div class="ex-content-con" v-loading="historyLoading">
            <div v-if="!historyLoading && historyList.length === 0" class="dd-history-empty">暂无历史申请</div>
            <div v-for="item in historyList" :key="item.id" class="dd-history-card">
              <div class="dd-history-top">
                <span class="dd-history-title">{{ historyTitle(item) }}</span>
                <el-tag size="mini" effect="plain" :type="historyTagType(item)">{{ historyStatusText(item) }}</el-tag>
              </div>
              <div v-if="historyBrief(item)" class="dd-history-brief">{{ historyBrief(item) }}</div>
              <div class="dd-history-time">{{ item.created_at || '--' }}</div>
              <button type="button" class="dd-history-reapply" @click="reapplyHistory(item)">再次提交</button>
            </div>
          </div>
        </el-scrollbar>
      </div>
      <div v-if="isReimburseForm && ddTab === 'submit'" class="dd-footer-bar fix btn-shadow">
        <button type="button" class="dd-draft-btn" @click="saveDraft">
          <i class="el-icon-receiving"></i>
          <span>存草稿</span>
        </button>
        <button type="button" class="dd-submit-btn" :disabled="loading" @click="handleConfirm('ruleForm')">
          {{ loading ? '提交中...' : '提 交' }}
        </button>
      </div>
      <div v-else-if="ddTab === 'submit'" class="button from-foot-btn fix btn-shadow">
        <el-button size="small" @click="close">{{ $t('public.cancel') }}</el-button>
        <el-button size="small" type="primary" :loading="loading" @click="handleConfirm('ruleForm')">
          {{ $t('public.ok') }}
        </el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import request from '@/api/request'
import {
  approveApplyEditApi,
  approveApplyFormApi,
  approveApplyListApi,
  approveApplyApi,
  attendanceAbnormalDateApi,
  attendanceAbnormalRecordApi,
  approveHolidayTypeSelectApi
} from '@/api/business'

export default {
  components: {
    processFrom: () => import('./process')
  },
  props: {
    parameterData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    typeNum: {
      type: Number,
      default: 1
    }
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      fapi: null,
      abnormal: '', //异常日期
      record: '', //异常记录
      holiday: '', //假期类型
      abnormal_id: '', //异常日期id
      record_id: 0, //异常记录id
      holiday_id: 0,
      abnormal_label: '',
      record_label: '',
      calculateType: '',
      option: {
        form: {
          labelWidth: '120px'
        },
        submitBtn: false,
        global: {
          frame: {
            props: {
              closeBtn: false,
              okBtn: false,
              onLoad: (e) => {
                e.fApi = this.$refs.fc.$f
              }
            }
          }
        }
      }, // 表单配置
      rules: [],
      approveId: 0,
      configData: null,
      formData: {},
      isRequest: true,
      examineData: {},
      id: 0,
      isEdit: false,
      loading: false,
      editUser: false, // 审批人添加按钮
      approverDelete: true,
      copyerDelete: true,
      tryDateOptions: null, // 异常时间
      tryYiChangOptions: null, // 异常记录
      holidayTypeOptions: null,
      windowWidth: typeof window !== 'undefined' ? window.innerWidth : 1200,
      ddTab: 'submit',
      historyLoading: false,
      historyList: [],
      draftHint: false,
      isReimburseForm: false,
    }
  },
  computed: {
    drawerSize() {
      return this.windowWidth < 768 ? '100%' : '760px'
    }
  },
  created() {},
  mounted() {
    this.updateViewport = () => {
      this.windowWidth = window.innerWidth
      if (this.option && this.option.form) {
        this.option.form.labelPosition = this.windowWidth < 768 ? 'top' : 'right'
      }
    }
    this.updateViewport()
    window.addEventListener('resize', this.updateViewport)

    if (this.typeNum == 1) {
      this.holidayType()
      this.attendanceAbnormalDate()
    }
  },
  beforeDestroy() {
    if (this.updateViewport) {
      window.removeEventListener('resize', this.updateViewport)
    }
  },
  watch: {},
  methods: {
    handleClose() {
      this.id = 0
      this.drawer = false
    },
    close() {
      this.drawer = false
      this.id = 0
    },
    openBox(command, val, type) {
      this.updateViewport && this.updateViewport()
      this.configData = command
      this.id = command.id ? command.id : command
      this.approveId = this.configData.approve_id ? this.configData.approve_id : this.configData.id
      if (!this.isEdit) {
        this.approveApply(this.id, val, type)
      } else {
        // 编辑
        this.approveApply(command.approve_id)
      }
    },
    // 获取表单配置
    approveApply(id, val, type) {
      approveApplyFormApi(id, this.parameterData).then((res) => {
        this.configData = res.data.info
        res.data.forms.forEach((item) => {
          if ((item.type === 'moneyFrom' || item.type === 'inputNumber') && item.props) {
            if (item.props.max) {
              item.props.max = Number(item.props.max)
            }
            if (item.props.min) {
              item.props.min = Number(item.props.min)
            }
            if (item.props.precision) {
              item.props.precision = Number(item.props.precision)
            }
          }
          if (item.children) {
            item.children.forEach((it) => {
              if (it.title == '异常日期') {
                this.abnormal = it.field
              }
              if (it.title == '异常记录') {
                this.record = it.field
              }
            })
          }
        })
        this.rules = []
        res.data.forms.map((item) => {
          if (item.children && item.type !== 'span') {
            item.children.forEach((per) => {
              if (per.calculateType) {
                per.calculateType = item.type
                this.calculateType = item.type
                if (per.title === '异常日期' && per.calculateType === 'refillFrom') {
                  per.options = this.tryDateOptions
                }
                if (per.title === '假期类型' && per.calculateType === 'leaveFrom') {
                  per.options = this.holidayTypeOptions
                }

                this.rules.push(per)
              } else {
                this.rules.push(per)
              }
            })
          } else {
            item.calculateType = item.type

            this.rules.push(item)
          }
        })
        this.markReimburseFields()
        if (!this.isEdit && this.isReimburseForm) {
          var selfRestore = this
          this.$nextTick(function () {
            setTimeout(function () {
              selfRestore.restoreDraft()
              selfRestore.hideNativeExpenseFields()
            }, 700)
          })
        }

        if (!this.isEdit) {
          this.drawer = true
          this.$nextTick(() => {
            const data = this.handleFromObj()
            if (this.configData.examine == 0) return
            this.approveApplyList(this.approveId, data)
          })
        } else {
          if (this.configData.examine == 0) return false
          this.approveApplyEdit(this.id, { types: 0 })
        }
      })
    },
    // 获取人员配置表单
    approveApplyList(id, data) {
      approveApplyListApi(this.approveId || this.id, data).then((res) => {
        this.examineData = res.data
        var rules = res.data.rules
        if (rules && rules.edit) {
          var rulesEdit = rules.edit + ''
          this.approverDelete = rulesEdit.includes('1')
          this.copyerDelete = rulesEdit.includes('2')
        }

        if (rules && rules.edit && rulesEdit.length > 0) {
          // 是否可删除审批人与抄送人
          if (this.examineData.list.length > 0) {
            this.examineData.list.map((value) => {
              if (value.types == 1) {
                // 判断审批人是否为指定审批人
                if (rules.abnormal > 0 && (value.settype === 2 || value.settype === 7) && value.users.length <= 0) {
                  value.users.push(rules)
                }
                if (rulesEdit.includes('1')) {
                  value.users.map((val) => {
                    val.isDelete = true
                  })
                } else {
                  this.editUser = true
                }
              }
              if (value.types == 2 && rulesEdit.includes('2')) {
                value.users.map((val) => {
                  val.isDelete = true
                })
              }
            })
          }
        } else {
          this.editUser = true
        }
      })
    },
    holidayType() {
      approveHolidayTypeSelectApi().then((res) => {
        this.holidayTypeOptions = res.data
      })
    },
    handleChange(e) {
      // V1.4动态显示隐藏申请开票的邮寄字段
      let expressList = ['liaisonMan', 'telephone', 'mailingAddress']
      let timeType
      const data = this.handleFromObj()
      this.rules.forEach((it) => {
        if (it.field === e && it.title == '假期类型' && this.holidayTypeOptions) {
          this.holidayTypeOptions.map((item) => {
            if (item.value == data[e]) {
              timeType = item.duration_type
            }
          })
        }
      })

      this.rules.forEach((item) => {
        if (data[e] == 'express') {
          if (expressList.includes(item.symbol)) {
            item.hidden = false
          }
          if (item.symbol == 'invoicingEmail') {
            item.hidden = true
          }
        } else if (data[e] == 'mail') {
          if (expressList.includes(item.symbol)) {
            item.hidden = true
          }
          if (item.symbol == 'invoicingEmail') {
            item.hidden = false
          }
        }

        if (this.fapi.rule[9] && this.fapi.rule[9].value == 1) {
          if (item.symbol == 'dutyParagraph') {
            item.hidden = true
            item.effect.required = true
          }
        } else {
          if (item.symbol == 'dutyParagraph') {
            item.hidden = false
            item.effect.required = true
          }
        }
        if (!!JSON.stringify(timeType) && item.type == 'timeFrom') {
          item.props.timeType = timeType == 0 ? 'day' : 'time'
        }
      })
      if (data[e] && data[e].timeType == 'time') {
        const dateStart = data[e].dateStart
        const dateEnd = data[e].dateEnd

        // 将日期时间字符串转换为 Date 对象
        const startDate = new Date(dateStart)
        const endDate = new Date(dateEnd)

        // 计算时间差
        const timeDifference = endDate - startDate

        const millisecondsInHour = 60 * 60 * 1000
        const hours = timeDifference / millisecondsInHour
        data[e].duration = hours ? hours.toFixed(2) : ''
      }

      if (this.tryDateOptions) {
        this.tryDateOptions.map((item) => {
          if (item.value == data[e]) {
            this.abnormal_label = item.label
          }
        })
      }
      if (this.tryYiChangOptions) {
        this.tryYiChangOptions.map((item) => {
          if (item.value == data[e]) {
            this.record_label = item.label
          }
        })
      }

      let label
      for (let i in data) {
        if (data[i] == data[e]) {
          label = i
        }
        if (i == this.abnormal) {
          this.abnormal_id = data[i]
        }
        if (i == this.record) {
          this.record_id = data[i]
        }
        if (i == this.holiday) {
          this.holiday_id = data[i]
        }
      }

      // 赋值异常记录
      if (label == this.abnormal) {
        this.attendanceAbnormalRecord(data[e])
      }
      if (!this.isEdit) {
        if (this.configData.examine == 0) return
        this.approveApplyList(this.id, data)
      } else {
        if (this.configData.examine == 0) return
        this.approveApplyList(this.configData.approve_id, data)
      }
    },
    // 递归回显审批内容
    calculateLevel(arr, option) {
      arr.forEach((value) => {
        if (option[value.field]) {
          value.value = option[value.field]
        } else {
          this.calculateLevel(value.children, option)
        }
      })
      return arr
    },

    // 编辑
    approveApplyEdit(id, data) {
      approveApplyEditApi(this.id, data).then((res) => {
        this.drawer = true
        var option = res.data
        this.calculateLevel(this.rules, option)

        this.rules.forEach((item) => {
          if (item.title == '异常日期') {
            this.attendanceAbnormalDate()
            if (item.value.abnormal_id) {
              this.abnormal_id = item.value.abnormal_id.value
              this.abnormal_label = item.value.abnormal_id.label
            }
            item.value = this.abnormal_label

            if (!this.abnormal_id) return false
            this.attendanceAbnormalRecord(this.abnormal_id)
          }
        })

        const data = this.handleFromObj()

        this.approveApplyList(this.approveId, data)
      })
    },
    handleFromData() {
      const attr = []
      this.fapi.rule.map((item) => {
        const condition = {}
        condition[item.field] = item.value ? item.value : ''
        attr.push(condition)
      })
      return attr
    },
    handleFromObj() {
      const obj = {}
      var data = this.fapi ? this.fapi.rule : ''
      for (let i = 0; i < data.length; i++) {
        if (Object.prototype.toString.call(data[i].value) === '[object Object]') {
          data[i].value.type = data[i].calculateType
        }
        const keys = data[i]['field']
        obj[keys] = data[i].value ? data[i].value : ''
      }

      return obj
    },
    // 提交
    handleConfirm() {
      const id = !this.isEdit ? this.id : this.approveId
      this.approveApplySave(id)
    },
    // 保存
    approveApplySave(id) {
      const FromData = {
        title: this.$t('hr.addposition'),
        method: 'post',
        action: `approve/apply/save/${id}`
      }
      this.$refs.fc.$f.submit((formData) => {
        Object.keys(formData).forEach((k) => {
          if (formData[k] === undefined) formData[k] = ''
        })

        const processInfo = this.examineData.list
        if (processInfo && processInfo.length && this.configData.examine != 0) {
          let len = 0
          for (let i = 0; i < processInfo.length; i++) {
            const value = processInfo[i]
            if (!this.approverDelete && value.types == 1 && value.users.length <= 0) {
              this.$message.error('自选节点不能为空')
              return
            }
            if (!this.copyerDelete && value.types == 2 && value.users.length <= 0) {
              this.$message.error('自选节点不能为空')
              return
            }
            if (
              this.approverDelete &&
              value.types == 1 &&
              (value.settype == 4 || value.settype == 1) &&
              value.users.length <= 0
            ) {
              this.$message.error('自选节点不能为空')
              return
            }
            if (value.users.length <= 0) {
              len++
            }
          }
          if (len === processInfo.length) {
            this.$message.error('自选节点不能为空')
            return
          }
          processInfo.forEach((value, index) => {
            if (value.users.length <= 0) {
              processInfo.splice(index, 1)
            }
          })
        }
        const data = {
          formInfo: formData,
          processInfo: processInfo
        }

        if (this.isEdit) {
          data.apply_id = this.id
        }
        this.loading = true
        request[FromData.method.toLowerCase()](FromData.action, data)
          .then((res) => {
            this.loading = false
            if (this.isReimburseForm) this.clearDraftStorage()
            this.close()
            this.$emit('scheduleRecord')
            this.$emit('isOk')
          })
          .catch((err) => {
            this.loading = false
          })
      })
    },
    // 调用接口赋值 异常日期
    attendanceAbnormalDate() {
      attendanceAbnormalDateApi().then((res) => {
        if (res.status == 200) {
          this.tryDateOptions = res.data
          this.tryDateOptions = this.tryDateOptions.map((it) => {
            it.label = it.date
            it.value = it.id
            return it
          })
        }
      })
    },
    // 异常记录
    attendanceAbnormalRecord(id) {
      attendanceAbnormalRecordApi(id).then((res) => {
        if (res.status == 200) {
          this.tryYiChangOptions = res.data
          this.tryYiChangOptions = this.tryYiChangOptions.map((it) => {
            it.value = it.shift_number
            it.label = it.title + ' : ' + it.time
            return it
          })
          // 赋值
          for (let i = 0; i < this.rules.length; i++) {
            if (this.rules[i].title === '异常记录' && this.rules[i].symbol === 'attendanceExceptionRecord') {
              this.rules[i].options = this.tryYiChangOptions
              break
            }
          }
        }
      })
    },
    markReimburseFields() {
      var hasInvoice = false
      ;(this.rules || []).forEach(function (item) {
        if (item && (item.type === 'invoiceSelect' || item.type === 'invoice')) hasInvoice = true
      })
      this.isReimburseForm = hasInvoice
    },
    hideNativeExpenseFields() {
      if (!this.isReimburseForm) return
      var forms = document.querySelectorAll('.edit-examine-form')
      var form = forms.length ? forms[forms.length - 1] : null
      if (!form) return
      var items = form.querySelectorAll('.el-form-item')
      for (var i = 0; i < items.length; i++) {
        if (items[i].querySelector('.invoice-select-component')) continue
        var label = items[i].querySelector('.el-form-item__label')
        var text = label ? label.textContent || '' : ''
        if (/报销类型|费用类型|费用类别|报销总金额|报销金额|报销事由|事由|备注|费用明细/.test(text)) {
          items[i].style.display = 'none'
        }
      }
    },
    draftKey() {
      return 'oa_examine_draft_' + this.approveId
    },
    saveDraft() {
      var form = {}
      try {
        form = this.fapi && this.fapi.formData ? this.fapi.formData() : this.handleFromObj()
      } catch (e) {
        form = this.handleFromObj()
      }
      localStorage.setItem(this.draftKey(), JSON.stringify({ form: form, savedAt: Date.now() }))
      this.$message.success('草稿已保存，下次打开将自动恢复')
    },
    restoreDraft() {
      var raw = localStorage.getItem(this.draftKey())
      if (!raw || !this.fapi || !this.fapi.setValue) return
      try {
        var data = JSON.parse(raw)
        if (data && data.form) {
          this.fapi.setValue(data.form)
          this.draftHint = true
        }
      } catch (e) {}
    },
    clearDraftStorage() {
      localStorage.removeItem(this.draftKey())
      this.draftHint = false
    },
    clearDraftForm() {
      this.clearDraftStorage()
      if (this.fapi && this.fapi.resetFields) this.fapi.resetFields()
      this.$message.success('已清空草稿，请重新填写')
    },
    switchHistory() {
      this.ddTab = 'history'
      this.loadHistory()
    },
    loadHistory() {
      var self = this
      this.historyLoading = true
      approveApplyApi({ page: 1, limit: 20, types: 0 })
        .then(function (res) {
          var list = (res.data && res.data.list) || []
          var id = String(self.approveId || '')
          var filtered = list.filter(function (item) {
            if (!id) return true
            var aid = item.approve_id || (item.approve && item.approve.id) || ''
            return String(aid) === id
          })
          self.historyList = filtered.length ? filtered : list
        })
        .catch(function () {
          self.historyList = []
        })
        .finally(function () {
          self.historyLoading = false
        })
    },
    historyTitle(item) {
      if (!item) return '报销申请'
      if (item.approve && item.approve.name) return item.approve.name
      return item.name || '报销申请'
    },
    historyStatusText(item) {
      if (!item) return ''
      if (item.status === -1) return '已撤销'
      if (item.status === 1 && item.recall) return '撤销中'
      if (item.status === 0) return '审核中'
      if (item.status === 1) return '已通过'
      if (item.status === 2) return '已拒绝'
      return '处理中'
    },
    historyTagType(item) {
      if (!item) return 'info'
      if (item.status === 0) return 'warning'
      if (item.status === 2) return 'danger'
      return 'info'
    },
    historyBrief(item) {
      var content = item && item.content
      if (!Array.isArray(content)) return ''
      var parts = []
      content.forEach(function (row) {
        if (!row || row.type === 'uploadFrom' || row.type === 'rich_text') return
        var list = null
        if (Array.isArray(row.value) && row.value.length && row.value[0] && (row.value[0].invoice_number || row.value[0].total_amount !== undefined)) {
          list = row.value
        }
        if (row.type === 'invoiceSelect' || row.type === 'invoice' || list) {
          var invoices = list || []
          var total = 0
          invoices.forEach(function (inv) {
            total += parseFloat(inv.total_amount) || 0
          })
          if (invoices.length) parts.push('发票 ' + invoices.length + ' 张 ¥' + total.toFixed(2))
          return
        }
        if (row.label && /金额/.test(row.label) && row.value !== undefined && row.value !== '') {
          parts.push(row.label + ' ¥' + row.value)
        }
      })
      return parts.join(' · ')
    },
    reapplyHistory(item) {
      var content = item && item.content
      if (!Array.isArray(content) || !this.fapi || !this.fapi.setValue) {
        this.$message.warning('这条记录暂时无法引用')
        return
      }
      var self = this
      this.ddTab = 'submit'
      this.$nextTick(function () {
        content.forEach(function (row) {
          if (!row) return
          var field = row.field
          if (!field && self.fapi.rule) {
            for (var i = 0; i < self.fapi.rule.length; i++) {
              if (self.fapi.rule[i].title === row.label) {
                field = self.fapi.rule[i].field
                break
              }
            }
          }
          if (field) self.fapi.setValue(field, row.value)
        })
        self.hideNativeExpenseFields()
        self.$message.success('已引用该申请，请核对后提交')
      })
    },
  }
}
</script>

<style scoped lang="scss">
.headerBox {
  .portrait {
    width: 36px;
    height: 36px;
    border-radius: 5px;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    i {
      font-size: 20px;
      color: #fff;
    }
  }
  .nameBox {
    span {
      display: block;
    }
    .st1 {
      font-size: 15px;
      font-weight: 600;
      color: rgba(0, 0, 0, 0.85);
    }
    .st2 {
      font-size: 13px;
      color: #999999;
    }
  }
}
.iconBox {
  width: 46px;
  height: 46px;
  background: #1890ff;
  border-radius: 6px;
  color: #fff;
  font-size: 26px;
  line-height: 46px;
  text-align: center;
}

.iBox {
  background: #cccccc;
  i {
    color: #fff;
  }
}

.titleBox {
  color: #000000;
  font-size: 16px;
  line-height: 46px;
  font-weight: 600;
}

/deep/ .el-drawer__body {
  padding-bottom: 76px;
}
.ex-content {
  padding: 20px 0 20px 20px;
  height: 100%;
  .ex-content-con {
    padding-right: 24px;
  }
  /deep/.select-item {
    margin-top: 0 !important;
  }
  /deep/ .el-divider--horizontal {
    margin-top: 0;
    margin-bottom: 30px;
  }
  /deep/ .el-scrollbar__wrap {
    overflow-x: hidden;
  }
  .shu {
    width: 3px;
    height: 16px;
    background: #1890ff;
    display: inline-block;
  }
  .title {
    font-size: 14px;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.85);
  }
}
/deep/ .el-input--medium {
  font-size: 12px;
}
/deep/.el-cascader--medium {
  width: 100%;
}
/deep/.el-form-item--mini.el-form-item {
  margin-bottom: 20px;
}

/deep/.el-form-item--mini.el-form-item:last-child {
  margin-bottom: 0px;
}
/deep/ .el-form-item {
  margin-bottom: 20px;
}

/deep/ .edit-examine-form {
  .invoice-select-form-item {
    .el-form-item__label {
      float: none !important;
      display: block !important;
      text-align: left !important;
      width: 100% !important;
      padding-bottom: 6px !important;
      font-weight: 500;
      color: #303133;
    }
    .el-form-item__content {
      margin-left: 0 !important;
      width: 100% !important;
    }
  }

  @media (max-width: 767px) {
    .el-form-item {
      margin-bottom: 14px;
      .el-form-item__label {
        float: none !important;
        display: block !important;
        text-align: left !important;
        width: 100% !important;
        padding-bottom: 4px !important;
      }
      .el-form-item__content {
        margin-left: 0 !important;
        width: 100% !important;
      }
    }
  }
}

@media (max-width: 767px) {
  /deep/ .el-drawer__header {
    height: auto !important;
    padding: 12px 14px;
    margin-bottom: 0;
  }

  .headerBox {
    flex-wrap: wrap;
    gap: 8px;
    .portrait {
      width: 32px;
      height: 32px;
      i {
        font-size: 16px;
      }
    }
    .nameBox {
      .st1 {
        font-size: 14px;
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }
  }

  .ex-content {
    padding: 12px 0 20px 12px;
    .ex-content-con {
      padding-right: 12px;
    }
  }

  .from-foot-btn {
    padding: 10px 14px;
    display: flex;
    justify-content: flex-end;
    .el-button {
      flex: 1;
    }
  }
}

.dd-tabs-nav {
  display: flex;
  background: #fff;
  border-bottom: 1px solid #f0f1f5;
  padding: 0 16px;
}
.dd-tab {
  position: relative;
  flex: 1;
  text-align: center;
  height: 44px;
  line-height: 44px;
  font-size: 15px;
  color: #646a73;
  cursor: pointer;
}
.dd-tab.active {
  color: #1677ff;
  font-weight: 700;
}
.dd-tab.active:after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: 4px;
  width: 24px;
  height: 3px;
  margin-left: -12px;
  border-radius: 2px;
  background: #1677ff;
}
.dd-draft-hint {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f4f6fa;
  border-radius: 8px;
  padding: 8px 12px;
  margin-bottom: 12px;
  color: #646a73;
  font-size: 13px;
}
.dd-draft-clear {
  border: none;
  background: transparent;
  color: #1677ff;
  cursor: pointer;
}
.dd-footer-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px 16px;
  box-sizing: border-box;
}
.dd-draft-btn {
  width: 64px;
  border: none;
  background: transparent;
  color: #646a73;
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 12px;
  cursor: pointer;
}
.dd-draft-btn i {
  font-size: 18px;
  margin-bottom: 2px;
}
.dd-submit-btn {
  flex: 1;
  height: 44px;
  border: none;
  border-radius: 22px;
  background: linear-gradient(90deg, #1677ff 0%, #0052d9 100%);
  font-size: 16px;
  font-weight: 500;
  color: #fff;
  cursor: pointer;
}
.dd-submit-btn:disabled {
  opacity: 0.7;
}
.dd-history-card {
  background: #fff;
  border: 1px solid #f0f1f5;
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 10px;
}
.dd-history-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.dd-history-title {
  font-weight: 600;
  color: #1f2329;
}
.dd-history-brief,
.dd-history-time {
  margin-top: 6px;
  font-size: 12px;
  color: #8f959e;
  word-break: break-word;
}
.dd-history-reapply {
  margin-top: 8px;
  border: none;
  background: transparent;
  color: #1677ff;
  padding: 0;
  cursor: pointer;
}
.dd-history-empty {
  text-align: center;
  color: #8f959e;
  padding: 40px 0;
}
/deep/ .el-form-item.dd-native-field {
  display: none !important;
}
</style>

