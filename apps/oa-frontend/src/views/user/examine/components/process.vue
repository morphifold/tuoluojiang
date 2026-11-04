<template>
  <div class="stepsBox dd-flow-card">
    <div class="dd-flow-head">
      <span class="dd-flow-title">流程</span>
      <span class="dd-flow-help" @click="showFlowHelp">流程说明 &gt;</span>
    </div>
    <div
      v-for="(item, index) in examineList"
      :key="index"
      class="dd-flow-node"
      :class="{ 'is-last': index === examineList.length - 1 }"
    >
      <div class="dd-flow-rail"><span class="dd-flow-dot"></span></div>
      <div class="dd-flow-main">
        <div class="dd-flow-name">
          {{ item.title }}
          <el-tag
            v-if="item.types == 1 && item.settype != 5 && item.examine_mode > 0 && item.users && item.users.length > 1"
            effect="plain"
            size="mini"
          >
            {{ getExamineText(item.examine_mode) }}
          </el-tag>
        </div>
        <div class="dd-flow-sub">{{ flowSub(item) }}</div>
        <div class="dd-flow-people">
          <div v-for="(items, indexs) in item.users || []" :key="indexs" class="manList">
            <img v-if="items.card || items.avatar" :src="items.card ? items.card.avatar : items.avatar" />
            <img v-else src="../../../../assets/images/portrait.png" />
            <span>{{ items.card ? items.card.name : items.name }}</span>
            <span v-if="!items.isDelete" class="acea-icon" @click="deleteUsersItem(item.users, indexs)">
              <i class="el-icon-close"></i>
            </span>
          </div>
        </div>
      </div>
      <div class="dd-flow-side">
        <select-member
          ref="selectMember"
          :only-one="item.select_mode == 1 ? true : false"
          :value="item.users || []"
          @getSelectList="getSelectList($event, item)"
        >
          <template v-slot:custom>
            <div
              v-if="item.types == 1 && (item.options || editUser)"
              class="upload dd-plus-box"
              @click="openDepartment(item, index)"
            >
              <i v-if="item.types == 1" class="dd-star">*</i>
              <i class="el-icon-plus"></i>
            </div>
            <div
              v-if="item.types == 2 && item.self_select == 1"
              class="upload dd-plus-box"
              @click="openDepartment(item, index)"
            >
              <i class="el-icon-plus"></i>
            </div>
          </template>
        </select-member>
      </div>
    </div>
    <div v-if="approverDelete || copyerDelete" class="dd-flow-note">已由管理员预设不可{{ title01.substring(1) }}</div>
  </div>
</template>

<script>
export default {
  name: 'Process',
  components: {
    selectMember: () => import('@/components/form-common/select-member')
  },
  props: {
    examineData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    editUser: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      checkedList: [],
      examineList: [],
      examineRules: {},
      index: -1,
      isAppoint: false,
      appointList: [],
      approverDelete: true,
      copyerDelete: true,
      title01: '',
      memberShow: false
    }
  },
  watch: {
    examineData: {
      handler(nVal) {
        this.examineList = nVal.list
        this.examineRules = nVal.rules
        this.title01 = ''
        this.$nextTick(() => {
          if (this.examineRules && this.examineRules.edit) {
            let editText = ''
            editText = this.examineRules.edit + ''
            this.approverDelete = editText.includes('1')
            this.copyerDelete = editText.includes('2')
            this.title01 += this.approverDelete ? '和修改审批人' : ''
            this.title01 += this.copyerDelete ? '和删除抄送人' : ''
          }
        })
      },
      immediate: true
    },
    lang() {
      this.setOptions()
    }
  },
  methods: {
    getExamineText(id) {
      let str = ''
      if (id == 1) {
        str = '或签'
      } else if (id == 2) {
        str = '会签'
      } else if (id == 3) {
        str = '依次审批'
      }
      return str
    },

    // 选择成员完成回调
    getSelectList(data, item) {
      this.checkedList = data
      item.users = data
      this.index = -1
    },
    // 打开选择部门成员
    openDepartment(row, index) {
      this.index = index

      row.users.forEach((item) => {
        item.value = item.id
      })
      this.$refs.selectMember[index].handlePopoverShow()
    },
    deleteUsersItem(row, index) {
      row.splice(index, 1)
    },
    judgeNodeText(row) {
      let str = ''
      if (
        this.approverDelete &&
        row.types === 1 &&
        (row.settype === 2 || row.settype === 7) &&
        row.no_hander === 2 &&
        row.users.length <= 0
      ) {
        str = '直接跳过节点，不视为异常'
      }
      if (
        this.approverDelete &&
        row.types === 1 &&
        (row.settype === 2 || row.settype === 7) &&
        row.no_hander === 1 &&
        row.users.length <= 0
      ) {
        str = this.examineRules.abnormal <= 0 ? '自动同意' : '转交给指定人员处理'
      }
      return str
    },
    flowSub(item) {
      if (!item) return ''
      var users = item.users || []
      if (users.length) return '已选 ' + users.length + ' 人'
      return item.types == 2 ? '请选择抄送人' : '请选择审批人'
    },
    showFlowHelp() {
      this.$message.info('带 * 的节点需要选择审批人。流程由管理员配置，提交前请确认节点不为空。')
    }
  }
}
</script>

