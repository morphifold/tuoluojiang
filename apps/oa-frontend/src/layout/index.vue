<template>
  <div class="layout-app-wrapper">
    <!-- 移动端左上角汉堡切换按钮 -->
    <div
      class="mobile-hamburger"
      @click="mobileMenuOpen = !mobileMenuOpen"
    >
      <i :class="mobileMenuOpen ? 'el-icon-close' : 'el-icon-s-unfold'"></i>
    </div>

    <!-- 移动端展开时的半透明遮罩层 -->
    <div
      v-if="mobileMenuOpen"
      class="mobile-drawer-mask"
      @click="mobileMenuOpen = false"
    ></div>

    <div :class="[classObj, { 'mobile-layout': device === 'mobile', 'mobile-menu-open': mobileMenuOpen }]" style="box-sizing: border-box;">
      <!-- 左侧一级菜单 -->
      <navbar :type="type" />
      <!-- 左侧二级菜单 -->
      <sidebar class="sidebar-container" v-show="!sideShow" />
      <!-- 中间区域 -->
      <div :class="['main-container', { hasTagsView: needTagsView }, helpShow ? 'left' : '']">
        <!-- 顶部工具栏 -->
        <headerToolbar :helpIsShow="helpShow" ref="header" :searchShow="searchShow" @handleSearchConfirm="handleConfirm"  @openOrCloseHelp="openHelp"></headerToolbar>
        <!-- 标签栏目 -->
        <tags-view class="tags-view-container" v-show="needTagsView"></tags-view>
        <!-- 中间内容区域 -->
        <app-main />
      </div>
      <!-- 帮助侧边栏 -->
      <help v-show="helpShow" :helpShow="helpShow" @closeHelp="closeHelp"></help>
    </div>
    <!-- 消息组建 -->
   <message-handle-popup ref="messageHandlePopup" :detail="detail"></message-handle-popup>
  </div>
</template>

