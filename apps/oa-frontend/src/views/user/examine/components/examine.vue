<template>
  <div>
    <oaFromBox
      :search="search"
      :total="total"
      :isAddBtn="false"
      :isViewSearch="false"
      @dropdownFn="dropdownFn"
      @confirmData="confirmData"
    ></oaFromBox>
    <div class="mt-10" v-loading="loading" v-if="tableData.length > 0">
      <el-scrollbar style="height: 100%">
        <ul class="table-box" :style="{ height: tableHeight }">
          <li v-for="(item, index) in tableData" :key="index">
            <div class="table-header">
              <div class="table-header-left">
                <div class="table-title">
                  <div class="table-title-left" v-if="item.approve">
                    <div class="selIcon" :style="{ backgroundColor: item.approve.color }">
                      <i class="icon iconfont" :class="item.approve.icon"></i>
                    </div>
                  </div>
                  <div class="table-title-right">
                    <div class="title" v-if="item.approve">{{ item.approve.name }}</div>
                    <div class="tag">
                      <el-tag v-if="item.status === -1" type="info" size="mini"> 已撤销 </el-tag>
                      <el-tag v-if="item.status === 0" type="warning" size="mini"> 审核中 </el-tag>
                      <el-tag v-if="item.status === 1 && !item.recall" type="info" size="mini"> 已通过 </el-tag>
                      <el-tag v-if="item.status === 2" type="danger" size="mini"> 已拒绝 </el-tag>
                      <el-tag v-if="item.status === 1 && item.recall" type="danger" size="mini"> 撤销中 </el-tag>
                    </div>
                  </div>
                </div>
              </div>
              <div class="table-header-right">
                <el-button size="mini" @click="handleDetail(item)">查看详情</el-button>
                <template v-if="item.status == 0">
                  <el-button v-if="item.verify_status === 0" type="danger" size="mini" @click="handleRefuse(item)">
                    拒绝
                  </el-button>
                  <el-button v-if="item.verify_status === 0" type="primary" size="mini" @click="handleAgree(item)">
                    同意
                  </el-button>
                </template>
              </div>
            </div>
            <div class="table-body">
              <div class="table-body-title">
                <div class="table-body-left">
                  <img class="img-body" v-if="judge(item)" :src="item.card.avatar" alt="" />
                  <img class="img-body" v-else src="../../../../assets/images/portrait.png" alt="" />
                </div>
                <div class="table-body-right">
                  <div class="title">
                    <span>{{ item.card ? item.card.name : '' }}</span>
                    <span class="time">创建于{{ item.created_at }}</span>
                  </div>
                  <div class="mt15 table-body-body">
                    <div class="over-text" v-html="getValue(item.content)"></div>
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </el-scrollbar>
      <div class="page-fixed">
        <el-pagination
          :page-size="where.limit"
          :current-page="where.page"
          :page-sizes="[15, 20, 30]"
          layout="total,sizes, prev, pager, next, jumper"
          :total="total"
          @size-change="handleSizeChange"
          @current-change="pageChange"
        />
      </div>
    </div>

    <div v-else>
      <default-page :index="14" :min-height="420" />
    </div>
    <detail-examine ref="detailExamine" @getList="getTableData" :type="type" />
  </div>
</template>

<script>
import { approveApplyApi, approveVerifyStatusApi, approveConfigSearchApi } from '@/api/business'
import func from '@/utils/preload'
export default {
  name: 'Examine',
  components: {
    oaFromBox: () => import('@/components/common/oaFromBox'),
    detailExamine: () => import('@/views/user/examine/components/detailExamine'),
    defaultPage: () => import('@/components/common/defaultPage')
  },
  props: ['examineActive'],

  data() {
    return {
      type: 1,
      tableData: [],
      listType: [],
      loading: false,
      search: [
        {
          field_name: '人员',
          field_name_en: 'name',
          form_value: 'input'
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
          form_value: 'date_picker',
          data_dict: []
        }
      ],

      where: {
        page: 1,
        limit: 15,
        types: 1,
        time: '',
        status: '',
        approve_id: '',
        name: '',
        verify_status: ''
      },
      total: 0
    }
  },
  watch: {
    examineActive(val) {
      this.getConfigSearch(1)
      if (![1, 4].includes(val)) {
        if (this.search.length == 3) {
          this.search.splice(2, 0, {
            field_name: '审批状态',
            field_name_en: 'status',
            form_value: 'select',
            data_dict: [
              { name: '全部', id: '' },
              { name: '待审核', id: 0 },
              { name: '已通过', id: 1 },
              { name: '已拒绝', id: 2 },
              { name: '已撤销', id: -1 }
            ]
          })
        }
      }
      if (val == 1 || val == 4) {
        this.search.splice(2, 1)
      }
    }
  },
  beforeCreate() {
    this.$vue.prototype.$func = func
  },
  created() {
    this.getConfigSearch(1)
  },
  methods: {
    confirmData(data) {
      if (data === 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          types: 1,
          time: '',
          status: '',
          approve_id: '',
          name: '',
          verify_status: this.examineActive
        }
      } else {
        this.where = { ...this.where, ...data }
      }
      this.getTableData()
    },
    dropdownFn(item) {},
    restFn() {
      this.where = {
        page: 1,
        limit: 15,
        types: 1,
        time: '',
        status: '',
        approve_id: '',
        name: '',
        verify_status: this.examineActive
      }
      this.getTableData()
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    async getConfigSearch(id) {
      const result = await approveConfigSearchApi(id)
      this.listType = result.data
      this.search[1].data_dict = this.listType
      this.search[1].data_dict.unshift({ name: '全部', id: '' })
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    async getTableData() {
      this.loading = true
      const data = this.where
      if (data.verify_status == '5') {
        data.verify_status = ''
      }
      const result = await approveApplyApi(data)
      this.loading = false
      this.tableData = result.data.list
      this.total = result.data.count
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
    // 详情
    handleDetail(row) {
      this.$refs.detailExamine.openBox(row, 'revoke')
    },
    judge(row) {
      if (row.card) {
        return row.card.avatar.includes('https')
      }
    },
    // 同意
    handleAgree(row) {
      this.$modalSure('你确定要 同意 申请人的申请吗').then(() => {
        this.getApproveVerify(row.id, 1)
      })
    },
    // 拒绝
    handleRefuse(row) {
      this.$modalSure('你确定要 拒绝 申请人的申请吗').then(() => {
        this.getApproveVerify(row.id, 0)
      })
    },
    async getApproveVerify(id, status) {
      const result = await approveVerifyStatusApi(id, status)
      await this.getTableData()
    },
    getValue(row) {
      if (!Array.isArray(row)) return ''
      let arr = []
      row.forEach((item) => {
        if (!item || item.type === 'uploadFrom') return

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
            arr.push(`${label} : 共 ${count} 张（合计 ¥${total.toFixed(2)}）&nbsp;&nbsp;&nbsp;`)
          }
          return
        }

        // 时间区间
        if (item.type === 'timeFrom' && item.value && typeof item.value === 'object') {
          const start = item.value.dateStart || ''
          const end = item.value.dateEnd || ''
          const dur = item.value.duration ? ` (${item.value.duration})` : ''
          if (start || end) {
            arr.push(`${item.label} : ${start} ~ ${end}${dur}&nbsp;&nbsp;&nbsp;`)
          }
          return
        }

        // 普通文本/数值/数组
        if (item.value !== undefined && item.value !== null && item.value !== '') {
          if (typeof item.value === 'string' || typeof item.value === 'number') {
            arr.push(`${item.label} : ${item.value}&nbsp;&nbsp;&nbsp;`)
          } else if (Array.isArray(item.value)) {
            if (item.value.length > 0 && item.value.every((v) => typeof v === 'string' || typeof v === 'number')) {
              arr.push(`${item.label} : ${item.value.join('、')}&nbsp;&nbsp;&nbsp;`)
            }
          }
        }
      })
      return arr.join('')
    }
  }
}
</script>

