(function (drupalSettings) {

  var isDebug = drupalSettings.wireIsDebug || false;

  if (window.wire === undefined) {
    window.wire = new Wire();
    window.wire.devTools(isDebug);
    window.Wire = window.wire;
    window.wire_app_url = drupalSettings.wireAppUrl || '/';
  }

  if (isDebug && window.Alpine) {
    document.addEventListener("DOMContentLoaded", function () {
      setTimeout(function () {
        console.warn("Wire: It looks like AlpineJS has already been loaded. Make sure Wire\'s scripts are loaded before Alpine.\\n\\n Reference docs for more info: https://wire-drupal.com/docs/alpine-js")
      })
    });
  }

  window.deferLoadingAlpine = function (callback) {
    window.addEventListener('wire:load', function () {
      callback();
    });
  };

  var started = false;

  window.addEventListener('alpine:initializing', function () {
    if (!started) {
      window.wire.start();

      started = true;
    }
  });

  document.addEventListener("DOMContentLoaded", function () {
    if (!started) {
      window.wire.start();

      started = true;
    }
  });

})(drupalSettings);
