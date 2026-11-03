<template>
  <div class="divBox invoice-my-container">
    <!-- 顶部概览统计卡片 -->
    <el-row :gutter="14" class="mb14 statistics-cards">
      <el-col :xs="12" :sm="12" :md="6" :lg="6" class="stat-col">
        <el-card shadow="never" class="stat-card stat-total">
          <div class="stat-inner acea-row row-between row-middle">
            <div>
              <div class="stat-label">发票总览</div>
              <div class="stat-value">{{ stat.totalCount }} <span class="unit">张</span></div>
              <div class="stat-sub">总金额 ¥{{ formatMoney(stat.totalAmount) }}</div>
            </div>
            <div class="stat-icon-wrapper total-bg">
              <i class="el-icon-tickets stat-icon"></i>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="12" :sm="12" :md="6" :lg="6" class="stat-col">
        <el-card shadow="never" class="stat-card stat-unbound">
          <div class="stat-inner acea-row row-between row-middle">
            <div>
              <div class="stat-label">待报销 (未绑定)</div>
              <div class="stat-value text-success">{{ stat.unboundCount }} <span class="unit">张</span></div>
              <div class="stat-sub">可报销 ¥{{ formatMoney(stat.unboundAmount) }}</div>
            </div>
            <div class="stat-icon-wrapper success-bg">
              <i class="el-icon-wallet stat-icon"></i>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="12" :sm="12" :md="6" :lg="6" class="stat-col">
        <el-card shadow="never" class="stat-card stat-binding">
          <div class="stat-inner acea-row row-between row-middle">
            <div>
              <div class="stat-label">报销审批中</div>
              <div class="stat-value text-warning">{{ stat.bindingCount }} <span class="unit">张</span></div>
              <div class="stat-sub">在途金额 ¥{{ formatMoney(stat.bindingAmount) }}</div>
            </div>
            <div class="stat-icon-wrapper warning-bg">
              <i class="el-icon-time stat-icon"></i>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="12" :sm="12" :md="6" :lg="6" class="stat-col">
        <el-card shadow="never" class="stat-card stat-reimbursed">
          <div class="stat-inner acea-row row-between row-middle">
            <div>
              <div class="stat-label">已报销归档</div>
              <div class="stat-value text-info">{{ stat.reimbursedCount }} <span class="unit">张</span></div>
              <div class="stat-sub">归档总额 ¥{{ formatMoney(stat.reimbursedAmount) }}</div>
            </div>
            <div class="stat-icon-wrapper info-bg">
              <i class="el-icon-circle-check stat-icon"></i>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 主卡片区 -->
    <el-card :body-style="{ padding: '20px' }" class="normal-page main-card">
      <!-- 页面标头与操作按钮 -->
      <div class="header-box acea-row row-between row-middle mb20">
        <div class="title-section acea-row row-middle">
          <span class="page-title">我的发票夹</span>
          <span class="sub-title text-muted ml10">集中归集个人电子发票，报销时一键调用防重复</span>
        </div>
        <div class="action-section acea-row row-middle">
          <el-radio-group v-model="viewMode" size="small" class="mr15">
            <el-radio-button label="card"><i class="el-icon-menu"></i> 卡片视图</el-radio-button>
            <el-radio-button label="table"><i class="el-icon-s-grid"></i> 列表视图</el-radio-button>
          </el-radio-group>

          <el-button
            type="primary"
            size="small"
            icon="el-icon-upload2"
            @click="openUploadDialog"
          >
            上传/解析发票
          </el-button>

          <el-button
            type="default"
            size="small"
            icon="el-icon-edit-outline"
            @click="openManualAddDialog"
          >
            手动补录
          </el-button>
        </div>
      </div>

      <!-- 搜索筛选表单 -->
      <div class="search-filter-box mb20">
        <el-form :inline="true" size="small" class="demo-form-inline">
          <el-form-item label="发票状态">
            <el-select v-model="where.status" placeholder="全部状态" clearable style="width: 130px" @change="handleSearch">
              <el-option label="全部状态" value="" />
              <el-option label="未绑定(待报销)" :value="0" />
              <el-option label="已绑定(审批中)" :value="1" />
              <el-option label="已报销" :value="2" />
            </el-select>
          </el-form-item>

          <el-form-item label="发票类型">
            <el-select v-model="where.invoice_type" placeholder="全部类型" clearable style="width: 150px" @change="handleSearch">
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

          <el-form-item label="金额范围">
            <div class="acea-row row-middle amount-range-row">
              <el-input-number
                v-model="where.min_amount"
                :controls="false"
                placeholder="最小金额"
                style="width: 100px"
                :precision="2"
                :min="0"
              />
              <span class="mx5">-</span>
              <el-input-number
                v-model="where.max_amount"
                :controls="false"
                placeholder="最大金额"
                style="width: 100px"
                :precision="2"
                :min="0"
              />
            </div>
          </el-form-item>

          <el-form-item label="关键字">
            <el-input
              v-model="where.keyword"
              placeholder="发票号码/代码/销售方"
              clearable
              style="width: 180px"
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

      <!-- 内容展示区 -->
      <div v-loading="loading" class="content-section" :style="{ minHeight: '380px' }">
        <!-- 空状态 -->
        <div v-if="!loading && tableData.length === 0" class="empty-box acea-row row-column row-center row-middle">
          <img src="../../../assets/images/defd.png" alt="暂无发票" class="empty-img" />
          <p class="empty-text">发票夹暂无相关发票数据</p>
          <el-button type="primary" size="small" icon="el-icon-upload2" @click="openUploadDialog">
            立即上传发票
          </el-button>
        </div>

        <!-- 模式 1：卡片网格视图 -->
        <div v-else-if="viewMode === 'card'" class="card-grid">
          <el-row :gutter="16">
            <el-col :xs="24" :sm="12" :md="8" :lg="8" v-for="item in tableData" :key="item.id" class="mb16">
              <div class="invoice-card" :class="{ 'is-abnormal': item.abnormal_status > 0 }">
                <!-- 异常警示条 -->
                <div v-if="item.abnormal_status > 0" class="abnormal-banner acea-row row-middle">
                  <i class="el-icon-warning mr5"></i>
                  <span>异常【{{ getAbnormalName(item.abnormal_status) }}】：{{ item.abnormal_reason || '存在异常风险' }}</span>
                </div>

                <!-- 卡片头部 -->
                <div class="card-header acea-row row-between row-middle">
                  <el-tag size="mini" :type="getInvoiceTagType(item.invoice_type)">
                    {{ getInvoiceTypeName(item.invoice_type) }}
                  </el-tag>
                  <div class="status-badge">
                    <el-tag v-if="item.status === 0" size="mini" type="success" effect="plain">待报销</el-tag>
                    <el-tag v-else-if="item.status === 1" size="mini" type="warning" effect="plain">
                      <i class="el-icon-loading"></i> 审批中
                    </el-tag>
                    <el-tag v-else-if="item.status === 2" size="mini" type="info" effect="plain">
                      <i class="el-icon-lock"></i> 已报销
                    </el-tag>
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
                    <span class="info-title">开票日期：</span>
                    <span class="info-content">{{ item.invoice_date || '--' }}</span>
                  </div>
                  <div class="info-row acea-row">
                    <span class="info-title">销售方：</span>
                    <span class="info-content ellipsis" :title="item.seller_name">{{ item.seller_name || '--' }}</span>
                  </div>
                  <div class="info-row acea-row" v-if="item.mark">
                    <span class="info-title">备注说明：</span>
                    <span class="info-content ellipsis" :title="item.mark">{{ item.mark }}</span>
                  </div>
                </div>

                <!-- 卡片底部操作栏 -->
                <div class="card-footer acea-row row-between row-middle mt15 pt10">
                  <el-button
                    v-if="item.file_path"
                    type="text"
                    size="small"
                    icon="el-icon-view"
                    @click="previewInvoice(item)"
                  >
                    查看原件
                  </el-button>
                  <span v-else class="text-muted font-12">未上传原件</span>

                  <div class="btn-group">
                    <template v-if="item.status === 0">
                      <el-button type="text" size="small" icon="el-icon-edit" @click="handleEdit(item)">编辑</el-button>
                      <el-button type="text" size="small" style="color: #F56C6C;" icon="el-icon-delete" @click="handleDelete(item)">删除</el-button>
                    </template>
                    <template v-else>
                      <el-tooltip :content="item.status === 1 ? '发票处于审批流中，已锁定不可修改' : '发票已报销归档，不可修改'" placement="top">
                        <span class="locked-tip text-muted font-12"><i class="el-icon-lock"></i> 锁定</span>
                      </el-tooltip>
                    </template>
                  </div>
                </div>
              </div>
            </el-col>
          </el-row>
        </div>

        <!-- 模式 2：表格列表视图 -->
        <div v-else class="table-view-box">
          <el-table :data="tableData" border size="small" style="width: 100%">
            <el-table-column type="index" label="序号" width="50" align="center" />

            <el-table-column label="发票类型" min-width="120" show-overflow-tooltip>
              <template slot-scope="{ row }">
                <el-tag size="mini" :type="getInvoiceTagType(row.invoice_type)">
                  {{ getInvoiceTypeName(row.invoice_type) }}
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column prop="invoice_number" label="发票号码" min-width="130">
              <template slot-scope="{ row }">
                <span class="font-mono font-bold">{{ row.invoice_number }}</span>
                <i class="el-icon-document-copy pointer copy-btn ml5" title="点击复制" @click="copyText(row.invoice_number)"></i>
              </template>
            </el-table-column>

            <el-table-column prop="invoice_code" label="发票代码" min-width="110">
              <template slot-scope="{ row }">
                <span class="font-mono">{{ row.invoice_code || '-' }}</span>
              </template>
            </el-table-column>

            <el-table-column prop="total_amount" label="价税合计(元)" min-width="120" align="right">
              <template slot-scope="{ row }">
                <span class="price-text">¥{{ formatMoney(row.total_amount) }}</span>
              </template>
            </el-table-column>

            <el-table-column prop="amount" label="金额" width="100" align="right">
              <template slot-scope="{ row }">¥{{ formatMoney(row.amount) }}</template>
            </el-table-column>

            <el-table-column prop="tax_amount" label="税额" width="90" align="right">
              <template slot-scope="{ row }">¥{{ formatMoney(row.tax_amount) }}</template>
            </el-table-column>

            <el-table-column prop="invoice_date" label="开票日期" width="105" align="center" />

            <el-table-column prop="seller_name" label="销售方名称" min-width="160" show-overflow-tooltip />

            <el-table-column label="状态" width="100" align="center">
              <template slot-scope="{ row }">
                <el-tag v-if="row.status === 0" size="mini" type="success">待报销</el-tag>
                <el-tag v-else-if="row.status === 1" size="mini" type="warning">审批中</el-tag>
                <el-tag v-else-if="row.status === 2" size="mini" type="info">已报销</el-tag>
              </template>
            </el-table-column>

            <el-table-column label="异常状态" width="100" align="center">
              <template slot-scope="{ row }">
                <el-tooltip v-if="row.abnormal_status > 0" :content="row.abnormal_reason || '异常发票'" placement="top">
                  <el-tag size="mini" type="danger">{{ getAbnormalName(row.abnormal_status) }}</el-tag>
                </el-tooltip>
                <span v-else class="text-success font-12"><i class="el-icon-check"></i> 正常</span>
              </template>
            </el-table-column>

            <el-table-column label="原件" width="75" align="center">
              <template slot-scope="{ row }">
                <el-button v-if="row.file_path" type="text" size="small" icon="el-icon-view" @click="previewInvoice(row)">查看</el-button>
                <span v-else class="text-muted font-12">-</span>
              </template>
            </el-table-column>

            <el-table-column label="操作" width="120" align="center" fixed="right">
              <template slot-scope="{ row }">
                <template v-if="row.status === 0">
                  <el-button type="text" size="small" @click="handleEdit(row)">编辑</el-button>
                  <el-button type="text" size="small" style="color: #F56C6C;" @click="handleDelete(row)">删除</el-button>
                </template>
                <template v-else>
                  <span class="text-muted font-12"><i class="el-icon-lock"></i> 锁定</span>
                </template>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- 分页 -->
        <div class="pagination-box mt20 acea-row row-right">
          <el-pagination
            :current-page="where.page"
            :page-sizes="[12, 24, 36, 48]"
            :page-size="where.limit"
            layout="total, sizes, prev, pager, next, jumper"
            :total="total"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </div>
    </el-card>

    <!-- 发票上传与智能解析/手动录入弹窗 -->
    <el-dialog
      :title="dialogMode === 'upload' ? '发票上传与智能解析' : (form.id ? '编辑发票信息' : '手动补录发票')"
      :visible.sync="editDialogVisible"
      :width="isMobile ? '95%' : '680px'"
      :top="isMobile ? '4vh' : '8vh'"
      :close-on-click-modal="false"
      class="invoice-edit-dialog"
    >
      <!-- 上传拖拽区域（仅 upload 模式展示） -->
      <div v-if="dialogMode === 'upload' && !hasParsed" class="upload-drop-zone mb20">
        <el-upload
          drag
          action="#"
          :show-file-list="false"
          :auto-upload="false"
          :on-change="handleUploadFileChange"
          accept=".pdf,.png,.jpg,.jpeg"
          class="invoice-uploader"
        >
          <i class="el-icon-upload"></i>
          <div class="el-upload__text">
            将发票文件拖到此处，或<em>点击上传</em>
          </div>
          <div class="el-upload__tip" slot="tip">
            支持标准 PDF 增值税电子发票、全电发票及发票高清图片，单文件不超过 15MB
          </div>
        </el-upload>

        <div v-if="parsing" class="parsing-overlay acea-row row-column row-center row-middle">
          <i class="el-icon-loading parsing-spinner"></i>
          <div class="parsing-text mt10">正在智能提取发票代码、号码、金额、税额、销方信息...</div>
        </div>
      </div>

      <!-- 解析/录入表单 -->
      <div v-if="dialogMode !== 'upload' || hasParsed" class="form-content">
        <!-- 查重预警信息条 -->
        <el-alert
          v-if="duplicateWarning"
          :title="duplicateWarning"
          type="error"
          show-icon
          class="mb15"
          :closable="false"
        />

        <el-alert
          v-if="dialogMode === 'upload' && hasParsed && hasExtracted"
          title="发票要素已智能识别，请核对以下字段信息无误后保存："
          type="success"
          show-icon
          class="mb15"
          :closable="false"
        />
        <el-alert
          v-else-if="dialogMode === 'upload' && hasParsed"
          title="原件已上传，请手动补录发票要素后再保存"
          type="info"
          show-icon
          class="mb15"
          :closable="false"
        />

        <el-form ref="invoiceForm" :model="form" :rules="rules" :label-width="isMobile ? '90px' : '110px'" :label-position="isMobile ? 'top' : 'right'" size="small">
          <el-row :gutter="16">
            <el-col :span="12" :xs="24">
              <el-form-item label="发票类型" prop="invoice_type">
                <el-select v-model="form.invoice_type" placeholder="请选择发票类型" style="width: 100%">
                  <el-option label="增值税专用发票" value="special" />
                  <el-option label="增值税普通发票" value="general" />
                  <el-option label="增值税电子普通发票" value="electronic" />
                  <el-option label="全电专用发票" value="digital_special" />
                  <el-option label="全电普通发票" value="digital_general" />
                  <el-option label="通行费发票" value="toll" />
                  <el-option label="定额发票" value="quota" />
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24">
              <el-form-item label="发票号码" prop="invoice_number">
                <el-input
                  v-model="form.invoice_number"
                  placeholder="请输入发票号码"
                  @blur="checkDuplicate"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12" :xs="24">
              <el-form-item label="发票代码" prop="invoice_code">
                <el-input
                  v-model="form.invoice_code"
                  placeholder="全电发票可为空"
                  @blur="checkDuplicate"
                />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24">
              <el-form-item label="开票日期" prop="invoice_date">
                <el-date-picker
                  v-model="form.invoice_date"
                  type="date"
                  value-format="yyyy-MM-dd"
                  placeholder="选择开票日期"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12" :xs="24">
              <el-form-item label="价税合计(元)" prop="total_amount">
                <el-input-number
                  v-model="form.total_amount"
                  :precision="2"
                  :min="0"
                  :controls="false"
                  placeholder="0.00"
                  style="width: 100%"
                  @change="handleTotalAmountChange"
                />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24">
              <el-form-item label="金额(不含税)" prop="amount">
                <el-input-number
                  v-model="form.amount"
                  :precision="2"
                  :min="0"
                  :controls="false"
                  placeholder="0.00"
                  style="width: 100%"
                  @change="handleAmountChange"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12" :xs="24">
              <el-form-item label="税额(元)" prop="tax_amount">
                <el-input-number
                  v-model="form.tax_amount"
                  :precision="2"
                  :min="0"
                  :controls="false"
                  placeholder="0.00"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24">
              <el-form-item label="销售方名称" prop="seller_name">
                <el-input v-model="form.seller_name" placeholder="请输入销售方单位全称" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12" :xs="24">
              <el-form-item label="购买方名称">
                <el-input v-model="form.buyer_name" placeholder="购买方企业或个人名称" />
              </el-form-item>
            </el-col>

            <el-col :span="12" :xs="24">
              <el-form-item label="发票原件文件">
                <div class="acea-row row-middle">
                  <span v-if="form.file_name" class="font-12 text-muted ellipsis" style="max-width: 160px;" :title="form.file_name">
                    {{ form.file_name }}
                  </span>
                  <el-upload
                    action="#"
                    :show-file-list="false"
                    :auto-upload="false"
                    :on-change="handleFileOnlyChange"
                    accept=".pdf,.png,.jpg,.jpeg"
                    class="ml5"
                  >
                    <el-button size="mini" type="text">替换文件</el-button>
                  </el-upload>
                </div>
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="报销事由备注">
            <el-input
              v-model="form.mark"
              type="textarea"
              :rows="2"
              placeholder="填写费用说明或项目事由（选填）"
            />
          </el-form-item>
        </el-form>
      </div>

      <div slot="footer" class="acea-row row-between row-middle">
        <div>
          <el-button v-if="dialogMode === 'upload' && hasParsed" size="small" @click="reUpload">
            重新上传文件
          </el-button>
        </div>
        <div>
          <el-button size="small" @click="editDialogVisible = false">取消</el-button>
          <el-button
            v-if="dialogMode !== 'upload' || hasParsed"
            type="primary"
            size="small"
            :loading="submitting"
            @click="submitInvoiceForm"
          >
            保存入库
          </el-button>
        </div>
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
  </div>
