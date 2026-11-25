export default () => ({
  html: '',
  loaded: false,
  loading: false,
  failed: false,

  init() {
    this.$watch('open', value => {
      if (value) this.loadTree()
    })
  },

  async loadTree() {
    if (this.loaded || this.loading) return
    this.loading = true
    this.failed = false
    const controller = new AbortController()
    const timeout = setTimeout(() => controller.abort(), 20000)

    try {
      const url = new URL(this.$el.dataset.treeUrl, window.location.origin)
      // Preserve the current page's query parameters in category links, as before.
      url.search = window.location.search
      const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store',
        signal: controller.signal,
      })
      if (!response.ok) throw new Error('Category menu request failed')
      const data = await response.json()
      if (typeof data.html !== 'string') throw new Error('Invalid category menu response')
      this.html = data.html
      this.loaded = true
    } catch {
      this.failed = true
    } finally {
      clearTimeout(timeout)
      this.loading = false
    }
  },
})
