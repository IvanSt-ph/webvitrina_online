const activeLocks = new Set()
let savedBodyStyles = null
let scrollPosition = 0

function lockPage() {
  const body = document.body
  const scrollbarWidth = Math.max(0, window.innerWidth - document.documentElement.clientWidth)
  const computedPaddingRight = Number.parseFloat(window.getComputedStyle(body).paddingRight) || 0

  scrollPosition = window.scrollY
  savedBodyStyles = {
    overflow: body.style.overflow,
    position: body.style.position,
    top: body.style.top,
    left: body.style.left,
    right: body.style.right,
    width: body.style.width,
    paddingRight: body.style.paddingRight,
  }

  body.style.overflow = 'hidden'
  body.style.position = 'fixed'
  body.style.top = `-${scrollPosition}px`
  body.style.left = '0'
  body.style.right = '0'
  body.style.width = '100%'
  if (scrollbarWidth) body.style.paddingRight = `${computedPaddingRight + scrollbarWidth}px`
}

function unlockPage() {
  if (!savedBodyStyles) return

  const body = document.body
  const position = scrollPosition
  Object.assign(body.style, savedBodyStyles)
  savedBodyStyles = null
  window.scrollTo(0, position)
}

const scrollLock = {
  acquire() {
    const token = Symbol('scroll-lock')
    if (activeLocks.size === 0) lockPage()
    activeLocks.add(token)

    let released = false
    return () => {
      if (released) return
      released = true
      activeLocks.delete(token)
      if (activeLocks.size === 0) unlockPage()
    }
  },
}

window.wvScrollLock = scrollLock

export default scrollLock
