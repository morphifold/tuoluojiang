<!-- 新增账目侧滑弹窗  -->
<template>
  <div class="station">
    <el-drawer
      :title="formData.title"
      :visible.sync="drawer"
      :direction="direction"
      :modal="true"
      :wrapper-closable="false"
      :before-close="handleClose"
      size="550px"
      custom-class="fd-bill-edit-drawer"
    >
      <formCreate v-if="rules.length > 0" ref="fc" :option="option" :rule="rules" />

      <div class="button from-foot-btn fix btn-shadow">
        <el-button size="small" @click="drawer = false">{{ $t('public.cancel') }}</el-button>
        <el-button size="small" type="primary" @click="handleConfirm('ruleForm')" :loading="loading">{{
          $t('public.ok')
        }}</el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import formCreate from '@form-create/element-ui'
import request from '@/api/request'
import ueditorFrom from '@/components/form-common/oa-wangeditor'
formCreate.component('ueditorFrom', ueditorFrom)
var fileData = {}
export default {
  name: 'Index',
  components: { formCreate: formCreate.$form() },
  props: {
    title: {
      type: String,
      default: ''
    },
    rolesConfig: {
      type: Array,
      default: () => {
        return []
      }
    },
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    isRequest: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      drawer: false,
      loading: false,
      direction: 'rtl',
      option: {
        form: {
          labelWidth: '90px',
          labelSuffix: '：'
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
          },
          upload: {
            props: {
              onSuccess(res, file) {
                file.url = res.data.url
                fileData = res.data
              },
              onRemove(res, file) {
                fileData = {}
              }
            }
          }
        }
      }, // 表单配置
      rules: []
    }
  },
  watch: {
    rolesConfig: {
      handler(nVal, oVal) {
        this.rules = this.rolesConfig
      },
      deep: true
    }
  },
  methods: {
    handleClose() {
      this.drawer = false
    },
    openBox() {
      this.drawer = true
    },
    // 提交
    handleConfirm() {
      this.$refs.fc.$f.submit((formDatas, fApi) => {
        this.loading = true
        if (this.formData.hasOwnProperty('options')) {
          formDatas = Object.assign(formDatas, this.formData.options)
        }
        if (this.isRequest) {
          formDatas.file_id = fileData.id ? [fileData.id] : []
          request[this.formData.method.toLowerCase()](this.formData.action, formDatas)
            .then((res) => {
              this.loading = false
              this.$emit('isOk')
              this.drawer = false
            })
            .catch((err) => {
              this.loading = false
            })
        } else {
          this.$emit('changData', formDatas)
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.station /deep/.edui-editor-iframeholder {
  height: 300px !important;
}
.station /deep/.el-drawer__body {
  padding: 20px 20px 50px 20px;
}
/deep/.tips {
  font-size: 13px;
  color: #999;
  margin-bottom: 20px;
  margin-left: 75px;
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
/deep/.el-cascader,
/deep/ .el-input-number,
/deep/ .el-select,
/deep/ .el-date-editor {
  width: 100%;
}

@media (max-width: 767px) {
  .station /deep/ .fd-bill-edit-drawer {
    width: 100% !important;
  }

  .station /deep/ .el-drawer__body {
    padding: 12px 12px 64px 12px;
  }

  .station /deep/ .el-col {
    width: 100% !important;
    max-width: 100%;
    flex: 0 0 100%;
  }

  .station /deep/ .el-form-item__label {
    float: none !important;
    display: block;
    width: 100% !important;
    text-align: left !important;
    line-height: 1.4 !important;
    padding: 0 0 4px !important;
  }

  .station /deep/ .el-form-item__content {
    margin-left: 0 !important;
  }

  .station /deep/ .el-input,
  .station /deep/ .el-select,
  .station /deep/ .el-cascader,
  .station /deep/ .el-date-editor,
  .station /deep/ .el-input-number,
  .station /deep/ .el-textarea {
    width: 100% !important;
  }
}
</style>
