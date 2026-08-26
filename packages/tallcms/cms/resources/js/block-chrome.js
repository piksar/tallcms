import { Extension } from '@tiptap/core'
import { NodeSelection, Plugin, PluginKey } from '@tiptap/pm/state'
import Sortable from 'sortablejs'

// Expose Sortable for the inline Alpine outline tab — the Blade view has no
// build step and can't import directly. Both bits of code load via the same
// on-request asset, so timing works out.
window.tallcmsSortable = Sortable

const PLUGIN_KEY = new PluginKey('cmsBlockChrome')
const OUTLINE_PLUGIN_KEY = new PluginKey('cmsBlockOutline')
const SLASH_PLUGIN_KEY = new PluginKey('cmsBlockSlash')
const INJECTED_FLAG = 'cmsChromeInjected'
const NODE_TYPE = 'customBlock'
const OUTLINE_EVENT = 'cms-block-outline-changed'
const ACTION_EVENT = 'cms-block-action'
const SLASH_INSERT_EVENT = 'cms-slash-insert'
const SLASH_TRIGGER = /(?:^|[\s ])(\/[a-zA-Z0-9-]*)$/

const TITLE_KEYS = ['title', 'heading', 'headline', 'heading_text', 'name']

// Block config fields can hold rich HTML (Hero's heading is a rich editor),
// so a raw string can be "<p>Welcome</p>". DOMParser is the safe way to get
// plain text out — it doesn't execute scripts or trigger image loads, unlike
// innerHTML on a live element.
function stripHtml(str) {
    if (!str.includes('<')) return str
    return (
        new DOMParser().parseFromString(str, 'text/html').body.textContent ||
        ''
    )
}

function extractTitle(config) {
    if (!config || typeof config !== 'object') return null
    for (const key of TITLE_KEYS) {
        const value = config[key]
        if (typeof value !== 'string') continue
        const text = stripHtml(value).trim()
        if (text.length > 0) return text
    }
    return null
}

function collectOutlineItems(doc) {
    const items = []
    doc.forEach((node, pos) => {
        if (node.type.name !== NODE_TYPE) return
        items.push({
            pos,
            id: node.attrs.id,
            label: node.attrs.label,
            title: extractTitle(node.attrs.config),
        })
    })
    return items
}

const ICONS = {
    grip: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM7 10a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM7 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 10a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>',
    up: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 1 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 0 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd"/></svg>',
    down: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd"/></svg>',
    duplicate: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A1.5 1.5 0 0 1 8.5 2h3.879a1.5 1.5 0 0 1 1.06.44l3.122 3.12A1.5 1.5 0 0 1 17 6.622V12.5a1.5 1.5 0 0 1-1.5 1.5h-1v-3.379a3 3 0 0 0-.879-2.121L10.5 5.379A3 3 0 0 0 8.379 4.5H7v-1Z"/><path d="M4.5 6A1.5 1.5 0 0 0 3 7.5v9A1.5 1.5 0 0 0 4.5 18h7a1.5 1.5 0 0 0 1.5-1.5v-5.879a1.5 1.5 0 0 0-.44-1.06L9.44 6.439A1.5 1.5 0 0 0 8.378 6H4.5Z"/></svg>',
    collapse: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M14.78 12.78a.75.75 0 0 1-1.06 0L10 9.06l-3.72 3.72a.75.75 0 1 1-1.06-1.06l4.25-4.25a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd"/></svg>',
    expand: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>',
}

const COLLAPSED_CLASS = 'fi-cms-block-collapsed'

function findTopLevelCustomBlockPos(view, blockDom) {
    let foundPos = -1
    view.state.doc.forEach((child, offset) => {
        if (foundPos !== -1) return
        if (child.type.name !== NODE_TYPE) return
        if (view.nodeDOM(offset) === blockDom) {
            foundPos = offset
        }
    })
    return foundPos
}

function makeButton({ className, title, html, onClick }) {
    const btn = document.createElement('button')
    btn.type = 'button'
    btn.className = className
    btn.title = title
    btn.setAttribute('aria-label', title)
    btn.innerHTML = html
    btn.addEventListener('click', (event) => {
        event.preventDefault()
        event.stopPropagation()
        onClick(event)
    })
    return btn
}

