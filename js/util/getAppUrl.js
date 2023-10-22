export function getAppUrl() {
    const appUrlTag = document.head.querySelector('meta[name="w-app-url"]')

    if (appUrlTag) {
        return appUrlTag.content
    }

    return window.wire_app_url ?? window.location.origin ?? ''
}
