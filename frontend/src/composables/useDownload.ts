import { ref } from 'vue'
import { api } from '@/services/api'
import { useToast } from 'primevue/usetoast'

export function useDownload() {
  const toast = useToast()
  const downloading = ref<Record<string, boolean>>({})

  async function downloadReport(url: string, filename: string, key: string): Promise<void> {
    if (downloading.value[key]) return

    downloading.value[key] = true

    try {
      const response = await api.get(url, { responseType: 'blob' })

      const contentDisposition = response.headers['content-disposition']
      let finalFilename = filename

      if (contentDisposition) {
        const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
        if (match?.[1]) {
          finalFilename = match[1].replace(/['"]/g, '')
        }
      }

      const blob = new Blob([response.data])
      const blobUrl = URL.createObjectURL(blob)

      const anchor = document.createElement('a')
      anchor.href = blobUrl
      anchor.download = finalFilename
      document.body.appendChild(anchor)
      anchor.click()

      document.body.removeChild(anchor)
      URL.revokeObjectURL(blobUrl)
    } catch (error: any) {
      const message = error.response?.status === 403
        ? 'Você não tem permissão para exportar relatórios.'
        : error.response?.status === 500
          ? 'Erro ao gerar relatório. Tente novamente.'
          : error.code === 'ECONNABORTED'
            ? 'Tempo limite excedido. Tente novamente.'
            : 'Erro ao baixar relatório. Tente novamente.'

      toast.add({ severity: 'error', summary: 'Erro', detail: message, life: 5000 })
    } finally {
      downloading.value[key] = false
    }
  }

  return { downloading, downloadReport }
}