<script>
import { AppMain, Navbar, Sidebar, TagsView } from './components'
import ResizeMixin from './mixin/ResizeHandler'
import { roterPre } from '@/settings'
import { mapGetters, mapState } from 'vuex'
import { EventBus } from '@/libs/bus'
export default {
  name: 'Layout',
  components: {
    AppMain,
    Navbar,
    Sidebar,
    TagsView,
    headerToolbar: () => import('./components/Header/Toolbar'),
    // breadcrumb: () => import('./components/Header/breadcrumb'),
    help: () => import('./components/Document/help'),
    messageHandlePopup: () => import('@/components/common/messageHandlePopup')
  },
  mixins: [ResizeMixin],
  computed: {
    ...mapState({
      sidebar: (state) => state.app.sidebar,
      word: (state) => state.app.keyWord,
      device: (state) => state.app.device,
      showSettings: (state) => state.settings.showSettings,
      needTagsView: (state) => state.settings.tagsView,
      fixedHeader: (state) => state.settings.fixedHeader,
      sidebarType: (state) => state.app.sidebarType
    }),
    ...mapGetters(['menuList', 'sidebarParentCur']),
    classObj() {
      const sidebarData = this.menuList[this.sidebarParentCur]

      // 二级菜单只有一个，那就用is_show==0来判断显示隐藏
      if (sidebarData) {
        if (sidebarData.children && sidebarData.children.length > 1) {
          this.isSidebarShow = sidebarData.children && sidebarData.children.length > 0
        } else {
          if (sidebarData.children && sidebarData.children[0].is_show == 0) {
            this.isSidebarShow = false
          } else if(!sidebarData.children){
            this.isSidebarShow = false
          } else {
            this.isSidebarShow = true
          }
        }
      }

      return {
        hideSidebar: !this.sidebar.opened && this.isSidebarShow,
        openSidebar: this.sidebar.opened && this.isSidebarShow,
        showSidebar: !this.isSidebarShow
      }
    }
  },

  watch: {
    $route(to, from) {
      this.mobileMenuOpen = false
      let data = {
        list: [],
        type: '',
        additional_search_boolean: '1'
      }
      this.$store.commit('uadatefieldOptions', data)
      this.$store.state.business.conditionDialog = false
      if (to.path == `${roterPre}/user/resume` || to.path == `${roterPre}/user/news/subscribe`) {
        this.sideShow = true
        this.helpShow = false
      } else {
        this.sideShow = false
      }
    },

    sidebarType: {
      handler(nVal, oVal) {
        if (nVal) {
          this.widthNum = 'calc(100% - 270px)'
        } else {
          this.widthNum = 'calc(100% - 70px)'
        }
      },
      deep: true
    },
    word: {
      handler(nVal, oVal) {
        if (nVal) {
          this.keyword = nVal
        }
      },
      deep: true
    }
  },
  data() {
    return {
      mobileMenuOpen: false,
      widthNum: 'calc(100% - 70px)',
      isSidebarShow: true,
      helpShow: false,
      searchShow: false,
      type: 2,
      keyword: '',
      detail: {},
      sideShow: false
    }
  },
  created() {
    EventBus.$on('messageSuccess', async (e) => {
      this.detail = e.detail
      await this.$nextTick()
      this.$refs.messageHandlePopup.openMessage(e.item)
    })
    if (
      this.$route.fullPath == `${roterPre}/user/resume` ||
      this.$route.fullPath == `${roterPre}/user/news/subscribe`
    ) {
      this.sideShow = true
    } else {
      this.sideShow = false
    }
  },
  mounted() {
    this.widthNum = this.sidebarType ? 'calc(100% - 152px)' : 'calc(100% - 70px)'
  },
  methods: {
    handleClickOutside() {
      this.$store.dispatch('app/closeSideBar', { withoutAnimation: false })
    },
    openHelp(isshow) {
      this.helpShow = isshow
    },
    closeHelp() {
      this.helpShow = false
      this.$refs.header.helpShow = false
    },
    handleConfirm(keyword) {
      this.$router.push({
        path: `${roterPre}/search`,
        query: { keyword: keyword }
      })

      this.searchShow = false
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/mixin.scss';
@import '~@/styles/variables.scss';

.flex {
  display: flex;
  align-items: center;
}

.app-wrapper {
  @include clearfix;
  position: relative;
  height: 100%;
  width: 100%;

  &.mobile.openSidebar {
    position: fixed;
    top: 0;
  }
}


.nav-wrapper {
  position: relative;
  z-index: 700;
  width: 100%;
  transition: width 0.28s;
}

//二级菜单展开
.openSidebar {
  .breadcrumb {
    padding-left: 250px !important;
  }
  .main-container {
    margin-left: 219px !important;
  }
}
//二级菜单缩进
.hideSidebar {
  .breadcrumb {
    padding-left: 150px !important;
  }
  .main-container {
    margin-left: 128px !important;
  }
}
//二级菜单隐藏
.showSidebar {
  .breadcrumb {
    padding-left: 100px !important;
  }
  .main-container {
    margin-left: 64px !important;
  }
}

@keyframes fadenum {
  0% {
    margin-right: 0px;
  }
  100% {
    margin-right: 360px;
  }
}
.left {
  animation: fadenum 0.5s;
  animation-fill-mode: forwards;
}
.el-icon-search {
  color: #909399;
}
.top {
  /deep/.el-button--text {
    color: #909399;
  }
}

// 移动端汉堡切换按钮
.mobile-hamburger {
  display: none;
  position: fixed;
  top: 7px;
  left: 10px;
  z-index: 2100;
  width: 34px;
  height: 34px;
  background: #1890ff;
  color: #ffffff;
  border-radius: 6px;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
  cursor: pointer;
  font-size: 19px;
  transition: all 0.25s ease;
  &:hover {
    background: #40a9ff;
  }
}

// 移动端展开时的全屏遮罩层
.mobile-drawer-mask {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1999;
  backdrop-filter: blur(1px);
}

// ==============================
// 移动端响应式布局适配（手机端视口 < 768px 或 mobile 模式）
// ==============================
@media (max-width: 767px) {
  .mobile-hamburger {
    display: flex !important;
  }

  .mobile-drawer-mask {
    display: block !important;
  }

  // 默认情况下收起导航栏与侧边栏
  /deep/ .navbar,
  /deep/ .sidebar-container {
    transform: translateX(-260px) !important;
    transition: transform 0.25s ease !important;
  }

  // 汉堡菜单激活时滑出
  .mobile-menu-open {
    /deep/ .navbar {
      transform: translateX(0) !important;
      z-index: 2002 !important;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2) !important;
    }
    /deep/ .sidebar-container {
      transform: translateX(0) !important;
      z-index: 2001 !important;
      box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15) !important;
    }
  }

  // 强制重置主内容容器边距为 0，防止被 219px 挤压截断
  .main-container {
    margin-left: 0 !important;
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100vw !important;
    box-sizing: border-box !important;
    overflow-x: hidden !important;
  }

  // 覆盖各类状态下的边距
  .openSidebar,
  .hideSidebar,
  .showSidebar {
    .breadcrumb {
      padding-left: 52px !important;
    }
    .main-container {
      margin-left: 0 !important;
      width: 100% !important;
      max-width: 100vw !important;
    }
  }

  // 顶栏面包屑左侧让位给汉堡按钮
  /deep/ .breadcrumb {
    padding-left: 52px !important;
  }

  // 页面全局内容容器紧凑边距，彻底防溢出
  /deep/ .divBox {
    padding: 10px !important;
    box-sizing: border-box !important;
    width: 100% !important;
    max-width: 100vw !important;
    overflow-x: hidden !important;
  }
}
</style>

<style lang="scss">
@media (max-width: 767px) {
  body {
    min-width: 100% !important;
    width: 100% !important;
    max-width: 100vw !important;
    overflow-x: hidden !important;
  }
  #app {
    min-width: 100% !important;
    width: 100% !important;
    max-width: 100vw !important;
    overflow-x: hidden !important;
  }
  .divBox {
    margin: 6px !important;
    padding: 0 !important;
    padding-top: 6px !important;
    box-sizing: border-box !important;
    width: auto !important;
    overflow-x: hidden !important;
  }
}
</style>
