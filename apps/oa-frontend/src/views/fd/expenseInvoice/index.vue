<template>
  <div class="divBox finance-invoice-container">
    <!-- 顶部财务数据概览卡片 -->
    <el-row :gutter="14" class="mb14 stat-row">
      <el-col :span="4" :xs="24" :sm="8" class="stat-col">
        <el-card shadow="never" class="stat-card">
          <div class="stat-inner">
            <div class="stat-title">全员进项总览</div>
            <div class="stat-val">{{ stat.totalCount }} <span class="unit">张</span></div>
            <div class="stat-desc">总价税 ¥{{ formatMoney(stat.totalAmount) }}</div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="5" :xs="24" :sm="8" class="stat-col">
        <el-card shadow="never" class="stat-card">
          <div class="stat-inner">
            <div class="stat-title">待报销 (未绑定)</div>
            <div class="stat-val text-success">{{ stat.unboundCount }} <span class="unit">张</span></div>
            <div class="stat-desc">可入账 ¥{{ formatMoney(stat.unboundAmount) }}</div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="5" :xs="24" :sm="8" class="stat-col">
        <el-card shadow="never" class="stat-card">
          <div class="stat-inner">
            <div class="stat-title">报销审批中 (已绑定)</div>
            <div class="stat-val text-warning">{{ stat.bindingCount }} <span class="unit">张</span></div>
            <div class="stat-desc">在途报销 ¥{{ formatMoney(stat.bindingAmount) }}</div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="5" :xs="24" :sm="8" class="stat-col">
        <el-card shadow="never" class="stat-card">
          <div class="stat-inner">
            <div class="stat-title">已报销归档</div>
            <div class="stat-val text-info">{{ stat.reimbursedCount }} <span class="unit">张</span></div>
            <div class="stat-desc">归档核销 ¥{{ formatMoney(stat.reimbursedAmount) }}</div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="5" :xs="24" :sm="8" class="stat-col">
        <el-card shadow="never" class="stat-card abnormal-card">
          <div class="stat-inner">
            <div class="stat-title text-danger"><i class="el-icon-warning"></i> 异常预警发票</div>
            <div class="stat-val text-danger">{{ stat.abnormalCount }} <span class="unit">张</span></div>
            <div class="stat-desc text-danger">风险金额 ¥{{ formatMoney(stat.abnormalAmount) }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 主表格卡片 -->
    <el-card :body-style="{ padding: '20px' }" class="normal-page">
      <!-- 页面标题与操作 -->
      <div class="header-box acea-row row-between row-middle mb20">
        <div class="title-section acea-row row-middle">
          <span class="page-title">进项发票台账</span>
          <span class="sub-title text-muted ml10">全员报销发票归集审计、防重把关、风险排查及异常标记管理</span>
        </div>
        <div class="action-section acea-row row-middle">
          <el-radio-group v-model="viewMode" size="small" class="mr15 view-mode-radio">
            <el-radio-button label="card"><i class="el-icon-menu"></i> 卡片</el-radio-button>
            <el-radio-button label="table"><i class="el-icon-s-grid"></i> 表格</el-radio-button>
          </el-radio-group>
          <el-button
            type="primary"
            size="small"
            icon="el-icon-download"
            :loading="exporting"
            @click="handleExport"
          >
            导出台账 Excel
          </el-button>
          <el-button
            size="small"
            icon="el-icon-refresh"
            @click="getTableData"
          >
            刷新
          </el-button>
        </div>
      </div>
      <!-- 高级复合检索栏 -->
      <div class="filter-box mb20">
        <el-form :inline="true" size="small" class="demo-form-inline">
          <el-form-item label="报销人">
            <el-input
              v-model="where.keyword_user"
              placeholder="报销人姓名/工号"
              clearable
              style="width: 140px"
              @keyup.enter.native="handleSearch"
            />
          </el-form-item>

          <el-form-item label="所属部门">
            <el-input
              v-model="where.dep_name"
              placeholder="部门名称"
              clearable
              style="width: 130px"
              @keyup.enter.native="handleSearch"
            />
          </el-form-item>

          <el-form-item label="报销状态">
            <el-select v-model="where.status" placeholder="全部状态" clearable style="width: 130px" @change="handleSearch">
              <el-option label="全部状态" value="" />
              <el-option label="未绑定(待报销)" :value="0" />
              <el-option label="已绑定(审批中)" :value="1" />
              <el-option label="已报销" :value="2" />
            </el-select>
          </el-form-item>

          <el-form-item label="异常状态">
            <el-select v-model="where.abnormal_status" placeholder="全部" clearable style="width: 120px" @change="handleSearch">
              <el-option label="全部" value="" />
              <el-option label="正常发票" :value="0" />
              <el-option label="作废发票" :value="1" />
              <el-option label="红冲发票" :value="2" />
              <el-option label="虚假发票" :value="3" />
            </el-select>
          </el-form-item>

          <el-form-item label="发票类型">
            <el-select v-model="where.invoice_type" placeholder="全部类型" clearable style="width: 140px" @change="handleSearch">
              <el-option label="全部类型" value="" />
              <el-option label="增值税专用发票" value="special" />
              <el-option label="增值税普通发票" value="general" />
              <el-option label="增值税电子普通发票" value="electronic" />
              <el-option label="全电专用发票" value="digital_special" />
              <el-option label="全电普通发票" value="digital_general" />
              <el-option label="通行费发票" value="toll" />
              <el-option label="定额发票" value="quota" />
            </el-select>
          </el-form-item>

          <el-form-item label="开票日期">
            <el-date-picker
              v-model="dateRange"
              type="daterange"
              value-format="yyyy-MM-dd"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              style="width: 240px"
              @change="handleDateChange"
            />
          </el-form-item>

          <el-form-item label="关键字">
            <el-input
              v-model="where.keyword"
              placeholder="发票号码/发票代码/销方名称"
              clearable
              style="width: 190px"
              prefix-icon="el-icon-search"
              @keyup.enter.native="handleSearch"
            />
          </el-form-item>

          <el-form-item class="filter-actions-item">
            <div class="filter-btn-row">
              <el-button type="primary" icon="el-icon-search" @click="handleSearch">查询</el-button>
              <el-button icon="el-icon-refresh" @click="handleReset">重置</el-button>
            </div>
          </el-form-item>
        </el-form>
      </div>

      <!-- 数据内容区 (支持移动端卡片与列表表格双视图) -->
      <div v-loading="loading">
        <!-- 空状态 (卡片模式下) -->
        <div v-if="viewMode === 'card' && tableData.length === 0" class="empty-box acea-row row-column row-center row-middle">
          <img src="../../../assets/images/defd.png" alt="暂无台账" class="empty-img" @error="onStaticImgError" />
          <p class="empty-text">进项发票台账暂无相关记录</p>
        </div>

        <!-- 模式 1：移动端财务台账卡片列表 -->
        <div v-else-if="viewMode === 'card'" class="card-grid">
          <el-row :gutter="14">
            <el-col :xs="24" :sm="12" :md="8" :lg="8" v-for="item in tableData" :key="item.id" class="mb14">
              <div class="finance-invoice-card" :class="{ 'is-abnormal': item.abnormal_status > 0 }">
                <!-- 异常预警横幅 -->
                <div v-if="item.abnormal_status > 0" class="abnormal-banner acea-row row-middle">
                  <i class="el-icon-warning mr5"></i>
                  <span>异常【{{ getAbnormalName(item.abnormal_status) }}】：{{ item.abnormal_reason || '存在异常风险' }}</span>
                </div>

                <!-- 卡片头部：类型与状态标签 -->
                <div class="card-header acea-row row-between row-middle">
                  <el-tag size="mini" :type="getInvoiceTagType(item.invoice_type)">
                    {{ getInvoiceTypeName(item.invoice_type) }}
                  </el-tag>
                  <div class="status-tags acea-row row-middle">
                    <el-tag v-if="item.status === 0" size="mini" type="success" effect="plain">未绑定</el-tag>
                    <el-tag v-else-if="item.status === 1" size="mini" type="warning" effect="plain">审批中</el-tag>
                    <el-tag v-else-if="item.status === 2" size="mini" type="info" effect="plain">已报销</el-tag>

                    <el-tag v-if="item.abnormal_status > 0" size="mini" type="danger" effect="dark" class="ml5">
                      {{ getAbnormalName(item.abnormal_status) }}
                    </el-tag>
                    <span v-else class="text-success font-12 ml5"><i class="el-icon-check"></i> 正常</span>
                  </div>
                </div>

                <!-- 金额醒目展示 -->
                <div class="card-amount-box mt10">
                  <div class="amount-label">价税合计</div>
                  <div class="amount-val">
                    <span class="currency">¥</span>{{ formatMoney(item.total_amount) }}
                  </div>
                  <div class="sub-amount-row acea-row row-between">
                    <span>不含税：¥{{ formatMoney(item.amount) }}</span>
                    <span>税额：¥{{ formatMoney(item.tax_amount) }}</span>
                  </div>
                </div>

                <!-- 发票核心字段信息 -->
                <div class="card-info-list mt10">
                  <div class="info-row acea-row">
                    <span class="info-title">发票号码：</span>
                    <span class="info-content font-mono font-bold">
                      {{ item.invoice_number }}
                      <i class="el-icon-document-copy pointer copy-btn" title="点击复制" @click="copyText(item.invoice_number)"></i>
                    </span>
                  </div>
                  <div class="info-row acea-row" v-if="item.invoice_code">
                    <span class="info-title">发票代码：</span>
                    <span class="info-content font-mono">{{ item.invoice_code }}</span>
                  </div>
                  <div class="info-row acea-row">
                    <span class="info-title">报销人员：</span>
                    <span class="info-content">
                      <span class="font-bold">{{ item.user_name || ((item.card || {}).name) || ('员工' + (item.uid || '')) }}</span>
                      <span v-if="item.dep_name || (((item.card || {}).frame || {}).name)" class="dep-name text-muted">
                        ({{ item.dep_name || (((item.card || {}).frame || {}).name) }})
                      </span>
                    </span>
                  </div>
                  <div class="info-row acea-row">
                    <span class="info-title">开票日期：</span>
                    <span class="info-content">{{ item.invoice_date || '--' }}</span>
                  </div>
                  <div class="info-row acea-row">
                    <span class="info-title">销售方：</span>
                    <span class="info-content ellipsis" :title="item.seller_name">{{ item.seller_name || '--' }}</span>
                  </div>
                  <div class="info-row acea-row" v-if="item.apply_id">
                    <span class="info-title">关联单据：</span>
                    <span class="info-content">
                      <el-link type="primary" size="mini" @click="viewApprovalDetail(item.apply_id)">
                        审批单 #{{ item.apply_id }}
                      </el-link>
                    </span>
                  </div>
                </div>

                <!-- 卡片底部操作栏 -->
                <div class="card-footer acea-row row-between row-middle mt15 pt10">
                  <div>
                    <el-button
                      v-if="item.file_path"
                      type="text"
                      size="small"
                      icon="el-icon-view"
                      @click="previewInvoice(item)"
                    >
                      查看原件
                    </el-button>
                    <span v-else class="text-muted font-12">未传原件</span>
                  </div>

                  <div class="btn-group">
                    <el-button
                      type="text"
                      size="small"
                      :style="{ color: item.abnormal_status > 0 ? '#67C23A' : '#E6A23C' }"
                      @click="openAbnormalDialog(item)"
                    >
                      {{ item.abnormal_status > 0 ? '解除异常' : '标记异常' }}
                    </el-button>

                    <el-button
                      type="text"
                      size="small"
                      icon="el-icon-info"
                      @click="openDetailDrawer(item)"
                    >
                      详情
                    </el-button>
                  </div>
                </div>
              </div>
            </el-col>
          </el-row>
        </div>

        <!-- 模式 2：表格列表视图 -->
        <div v-else class="table-view-box">
          <el-table
            :data="tableData"
            border
            size="small"
            style="width: 100%"
            :row-class-name="tableRowClassName"
          >
          <el-table-column type="index" label="序号" width="50" align="center" />

          <el-table-column prop="invoice_number" label="发票号码" min-width="130" show-overflow-tooltip>
            <template slot-scope="{ row }">
              <span class="font-mono font-bold">{{ row.invoice_number }}</span>
              <i class="el-icon-document-copy pointer copy-btn ml5" title="点击复制" @click="copyText(row.invoice_number)"></i>
            </template>
          </el-table-column>

          <el-table-column prop="invoice_code" label="发票代码" min-width="110" show-overflow-tooltip>
            <template slot-scope="{ row }">
              <span class="font-mono">{{ row.invoice_code || '-' }}</span>
            </template>
          </el-table-column>

          <el-table-column label="发票类型" min-width="120" show-overflow-tooltip>
            <template slot-scope="{ row }">
              <el-tag size="mini" :type="getInvoiceTagType(row.invoice_type)">
                {{ getInvoiceTypeName(row.invoice_type) }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="total_amount" label="价税合计(元)" width="120" align="right">
            <template slot-scope="{ row }">
              <span class="price-val">¥{{ formatMoney(row.total_amount) }}</span>
            </template>
          </el-table-column>

          <el-table-column prop="amount" label="不含税金额" width="105" align="right">
            <template slot-scope="{ row }">¥{{ formatMoney(row.amount) }}</template>
          </el-table-column>

          <el-table-column prop="tax_amount" label="税额" width="90" align="right">
            <template slot-scope="{ row }">¥{{ formatMoney(row.tax_amount) }}</template>
          </el-table-column>

          <el-table-column prop="invoice_date" label="开票日期" width="105" align="center" />

          <el-table-column prop="seller_name" label="销售方名称" min-width="160" show-overflow-tooltip />

          <el-table-column label="报销人/部门" min-width="130" show-overflow-tooltip>
            <template slot-scope="{ row }">
              <div class="user-cell">
                <span class="user-name">{{ row.user_name || (row.card || {}).name || `员工${row.uid || ''}` }}</span>
                <span v-if="row.dep_name || ((row.card || {}).frame || {}).name" class="dep-name text-muted">
                  ({{ row.dep_name || ((row.card || {}).frame || {}).name }})
                </span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="报销状态" width="100" align="center">
            <template slot-scope="{ row }">
              <el-tag v-if="row.status === 0" size="mini" type="success">未绑定</el-tag>
              <el-tag v-else-if="row.status === 1" size="mini" type="warning">审批中</el-tag>
              <el-tag v-else-if="row.status === 2" size="mini" type="info">已报销</el-tag>
            </template>
          </el-table-column>

          <el-table-column label="异常状态" width="110" align="center">
            <template slot-scope="{ row }">
              <el-tooltip
                v-if="row.abnormal_status > 0"
                :content="`异常原因：${row.abnormal_reason || '未填写'}`"
                placement="top"
              >
                <el-tag size="mini" type="danger" effect="dark" class="pointer">
                  <i class="el-icon-warning"></i> {{ getAbnormalName(row.abnormal_status) }}
                </el-tag>
              </el-tooltip>
              <span v-else class="text-success font-12"><i class="el-icon-check"></i> 正常</span>
            </template>
          </el-table-column>

          <el-table-column label="关联单据" width="100" align="center">
            <template slot-scope="{ row }">
              <el-link
                v-if="row.apply_id"
                type="primary"
                size="mini"
                @click="viewApprovalDetail(row.apply_id)"
              >
                审批单 #{{ row.apply_id }}
              </el-link>
              <span v-else class="text-muted font-12">-</span>
            </template>
          </el-table-column>

          <el-table-column label="原件" width="75" align="center">
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
              <span v-else class="text-muted font-12">-</span>
            </template>
          </el-table-column>

          <el-table-column label="财务操作" width="140" align="center" fixed="right">
            <template slot-scope="{ row }">
              <el-button
                type="text"
                size="small"
                :style="{ color: row.abnormal_status > 0 ? '#67C23A' : '#E6A23C' }"
                @click="openAbnormalDialog(row)"
              >
                {{ row.abnormal_status > 0 ? '解除异常' : '标记异常' }}
              </el-button>

              <el-button
                type="text"
                size="small"
                @click="openDetailDrawer(row)"
              >
                详情
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        </div>

        <!-- 分页 -->
        <div class="pagination-box mt20 acea-row row-right">
          <el-pagination
            :current-page="where.page"
            :page-sizes="[15, 30, 50, 100]"
            :page-size="where.limit"
            layout="total, sizes, prev, pager, next, jumper"
            :total="total"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </div>
    </el-card>

    <!-- 财务标记异常弹窗 -->
    <el-dialog
      :title="`发票异常标记维护 - [${currentInvoice.invoice_number || ''}]`"
      :visible.sync="abnormalDialogVisible"
      :width="isMobile ? '95%' : '520px'"
      append-to-body
      :top="isMobile ? '4vh' : '15vh'"
      :close-on-click-modal="false"
      class="abnormal-dialog"
    >
      <el-form ref="abnormalForm" :model="abnormalForm" :rules="abnormalRules" :label-width="isMobile ? '80px' : '100px'" :label-position="isMobile ? 'top' : 'right'" size="small">
        <el-form-item label="发票号码">
          <span class="font-mono font-bold">{{ currentInvoice.invoice_number }}</span>
        </el-form-item>

        <el-form-item label="价税金额">
          <span class="price-val">¥{{ formatMoney(currentInvoice.total_amount) }}</span>
        </el-form-item>

        <el-form-item label="销售方">
          <span>{{ currentInvoice.seller_name || '--' }}</span>
        </el-form-item>

        <el-form-item label="异常类型" prop="abnormal_status">
          <el-radio-group v-model="abnormalForm.abnormal_status">
            <el-radio :label="0">正常 (解除异常)</el-radio>
            <el-radio :label="1"><span class="text-danger">作废</span></el-radio>
            <el-radio :label="2"><span class="text-danger">红冲</span></el-radio>
            <el-radio :label="3"><span class="text-danger">假票</span></el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item
          label="异常原因"
          prop="abnormal_reason"
          :rules="abnormalForm.abnormal_status > 0 ? [{ required: true, message: '请填写异常原因备注', trigger: 'blur' }] : []"
        >
          <el-input
            v-model="abnormalForm.abnormal_reason"
            type="textarea"
            :rows="3"
            placeholder="请详细说明标记异常的具体原因（如税局核实作废、重复报销红冲单等）"
          />
        </el-form-item>

        <el-alert
          v-if="abnormalForm.abnormal_status > 0"
          title="标记为异常状态的发票将被自动标红拦截，禁止用于后续任何报销流程！"
          type="warning"
          show-icon
          :closable="false"
          class="mt10"
        />
      </el-form>

      <div slot="footer">
        <el-button size="small" @click="abnormalDialogVisible = false">取消</el-button>
        <el-button size="small" type="primary" :loading="savingAbnormal" @click="submitAbnormal">确定</el-button>
      </div>
    </el-dialog>

    <!-- 原件弹窗预览 -->
    <el-dialog
      :title="`发票原件预览 - ${currentPreview.invoice_number || ''}`"
      :visible.sync="previewDialogVisible"
      :width="isMobile ? '96%' : '860px'"
      append-to-body
      :top="isMobile ? '4vh' : '5vh'"
      class="invoice-preview-dialog"
    >
      <div class="preview-container">
        <div v-if="isPreviewPdf" class="pdf-box">
          <iframe
            :src="previewUrl"
            width="100%"
            height="580px"
            frameborder="0"
          ></iframe>
        </div>
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

    <!-- 发票详情抽屉 -->
    <el-drawer
      title="发票台账全要素详情"
      :visible.sync="detailDrawerVisible"
      :size="isMobile ? '92%' : '540px'"
      append-to-body
    >
      <div class="drawer-content p20" v-if="drawerInvoice.id">
        <div class="detail-header-card mb20">
          <div class="acea-row row-between row-middle">
            <el-tag size="small" :type="getInvoiceTagType(drawerInvoice.invoice_type)">
              {{ getInvoiceTypeName(drawerInvoice.invoice_type) }}
            </el-tag>
            <div>
              <el-tag v-if="drawerInvoice.abnormal_status > 0" size="small" type="danger" effect="dark">
                {{ getAbnormalName(drawerInvoice.abnormal_status) }}
              </el-tag>
              <el-tag v-else size="small" type="success">正常</el-tag>
            </div>
          </div>
          <div class="drawer-amount mt15">
            <span class="currency">¥</span>
            <span class="amount-number">{{ formatMoney(drawerInvoice.total_amount) }}</span>
            <span class="amount-unit">价税合计</span>
          </div>
          <div class="drawer-sub-amount acea-row row-between mt5">
            <span>不含税金额：¥{{ formatMoney(drawerInvoice.amount) }}</span>
            <span>税额：¥{{ formatMoney(drawerInvoice.tax_amount) }}</span>
          </div>
        </div>

        <el-descriptions title="发票基本要素" :column="1" border size="small" class="mb20">
          <el-descriptions-item label="发票号码">
            <span class="font-mono font-bold">{{ drawerInvoice.invoice_number }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="发票代码">
            <span class="font-mono">{{ drawerInvoice.invoice_code || '-' }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="开票日期">
            {{ drawerInvoice.invoice_date || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="销售方名称">
            {{ drawerInvoice.seller_name || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="购买方名称">
            {{ drawerInvoice.buyer_name || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="报销说明备注">
            {{ drawerInvoice.mark || '--' }}
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="报销与流转状态" :column="1" border size="small" class="mb20">
          <el-descriptions-item label="上传员工">
            {{ drawerInvoice.user_name || (drawerInvoice.card || {}).name || `用户ID ${drawerInvoice.uid}` }}
          </el-descriptions-item>
          <el-descriptions-item label="所属部门">
            {{ drawerInvoice.dep_name || ((drawerInvoice.card || {}).frame || {}).name || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="业务状态">
            <el-tag v-if="drawerInvoice.status === 0" size="mini" type="success">待报销 (未绑定)</el-tag>
            <el-tag v-else-if="drawerInvoice.status === 1" size="mini" type="warning">审批中 (已绑定)</el-tag>
            <el-tag v-else-if="drawerInvoice.status === 2" size="mini" type="info">已报销归档</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="关联报销单号" v-if="drawerInvoice.apply_id">
            <el-link type="primary" @click="viewApprovalDetail(drawerInvoice.apply_id)">
              查看审批单 #{{ drawerInvoice.apply_id }}
            </el-link>
          </el-descriptions-item>
          <el-descriptions-item label="录入时间">
            {{ drawerInvoice.created_at || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="报销时间" v-if="drawerInvoice.reimbursed_at">
            {{ drawerInvoice.reimbursed_at }}
          </el-descriptions-item>
          <el-descriptions-item label="异常原因" v-if="drawerInvoice.abnormal_status > 0">
            <span class="text-danger">{{ drawerInvoice.abnormal_reason }}</span>
          </el-descriptions-item>
        </el-descriptions>

        <div v-if="drawerInvoice.file_path" class="drawer-preview-btn">
          <el-button type="primary" size="small" icon="el-icon-view" style="width: 100%" @click="previewInvoice(drawerInvoice)">
            查看发票原件 (PDF/图片)
          </el-button>
        </div>
      </div>
    </el-drawer>

    <!-- 审批详情弹窗 -->
    <detail-examine ref="detailExamine" />

    <!-- 导出 Excel 辅助组件 -->
    <export-excel
      ref="exportExcel"
      :save-name="`企业进项发票台账_${$moment().format('YYYYMMDDHHmmss')}.xlsx`"
      :export-data="excelExportData"
    />
  </div>
</template>

<script>
import {
  getExpenseInvoiceFinanceListApi,
  markExpenseInvoiceAbnormalApi,
  exportExpenseInvoiceFinanceApi
} from '@/api/expenseInvoice'
import { processResourceUrl } from '@/utils/resourceUtil'
import { mapInvoiceStat } from '@/utils/invoiceStat'

export default {
  name: 'FinanceInvoiceIndex',
  components: {
    detailExamine: () => import('@/views/user/examine/components/detailExamine'),
    exportExcel: () => import('@/components/common/exportExcel')
  },
  data() {
    return {
      isMobile: window.innerWidth < 768,
      viewMode: window.innerWidth < 768 ? 'card' : 'table',
      loading: false,
      exporting: false,
      tableData: [],
      total: 0,
      dateRange: [],
      where: {
        page: 1,
        limit: 15,
        status: '',
        abnormal_status: '',
        invoice_type: '',
        start_date: '',
        end_date: '',
        keyword: '',
        keyword_user: '',
        dep_name: ''
      },
      stat: {
        totalCount: 0,
        totalAmount: 0,
        unboundCount: 0,
        unboundAmount: 0,
        bindingCount: 0,
        bindingAmount: 0,
        reimbursedCount: 0,
        reimbursedAmount: 0,
        abnormalCount: 0,
        abnormalAmount: 0
      },
      // 标记异常弹窗
      abnormalDialogVisible: false,
      savingAbnormal: false,
      currentInvoice: {},
      abnormalForm: {
        abnormal_status: 1,
        abnormal_reason: ''
      },
      abnormalRules: {
        abnormal_status: [{ required: true, message: '请选择异常状态', trigger: 'change' }]
      },
      // 原件预览
      previewDialogVisible: false,
      currentPreview: {},
      previewUrl: '',
      // 详情抽屉
      detailDrawerVisible: false,
      drawerInvoice: {},
      // 导出数据
      excelExportData: {
        data: []
      }
    }
  },
  computed: {
    isPreviewPdf() {
      const path = (this.currentPreview.file_path || '').toLowerCase()
      const type = (this.currentPreview.file_type || '').toLowerCase()
      return path.endsWith('.pdf') || type.includes('pdf')
    }
  },
  created() {
    this.getTableData()
  },
  mounted() {
    window.addEventListener('resize', this.handleResize)
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.handleResize)
  },
  methods: {
    onStaticImgError(e) {
      var target = e && e.target ? e.target : null
      if (!target || target.getAttribute('data-oa-fallback')) {
        return
      }
      target.setAttribute('data-oa-fallback', '1')
      target.style.display = 'none'
    },
    async getTableData() {
      this.loading = true
      try {
        const params = {
          ...this.where,
          status: this.where.status !== '' ? this.where.status : undefined,
          abnormal_status: this.where.abnormal_status !== '' ? this.where.abnormal_status : undefined
        }
        const res = await getExpenseInvoiceFinanceListApi(params)
        const data = res.data || {}
        this.tableData = data.list || []
        this.total = data.count || data.total || 0

        if (data.stat) {
          this.stat = mapInvoiceStat(data.stat)
        } else {
          this.calcLocalStat()
        }
      } catch (err) {
        this.$message.error('获取台账列表失败')
      } finally {
        this.loading = false
      }
    },

    calcLocalStat() {
      let totalCount = this.total
      let totalAmount = 0
      let unboundCount = 0
      let unboundAmount = 0
      let bindingCount = 0
      let bindingAmount = 0
      let reimbursedCount = 0
      let reimbursedAmount = 0
      let abnormalCount = 0
      let abnormalAmount = 0

      this.tableData.forEach((item) => {
        const amt = parseFloat(item.total_amount) || 0
        totalAmount += amt
        if (item.status === 0) {
          unboundCount++
          unboundAmount += amt
        } else if (item.status === 1) {
          bindingCount++
          bindingAmount += amt
        } else if (item.status === 2) {
          reimbursedCount++
          reimbursedAmount += amt
        }
        if (item.abnormal_status > 0) {
          abnormalCount++
          abnormalAmount += amt
        }
      })

      this.stat = {
        totalCount,
        totalAmount,
        unboundCount,
        unboundAmount,
        bindingCount,
        bindingAmount,
        reimbursedCount,
        reimbursedAmount,
        abnormalCount,
        abnormalAmount
      }
    },

    handleSearch() {
      this.where.page = 1
      this.getTableData()
    },

    handleReset() {
      this.dateRange = []
      this.where = {
        page: 1,
        limit: this.where.limit,
        status: '',
        abnormal_status: '',
        invoice_type: '',
        start_date: '',
        end_date: '',
        keyword: '',
        keyword_user: '',
        dep_name: ''
      }
      this.getTableData()
    },

    handleDateChange(val) {
      if (val && val.length === 2) {
        this.where.start_date = val[0]
        this.where.end_date = val[1]
      } else {
        this.where.start_date = ''
        this.where.end_date = ''
      }
      this.handleSearch()
    },

    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },

    handlePageChange(val) {
      this.where.page = val
      this.getTableData()
    },

    tableRowClassName({ row }) {
      if (row.abnormal_status > 0) {
        return 'abnormal-table-row'
      }
      return ''
    },

    openAbnormalDialog(row) {
      this.currentInvoice = row
      this.abnormalForm = {
        abnormal_status: row.abnormal_status > 0 ? 0 : 1, // 若已异常默认选择解除异常，否则默认作废
        abnormal_reason: row.abnormal_reason || ''
      }
      this.abnormalDialogVisible = true
      this.$nextTick(() => {
        if (this.$refs.abnormalForm) {
          this.$refs.abnormalForm.clearValidate()
        }
      })
    },

    async submitAbnormal() {
      this.$refs.abnormalForm.validate(async (valid) => {
        if (!valid) return

        this.savingAbnormal = true
        try {
          const res = await markExpenseInvoiceAbnormalApi({
            id: this.currentInvoice.id,
            abnormal_status: this.abnormalForm.abnormal_status,
            abnormal_reason: this.abnormalForm.abnormal_reason
          })
          if (res.status !== 200) return
          this.$message.success(
            this.abnormalForm.abnormal_status > 0 ? '发票已标记为异常预警状态' : '已成功解除发票异常状态'
          )
          this.abnormalDialogVisible = false
          this.getTableData()
        } catch (err) {
          this.$message.error(err.message || '标记失败')
        } finally {
          this.savingAbnormal = false
        }
      })
    },

    openDetailDrawer(row) {
      this.drawerInvoice = row
      this.detailDrawerVisible = true
    },

    viewApprovalDetail(applyId) {
      if (!applyId) return
      this.$refs.detailExamine.openBox({ id: applyId })
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

    // 导出 Excel
    async handleExport() {
      this.exporting = true
      try {
        const params = {
          ...this.where,
          page: 1,
          limit: 2000,
          status: this.where.status !== '' ? this.where.status : undefined,
          abnormal_status: this.where.abnormal_status !== '' ? this.where.abnormal_status : undefined
        }

        // 尝试调用后端导出接口
        let exportRows = []
        try {
          const res = await exportExpenseInvoiceFinanceApi(params)
          if (res.status !== 200) return
          if (res.data && res.data.url) {
            window.open(res.data.url, '_blank')
            return
          } else if (res.data && Array.isArray(res.data.list)) {
            exportRows = res.data.list
          } else if (Array.isArray(res.data)) {
            exportRows = res.data
          }
        } catch (e) {
          // fallback to client export
        }

        if (exportRows.length === 0) {
          // 若后端未返回二进制/URL，则请求全量列表数据进行前端导出
          const listRes = await getExpenseInvoiceFinanceListApi(params)
          if (listRes.status !== 200) return
          exportRows = (listRes.data && listRes.data.list) || this.tableData
        }

        // 构建 AOA 格式数据
        const header = [
          '发票号码',
          '发票代码',
          '发票类型',
          '价税合计(元)',
          '金额(不含税)',
          '税额(元)',
          '开票日期',
          '销售方名称',
          '购买方名称',
          '报销人',
          '所属部门',
          '报销状态',
          '异常状态',
          '异常原因',
          '关联审批单号',
          '录入时间'
        ]

        const dataRows = exportRows.map((item) => {
          return [
            item.invoice_number,
            item.invoice_code || '',
            this.getInvoiceTypeName(item.invoice_type),
            this.formatMoney(item.total_amount),
            this.formatMoney(item.amount),
            this.formatMoney(item.tax_amount),
            item.invoice_date || '',
            item.seller_name || '',
            item.buyer_name || '',
            item.user_name || (item.card && item.card.name) || '',
            item.dep_name || (item.card && item.card.frame && item.card.frame.name) || '',
            item.status === 0 ? '待报销' : (item.status === 1 ? '审批中' : '已报销'),
            item.abnormal_status > 0 ? this.getAbnormalName(item.abnormal_status) : '正常',
            item.abnormal_reason || '',
            item.apply_id ? `#${item.apply_id}` : '',
            item.created_at || ''
          ]
        })

        this.excelExportData = {
          data: [header, ...dataRows]
        }

        this.$nextTick(() => {
          this.$refs.exportExcel.exportExcel()
          this.$message.success('进项发票台账导出成功！')
        })
      } catch (err) {
        this.$message.error('导出失败，请重试')
      } finally {
        this.exporting = false
      }
    },

    copyText(text) {
      if (!text) return
      this.$copyText(text).then(() => {
        this.$message.success('已复制发票号码')
      })
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
    },

    getAbnormalName(code) {
      const map = {
        1: '作废',
        2: '红冲',
        3: '假票'
      }
      return map[code] || '异常'
    },

    handleResize() {
      const mobile = window.innerWidth < 768
      if (this.isMobile !== mobile) {
        this.isMobile = mobile
        if (mobile && this.viewMode === 'table') {
          this.viewMode = 'card'
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.finance-invoice-container {
  .stat-row {
    .stat-card {
      border-radius: 8px;
      .stat-inner {
        padding: 4px;
      }
      .stat-title {
        font-size: 13px;
        color: #909399;
        margin-bottom: 6px;
      }
      .stat-val {
        font-size: 22px;
        font-weight: bold;
        color: #303133;
        font-family: 'Helvetica Neue', Helvetica, sans-serif;
        .unit {
          font-size: 12px;
          font-weight: normal;
          color: #909399;
        }
      }
      .stat-desc {
        font-size: 12px;
        color: #606266;
        margin-top: 4px;
      }
      &.abnormal-card {
        border-color: #ffa39e;
        background: #fff1f0;
      }
    }
  }

  .header-box {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
  }

  .page-title {
    font-size: 16px;
    font-weight: bold;
    color: #303133;
  }
  .sub-title {
    font-size: 12px;
    color: #909399;
  }

  .price-val {
    color: #f5222d;
    font-weight: 600;
    font-family: 'Helvetica Neue', Helvetica, monospace;
  }

  .font-mono {
    font-family: Menlo, Monaco, Consolas, monospace;
  }

  .copy-btn {
    color: #409EFF;
    font-size: 13px;
    &:hover {
      color: #66b1ff;
    }
  }

  .user-cell {
    .user-name {
      font-weight: 500;
      color: #303133;
    }
    .dep-name {
      font-size: 11px;
      margin-left: 4px;
    }
  }

  /deep/ .abnormal-table-row {
    background: #fff8f8 !important;
  }

  .card-grid {
    .finance-invoice-card {
      background: #ffffff;
      border: 1px solid #e8e8e8;
      border-radius: 8px;
      padding: 14px 16px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
      transition: all 0.25s ease;
      position: relative;
      overflow: hidden;

      &:hover {
        border-color: #409EFF;
        box-shadow: 0 4px 14px rgba(64, 158, 255, 0.15);
      }

      &.is-abnormal {
        border-color: #ff4d4f;
        background: #fffcfc;
      }
    }

    .abnormal-banner {
      background: #ff4d4f;
      color: #fff;
      font-size: 11px;
      padding: 3px 8px;
      border-radius: 4px;
      margin-bottom: 8px;
    }

    .card-amount-box {
      background: #fbfcfe;
      border-radius: 6px;
      padding: 8px 12px;
      border: 1px dashed #e1e9f4;

      .amount-label {
        font-size: 11px;
        color: #8c8c8c;
      }
      .amount-val {
        font-size: 22px;
        font-weight: bold;
        color: #f5222d;
        font-family: 'Helvetica Neue', Helvetica, sans-serif;
        .currency {
          font-size: 14px;
          margin-right: 2px;
        }
      }
      .sub-amount-row {
        font-size: 11px;
        color: #8c8c8c;
        margin-top: 4px;
      }
    }

    .card-info-list {
      font-size: 12px;
      line-height: 22px;

      .info-row {
        .info-title {
          color: #8c8c8c;
          width: 70px;
          flex-shrink: 0;
        }
        .info-content {
          color: #262626;
          flex: 1;
        }
      }
    }

    .card-footer {
      border-top: 1px solid #f0f0f0;
    }
  }

  .table-view-box {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .empty-box {
    padding: 60px 0;
    .empty-img {
      width: 160px;
    }
    .empty-text {
      color: #8c8c8c;
      margin: 14px 0 16px 0;
      font-size: 14px;
    }
  }

  .ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.drawer-content {
  .detail-header-card {
    background: #f8faff;
    border: 1px solid #e1e9f4;
    border-radius: 8px;
    padding: 16px;

    .drawer-amount {
      color: #f5222d;
      font-family: 'Helvetica Neue', Helvetica, sans-serif;
      .currency {
        font-size: 18px;
        margin-right: 2px;
      }
      .amount-number {
        font-size: 28px;
        font-weight: bold;
      }
      .amount-unit {
        font-size: 12px;
        color: #8c8c8c;
        margin-left: 6px;
      }
    }

    .drawer-sub-amount {
      font-size: 12px;
      color: #606266;
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

@media (max-width: 767px) {
  .finance-invoice-container {
    .stat-row {
      margin-left: 0 !important;
      margin-right: 0 !important;
      .stat-col {
        margin-bottom: 10px;
        padding-left: 3px !important;
        padding-right: 3px !important;
      }
      .stat-card {
        .stat-inner {
          padding: 4px 2px;
        }
        .stat-title {
          font-size: 12px;
          margin-bottom: 4px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .stat-val {
          font-size: 17px;
          .unit {
            font-size: 11px;
          }
        }
        .stat-desc {
          font-size: 11px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
      }
    }

    .normal-page {
      /deep/ .el-card__body {
        padding: 12px !important;
      }
    }

    .header-box {
      flex-direction: column;
      align-items: flex-start !important;
      .title-section {
        width: 100%;
        margin-bottom: 4px;
        .sub-title {
          display: none;
        }
      }
      .action-section {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        .mr15 {
          margin-right: 0 !important;
        }
        .el-button {
          flex: 1;
          min-width: 105px;
          margin-left: 0 !important;
        }
      }
    }

    .filter-box {
      .demo-form-inline {
        .el-form-item {
          display: flex;
          flex-direction: column;
          width: 100%;
          margin-right: 0;
          margin-bottom: 12px;

          /deep/ .el-form-item__label {
            text-align: left;
            padding-bottom: 4px;
            line-height: 1.4;
          }
          /deep/ .el-form-item__content {
            width: 100%;
          }
          .el-select,
          .el-date-editor,
          .el-input,
          .el-range-editor {
            width: 100% !important;
          }
        }
        .filter-btn-row {
          display: flex;
          gap: 10px;
          width: 100%;
          .el-button {
            flex: 1;
            margin-left: 0 !important;
          }
        }
      }
    }

    .card-grid {
      .finance-invoice-card {
        padding: 12px;
        .card-amount-box .amount-val {
          font-size: 20px;
        }
        .card-footer {
          .btn-group {
            display: flex;
            gap: 8px;
            .el-button {
              padding: 5px 0;
              margin-left: 0;
            }
          }
        }
      }
    }

    .pagination-box {
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

  /deep/ .abnormal-dialog {
    .el-dialog {
      margin-bottom: 20px;
    }
    .el-dialog__body {
      padding: 14px 16px;
    }
  }

  /deep/ .invoice-preview-dialog {
    .el-dialog__body {
      padding: 10px;
    }
    .preview-container {
      min-height: 240px;
      max-height: 70vh;
      .pdf-box {
        height: 380px;
      }
      .img-box .preview-img {
        max-height: 380px;
      }
    }
  }

  .drawer-content {
    padding: 14px !important;
    .detail-header-card {
      padding: 12px;
      .drawer-amount .amount-number {
        font-size: 22px;
      }
    }
  }
}
</style>
