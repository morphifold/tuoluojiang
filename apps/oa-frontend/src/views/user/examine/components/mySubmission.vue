<template>
  <div>
    <oaFromBox :isAddBtn="false" title="我的申请" :search="search" :total="total" :isViewSearch="false"
      @confirmData="confirmData">
      <template slot="rightBtn">
        <el-dropdown trigger="click" size="small" placement="bottom-start" @command="handleBuild">
          <el-button type="primary" size="small">发起申请</el-button>
          <el-dropdown-menu slot="dropdown" class="build-dropdown">
            <el-dropdown-item v-for="(item, index) in dropdownList" :key="item.id" class="over-text" placement="top-end"
              :command="item.id">
              <i class="iconfont" :class="item.icon" :style="{ color: item.color }"></i>
              {{ item.name }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </template>
    </oaFromBox>
    <div class="table-box mt10">
      <!-- PC 端表格视图 -->
      <div class="pc-table">
        <el-table :data="tableData" :height="tableHeight" style="width: 100%" v-loading="loading" row-key="id"
          default-expand-all>
          <el-table-column prop="name" label="审批类型" min-width="250">
            <template slot-scope="scope">
              <el-row class="table-title">
                <el-col class="table-title-left" v-if="scope.row.approve">
                  <div class="selIcon" :style="{ backgroundColor: scope.row.approve.color }">
                    <i class="icon iconfont" :class="scope.row.approve.icon"></i>
                  </div>
                </el-col>
                <el-col class="table-title-right">
                  <p class="title" v-if="scope.row.approve">{{ scope.row.approve.name }}</p>
                  <p class="over-text">{{ getValue(scope.row.content) }}</p>
                </el-col>
              </el-row>
            </template>
          </el-table-column>
          <el-table-column prop="name" label="审批状态" min-width="80">
            <template slot-scope="scope">
              <span class="status">
                <el-tag v-if="scope.row.status === -1" type="info" effect="plain" size="mini"> 已撤销 </el-tag>
                <el-tag v-if="scope.row.status === 1 && scope.row.recall" type="info" effect="plain" size="mini">
                  撤销中
                </el-tag>
                <el-tag v-if="scope.row.status === 0" type="warning" effect="plain" size="mini"> 审核中 </el-tag>
                <el-tag v-if="scope.row.status === 1 && !scope.row.recall" type="info" effect="plain" size="mini">
                  已通过
                </el-tag>
                <el-tag v-if="scope.row.status === 2" type="danger" effect="plain" size="mini"> 已拒绝 </el-tag>
              </span>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="提交时间" min-width="150" />
          <el-table-column prop="name" :label="$t('public.operation')" width="200">
            <template slot-scope="scope">
              <el-button type="text" @click="handleDetail(scope.row)">详情</el-button>
              <el-button v-if="canReapply(scope.row)" type="text" @click="handleEdit(scope.row)">再次提交</el-button>
              <el-button v-if="canRevoke(scope.row)" type="text" @click="handleRefuse(scope.row)">撤销</el-button>
              <el-button v-if="canResubmit(scope.row)" type="text" @click="handleEdit(scope.row)">重新提交</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- 移动端紧凑卡片视图 -->
      <div class="mobile-card-list" v-loading="loading">
        <div v-if="tableData.length === 0 && !loading" class="mobile-empty">
          <default-page :index="14" :min-height="300" />
        </div>
        <div v-for="(item, index) in tableData" :key="item.id || index" class="mobile-card-item">
          <div class="card-header">
            <div class="header-left">
              <div class="selIcon" v-if="item.approve" :style="{ backgroundColor: item.approve.color }">
                <i class="icon iconfont" :class="item.approve.icon"></i>
              </div>
              <span class="card-title" v-if="item.approve">{{ item.approve.name }}</span>
            </div>
            <div class="header-right">
              <span class="status">
                <el-tag v-if="item.status === -1" type="info" effect="plain" size="mini"> 已撤销 </el-tag>
                <el-tag v-if="item.status === 1 && item.recall" type="info" effect="plain" size="mini"> 撤销中 </el-tag>
                <el-tag v-if="item.status === 0" type="warning" effect="plain" size="mini"> 审核中 </el-tag>
                <el-tag v-if="item.status === 1 && !item.recall" type="info" effect="plain" size="mini"> 已通过 </el-tag>
                <el-tag v-if="item.status === 2" type="danger" effect="plain" size="mini"> 已拒绝 </el-tag>
              </span>
            </div>
          </div>

          <div class="card-body">
            <div class="card-content" v-if="getValue(item.content)">
              {{ getValue(item.content) }}
            </div>
            <div class="card-time">
              <i class="el-icon-time"></i>
              <span>提交时间：{{ item.created_at }}</span>
            </div>
          </div>

          <div class="card-footer">
            <el-button type="text" size="small" @click="handleDetail(item)">详情</el-button>
            <el-button v-if="canReapply(item)" type="text" size="small" @click="handleEdit(item)">再次提交</el-button>
            <el-button v-if="canRevoke(item)" type="text" size="small" class="btn-danger" @click="handleRefuse(item)">撤销</el-button>
            <el-button v-if="canResubmit(item)" type="text" size="small" @click="handleEdit(item)">重新提交</el-button>
          </div>
        </div>
      </div>

      <div class="page-fixed">
        <el-pagination :page-size="where.limit" :current-page="where.page" :page-sizes="[15, 20, 30]"
          layout="total,sizes, prev, pager, next, jumper" :total="total" @size-change="handleSizeChange"
          @current-change="pageChange" />
      </div>
    </div>

    <edit-examine ref="editExamine" @isOk="getTableData()" :type="type" />
    <detail-examine ref="detailExamine" @getList="getTableData" />
    <!-- 撤销 -->
    <oa-dialog ref="oaDialog" :fromData="fromData" :formConfig="formConfig" :formRules="formRules"
      :formDataInit="formDataInit" @submit="getApplyRevoke"></oa-dialog>
  </div>
</template>

<script>
import { approveApplyApi, approveApplyRevokeApi, approveConfigSearchApi } from '@/api/business'
import func from '@/utils/preload'
export default {
  name: 'Submission',
  components: {
    detailExamine: () => import('@/views/user/examine/components/detailExamine'),
    editExamine: () => import('@/views/user/examine/components/editExamine'),
    oaDialog: () => import('@/components/form-common/dialog-form'),
    defaultPage: () => import('@/components/common/defaultPage'),
    oaFromBox: () => import('@/components/common/oaFromBox')
  },
  data() {
    return {
      tableData: [],
      formDataInit: {
        info: ''
      },
      formConfig: [
        {
          type: 'textarea',
          label: '撤销理由：',
          placeholder: '请输入撤销理由',
          key: 'info'
        }
      ],
      formRules: {
        info: [{ required: true, message: '请输入撤销理由', trigger: 'blur' }]
      },
      fromData: {
        width: '600px',
        title: '撤销',
        btnText: '确定',
        labelWidth: 'auto',
        type: ''
      },
      rowData: {},
      loading: false,
      where: {
        page: 1,
        limit: 15,
        types: 0,
        time: '',
        status: '',
        approve_id: ''
      },
      total: 0,
      search: [
        {
          field_name: '审批状态',
          field_name_en: 'status',
          form_value: 'select',
          data_dict: [
            { name: '全部', id: '' },
            { name: '审核中', id: 0 },
            { name: '已通过', id: 1 },
            { name: '已拒绝', id: 2 },
            { name: '已撤销', id: -1 }
          ]
        },
        {
          field_name: '审批类型',
          field_name_en: 'approve_id',
          form_value: 'select',
          data_dict: []
        },
        {
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time',
          form_value: 'date_picker'
        }
      ],
      buildData: [],
      type: 0,
      dropdownList: []
    }
  },
  created() {
    // 挂载全局工具函数
    this.$vue.prototype.$func = func;
    // 并行请求数据以提高性能
    Promise.all([
      this.getTableData(),
      this.getConfigSearch(0),
      this.getConfigSearch(3)
    ]).catch(error => {
      console.error('数据请求出错:', error);
    });
  },
  methods: {
    handleBuild(command) {
      // 检查 editExamine 引用是否存在，避免潜在的错误
      if (this.$refs.editExamine) {
        this.$refs.editExamine.isEdit = false;
        this.$refs.editExamine.openBox(command);
      }
    },

    async getConfigSearch(id) {
      const result = await approveConfigSearchApi(id)
      const data = result.data ? result.data : []
      if (id === 0) {
        // name转label id转value
        data.map((item) => {
          item.label = item.name
          item.value = item.id
        })
        this.dropdownList = data
      }
      // 1、下级审批；3、我提交过的所有类型；
      if (id === 3 || id === 1) {
        this.search[1].data_dict = data
        this.search[1].data_dict.unshift({ name: '全部', id: '' })
      }
    },
    
    confirmData(data) {
      // 当数据为 'reset' 时，重置查询条件
      if (data === 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          types: 0,
          time: '',
          status: '',
          approve_id: ''
        };
      } else {
        // 合并查询条件
        this.where = { ...this.where, ...data };
      }
      this.getTableData();
    },

    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },


    async getTableData() {
      this.loading = true
      const data = this.where
      this.where.verify_status = ''
      const result = await approveApplyApi(data)
      this.tableData = result.data.list
      this.total = result.data.count
      this.loading = false
    },
    // 详情
    handleDetail(row) {
      this.type = 1
      this.$refs.detailExamine.openBox(row)
    },
    getApproveIcon(icon) {
      let str = ''
      if (icon.indexOf('iconjine') > -1 || icon.indexOf('iconwenjian') > -1) {
        str = icon + '2'
      } else if (icon.indexOf('icontupian2') > -1) {
        str = 'icontupian3'
      } else if (icon.indexOf('icona-xingzhuang2') > -1) {
        str = 'icona-xingzhuang21'
      } else if (icon === 'iconwendang2') {
        str = 'icona-xingzhuang21'
      } else if (icon === 'iconwendang1') {
        str = 'icona-xingzhuang12'
      } else if (icon === 'iconrili1') {
        str = 'iconrili2'
      } else {
        str = icon
      }

      return str
    },
    // 重新提交
    handleEdit(row) {
      this.$refs.editExamine.isEdit = true
      this.$refs.editExamine.openBox(row)
    },
    handleRefuse(row) {
      this.rowData = row
      if (row.status === 0) {
        this.$modalSure(this.$t('你确定要撤销申请吗')).then(() => {
          this.getApplyRevoke('', row.id)
          this.getTableData()
        })
      } else {
        this.$refs.oaDialog.openBox()
      }
    },
    async getApplyRevoke(data) {
      await approveApplyRevokeApi(this.rowData.id, data)
      if (data) {
        this.$refs.oaDialog.handleClose()
      }
      this.getTableData()
    },
    canReapply(row) {
      return Boolean(row && row.status === 1 && Number(row.type) < 6)
    },
    canRevoke(row) {
      if (!row) return false
      const allowRecall = row.status === 1 && row.rules && Number(row.rules.recall) === 1
      return (allowRecall || row.status === 0) && !row.recall
    },
    canResubmit(row) {
      if (!row || row.crud_id != 0) return false
      const statusMatch = [2, -1].includes(row.status)
      const types = row.approve ? Number(row.approve.types) : 0
      return (statusMatch && types < 6) || types > 11
    },
    getValue(row) {
      if (!Array.isArray(row)) return ''
      let arr = []
      row.forEach((item) => {
        if (!item || item.type === 'uploadFrom' || item.type === 'rich_text') return

        // 发票明细摘要友好展示
        let isInvoice = item.type === 'invoiceSelect' || item.type === 'invoice'
        let invoiceList = null
        if (Array.isArray(item.value)) {
          if (item.value.length > 0 && (item.value[0].invoice_number || item.value[0].total_amount !== undefined || item.value[0].invoice_type)) {
            isInvoice = true
            invoiceList = item.value
          } else if (isInvoice) {
            invoiceList = item.value
          }
        } else if (typeof item.value === 'string' && (item.value.includes('invoice_number') || item.value.includes('total_amount'))) {
          try {
            const parsed = JSON.parse(item.value)
            if (Array.isArray(parsed)) {
              isInvoice = true
              invoiceList = parsed
            }
          } catch (e) {}
        }

        if (isInvoice) {
          const count = invoiceList ? invoiceList.length : 0
          const total = invoiceList ? invoiceList.reduce((sum, cur) => sum + (parseFloat(cur.total_amount) || 0), 0) : 0
          const label = item.label || '发票明细'
          if (count > 0) {
            arr.push(`${label} : 共 ${count} 张（合计 ¥${total.toFixed(2)}） `)
          }
          return
        }

        // 时间区间
        if (item.type === 'timeFrom' && item.value && typeof item.value === 'object') {
          const start = item.value.dateStart || ''
          const end = item.value.dateEnd || ''
          const dur = item.value.duration ? ` (${item.value.duration})` : ''
          if (start || end) {
            arr.push(`${item.label} : ${start} ~ ${end}${dur} `)
          }
          return
        }

        // 普通文本/数值/数组
        if (item.value !== undefined && item.value !== null && item.value !== '') {
          if (typeof item.value === 'string' || typeof item.value === 'number') {
            arr.push(`${item.label} : ${item.value} `)
          } else if (Array.isArray(item.value)) {
            if (item.value.length > 0 && item.value.every((v) => typeof v === 'string' || typeof v === 'number')) {
              arr.push(`${item.label} : ${item.value.join('、')} `)
            }
          }
        }
      })
      return arr.slice(0, 3).join('')
    }
  }
}
</script>

