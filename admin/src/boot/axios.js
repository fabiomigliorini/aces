import { defineBoot } from '#q-app/wrappers'
import { api } from 'src/services/api'

export default defineBoot(({ app }) => {
  app.config.globalProperties.$api = api
})

export { api }
