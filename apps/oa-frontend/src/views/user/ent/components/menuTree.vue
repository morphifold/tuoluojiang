<template>
  <div class="tree-box">
    <div class="el-card__header clearfix tree-header">
      <el-input size="small" v-model="filterText" clearable placeholder="搜索部门" prefix-icon="el-icon-search" />
      <button type="button" class="tree-fold" @click="treeFolded = !treeFolded">
        {{ treeFolded ? '展开部门' : '收起部门' }}
      </button>
    </div>
    <div v-show="!treeFolded" v-height class="tree-scroll">
      <el-scrollbar style="height: 100%">
        <el-tree
          ref="tree"
          :data="treeData"
          :props="defaultProps"
          :highlight-current="true"
          :filter-node-method="filterNode"
          :default-expanded-keys="defaultFrame"
          :expand-on-click-node="false"
          node-key="value"
          @node-click="handleClick"
        >
          <div slot-scope="{ node, data }" class="custom-tree-node">
            <span class="flex-box">
              <i class="icon iconfont tree-icon iconwenjianjia" />
              {{ node.label }}
              <span v-if="data.user_count && isShowUserCount">({{ data.user_count }})</span>
            </span>
          </div>
        </el-tree>
      </el-scrollbar>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MenuTree',
  props: {
    treeData: {
      type: Array,
      default: () => {
        return []
      }
    },
    isShowUserCount: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      filterText: '',
      treeFolded: false,
      defaultProps: {
        children: 'children',
        label: 'label'
      },
      defaultFrame: []
    }
  },
  watch: {
    treeData: {
      handler(nVal, oVal) {
        this.$nextTick(() => {
          if (nVal.length) {
            const value = nVal[0].value
            this.defaultFrame = [value]
            this.$refs.tree.setCurrentKey(value)
          }
        })
      },
      deep: true
    },
    filterText(val) {
      this.$refs.tree.filter(val)
    }
  },
  methods: {
    // tree节点点击
    handleClick(node, data) {
      this.$emit('frameId', node.$treeNodeId === 1 ? '' : node.value)
    },
    // tree搜索过滤
    filterNode(value, data) {
      if (!value) return true
      return data.label.indexOf(value) !== -1
    }
  }
}
</script>

<style lang="scss" scoped>
.flex-box {
  font-family: PingFangSC-Regular, PingFang SC;
  font-size: 14px;
  font-weight: 400;
  color: #303133;
}
.tree-icon {
  margin-right: 5px;
  font-size: 15px;
  color: #ffca28;
}
.el-card__header {
  padding: 15px 20px;
}
.el-card__header /deep/.el-input--small .el-input__inner {
  cursor: pointer;
}

/deep/ .el-scrollbar__wrap {
  overflow-x: hidden;
}

/deep/ .el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content {
  background: rgba(24, 144, 255, 0.08);
  border-color: #1890ff;
  .flex-box {
    color: #1890ff !important;
    font-weight: 600;
  }
}
/deep/.el-tree-node__content {
  height: 40px;
  padding-right: 15px;
  border-right: 2px solid transparent;
}
/deep/ .el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content .custom-tree-node,
.el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content .right-icon {
  display: inline-block;
  color: #1890ff;
  font-weight: 600;
}

/deep/ .el-tree {
  // padding: 14px 0;
}

/deep/.el-tree-node > .el-tree-node__children {
  overflow: inherit;
}
.custom-tree-node {
  position: relative;
  width: 100%;
  .right-icon {
    display: none;
    position: absolute;
    right: 10px;
  }
}
.tree-box {
  border-right: 1px solid #eeeeee;
  background: #fff;
  margin: -20px 0 -20px -20px;
}
.tree-fold {
  display: none;
}

@media (max-width: 767px) {
  .tree-fold {
    display: inline-block;
    margin-top: 8px;
    padding: 4px 12px;
    border: none;
    border-radius: 14px;
    background: #f5f7fa;
    color: #1890ff;
    font-size: 12px;
    line-height: 20px;
    cursor: pointer;
  }
  .tree-box {
    max-height: 280px;
    overflow: hidden;
    border-right: none;
    border-bottom: 1px solid #eeeeee;
    margin: 0 0 8px 0;
  }
  .tree-scroll {
    height: 220px !important;
    max-height: 220px !important;
    overflow: auto;
  }
}
</style>