<style lang="scss" scoped>
.build-dropdown {
  max-height: fit-content;
  overflow: auto;
  overflow-x: hidden;

  .iconfont {
    font-size: 14px !important;
  }
}

.status {
  /deep/ .el-tag {
    background: #fff;
  }
}

.build-dropdown::-webkit-scrollbar {
  /*width: 0;宽度为0隐藏*/
  width: 8px;
  height: 4px;
}

.build-dropdown::-webkit-scrollbar-thumb {
  border-radius: 5px;
  height: 8px;
  background: rgba(0, 0, 0, 0.2); //滚动条颜色
}

.build-dropdown::-webkit-scrollbar-track {
  box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
  border-radius: 5px;
  background: #eee; //滚动条背景色
}

.mobile-card-list {
  display: none;
}
.pc-table {
  display: block;
}

.table-box {

  .table-title {
    display: flex;
    align-items: center;

    .table-title-left {
      width: 56px;

      i {
        color: #fff;
        font-size: 46px;
      }
    }

    .table-title-right {
      width: calc(100% - 56px);

      p {
        margin: 0;
        font-size: 13px;
      }

      .title {
        font-weight: bold;
        font-size: 13px;
      }

      .over-text {
        margin-top: 8px;
      }
    }
  }

  /deep/ .el-table .cell {
    line-height: 1;
  }
}

