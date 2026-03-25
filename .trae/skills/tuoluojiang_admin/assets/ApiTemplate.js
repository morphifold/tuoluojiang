import request from '@/api/request'

const {{ apiName }} = {
  // 获取列表
  getList(params) {
    return request({
      url: '/api/{{ apiName }}/list',
      method: 'get',
      params
    })
  },

  // 获取详情
  getDetail(id) {
    return request({
      url: `/api/{{ apiName }}/${id}`,
      method: 'get'
    })
  },

  // 新增
  create(data) {
    return request({
      url: '/api/{{ apiName }}',
      method: 'post',
      data
    })
  },

  // 更新
  update(id, data) {
    return request({
      url: `/api/{{ apiName }}/${id}`,
      method: 'put',
      data
    })
  },

  // 删除
  delete(id) {
    return request({
      url: `/api/{{ apiName }}/${id}`,
      method: 'delete'
    })
  }
}

export default {{ apiName }}