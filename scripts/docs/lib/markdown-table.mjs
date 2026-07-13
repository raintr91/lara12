/** Team convention: `#` trong code = chưa có link / chưa có nội dung. */
export const MD_NONE = '`#`'

export function escapeCell(value) {
  if (value == null || value === '') return ''
  return String(value).replace(/\|/g, '\\|').replace(/\n/g, '<br>')
}

export function renderTable(headers, rows) {
  if (!rows.length) return MD_NONE

  const head = `| ${headers.join(' | ')} |`
  const sep = `| ${headers.map(() => '---').join(' | ')} |`
  const body = rows.map((row) => `| ${row.map(escapeCell).join(' | ')} |`).join('\n')

  return `${head}\n${sep}\n${body}`
}

export function renderBullets(items = []) {
  if (!items?.length) return MD_NONE
  return items
    .map((item) => {
      if (typeof item === 'string') return `- ${item}`
      if (item?.id) return `- **${item.id}** — ${item.summary ?? item.question ?? item.title ?? JSON.stringify(item)}`
      return `- ${JSON.stringify(item)}`
    })
    .join('\n')
}