</template>

<script>
import {
  getExpenseInvoiceMyListApi,
  addExpenseInvoiceMyApi,
  updateExpenseInvoiceMyApi,
  deleteExpenseInvoiceMyApi,
  parseExpenseInvoiceApi,
  checkExpenseInvoiceDuplicateApi
} from '@/api/expenseInvoice'
import { processResourceUrl } from '@/utils/resourceUtil'
import { mapInvoiceStat } from '@/utils/invoiceStat'

export default {
  name: 'MyInvoiceIndex',
  data() {
    return {
      isMobile: window.innerWidth < 768,
      viewMode: 'card', // 'card' | 'table'
      loading: false,
      tableData: [],
      total: 0,
      dateRange: [],
      where: {
        page: 1,
        limit: 12,
        status: '',
        invoice_type: '',
        start_date: '',
        end_date: '',
        min_amount: undefined,
        max_amount: undefined,
        keyword: ''
      },
      // 顶部统计
      stat: {
        totalCount: 0,
        totalAmount: 0,
        unboundCount: 0,
        unboundAmount: 0,
        bindingCount: 0,
        bindingAmount: 0,
        reimbursedCount: 0,
        reimbursedAmount: 0
      },
      // 编辑/上传弹窗
      editDialogVisible: false,
      dialogMode: 'upload', // 'upload' | 'manual' | 'edit'
      parsing: false,
      hasParsed: false,
      submitting: false,
      duplicateWarning: '',
      form: {
        id: 0,
        invoice_type: 'electronic',
        invoice_code: '',
        invoice_number: '',
        amount: 0,
        tax_amount: 0,
        total_amount: 0,
        invoice_date: '',
        seller_name: '',
        buyer_name: '',
        file_path: '',
        file_name: '',
        file_type: 'pdf',
        mark: ''
      },
      rules: {
        invoice_type: [{ required: true, message: '请选择发票类型', trigger: 'change' }],
        invoice_number: [{ required: true, message: '请输入发票号码', trigger: 'blur' }],
        total_amount: [{ required: true, message: '请输入价税合计金额', trigger: 'blur' }],
        invoice_date: [{ required: true, message: '请选择开票日期', trigger: 'change' }],
        seller_name: [{ required: true, message: '请输入销售方名称', trigger: 'blur' }]
      },
      // 原件预览
      previewDialogVisible: false,
      currentPreview: {},
      previewUrl: ''
    }
  },
  computed: {
    isPreviewPdf() {
      const path = (this.currentPreview.file_path || '').toLowerCase()
      const type = (this.currentPreview.file_type || '').toLowerCase()
      return path.endsWith('.pdf') || type.includes('pdf')
    },
    // 是否真的从原件里提取到了发票要素（图片或无文本 PDF 只有原件，没有要素）
    hasExtracted() {
      return !!(this.form.invoice_number || this.form.total_amount || this.form.seller_name)
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
    async getTableData() {
      this.loading = true
      try {
        const params = {
          ...this.where,
          status: this.where.status !== '' ? this.where.status : undefined
        }
        const res = await getExpenseInvoiceMyListApi(params)
        const data = res.data || {}
        this.tableData = data.list || []
        this.total = data.count || data.total || 0

        // 若接口返回统计数据则更新，否则前端根据当前列表估算
        if (data.stat) {
          this.stat = mapInvoiceStat(data.stat)
        } else {
          this.calcLocalStat()
        }
      } catch (err) {
        this.$message.error('获取发票列表失败')
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
      })

      this.stat = {
        totalCount,
        totalAmount,
        unboundCount,
        unboundAmount,
        bindingCount,
        bindingAmount,
        reimbursedCount,
        reimbursedAmount
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
        invoice_type: '',
        start_date: '',
        end_date: '',
        min_amount: undefined,
        max_amount: undefined,
        keyword: ''
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

    openUploadDialog() {
      this.dialogMode = 'upload'
      this.hasParsed = false
      this.parsing = false
      this.duplicateWarning = ''
      this.resetForm()
      this.editDialogVisible = true
    },

    openManualAddDialog() {
      this.dialogMode = 'manual'
      this.hasParsed = true
      this.duplicateWarning = ''
      this.resetForm()
      this.editDialogVisible = true
    },

    resetForm() {
      this.form = {
        id: 0,
        invoice_type: 'electronic',
        invoice_code: '',
        invoice_number: '',
        amount: 0,
        tax_amount: 0,
        total_amount: 0,
        invoice_date: '',
        seller_name: '',
        buyer_name: '',
        file_path: '',
        file_name: '',
        file_type: 'pdf',
        mark: ''
      }
      this.$nextTick(() => {
        if (this.$refs.invoiceForm) {
          this.$refs.invoiceForm.clearValidate()
        }
      })
    },

    reUpload() {
      this.hasParsed = false
      this.parsing = false
      this.duplicateWarning = ''
    },

    async handleUploadFileChange(file) {
      const rawFile = file.raw
      if (!rawFile) return

      const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']
      const isAllowed = allowedTypes.includes(rawFile.type) || rawFile.name.endsWith('.pdf')
      if (!isAllowed) {
        this.$message.error('仅支持上传 PDF 格式电子发票或 JPG/PNG 高清图片')
        return
      }

      if (rawFile.size / 1024 / 1024 > 15) {
        this.$message.error('发票原件文件不能超过 15MB')
        return
      }

      this.parsing = true
      const formData = new FormData()
      formData.append('file', rawFile)

      try {
        const res = await parseExpenseInvoiceApi(formData)
        // 业务失败时请求层以 status=400 resolve，错误提示由请求层统一弹出
        if (res.status !== 200) return
        const data = res.data || {}

        this.form = {
          ...this.form,
          invoice_type: data.invoice_type || 'electronic',
          invoice_code: data.invoice_code || '',
          invoice_number: data.invoice_number || '',
          amount: parseFloat(data.amount) || 0,
          tax_amount: parseFloat(data.tax_amount) || 0,
          total_amount: parseFloat(data.total_amount) || 0,
          invoice_date: data.invoice_date || '',
          seller_name: data.seller_name || '',
          buyer_name: data.buyer_name || '',
          file_path: data.file_path || '',
          file_name: data.file_name || rawFile.name,
          file_type: data.file_type || (rawFile.name.endsWith('.pdf') ? 'pdf' : 'image')
        }
        this.hasParsed = true
        if (this.hasExtracted) {
          this.$message.success(res.message || '发票要素已智能识别，请核对')
        } else {
          this.$message.info(res.message || '原件已上传，请手动补录发票要素')
        }
        // 自动触发查重校验
        if (this.form.invoice_number) {
          this.checkDuplicate()
        }
      } catch (err) {
        this.$message.error(err.message || '发票文件解析失败，请检查文件或手动补录')
      } finally {
        this.parsing = false
      }
    },

    handleFileOnlyChange(file) {
      const rawFile = file.raw
      if (!rawFile) return
      this.form.file_name = rawFile.name
      this.form.file_type = rawFile.name.endsWith('.pdf') ? 'pdf' : 'image'
      // 采用独立上传
      const formData = new FormData()
      formData.append('file', rawFile)
      parseExpenseInvoiceApi(formData).then((res) => {
        if (res.status !== 200) return
        if (res.data && res.data.file_path) {
          this.form.file_path = res.data.file_path
          this.$message.success('原件文件已替换')
        }
      })
    },

    async checkDuplicate() {
      if (!this.form.invoice_number) {
        this.duplicateWarning = ''
        return
      }
      try {
        const res = await checkExpenseInvoiceDuplicateApi({
          invoice_code: this.form.invoice_code || '',
          invoice_number: this.form.invoice_number,
          exclude_id: this.form.id || undefined
        })
        if (res.data && res.data.is_duplicate) {
          const exist = res.data.exist_invoice || {}
          this.duplicateWarning = `发票号码【${this.form.invoice_number}】已在系统中存在！录入人：${exist.user_name || '员工'}，录入时间：${exist.created_at || ''}。重复录入将被拒绝！`
        } else {
          this.duplicateWarning = ''
        }
      } catch (e) {
        // ignore
      }
    },

    handleTotalAmountChange(val) {
      if (val && !this.form.amount) {
        // 若无不含税金额，默认等于价税合计
        this.form.amount = val
        this.form.tax_amount = 0
      }
    },

    handleAmountChange(val) {
      if (val && this.form.total_amount && val <= this.form.total_amount) {
        this.form.tax_amount = parseFloat((this.form.total_amount - val).toFixed(2))
      }
    },

    async submitInvoiceForm() {
      this.$refs.invoiceForm.validate(async (valid) => {
        if (!valid) return

        if (this.duplicateWarning) {
          this.$message.error('该发票在系统中已存在，不可重复录入！')
          return
        }

        this.submitting = true
        try {
          let res
          if (this.form.id) {
            res = await updateExpenseInvoiceMyApi(this.form.id, this.form)
            if (res.status !== 200) return
            this.$message.success('发票信息更新成功')
          } else {
            res = await addExpenseInvoiceMyApi(this.form)
            if (res.status !== 200) return
            this.$message.success('发票已成功录入发票夹')
          }
          this.editDialogVisible = false
          this.getTableData()
        } catch (err) {
          this.$message.error(err.message || '保存发票失败')
        } finally {
          this.submitting = false
        }
      })
    },

    handleEdit(row) {
      this.dialogMode = 'edit'
      this.hasParsed = true
      this.duplicateWarning = ''
      this.form = {
        id: row.id,
        invoice_type: row.invoice_type || 'electronic',
        invoice_code: row.invoice_code || '',
        invoice_number: row.invoice_number || '',
        amount: parseFloat(row.amount) || 0,
        tax_amount: parseFloat(row.tax_amount) || 0,
        total_amount: parseFloat(row.total_amount) || 0,
        invoice_date: row.invoice_date || '',
        seller_name: row.seller_name || '',
        buyer_name: row.buyer_name || '',
        file_path: row.file_path || '',
        file_name: row.file_name || '',
        file_type: row.file_type || 'pdf',
        mark: row.mark || ''
      }
      this.editDialogVisible = true
    },

    async handleDelete(row) {
      if (row.status !== 0) {
        this.$message.warning('该发票已被报销单绑定或已报销，不可删除')
        return
      }

      await this.$modalSure(`确定要删除发票【${row.invoice_number}】吗？删除后可重新录入。`)
      try {
        const res = await deleteExpenseInvoiceMyApi(row.id)
        if (res.status !== 200) return
        this.$message.success('发票已删除')
        this.getTableData()
      } catch (err) {
        this.$message.error(err.message || '删除发票失败')
      }
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

    copyText(text) {
      if (!text) return
      this.$copyText(text).then(() => {
        this.$message.success('已复制到剪贴板')
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
        1: '作废发票',
        2: '红冲发票',
        3: '虚假发票'
      }
      return map[code] || '异常'
    },

    handleResize() {
      this.isMobile = window.innerWidth < 768
    }
  }
}
</script>

<style lang="scss" scoped>
.invoice-my-container {
  .statistics-cards {
    .stat-card {
      border-radius: 8px;
      transition: all 0.3s ease;
      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      }
    }
    .stat-inner {
      padding: 6px 4px;
    }
    .stat-label {
      font-size: 13px;
      color: #909399;
      margin-bottom: 4px;
    }
    .stat-value {
      font-size: 24px;
      font-weight: bold;
      color: #303133;
      font-family: 'Helvetica Neue', Helvetica, sans-serif;
      .unit {
        font-size: 13px;
        font-weight: normal;
        color: #909399;
      }
    }
    .stat-sub {
      font-size: 12px;
      color: #606266;
      margin-top: 4px;
    }
    .stat-icon-wrapper {
      width: 48px;
      height: 48px;
      border-radius: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      .stat-icon {
        font-size: 22px;
      }
    }
    .total-bg {
      background: #e6f7ff;
      color: #1890ff;
    }
    .success-bg {
      background: #f6ffed;
      color: #52c41a;
    }
    .warning-bg {
      background: #fffbe6;
      color: #faad14;
    }
    .info-bg {
      background: #f0f5ff;
      color: #2f54eb;
    }
  }

  .main-card {
    border-radius: 8px;

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
  }

  .card-grid {
    .invoice-card {
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

    .price-text {
      color: #f5222d;
      font-weight: 600;
      font-family: 'Helvetica Neue', Helvetica, monospace;
    }
  }

  .copy-btn {
    color: #409EFF;
    font-size: 13px;
    &:hover {
      color: #66b1ff;
    }
  }

  .font-mono {
    font-family: Menlo, Monaco, Consolas, monospace;
  }
}

.upload-drop-zone {
  position: relative;
  .invoice-uploader {
    text-align: center;
    /deep/ .el-upload-dragger {
      width: 100%;
      height: 180px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
  }

  .parsing-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.92);
    z-index: 10;
    border-radius: 6px;
    .parsing-spinner {
      font-size: 32px;
      color: #409EFF;
    }
    .parsing-text {
      font-size: 14px;
      color: #409EFF;
      font-weight: 500;
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

@media (max-width: 767px) {
  .invoice-my-container {
    .statistics-cards {
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
        .stat-label {
          font-size: 12px;
          margin-bottom: 2px;
        }
        .stat-value {
          font-size: 18px;
          .unit {
            font-size: 12px;
          }
        }
        .stat-sub {
          font-size: 11px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .stat-icon-wrapper {
          width: 36px;
          height: 36px;
          min-width: 36px;
          border-radius: 18px;
          .stat-icon {
            font-size: 18px;
          }
        }
      }
    }

    .main-card {
      /deep/ .el-card__body {
        padding: 12px !important;
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
    }

    .search-filter-box {
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
        .amount-range-row {
          display: flex;
          align-items: center;
          width: 100%;
          .el-input-number {
            flex: 1;
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
      .invoice-card {
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

  /deep/ .invoice-edit-dialog {
    .el-dialog {
      margin-bottom: 20px;
    }
    .el-dialog__body {
      padding: 14px 16px;
      max-height: 72vh;
      overflow-y: auto;
    }
    .el-dialog__footer {
      padding: 10px 16px;
      .acea-row {
        flex-wrap: wrap;
        gap: 8px;
      }
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
}
</style>
