<template>
  <div class="{{ componentName }}">
    <el-card :title="title">
      <slot></slot>
    </el-card>
  </div>
</template>

<script>
export default {
  name: '{{ ComponentName }}',
  props: {
    title: {
      type: String,
      default: '{{ ComponentName }}'
    }
  },
  data() {
    return {
      componentName: '{{ componentName }}'
    }
  },
  computed: {
  },
  methods: {
  },
  mounted() {
  }
}
</script>

<style scoped>
.{{ componentName }} {
  padding: 16px;
}
</style>