class BlockChromeView {
    constructor(view, editor) {
        this.view = view
        this.editor = editor
        this.observer = new MutationObserver(() => this.injectAll())
        this.observer.observe(view.dom, { childList: true, subtree: true })
        this.injectAll()
    }

    injectAll() {
        if (!this.view.editable) return
        const blocks = this.view.dom.querySelectorAll(
            `[data-type="${NODE_TYPE}"]`,
        )
        blocks.forEach((block) => this.inject(block))
    }

    inject(blockDom) {
        const header = blockDom.querySelector(
            '.fi-fo-rich-editor-custom-block-header',
        )
        if (!header || header.dataset[INJECTED_FLAG]) return
        header.dataset[INJECTED_FLAG] = '1'
        header.classList.add('fi-cms-block-chrome-host')

        const view = this.view
        const editor = this.editor

        const handle = document.createElement('span')
        handle.className = 'fi-cms-block-drag-handle'
        handle.title = 'Drag to reorder'
        handle.setAttribute('aria-hidden', 'true')
        handle.innerHTML = ICONS.grip

        const upBtn = makeButton({
            className: 'fi-icon-btn fi-cms-block-chrome-btn',
            title: 'Move block up',
            html: ICONS.up,
            onClick: () => {
                const pos = findTopLevelCustomBlockPos(view, blockDom)
                if (pos === -1) return
                editor.commands.moveCustomBlockUp(pos)
            },
        })

        const downBtn = makeButton({
            className: 'fi-icon-btn fi-cms-block-chrome-btn',
            title: 'Move block down',
            html: ICONS.down,
            onClick: () => {
                const pos = findTopLevelCustomBlockPos(view, blockDom)
                if (pos === -1) return
                editor.commands.moveCustomBlockDown(pos)
            },
        })

        const duplicateBtn = makeButton({
            className: 'fi-icon-btn fi-cms-block-chrome-btn',
            title: 'Duplicate block',
            html: ICONS.duplicate,
            onClick: () => {
                const pos = findTopLevelCustomBlockPos(view, blockDom)
                if (pos === -1) return
                editor.commands.duplicateCustomBlock(pos)
            },
        })

        // Collapse swaps its own icon and toggles the class on the wrapper.
        // State lives only in the DOM — survives tab switches and panel
        // toggles, resets on page reload (per the "opt-in, see how it
        // feels" Phase 2 decision).
        const collapseBtn = makeButton({
            className: 'fi-icon-btn fi-cms-block-chrome-btn',
            title: 'Collapse block',
            html: ICONS.collapse,
            onClick: () => {
                const willCollapse = !blockDom.classList.contains(COLLAPSED_CLASS)
                blockDom.classList.toggle(COLLAPSED_CLASS, willCollapse)
                collapseBtn.innerHTML = willCollapse ? ICONS.expand : ICONS.collapse
                collapseBtn.title = willCollapse ? 'Expand block' : 'Collapse block'
                collapseBtn.setAttribute(
                    'aria-label',
                    collapseBtn.title,
                )
            },
        })

        const reorderGroup = document.createElement('div')
        reorderGroup.className = 'fi-cms-block-chrome-group'
        reorderGroup.appendChild(upBtn)
        reorderGroup.appendChild(downBtn)
        reorderGroup.appendChild(duplicateBtn)
        reorderGroup.appendChild(collapseBtn)

        header.insertBefore(handle, header.firstChild)

        const deleteContainer = header.querySelector(
            '.fi-fo-rich-editor-custom-block-delete-btn-ctn',
        )
        if (deleteContainer) {
            header.insertBefore(reorderGroup, deleteContainer)
        } else {
            header.appendChild(reorderGroup)
        }
    }

    update() {
        this.injectAll()
    }

    destroy() {
        this.observer.disconnect()
    }
}

