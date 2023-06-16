export function getCsrfToken() {
    const tokenTag = document.head.querySelector('meta[name="w-csrf-token"]')

    if (tokenTag) {
        return tokenTag.content
    }

    return window.wire_token ?? undefined
}
