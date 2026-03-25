const state = {
  {{ storeName }}List: [],
  {{ storeName }}Detail: null,
  loading: false,
  pagination: {
    currentPage: 1,
    pageSize: 10,
    total: 0
  }
}

const mutations = {
  SET_{{ STORE_NAME }}_LIST(state, list) {
    state.{{ storeName }}List = list
  },
  
  SET_{{ STORE_NAME }}_DETAIL(state, detail) {
    state.{{ storeName }}Detail = detail
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  
  SET_PAGINATION(state, pagination) {
    state.pagination = { ...state.pagination, ...pagination }
  }
}

const actions = {
  async get{{ StoreName }}List({ commit }, params) {
    commit('SET_LOADING', true)
    try {
      const { data } = await this.$api.{{ storeName }}.getList(params)
      commit('SET_{{ STORE_NAME }}_LIST', data.list)
      commit('SET_PAGINATION', {
        currentPage: data.currentPage,
        pageSize: data.pageSize,
        total: data.total
      })
    } catch (error) {
      console.error('获取{{ storeName }}列表失败:', error)
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async get{{ StoreName }}Detail({ commit }, id) {
    commit('SET_LOADING', true)
    try {
      const { data } = await this.$api.{{ storeName }}.getDetail(id)
      commit('SET_{{ STORE_NAME }}_DETAIL', data)
    } catch (error) {
      console.error('获取{{ storeName }}详情失败:', error)
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async create{{ StoreName }}({ commit }, data) {
    commit('SET_LOADING', true)
    try {
      await this.$api.{{ storeName }}.create(data)
      return true
    } catch (error) {
      console.error('创建{{ storeName }}失败:', error)
      return false
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async update{{ StoreName }}({ commit }, { id, data }) {
    commit('SET_LOADING', true)
    try {
      await this.$api.{{ storeName }}.update(id, data)
      return true
    } catch (error) {
      console.error('更新{{ storeName }}失败:', error)
      return false
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async delete{{ StoreName }}({ commit }, id) {
    commit('SET_LOADING', true)
    try {
      await this.$api.{{ storeName }}.delete(id)
      return true
    } catch (error) {
      console.error('删除{{ storeName }}失败:', error)
      return false
    } finally {
      commit('SET_LOADING', false)
    }
  }
}

const getters = {
  {{ storeName }}List: state => state.{{ storeName }}List,
  {{ storeName }}Detail: state => state.{{ storeName }}Detail,
  loading: state => state.loading,
  pagination: state => state.pagination
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}