export default Extension.create({
    name: 'cmsBlockChrome',

    addCommands() {
        return {
            moveCustomBlockUp:
                (pos) =>
                ({ tr, state, dispatch }) => {
                    const node = state.doc.nodeAt(pos)
                    if (!node || node.type.name !== NODE_TYPE) return false

                    const $pos = state.doc.resolve(pos)
                    if ($pos.parent.type.name !== 'doc') return false

                    const index = $pos.index(0)
                    if (index === 0) return false

                    const prev = state.doc.child(index - 1)
                    const prevStart = pos - prev.nodeSize

                    if (dispatch) {
                        // Cut current then re-insert before previous sibling.
                        // Deleting at `pos` leaves `prevStart` unaffected.
                        tr.delete(pos, pos + node.nodeSize)
                        tr.insert(prevStart, node)
                        tr.setSelection(
                            NodeSelection.create(tr.doc, prevStart),
                        )
                        tr.scrollIntoView()
                        dispatch(tr)
                    }
                    return true
                },

            moveCustomBlockDown:
                (pos) =>
                ({ tr, state, dispatch }) => {
                    const node = state.doc.nodeAt(pos)
                    if (!node || node.type.name !== NODE_TYPE) return false

                    const $pos = state.doc.resolve(pos)
                    if ($pos.parent.type.name !== 'doc') return false

                    const index = $pos.index(0)
                    if (index >= state.doc.childCount - 1) return false

                    const next = state.doc.child(index + 1)
                    const currentEnd = pos + node.nodeSize
                    const nextEnd = currentEnd + next.nodeSize

                    if (dispatch) {
                        // Cut the next sibling and re-insert it before current.
                        // After the delete, current still occupies [pos, currentEnd];
                        // inserting `next` at `pos` shifts current right by
                        // next.nodeSize, landing it at pos + next.nodeSize.
                        tr.delete(currentEnd, nextEnd)
                        tr.insert(pos, next)
                        tr.setSelection(
                            NodeSelection.create(
                                tr.doc,
                                pos + next.nodeSize,
                            ),
                        )
                        tr.scrollIntoView()
                        dispatch(tr)
                    }
                    return true
                },

            duplicateCustomBlock:
                (pos) =>
                ({ tr, state, dispatch }) => {
                    const node = state.doc.nodeAt(pos)
                    if (!node || node.type.name !== NODE_TYPE) return false

                    const $pos = state.doc.resolve(pos)
                    if ($pos.parent.type.name !== 'doc') return false

                    const insertPos = pos + node.nodeSize
                    const clone = node.type.create(
                        node.attrs,
                        node.content,
                        node.marks,
                    )

                    if (dispatch) {
                        tr.insert(insertPos, clone)
                        tr.setSelection(
                            NodeSelection.create(tr.doc, insertPos),
                        )
                        tr.scrollIntoView()
                        dispatch(tr)
                    }
                    return true
                },

            // Moves the customBlock at `fromPos` to slot `toIndex` in the
            // ordered sequence of customBlock siblings (paragraphs and other
            // top-level nodes are ignored for ordering purposes). Used by the
            // Outline tab's drag-reorder — the user only sees customBlocks
            // there and wouldn't expect intervening paragraphs to factor in.
            moveCustomBlockTo:
                (fromPos, toIndex) =>
                ({ tr, state, dispatch }) => {
                    const node = state.doc.nodeAt(fromPos)
                    if (!node || node.type.name !== NODE_TYPE) return false

                    const $pos = state.doc.resolve(fromPos)
                    if ($pos.parent.type.name !== 'doc') return false

                    const customBlocks = []
                    state.doc.forEach((child, offset) => {
                        if (child.type.name === NODE_TYPE) {
                            customBlocks.push({
                                pos: offset,
                                size: child.nodeSize,
                            })
                        }
                    })

                    const fromIndex = customBlocks.findIndex(
                        (b) => b.pos === fromPos,
                    )
                    if (fromIndex === -1) return false
                    if (toIndex < 0 || toIndex >= customBlocks.length) {
                        return false
                    }
                    if (fromIndex === toIndex) return false

                    const target = customBlocks[toIndex]

                    if (dispatch) {
                        tr.delete(fromPos, fromPos + node.nodeSize)

                        // After delete, positions > fromPos shift left by node.nodeSize.
                        // Moving down: insert AFTER target (where target USED to end,
                        //   adjusted for the delete). Moving up: insert AT target
                        //   (target stayed put since it was before fromPos).
                        const insertPos =
                            toIndex > fromIndex
                                ? target.pos - node.nodeSize + target.size
                                : target.pos

                        tr.insert(insertPos, node)
                        tr.setSelection(
                            NodeSelection.create(tr.doc, insertPos),
                        )
                        tr.scrollIntoView()
                        dispatch(tr)
                    }
                    return true
                },
        }
    },

    addProseMirrorPlugins() {
        const editor = this.editor

        return [
            new Plugin({
                key: PLUGIN_KEY,
                view(editorView) {
                    return new BlockChromeView(editorView, editor)
                },
            }),
            new Plugin({
                key: OUTLINE_PLUGIN_KEY,
                view(editorView) {
                    return new OutlineSyncView(editorView, editor)
                },
            }),
            new Plugin({
                key: SLASH_PLUGIN_KEY,
                view(editorView) {
                    const instance = new SlashCommandView(editorView)
                    editorView._cmsSlashView = instance
                    return instance
                },
                props: {
                    handleKeyDown(view, event) {
                        const slashView = view._cmsSlashView
                        if (!slashView || !slashView.isOpen) return false
                        return slashView.handleKeyDown(event)
                    },
                },
            }),
        ]
    },
})

