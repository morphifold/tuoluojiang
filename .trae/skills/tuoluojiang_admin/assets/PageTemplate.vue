<template>
  <div class="{{ pageName }}">
    <el-page-header
      :title="title"
      @back="handleBack"
    />
    <el-card class="page-content">
      <slot></slot>
    </el-card>
  </div>
</template>

<script>
export default {
  name: '{{ PageName }}',
  props: {
    title: {
      type: String,
      default: '{{ PageName }}'
    }
  },
  data() {
    return {
      pageName: '{{ pageName }}'
    }
  },
  computed: {
  },
  methods: {
    handleBack() {
      this.$router.back()
    }
  },
  mounted() {
  }
}
</script>

<style scoped>
.{{ pageName }} {
  padding: 16px;
}

.page-content {
  margin-top: 16px;
}
</style>