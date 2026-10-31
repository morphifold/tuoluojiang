<template>
  <div class="invoice-select-component">
    <!-- 头部操作区（只读模式隐藏） -->
    <div v-if="!disabled && !isView" class="action-bar mb10 acea-row row-between row-middle">
      <div class="acea-row row-middle">
        <el-button
          type="primary"
          size="small"
          icon="el-icon-tickets"
          @click="openSelectDialog"
        >
          从我的发票夹选择
        </el-button>

        <el-upload
          class="upload-inline ml10"
          action="#"
          :show-file-list="false"
          :auto-upload="false"
          :on-change="handleDirectUpload"
          accept=".pdf,.png,.jpg,.jpeg"
        >
          <el-button
            size="small"
            icon="el-icon-upload2"
            :loading="uploading"
          >
            现场上传发票原件
          </el-button>
        </el-upload>

        <span class="upload-tip ml10 text-muted">
          支持 PDF 电子发票或发票图片（自动解析提取并入账）
        </span>
      </div>

      <div class="summary-badge" v-if="selectedList.length > 0">
        已选 <span class="highlight">{{ selectedList.length }}</span> 张发票
      </div>
    </div>

    <!-- 发票明细表格 -->
    <el-table
      :data="selectedList"
      border
      size="small"
      style="width: 100%"
      empty-text="暂未添加报销发票"
      class="invoice-table"
    >
      <el-table-column type="index" label="序号" width="50" align="center" />

      <el-table-column label="发票类型" min-width="120" show-overflow-tooltip>
        <template slot-scope="{ row }">
          <el-tag size="mini" :type="getInvoiceTagType(row.invoice_type)">
            {{ getInvoiceTypeName(row.invoice_type) }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="invoice_number" label="发票号码" min-width="130" show-overflow-tooltip>
        <template slot-scope="{ row }">
          <span class="font-mono font-bold">{{ row.invoice_number || '--' }}</span>
        </template>
      </el-table-column>

      <el-table-column prop="invoice_code" label="发票代码" min-width="120" show-overflow-tooltip>
        <template slot-scope="{ row }">
          <span class="font-mono">{{ row.invoice_code || '-' }}</span>
        </template>
      </el-table-column>

      <el-table-column prop="invoice_date" label="开票日期" width="110" align="center">
        <template slot-scope="{ row }">
          {{ row.invoice_date || '--' }}
        </template>
      </el-table-column>

      <el-table-column prop="seller_name" label="销售方名称" min-width="160" show-overflow-tooltip>
        <template slot-scope="{ row }">
          {{ row.seller_name || '--' }}
        </template>
      </el-table-column>

      <el-table-column prop="amount" label="金额(不含税)" width="110" align="right">
        <template slot-scope="{ row }">
          ¥{{ formatMoney(row.amount) }}
        </template>
      </el-table-column>

      <el-table-column prop="tax_amount" label="税额" width="90" align="right">
        <template slot-scope="{ row }">
          ¥{{ formatMoney(row.tax_amount) }}
        </template>
      </el-table-column>

      <el-table-column prop="total_amount" label="价税合计(元)" width="120" align="right">
        <template slot-scope="{ row }">
          <span class="total-amount-text">¥{{ formatMoney(row.total_amount) }}</span>
        </template>
      </el-table-column>

      <el-table-column label="原件" width="80" align="center">
        <template slot-scope="{ row }">
          <el-button
            v-if="row.file_path"
            type="text"
            size="small"
            icon="el-icon-view"
            @click="previewInvoice(row)"
          >
            查看
          </el-button>
          <span v-else class="text-muted">无</span>
        </template>
      </el-table-column>

      <el-table-column
        v-if="!disabled && !isView"
        label="操作"
        width="70"
        align="center"
      >
        <template slot-scope="{ $index }">
          <el-button
            type="text"
            size="small"
            style="color: #F56C6C;"
            icon="el-icon-delete"
            @click="removeInvoice($index)"
          >
            移除
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 底部统计栏 -->
    <div v-if="selectedList.length > 0" class="summary-footer mt10 acea-row row-between row-middle">
      <div class="summary-details">
        共计 <strong class="color-primary">{{ selectedList.length }}</strong> 张发票，
        不含税金额：<strong>¥{{ formatMoney(totalPureAmount) }}</strong>，
        税额：<strong>¥{{ formatMoney(totalTaxAmount) }}</strong>
      </div>
      <div class="total-sum">
        报销发票总金额：<span class="sum-number">¥{{ formatMoney(totalSumAmount) }}</span>
      </div>
    </div>

    <!-- 从发票夹选择弹窗 -->
    <el-dialog
      title="从我的发票夹选择发票"
      :visible.sync="selectDialogVisible"
      width="900px"
      append-to-body
      :close-on-click-modal="false"
      class="invoice-select-dialog"
    >
      <!-- 搜索筛选 -->
      <div class="dialog-filter mb15 acea-row row-middle">
        <el-input
          v-model="filterKeyword"
          placeholder="搜索发票号码/代码/销方"
          size="small"
          clearable
          style="width: 220px;"
          prefix-icon="el-icon-search"
          @keyup.enter.native="loadMyInvoiceList"
        />

        <el-select
          v-model="filterType"
          placeholder="发票类型"
          size="small"
          clearable
          class="ml10"
          style="width: 150px;"
          @change="loadMyInvoiceList"
        >
          <el-option label="全部类型" value="" />
          <el-option label="增值税专用发票" value="special" />
          <el-option label="增值税普通发票" value="general" />
          <el-option label="电子普通发票" value="electronic" />
          <el-option label="全电专用发票" value="digital_special" />
          <el-option label="全电普通发票" value="digital_general" />
          <el-option label="通行费发票" value="toll" />
          <el-option label="定额发票" value="quota" />
        </el-select>

        <el-date-picker
          v-model="filterDateRange"
          type="daterange"
          size="small"
          value-format="yyyy-MM-dd"
          range-separator="至"
          start-placeholder="开票开始"
          end-placeholder="开票结束"
          class="ml10"
          style="width: 250px;"
          @change="loadMyInvoiceList"
        />

        <el-button
          type="primary"
          size="small"
          icon="el-icon-search"
          class="ml10"
          @click="loadMyInvoiceList"
        >
          查询
        </el-button>

        <el-button
          size="small"
          icon="el-icon-refresh"
          @click="resetFilter"
        >
          重置
        </el-button>
      </div>

      <!-- 可选发票列表 -->
      <el-table
        ref="invoiceSelectTable"
        v-loading="loadingMyInvoices"
        :data="myInvoiceList"
        border
        size="small"
        max-height="400"
        @selection-change="handleDialogSelectionChange"
      >
        <el-table-column type="selection" width="50" align="center" :selectable="canSelectInvoice" />

        <el-table-column label="发票类型" width="130">
          <template slot-scope="{ row }">
            <el-tag size="mini" :type="getInvoiceTagType(row.invoice_type)">
              {{ getInvoiceTypeName(row.invoice_type) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="invoice_number" label="发票号码" min-width="130">
          <template slot-scope="{ row }">
            <span class="font-mono font-bold">{{ row.invoice_number }}</span>
          </template>
        </el-table-column>

        <el-table-column prop="invoice_code" label="发票代码" min-width="110">
          <template slot-scope="{ row }">
            <span class="font-mono">{{ row.invoice_code || '-' }}</span>
          </template>
        </el-table-column>

        <el-table-column prop="invoice_date" label="开票日期" width="110" align="center" />

        <el-table-column prop="seller_name" label="销售方名称" min-width="150" show-overflow-tooltip />

        <el-table-column prop="total_amount" label="价税合计(元)" width="120" align="right">
          <template slot-scope="{ row }">
            <span class="total-amount-text">¥{{ formatMoney(row.total_amount) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="原件" width="70" align="center">
          <template slot-scope="{ row }">
            <el-button
              v-if="row.file_path"
              type="text"
              size="small"
              @click.stop="previewInvoice(row)"
            >
              预览
            </el-button>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
      </el-table>

      <!-- 弹窗底部操作 -->
      <div slot="footer" class="acea-row row-between row-middle">
        <div class="selected-summary">
          当前选中 <strong class="color-primary">{{ dialogSelection.length }}</strong> 张发票，
          合计金额：<strong class="color-primary">¥{{ formatMoney(dialogSelectedAmount) }}</strong>
        </div>
        <div>
          <el-button size="small" @click="selectDialogVisible = false">取消</el-button>
          <el-button
            size="small"
            type="primary"
            :disabled="dialogSelection.length === 0"
            @click="confirmDialogSelection"
          >
            确认添加 ({{ dialogSelection.length }})
          </el-button>
        </div>
      </div>
    </el-dialog>

    <!-- 原件预览弹窗 -->
    <el-dialog
      :title="`发票原件预览 - ${currentPreview.invoice_number || ''}`"
      :visible.sync="previewDialogVisible"
      width="820px"
      append-to-body
      top="5vh"
      class="invoice-preview-dialog"
    >
      <div class="preview-container">
        <!-- PDF 预览 -->
        <div v-if="isPreviewPdf" class="pdf-box">
          <iframe
            :src="previewUrl"
            width="100%"
            height="580px"
            frameborder="0"
          ></iframe>
        </div>

        <!-- 图片预览 -->
        <div v-else class="img-box acea-row row-center row-middle">
          <img :src="previewUrl" alt="发票原件" class="preview-img" />
        </div>
      </div>
      <div slot="footer" class="acea-row row-between row-middle">
        <span class="text-muted font-12">{{ currentPreview.file_name || '发票文件' }}</span>
        <div>
          <el-button size="small" icon="el-icon-download" @click="downloadPreviewFile">
            下载原件
          </el-button>
          <el-button size="small" type="primary" @click="openExternalPreview">
            新窗口查看
          </el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import {
  getExpenseInvoiceMyListApi,
  parseExpenseInvoiceApi,
  checkExpenseInvoiceDuplicateApi,
  addExpenseInvoiceMyApi
} from '@/api/expenseInvoice'
import { processResourceUrl } from '@/utils/resourceUtil'

export default {
  name: 'InvoiceSelect',
  props: {
    // 支持 v-model
    value: {
      type: [Array, String],
      default: () => []
    },
    // 是否只读/禁用
    disabled: {
      type: Boolean,
      default: false
    },
    // 是否预览查看
    isView: {
      type: Boolean,
      default: false
    },
    // form-create 注入配置
    formCreateInject: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      selectedList: [],
      // 发票夹选择弹窗
      selectDialogVisible: false,
      loadingMyInvoices: false,
      myInvoiceList: [],
      dialogSelection: [],
      filterKeyword: '',
      filterType: '',
      filterDateRange: [],
      // 现场直接上传解析
      uploading: false,
      // 预览弹窗
      previewDialogVisible: false,
      currentPreview: {},
      previewUrl: ''
    }
  },
  computed: {
    totalPureAmount() {
      return this.selectedList.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
    },
    totalTaxAmount() {
      return this.selectedList.reduce((sum, item) => sum + (parseFloat(item.tax_amount) || 0), 0)
    },
    totalSumAmount() {
      return this.selectedList.reduce((sum, item) => sum + (parseFloat(item.total_amount) || 0), 0)
    },
    dialogSelectedAmount() {
      return this.dialogSelection.reduce((sum, item) => sum + (parseFloat(item.total_amount) || 0), 0)
    },
    isPreviewPdf() {
      const path = (this.currentPreview.file_path || '').toLowerCase()
      const type = (this.currentPreview.file_type || '').toLowerCase()
      return path.endsWith('.pdf') || type.includes('pdf')
    }
  },
  watch: {
    value: {
      handler(val) {
        this.initValue(val)
      },
      immediate: true,
      deep: true
    }
  },
  methods: {
    initValue(val) {
      if (!val) {
        this.selectedList = []
        return
      }
      let list = []
      if (typeof val === 'string') {
        try {
          list = JSON.parse(val)
        } catch (e) {
          list = []
        }
      } else if (Array.isArray(val)) {
        list = [...val]
      }
      this.selectedList = list
    },

    emitChange() {
      const result = [...this.selectedList]
      this.$emit('input', result)
      this.$emit('change', result)
      this.$emit('total-change', {
        count: this.selectedList.length,
        totalAmount: this.totalSumAmount,
        pureAmount: this.totalPureAmount,
        taxAmount: this.totalTaxAmount
      })

      // 如果通过 form-create 注入，触发 form-create 关联事件
      if (this.formCreateInject && this.formCreateInject.api) {
        const field = this.formCreateInject.rule && this.formCreateInject.rule.field
        if (field) {
          this.formCreateInject.api.setValue(field, result)
        }
        this.syncAmountToForm()
      }

    },

    /**
     * 发票合计金额回写到同一表单的金额字段（moneyFrom 或标题含"金额"的字段）
     * 避免用户选完发票后还要手工再填一次报销金额
     */
    syncAmountToForm() {
      const inject = this.formCreateInject || {}
      const api = inject.api
      if (!api || typeof api.getRule !== 'function' || typeof api.fields !== 'function') return
      const selfField = inject.rule && inject.rule.field
      const target = (api.fields() || []).find((name) => {
        if (name === selfField) return false
        const rule = api.getRule(name) || {}
        return rule.type === 'moneyFrom' || /金额/.test(rule.title || '')
      })
      if (!target) return
      api.setValue(target, Number(this.totalSumAmount.toFixed(2)))
    },

    // 打开选择弹窗
    openSelectDialog() {
      this.selectDialogVisible = true
      this.dialogSelection = []
      this.loadMyInvoiceList()
    },

    // 加载未绑定的员工发票
    async loadMyInvoiceList() {
      this.loadingMyInvoices = true
      try {
        const params = {
          status: 0, // 未绑定
          abnormal_status: 0, // 正常，排除异常发票
          keyword: this.filterKeyword || undefined,
          invoice_type: this.filterType || undefined,
          start_date: this.filterDateRange && this.filterDateRange[0] ? this.filterDateRange[0] : undefined,
          end_date: this.filterDateRange && this.filterDateRange[1] ? this.filterDateRange[1] : undefined,
          page: 1,
          limit: 100
        }
        const res = await getExpenseInvoiceMyListApi(params)
        const list = res.data?.list || res.data || []
        this.myInvoiceList = list
      } catch (err) {
        this.$message.error('获取发票列表失败')
      } finally {
        this.loadingMyInvoices = false
      }
    },

    resetFilter() {
      this.filterKeyword = ''
      this.filterType = ''
      this.filterDateRange = []
      this.loadMyInvoiceList()
    },

    canSelectInvoice(row) {
      // 若已被添加到选中列表中，则不可重复勾选
      return !this.selectedList.some((item) => item.id && item.id === row.id)
    },

    handleDialogSelectionChange(selection) {
      this.dialogSelection = selection
    },

    confirmDialogSelection() {
      if (this.dialogSelection.length === 0) return
      // 追加未重复的项目
      const toAdd = this.dialogSelection.filter(
        (item) => !this.selectedList.some((selected) => selected.id === item.id)
      )
      this.selectedList = [...this.selectedList, ...toAdd]
      this.selectDialogVisible = false
      this.emitChange()
      this.$message.success(`成功添加 ${toAdd.length} 张发票`)
    },

    // 现场直接上传并自动提取发票
    async handleDirectUpload(file) {
      const rawFile = file.raw
      if (!rawFile) return

      const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']
      const isAllowed = allowedTypes.includes(rawFile.type) || rawFile.name.endsWith('.pdf')
      if (!isAllowed) {
        this.$message.error('仅支持上传 PDF 格式电子发票或 JPG/PNG 图片')
        return
      }

      const isLt15M = rawFile.size / 1024 / 1024 < 15
      if (!isLt15M) {
        this.$message.error('文件大小不能超过 15MB')
        return
      }

      this.uploading = true
      const formData = new FormData()
      formData.append('file', rawFile)

      try {
        // 1. 调用解析接口提取发票元数据
        const parseRes = await parseExpenseInvoiceApi(formData)
        const parsed = parseRes.data || {}
        if (parseRes.status !== 200) return

        // 图片或无文本 PDF 只完成原件上传，没有发票号码不能直接加入报销单
        if (!parsed.invoice_number) {
          this.$message.warning(parseRes.message || '原件已上传，请手动补录发票要素后再加入报销单')
          return
        }

        // 2. 查重拦截校验
        if (parsed.invoice_number) {
          const checkRes = await checkExpenseInvoiceDuplicateApi({
            invoice_code: parsed.invoice_code || '',
            invoice_number: parsed.invoice_number
          })
          if (checkRes.data && checkRes.data.is_duplicate) {
            const exist = checkRes.data.exist_invoice || {}
            this.$message.error(
              `该发票(${parsed.invoice_number})在系统中已存在！已被【${exist.user_name || '其他员工'}】于 ${exist.created_at || ''} 录入，禁止重复添加`
            )
            this.uploading = false
            return
          }
        }

        // 3. 入库并加入当前报销明细
        let savedInvoice = parsed
        if (!parsed.id) {
          const saveRes = await addExpenseInvoiceMyApi({
            invoice_type: parsed.invoice_type || 'electronic',
            invoice_code: parsed.invoice_code || '',
            invoice_number: parsed.invoice_number || '',
            amount: parsed.amount || 0,
            tax_amount: parsed.tax_amount || 0,
            total_amount: parsed.total_amount || 0,
            invoice_date: parsed.invoice_date || '',
            seller_name: parsed.seller_name || '',
            buyer_name: parsed.buyer_name || '',
            file_path: parsed.file_path || '',
            file_name: parsed.file_name || rawFile.name,
            file_type: parsed.file_type || (rawFile.name.endsWith('.pdf') ? 'pdf' : 'image')
          })
          savedInvoice = saveRes.data || parsed
        }

        // 4. 追加到当前已选发票明细
        this.selectedList.push(savedInvoice)
        this.emitChange()
        this.$message.success('发票要素已智能识别，并已添加至报销单！')
      } catch (err) {
        this.$message.error(err.message || '发票解析上传失败，请重试')
      } finally {
        this.uploading = false
      }
    },

    removeInvoice(index) {
      this.selectedList.splice(index, 1)
      this.emitChange()
    },

    previewInvoice(row) {
      this.currentPreview = row
      // 后端返回的是相对存储路径，必须转成站点根路径，否则会解析到当前路由目录下
      const path = (row.file_path || '').replace(/^\/+/, '')
      this.previewUrl = path ? processResourceUrl('/' + path) : ''
      this.previewDialogVisible = true
    },

    openExternalPreview() {
      if (this.previewUrl) {
        window.open(this.previewUrl, '_blank')
      }
    },

    downloadPreviewFile() {
      if (!this.previewUrl) return
      const a = document.createElement('a')
      a.href = this.previewUrl
      a.download = this.currentPreview.file_name || '发票原件'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    },

    formatMoney(val) {
      const num = parseFloat(val)
      if (isNaN(num)) return '0.00'
      return num.toFixed(2)
    },

    getInvoiceTypeName(type) {
      const map = {
        special: '增值税专用发票',
        general: '增值税普通发票',
        electronic: '增值税电子普通发票',
        digital_special: '全电专用发票',
        digital_general: '全电普通发票',
        toll: '通行费发票',
        quota: '定额发票'
      }
      return map[type] || type || '普通发票'
    },

    getInvoiceTagType(type) {
      const map = {
        special: 'danger',
        digital_special: 'danger',
        electronic: 'success',
        digital_general: 'warning',
        general: 'info'
      }
      return map[type] || ''
    }
  }
}
</script>

<style lang="scss" scoped>
.invoice-select-component {
  width: 100%;

  .action-bar {
    padding: 8px 0;
  }

  .upload-inline {
    display: inline-block;
  }

  .upload-tip {
    font-size: 12px;
    color: #909399;
  }

  .summary-badge {
    font-size: 13px;
    color: #606266;
    .highlight {
      font-weight: bold;
      color: #1890ff;
    }
  }

  .invoice-table {
    margin-top: 6px;
    font-size: 13px;

    .total-amount-text {
      color: #f5222d;
      font-weight: 600;
      font-family: 'Helvetica Neue', Helvetica, monospace;
    }
    .font-mono {
      font-family: Menlo, Monaco, Consolas, monospace;
    }
  }

  .summary-footer {
    background: #f8faff;
    border: 1px dashed #d9e8ff;
    border-radius: 4px;
    padding: 10px 16px;
    font-size: 13px;

    .summary-details {
      color: #606266;
      strong {
        color: #303133;
      }
    }

    .total-sum {
      font-size: 14px;
      color: #303133;
      .sum-number {
        font-size: 18px;
        font-weight: bold;
        color: #f5222d;
      }
    }
  }
}

.preview-container {
  min-height: 400px;
  max-height: 650px;
  overflow: auto;

  .pdf-box {
    width: 100%;
    height: 580px;
  }

  .img-box {
    text-align: center;
    padding: 10px;
    .preview-img {
      max-width: 100%;
      max-height: 580px;
      object-fit: contain;
      box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
    }
  }
}

.selected-summary {
  font-size: 13px;
  color: #606266;
}

.color-primary {
  color: #1890ff;
}
</style>