// Bridges the editor to the Outline tab in the side panel:
//   - On every doc change, emits OUTLINE_EVENT with the current customBlock list
//   - Listens for ACTION_EVENT and runs the requested editor command
// Both events are scoped to the editor.dom so multiple editors on a page don't
// cross-talk; the panel finds the editor.dom via its closest .fi-fo-rich-editor.
class OutlineSyncView {
    constructor(view, editor) {
        this.view = view
        this.editor = editor
        this.actionHandler = (event) => this.handleAction(event)
        view.dom.addEventListener(ACTION_EVENT, this.actionHandler)
        // Defer initial emit by a tick so the panel's listener is wired first.
        queueMicrotask(() => this.emit())
    }

    emit() {
        const items = collectOutlineItems(this.view.state.doc)
        this.view.dom.dispatchEvent(
            new CustomEvent(OUTLINE_EVENT, {
                detail: { items },
                bubbles: true,
            }),
        )
    }

    handleAction(event) {
        const { action, args } = event.detail || {}
        if (action === 'scrollTo' && typeof args?.pos === 'number') {
            this.editor
                .chain()
                .focus()
                .setNodeSelection(args.pos)
                .scrollIntoView()
                .run()
        } else if (
            action === 'moveTo' &&
            typeof args?.fromPos === 'number' &&
            typeof args?.toIndex === 'number'
        ) {
            this.editor.commands.moveCustomBlockTo(args.fromPos, args.toIndex)
        }
    }

    update(_view, prevState) {
        if (this.view.state.doc !== prevState.doc) this.emit()
    }

    destroy() {
        this.view.dom.removeEventListener(ACTION_EVENT, this.actionHandler)
    }
}

// Notion-style "/" suggestion menu. Detects /<query> at the cursor (after
// whitespace or start-of-line), shows a floating list of matching blocks
// pulled from the side panel's Alpine state, and on select dispatches
// SLASH_INSERT_EVENT for the panel to mount the customBlock action — same
// flow as clicking a block in the picker.
class SlashCommandView {
    constructor(view) {
        this.view = view
        this.isOpen = false
        this.query = ''
        this.range = null
        this.items = []
        this.activeIndex = 0

        this.popup = document.createElement('div')
        this.popup.className = 'fi-cms-slash-popup'
        this.popup.setAttribute('role', 'listbox')
        this.popup.style.display = 'none'
        document.body.appendChild(this.popup)

        this.docClickHandler = (event) => {
            if (!this.isOpen) return
            if (this.popup.contains(event.target)) return
            this.close()
        }
        document.addEventListener('mousedown', this.docClickHandler)
    }

    update(view, prevState) {
        const trigger = this.detectTrigger(view.state)
        if (!trigger) {
            if (this.isOpen) this.close()
            return
        }

        this.range = { from: trigger.from, to: trigger.to }
        this.query = trigger.query

        // Lazy-load blocks from the panel's Alpine state on first open.
        // window.Alpine is exposed by Filament; the panel's x-data is on
        // .fi-fo-rich-editor-custom-blocks-list inside the same wrapper.
        if (!this.allBlocks) {
            const wrapper = view.dom.closest('.fi-fo-rich-editor')
            const panelEl = wrapper?.querySelector(
                '.fi-fo-rich-editor-custom-blocks-list',
            )
            if (panelEl && window.Alpine) {
                const data = window.Alpine.$data(panelEl)
                this.allBlocks = data?.blocks
                    ? Object.values(data.blocks).flat()
                    : []
            } else {
                this.allBlocks = []
            }
        }

        this.refresh()
        if (!this.isOpen) this.open()
        else this.position()
    }