<style scoped lang="scss">
.el-icon-arrow-right {
  line-height: 44px;
  font-size: 18px;
  color: rgba(0, 0, 0, 0.5);
}
.manList {
  flex-direction: column;
  position: relative;
  img {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    display: block;
  }
  span {
    font-size: 13px;
    font-weight: 400;
    color: rgba(102, 102, 102, 0.85);
    line-height: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 75px;
    text-align: center;
  }
  .acea-icon {
    position: absolute;
    right: 14px;
    top: 0;
    width: 14px;
    height: 14px;
    background-color: #ccc;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    i {
      font-size: 13px;
      color: #fff;
    }
  }
  .acea-arrow-right {
    position: absolute;
    right: -12px;
  }
}
.caption {
  .tab-icon {
    font-size: 16px;
  }
  .tip-title {
    font-size: 13px;
    font-weight: normal;
  }
}
.stepsBox {
  /deep/.el-step__description {
    margin-top: 17px;
    margin-bottom: 30px;
  }
  /deep/.el-step__title {
    color: #000;
    font-size: 14px;
    font-weight: 600;
  }
  .tit1 {
    font-style: normal;
    font-size: 13px;
    color: #999999;
    font-weight: normal;
  }
  /deep/.el-step__icon {
    background: #ccc;
  }
  .liuchen {
    i {
      color: #fff;
    }
  }
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
.upload {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: 1px solid #e5e6eb;
  background: #fff;
  color: #1677ff;
  font-size: 16px;
  margin-left: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  i {
    font-size: 18px;
  }
}
.dd-flow-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-top: 12px;
  box-sizing: border-box;
}
.dd-flow-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.dd-flow-title {
  font-size: 16px;
  font-weight: 700;
  color: #1f2329;
}
.dd-flow-help {
  font-size: 13px;
  color: #8f959e;
  cursor: pointer;
}
.dd-flow-node {
  display: flex;
  align-items: flex-start;
  position: relative;
  padding-bottom: 16px;
}
.dd-flow-rail {
  width: 16px;
  position: relative;
  align-self: stretch;
  display: flex;
  justify-content: center;
}
.dd-flow-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #c9cdd4;
  margin-top: 6px;
  position: relative;
  z-index: 1;
}
.dd-flow-node:not(.is-last) .dd-flow-rail:after {
  content: '';
  position: absolute;
  top: 14px;
  bottom: -8px;
  width: 1px;
  background: #e5e6eb;
}
.dd-flow-main {
  flex: 1;
  min-width: 0;
  padding-right: 8px;
}
.dd-flow-name {
  font-size: 14px;
  font-weight: 600;
  color: #1f2329;
}
.dd-flow-sub {
  margin-top: 4px;
  font-size: 12px;
  color: #8f959e;
}
.dd-flow-people {
  display: flex;
  flex-wrap: wrap;
  margin-top: 8px;
}
.dd-star {
  position: absolute;
  top: -6px;
  right: -4px;
  color: #f54a45;
  font-style: normal;
  font-size: 14px;
  line-height: 1;
}
.dd-flow-note {
  margin-top: 4px;
  font-size: 12px;
  color: #8f959e;
}
.manList {
  width: 56px;
  margin-right: 8px;
  text-align: center;
  position: relative;
}
.manList img {
  width: 36px;
  height: 36px;
  border-radius: 8px;
}
.manList span {
  display: block;
  max-width: 56px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
  color: #646a73;
}
@media (max-width: 767px) {
  .stepsBox {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
  }
  .stepsBox /deep/ .el-step__description,
  .stepsBox /deep/ .el-step__main {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    overflow: hidden;
  }
  .acea-row {
    max-width: 100%;
    flex-wrap: wrap;
  }
  .manList {
    max-width: 100% !important;
    min-width: 0 !important;
  }
  .manList span {
    width: auto !important;
    max-width: 72px !important;
    min-width: 0 !important;
  }
}
</style>
