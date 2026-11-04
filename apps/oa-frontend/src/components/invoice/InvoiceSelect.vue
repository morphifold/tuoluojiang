<template>
  <div class="invoice-select-component">
    <!-- 头部操作区（只读模式隐藏） -->
    <div v-if="!disabled && !isView" class="action-bar mb10">
      <div class="action-buttons">
        <el-button
          type="primary"
          size="small"
          icon="el-icon-tickets"
          @click="openSelectDialog"
        >
          从我的发票夹选择
        </el-button>

        <el-upload
          class="upload-inline"
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
      </div>

      <div class="summary-badge" v-if="selectedList.length > 0">
        已选 <span class="highlight">{{ selectedList.length }}</span> 张发票
      </div>

      <div class="upload-tip text-muted">
        支持 PDF 电子发票或发票图片（自动解析提取并入账）
      </div>
    </div>

    <!-- 发票明细紧凑卡片流（彻底取代原 11 列 1160px 超宽表格，实现 375px~760px 自适应零截断） -->
    <div class="invoice-card-list">
      <!-- 空状态 -->
      <div v-if="selectedList.length === 0" class="empty-invoice-box">
        <i class="el-icon-document-remove empty-icon"></i>
        <span class="empty-text">暂未添加报销发票</span>
      </div>

      <!-- 发票卡片 -->
      <div
        v-for="(row, index) in selectedList"
        :key="row.id || (row.invoice_number + '_' + index)"
        class="invoice-item-card"
      >
        <!-- 卡片第一行：头部栏 -->
        <div class="card-head">
          <div class="card-head-left">
            <span class="inv-badge">#{{ index + 1 }}</span>
            <el-tag size="mini" :type="getInvoiceTagType(row.invoice_type)">
              {{ getInvoiceTypeName(row.invoice_type) }}
            </el-tag>
            <span class="inv-no font-mono">{{ row.invoice_number || '--' }}</span>
          </div>
          <div class="card-head-right">
            <span class="inv-total">¥{{ formatMoney(row.total_amount) }}</span>
            <div class="card-actions">
              <el-button
                v-if="row.file_path"
                type="text"
                size="mini"
                icon="el-icon-view"
                @click="previewInvoice(row)"
              >
                查看原件
              </el-button>
              <span v-else class="text-muted text-no-file">无原件</span>

              <el-button
                v-if="!disabled && !isView"
                type="text"
                size="mini"
                class="btn-remove"
                icon="el-icon-delete"
                @click="removeInvoice(index)"
              >
                移除
              </el-button>
            </div>
          </div>
        </div>

        <!-- 卡片第二行：要素网格 -->
        <div class="card-meta-grid">
          <div class="meta-item meta-seller">
            <span class="meta-label">销售方：</span>
            <span class="meta-value" :title="row.seller_name">{{ row.seller_name || '--' }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">开票日期：</span>
            <span class="meta-value">{{ row.invoice_date || '--' }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">不含税：</span>
            <span class="meta-value font-mono">¥{{ formatMoney(row.amount) }}</span>
            <span class="meta-divider">|</span>
            <span class="meta-label">税额：</span>
            <span class="meta-value font-mono">¥{{ formatMoney(row.tax_amount) }}</span>
          </div>
          <div v-if="row.invoice_code && row.invoice_code !== '-'" class="meta-item">
            <span class="meta-label">发票代码：</span>
            <span class="meta-value font-mono">{{ row.invoice_code }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部统计栏 -->
    <div v-if="selectedList.length > 0" class="summary-footer mt10">
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
      :width="isMobile ? '96%' : '860px'"
      :top="isMobile ? '4vh' : '10vh'"
      append-to-body
      :close-on-click-modal="false"
      class="invoice-select-dialog"
    >
      <!-- 搜索筛选 -->
      <div class="dialog-filter mb15">
        <el-input
          v-model="filterKeyword"
          placeholder="搜索发票号码/代码/销方"
          size="small"
          clearable
          class="filter-item filter-keyword"
          prefix-icon="el-icon-search"
          @keyup.enter.native="loadMyInvoiceList"
        />

        <el-select
          v-model="filterType"
          placeholder="发票类型"
          size="small"
          clearable
          class="filter-item filter-type"
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
          class="filter-item filter-date"
          @change="loadMyInvoiceList"
        />

        <div class="filter-actions">
          <el-button
            type="primary"
            size="small"
            icon="el-icon-search"
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
      </div>

      <!-- 移动端：紧凑可勾选发票卡片列表 -->
      <div v-if="isMobile" v-loading="loadingMyInvoices" class="dialog-mobile-card-list">
        <div v-if="myInvoiceList.length === 0" class="empty-tip-mobile">
          暂无未报销发票
        </div>
        <div
          v-for="row in myInvoiceList"
          :key="row.id"
          class="dialog-mobile-item"
          :class="{
            'is-selected': isRowSelectedInDialog(row),
            'is-disabled': !canSelectInvoice(row)
          }"
          @click="toggleRowSelection(row)"
        >
          <div class="item-check">
            <el-checkbox
              :value="isRowSelectedInDialog(row)"
              :disabled="!canSelectInvoice(row)"
              @click.native.stop
              @change="handleMobileCheck(row, $event)"
            />
          </div>
          <div class="item-content">
            <div class="item-head">
              <el-tag size="mini" :type="getInvoiceTagType(row.invoice_type)">
                {{ getInvoiceTypeName(row.invoice_type) }}
              </el-tag>
              <span class="inv-num font-mono">{{ row.invoice_number || '--' }}</span>
              <span class="inv-amount">¥{{ formatMoney(row.total_amount) }}</span>
            </div>
            <div class="item-meta">
              <div class="seller-line">销方：{{ row.seller_name || '--' }}</div>
              <div class="sub-line">
                <span>日期：{{ row.invoice_date || '--' }}</span>
                <span v-if="!canSelectInvoice(row)" class="tag-added">已在列表中</span>
                <el-button
                  v-else-if="row.file_path"
                  type="text"
                  size="mini"
                  @click.stop="previewInvoice(row)"
                >
                  预览原件
                </el-button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PC 端：优化列宽的可选发票列表 -->
      <el-table
        v-else
        ref="invoiceSelectTable"
        v-loading="loadingMyInvoices"
        :data="myInvoiceList"
        border
        size="small"
        max-height="400"
        style="width: 100%"
        @selection-change="handleDialogSelectionChange"
      >
        <el-table-column type="selection" width="48" align="center" :selectable="canSelectInvoice" />

        <el-table-column label="发票类型" width="125">
          <template slot-scope="{ row }">
            <el-tag size="mini" :type="getInvoiceTagType(row.invoice_type)">
              {{ getInvoiceTypeName(row.invoice_type) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="invoice_number" label="发票号码" min-width="135">
          <template slot-scope="{ row }">
            <div class="font-mono font-bold">{{ row.invoice_number }}</div>
            <div v-if="row.invoice_code && row.invoice_code !== '-'" class="font-mono font-12 text-muted">
              代码: {{ row.invoice_code }}
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="seller_name" label="销售方名称" min-width="150" show-overflow-tooltip>
          <template slot-scope="{ row }">
            {{ row.seller_name || '--' }}
          </template>
        </el-table-column>

        <el-table-column prop="invoice_date" label="开票日期" width="105" align="center">
          <template slot-scope="{ row }">
            {{ row.invoice_date || '--' }}
          </template>
        </el-table-column>

        <el-table-column prop="total_amount" label="价税合计(元)" width="115" align="right">
          <template slot-scope="{ row }">
            <span class="total-amount-text">¥{{ formatMoney(row.total_amount) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="原件" width="65" align="center">
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
      <div slot="footer" class="dialog-footer-box">
        <div class="selected-summary">
          当前选中 <strong class="color-primary">{{ dialogSelection.length }}</strong> 张发票，
          合计金额：<strong class="color-primary">¥{{ formatMoney(dialogSelectedAmount) }}</strong>
        </div>
        <div class="footer-btns">
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
      :title="'发票原件预览 - ' + (currentPreview.invoice_number || '')"
      :visible.sync="previewDialogVisible"
      :width="isMobile ? '96%' : '860px'"
      append-to-body
      :top="isMobile ? '4vh' : '8vh'"
      class="invoice-preview-dialog"
    >
      <div class="preview-container" :class="{ 'preview-mobile': isMobile }">
        <!-- PDF 预览 -->
        <div v-if="isPreviewPdf" class="pdf-box">
          <iframe
            :src="previewUrl"
            width="100%"
            :height="isMobile ? '400px' : '580px'"
            frameborder="0"
          ></iframe>
        </div>

        <!-- 图片预览 -->
        <div v-else class="img-box acea-row row-center row-middle">
          <img :src="previewUrl" alt="发票原件" class="preview-img" />
        </div>
      </div>
      <div slot="footer" class="preview-footer-box">
        <span class="text-muted font-12 file-name-text">{{ currentPreview.file_name || '发票文件' }}</span>
        <div class="preview-btns">
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
      previewUrl: '',
      // 响应式移动端状态
      isMobile: typeof window !== 'undefined' ? window.innerWidth < 768 : false
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
  mounted() {
    this.handleResize = () => {
      this.isMobile = window.innerWidth < 768
    }
    window.addEventListener('resize', this.handleResize)
    var formItem = this.$el && this.$el.closest ? this.$el.closest('.el-form-item') : null
    if (formItem) formItem.classList.add('invoice-select-form-item')
  },
  beforeDestroy() {
    if (this.handleResize) {
      window.removeEventListener('resize', this.handleResize)
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
        const list = (res.data && res.data.list) || res.data || []
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

    isRowSelectedInDialog(row) {
      return this.dialogSelection.some((item) => item.id === row.id)
    },

    toggleRowSelection(row) {
      if (!this.canSelectInvoice(row)) return
      const index = this.dialogSelection.findIndex((item) => item.id === row.id)
      if (index > -1) {
        this.dialogSelection.splice(index, 1)
      } else {
        this.dialogSelection.push(row)
      }
    },

    handleMobileCheck(row, val) {
      if (!this.canSelectInvoice(row)) return
      const index = this.dialogSelection.findIndex((item) => item.id === row.id)
      if (val && index === -1) {
        this.dialogSelection.push(row)
      } else if (!val && index > -1) {
        this.dialogSelection.splice(index, 1)
      }
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
  box-sizing: border-box;

  .action-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    padding: 4px 0;

    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
    }

    .upload-inline {
      display: inline-block;
    }

    .upload-tip {
      font-size: 12px;
      color: #909399;
      flex-basis: 100%;
      margin-top: 2px;
    }

    .summary-badge {
      margin-left: auto;
      font-size: 13px;
      color: #606266;
      .highlight {
        font-weight: bold;
        color: #1890ff;
      }
    }
  }

  /* 紧凑卡片列表流 */
  .invoice-card-list {
    width: 100%;
    margin-top: 6px;

    .empty-invoice-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 26px 16px;
      background: #fafbfc;
      border: 1px dashed #dcdfe6;
      border-radius: 6px;
      color: #909399;
      font-size: 13px;

      .empty-icon {
        font-size: 28px;
        margin-bottom: 6px;
        color: #c0c4cc;
      }
    }

    .invoice-item-card {
      background: #ffffff;
      border: 1px solid #ebeef5;
      border-radius: 6px;
      margin-bottom: 10px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
      transition: all 0.2s ease;
      overflow: hidden;

      &:hover {
        border-color: #c6e2ff;
        box-shadow: 0 2px 8px rgba(24, 144, 255, 0.08);
      }

      .card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding: 8px 12px;
        background: #fafbfc;
        border-bottom: 1px solid #f0f2f5;

        .card-head-left {
          display: flex;
          align-items: center;
          flex-wrap: wrap;
          gap: 6px;

          .inv-badge {
            display: inline-block;
            padding: 1px 6px;
            background: #e6f7ff;
            color: #1890ff;
            font-weight: 600;
            font-size: 12px;
            border-radius: 4px;
            font-family: monospace;
          }

          .inv-no {
            font-weight: 600;
            font-size: 13px;
            color: #303133;
          }
        }

        .card-head-right {
          display: flex;
          align-items: center;
          gap: 10px;
          margin-left: auto;

          .inv-total {
            font-size: 15px;
            font-weight: 700;
            color: #f5222d;
            font-family: 'Helvetica Neue', Helvetica, monospace;
          }

          .card-actions {
            display: flex;
            align-items: center;
            gap: 8px;

            .btn-remove {
              color: #f56c6c !important;
              &:hover {
                color: #ff7875 !important;
              }
            }

            .text-no-file {
              font-size: 12px;
              color: #c0c4cc;
            }
          }
        }
      }

      .card-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 6px 14px;
        padding: 10px 12px;
        font-size: 12px;
        color: #606266;
        line-height: 1.5;

        .meta-item {
          display: flex;
          align-items: center;
          overflow: hidden;

          .meta-label {
            color: #909399;
            flex-shrink: 0;
          }

          .meta-value {
            color: #303133;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }

          .meta-divider {
            margin: 0 8px;
            color: #dcdfe6;
          }
        }
      }
    }
  }

  .font-mono {
    font-family: Menlo, Monaco, Consolas, monospace;
  }

  /* 底部统计栏 */
  .summary-footer {
    background: #f8faff;
    border: 1px dashed #d9e8ff;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 13px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 8px;

    .summary-details {
      color: #606266;
      line-height: 1.6;
      strong {
        color: #303133;
      }
    }

    .total-sum {
      font-size: 13px;
      color: #303133;
      white-space: nowrap;
      .sum-number {
        font-size: 17px;
        font-weight: bold;
        color: #f5222d;
        font-family: 'Helvetica Neue', Helvetica, monospace;
      }
    }
  }

  @media (max-width: 767px) {
    .action-bar {
      .summary-badge {
        margin-left: 0;
        width: 100%;
      }
    }

    .invoice-card-list {
      .invoice-item-card {
        .card-meta-grid {
          grid-template-columns: 1fr;
        }
      }
    }

    .summary-footer {
      flex-direction: column;
      align-items: flex-start;
      .total-sum {
        width: 100%;
        text-align: right;
        border-top: 1px dashed #ebf2ff;
        padding-top: 6px;
      }
    }
  }
}

/* 选票弹窗内部样式 */
.dialog-filter {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;

  .filter-item {
    margin: 0 !important;
  }
  .filter-keyword {
    width: 210px;
  }
  .filter-type {
    width: 150px;
  }
  .filter-date {
    width: 240px;
  }
  .filter-actions {
    display: flex;
    gap: 8px;
  }

  @media (max-width: 767px) {
    flex-direction: column;
    align-items: stretch;

    .filter-keyword,
    .filter-type,
    .filter-date {
      width: 100% !important;
    }
    .filter-actions {
      display: flex;
      justify-content: flex-end;
      width: 100%;
      .el-button {
        flex: 1;
      }
    }
  }
}

.total-amount-text {
  color: #f5222d;
  font-weight: 600;
  font-family: 'Helvetica Neue', Helvetica, monospace;
}

.dialog-mobile-card-list {
  max-height: 380px;
  overflow-y: auto;
  padding: 2px;

  .empty-tip-mobile {
    text-align: center;
    padding: 30px 10px;
    color: #909399;
    font-size: 13px;
  }

  .dialog-mobile-item {
    display: flex;
    align-items: flex-start;
    padding: 10px;
    background: #ffffff;
    border: 1px solid #ebeef5;
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;

    &.is-selected {
      border-color: #1890ff;
      background: #f0f7ff;
    }

    &.is-disabled {
      opacity: 0.6;
      background: #f5f7fa;
      cursor: not-allowed;
    }

    .item-check {
      margin-right: 10px;
      margin-top: 2px;
    }

    .item-content {
      flex: 1;
      min-width: 0;

      .item-head {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;

        .inv-num {
          font-weight: 600;
          font-size: 13px;
          color: #303133;
        }

        .inv-amount {
          margin-left: auto;
          color: #f5222d;
          font-weight: bold;
          font-size: 14px;
          font-family: monospace;
        }
      }

      .item-meta {
        margin-top: 4px;
        font-size: 12px;
        color: #606266;
        line-height: 1.4;

        .seller-line {
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .sub-line {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-top: 2px;
          color: #909399;

          .tag-added {
            color: #e6a23c;
            font-size: 11px;
          }
        }
      }
    }
  }
}

.dialog-footer-box,
.preview-footer-box {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 8px;

  @media (max-width: 767px) {
    flex-direction: column;
    align-items: stretch;
    .selected-summary,
    .file-name-text {
      font-size: 12px;
      text-align: left;
    }
    .footer-btns,
    .preview-btns {
      display: flex;
      justify-content: flex-end;
      .el-button {
        flex: 1;
      }
    }
  }
}

.preview-container {
  min-height: 360px;
  max-height: 650px;
  overflow: auto;

  &.preview-mobile {
    min-height: 280px;
    max-height: 450px;
  }

  .pdf-box {
    width: 100%;
    height: 100%;
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