    detectTrigger(state) {
        const { selection } = state
        if (!selection.empty) return null
        const $from = selection.$from
        if ($from.parent.type.name === NODE_TYPE) return null
        const text = $from.parent.textBetween(
            0,
            $from.parentOffset,
            undefined,
            '\n',
        )
        const match = text.match(SLASH_TRIGGER)
        if (!match) return null
        const trigger = match[1]
        const triggerStart = $from.pos - trigger.length
        return {
            query: trigger.slice(1).toLowerCase(),
            from: triggerStart,
            to: $from.pos,
        }
    }

    refresh() {
        const terms = this.query
            .toLowerCase()
            .trim()
            .split(/\s+/)
            .filter(Boolean)

        this.items = this.allBlocks.filter((b) => {
            if (terms.length === 0) return true
            return terms.every((t) => b.searchable.includes(t))
        })
        this.activeIndex = 0
        this.render()
    }

    render() {
        if (this.items.length === 0) {
            this.popup.innerHTML =
                '<div class="fi-cms-slash-empty">No matching blocks</div>'
            return
        }

        this.popup.innerHTML = ''
        this.items.forEach((item, index) => {
            const el = document.createElement('button')
            el.type = 'button'
            el.className = 'fi-cms-slash-item'
            if (index === this.activeIndex) el.classList.add('is-active')
            el.dataset.index = String(index)
            el.setAttribute('role', 'option')
            el.innerHTML = `
                <span class="fi-cms-slash-icon">${item.iconHtml || ''}</span>
                <span class="fi-cms-slash-label">${item.label}</span>
            `
            el.addEventListener('mousedown', (event) => {
                event.preventDefault()
                this.activeIndex = index
                this.select()
            })
            el.addEventListener('mousemove', () => {
                if (this.activeIndex === index) return
                this.activeIndex = index
                this.render()
            })
            this.popup.appendChild(el)
        })
    }

    open() {
        this.isOpen = true
        this.popup.style.display = 'block'
        this.position()
    }

    close() {
        this.isOpen = false
        this.popup.style.display = 'none'
        this.range = null
        this.query = ''
        this.items = []
        this.activeIndex = 0
    }

    position() {
        if (!this.range) return
        const coords = this.view.coordsAtPos(this.range.from)
        this.popup.style.position = 'fixed'
        this.popup.style.left = `${coords.left}px`
        this.popup.style.top = `${coords.bottom + 4}px`
    }

    handleKeyDown(event) {
        if (event.key === 'Escape') {
            this.close()
            return true
        }
        if (event.key === 'ArrowDown') {
            this.activeIndex = Math.min(
                this.activeIndex + 1,
                this.items.length - 1,
            )
            this.render()
            return true
        }
        if (event.key === 'ArrowUp') {
            this.activeIndex = Math.max(this.activeIndex - 1, 0)
            this.render()
            return true
        }
        if (event.key === 'Enter' || event.key === 'Tab') {
            if (this.items.length === 0) {
                this.close()
                return false
            }
            this.select()
            return true
        }
        return false
    }

    select() {
        const item = this.items[this.activeIndex]
        if (!item || !this.range) return

        const { from, to } = this.range
        const tr = this.view.state.tr.delete(from, to)
        this.view.dispatch(tr)

        const blockId = item.id
        const view = this.view

        // Defer dispatch so Filament's reactive editorSelection updates to
        // the post-delete cursor before the panel reads it.
        queueMicrotask(() => {
            view.dom.dispatchEvent(
                new CustomEvent(SLASH_INSERT_EVENT, {
                    detail: { blockId },
                    bubbles: true,
                }),
            )
        })

        this.close()
    }

    destroy() {
        document.removeEventListener('mousedown', this.docClickHandler)
        this.popup.remove()
        delete this.view._cmsSlashView
    }
}
