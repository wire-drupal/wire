
// Unsupported way of getting csrf token.
export function getCsrfToken() {
    const tokenTag = document.head.querySelector('meta[name="csrf-token"]')

    if (tokenTag) {
        return tokenTag.content
    }

    return window.wire_token ?? undefined
}
