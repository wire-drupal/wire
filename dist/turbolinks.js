if (typeof window.Wire === 'undefined') {
  throw 'Wire Turbolinks Plugin: window.Wire is undefined.'
}

var firstTime = true

function wireTurboAfterFirstVisit() {
  // We only want this handler to run AFTER the first load.
  if (firstTime) {
    firstTime = false

    return
  }

  window.Wire.restart()

  window.Alpine && window.Alpine.flushAndStopDeferringMutations && window.Alpine.flushAndStopDeferringMutations()
}

function wireTurboBeforeCache() {
  document.querySelectorAll('[wire\\:id]').forEach(function (el) {
    const component = el.__wire || null;

    if (component !== null) {
      const dataObject = {
        fingerprint: component.fingerprint,
        serverMemo: component.serverMemo,
        effects: component.effects,
      };
      el.setAttribute('wire:initial-data', JSON.stringify(dataObject));
    }
  });

  window.Alpine && window.Alpine.deferMutations && window.Alpine.deferMutations()
}

document.addEventListener("turbo:load", wireTurboAfterFirstVisit)
document.addEventListener("turbo:before-cache", wireTurboBeforeCache);

document.addEventListener("turbolinks:load", wireTurboAfterFirstVisit)
document.addEventListener("turbolinks:before-cache", wireTurboBeforeCache);

Wire.hook('beforePushState', (state) => {
  if (!state.turbolinks) {
    state.turbolinks = {}
  }
})

Wire.hook('beforeReplaceState', (state) => {
  if (!state.turbolinks) {
    state.turbolinks = {}
  }
})