<style lang="scss" scoped>
/deep/ .el-scrollbar__wrap {
  overflow-x: hidden;
}
/deep/ .el-card__body {
  padding-right: 0;
}
.img-body {
  width: 30px;
  height: 30px;
  border-radius: 50%;
}
.selIcon {
  width: 25px;
  height: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  // line-height: 25px;
  // display: inline-block;
  // text-align: center;
  border-radius: 3px;
}
.iconfont {
  font-size: 13px;
  color: #fff;
}
.table-box {
  list-style: none;
  margin: 0px 0px 0 0;
  padding: 0;
  overflow: auto;
  li {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #eeeeee;
    border: 1px solid #f0f2f5;
    padding: 14px;
    border-radius: 4px;
    background: #fff;
    .table-header {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
      margin-bottom: 15px;
      .table-header-left {
        display: flex;
        align-items: center;
        min-width: 0;
        flex: 1 1 auto;
      }
      .table-title {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
        .table-title-left {
          width: 38px;
          flex-shrink: 0;
        }
        .table-title-right {
          width: calc(100% - 38px);
          display: flex;
          align-items: center;
          flex-wrap: wrap;
          gap: 6px;
          min-width: 0;
          .title {
            font-weight: bold;
            font-size: 13px;
            line-height: 20px;
          }
          .tag {
            /deep/ .el-tag {
              background: #fff;
            }
          }
        }
      }
      .table-header-right {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
        flex-shrink: 0;
        /deep/ .el-button + .el-button {
          margin-left: 0;
        }
      }
    }
    .table-body {
      p {
        margin: 0;
        padding: 0;
      }
      padding: 16px 15px;
      background-color: rgba(24, 144, 255, 0.04);
      border-radius: 4px;
      .table-body-title {
        display: flex;
        align-items: flex-start;
      }
      .table-body-left {
        width: 40px;
        flex-shrink: 0;
      }
      .table-body-right {
        width: calc(100% - 40px);
        min-width: 0;
        font-size: 13px;
        color: rgba(119, 119, 119, 0.85);
        .title {
          color: #333;
          font-size: 14px;
          margin-top: 6px;
          margin-bottom: 10px;
          .time {
            padding-left: 10px;
            font-size: 13px;
            color: rgba(119, 119, 119, 0.85);
          }
        }
        .table-body-body {
          div {
            width: 100%;
          }
          span {
            padding-right: 15px;
          }
        }
      }
    }
  }
}

@media (max-width: 767px) {
  .table-box {
    height: auto !important;
    max-height: calc(100vh - 220px) !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 10px;

    li {
      margin-bottom: 10px;
      padding: 10px;

      .table-header {
        margin-bottom: 10px;
        gap: 8px;

        .table-header-left {
          width: 100%;
        }

        .table-header-right {
          width: 100%;
          justify-content: flex-end;
          gap: 6px;
        }
      }

      .table-body {
        padding: 10px;

        .table-body-left {
          width: 36px;
        }

        .table-body-right {
          width: calc(100% - 36px);

          .title {
            display: flex;
            flex-direction: column;
            gap: 2px;

            .time {
              padding-left: 0;
              font-size: 12px;
            }
          }

          .table-body-body {
            margin-top: 8px;
            .over-text {
              white-space: normal;
              word-break: break-word;
              line-height: 1.5;
            }
          }
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
</style>