.selIcon {
  width: 25px;
  height: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 3px;
}

.iconfont {
  font-size: 13px !important;
  color: #fff;
}

@media (max-width: 767px) {
  .pc-table {
    display: none !important;
  }

  .mobile-card-list {
    display: block;
    max-height: calc(100vh - 220px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 12px;

    .mobile-card-item {
      background: #fff;
      border: 1px solid #ebeef5;
      border-radius: 6px;
      padding: 12px;
      margin-bottom: 10px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);

      .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding-bottom: 8px;
        border-bottom: 1px dashed #f0f2f5;

        .header-left {
          display: flex;
          align-items: center;
          gap: 8px;
          min-width: 0;
          flex: 1;

          .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #303133;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }
        }

        .header-right {
          flex-shrink: 0;
        }
      }

      .card-body {
        padding: 10px 0;
        font-size: 13px;
        color: #606266;

        .card-content {
          line-height: 1.6;
          color: #606266;
          word-break: break-word;
          margin-bottom: 6px;
          background: rgba(24, 144, 255, 0.03);
          padding: 6px 8px;
          border-radius: 4px;
        }

        .card-time {
          display: flex;
          align-items: center;
          gap: 4px;
          color: #909399;
          font-size: 12px;
        }
      }

      .card-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 6px;
        border-top: 1px solid #f7f8fa;

        /deep/ .el-button--text {
          padding: 4px 6px;
          font-size: 13px;
        }
        .btn-danger {
          color: #f56c6c;
        }
      }
    }
  }

  .page-fixed {
    position: static !important;
    margin-top: 15px !important;
    padding: 10px 0 !important;
    justify-content: center !important;
    /deep/ .el-pagination {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 4px;
      .el-pagination__sizes,
      .el-pagination__jump {
        display: none;
      }
    }
  }
}
