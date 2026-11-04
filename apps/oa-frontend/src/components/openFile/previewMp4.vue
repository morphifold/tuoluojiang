<template>
  <div style="width: 100%">
    <video ref="{videoRef}" controls muted controlsList="nodownload" class="oa-m-video">
      <track kind="captions" />
      <source :src="fileUrl" type="video/mp4" />
    </video>
  </div>
</template>
<script>
export default {
  name: '',
  props: {
    url: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      fileUrl: this.url
    }
  },
  watch: {
    url(val) {
      this.$nextTick(() => {
        this.fileUrl = val
      })
    }
  }
}
</script>
<style scoped lang="scss">
.oa-m-video {
  width: 100%;
  max-width: 1200px;
  height: 650px;
}
@media (max-width: 767px) {
  .oa-m-video {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    height: auto;
  }
}
</style>
