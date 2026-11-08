// Inline decorations for Kirby's Writer without bundling prosemirror-view (~150 kB):
// DecorationSet is taken from the editor itself, Decoration is a compatible port of
// prosemirror-view's inline decoration (MIT, https://github.com/ProseMirror/prosemirror-view).
// Only inline decorations inside a single text node are supported, which is all the emoji images need.

const noSpec = {};

function compareObjs(a, b) {
    if (a === b) return true;
    for (const p in a) if (a[p] !== b[p]) return false;
    for (const p in b) if (!(p in a)) return false;
    return true;
}

class InlineType {
    constructor(attrs, spec) {
        this.attrs = attrs;
        this.spec = spec || noSpec;
    }

    map(span, mapping, offset, oldOffset) {
        const from = mapping.map(span.from + oldOffset, this.spec.inclusiveStart ? -1 : 1) - offset;
        const to = mapping.map(span.to + oldOffset, this.spec.inclusiveEnd ? 1 : -1) - offset;
        return from >= to ? null : new Decoration(from, to, this);
    }

    valid(_, span) {
        return span.from < span.to;
    }

    eq(other) {
        return (
            this === other ||
            (other instanceof InlineType && compareObjs(this.attrs, other.attrs) && compareObjs(this.spec, other.spec))
        );
    }

    destroy() {}
}

export class Decoration {
    constructor(from, to, type) {
        this.from = from;
        this.to = to;
        this.type = type;
    }

    copy(from, to) {
        return new Decoration(from, to, this.type);
    }

    eq(other, offset = 0) {
        return this.type.eq(other.type) && this.from + offset === other.from && this.to + offset === other.to;
    }

    map(mapping, offset, oldOffset) {
        return this.type.map(this, mapping, offset, oldOffset);
    }

    static inline(from, to, attrs, spec) {
        return new Decoration(from, to, new InlineType(attrs, spec));
    }

    get spec() {
        return this.type.spec;
    }

    get inline() {
        return this.type instanceof InlineType;
    }

    get widget() {
        return false;
    }
}

let DecorationSet = null;

// The editor's own DecorationSet class: without other decorations, the document view's
// decorations are the (empty) DecorationSet.empty instance
export function resolveDecorationClasses(view) {
    if (!DecorationSet) {
        const candidate = view?.docView?.innerDeco?.constructor;
        if (typeof candidate?.create === "function" && candidate.empty instanceof candidate) DecorationSet = candidate;
    }

    return DecorationSet ? { Decoration, DecorationSet } : null;
}
