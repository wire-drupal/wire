export function getAppUrl() {
    return window.wire_app_url ?? window.location.origin ?? ''
}
