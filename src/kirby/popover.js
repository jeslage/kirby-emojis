// Opens components imperatively (Writer, Textarea) inside the Panel's app context,
// so k-* components and the Panel's globals work in them.
import { h, render } from "vue";

let current = null;

export function closePopover() {
    if (!current) return;
    const { host, onClose } = current;
    current = null;
    render(null, host);
    host.remove();
    onClose?.();
}

export function openPopover(component, props = {}, { onClose } = {}) {
    closePopover();

    const host = document.createElement("div");
    document.body.append(host);

    const vnode = h(component, { ...props, onClose: closePopover });
    vnode.appContext = window.panel.app._context;
    render(vnode, host);

    current = { host, onClose };
    return closePopover;
}

export function isPopoverOpen() {
    return current !== null;
}
