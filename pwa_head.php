<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#000000">
<style>
  html, body {
    margin: 0;
    overscroll-behavior: none;
    touch-action: manipulation;
    -webkit-text-size-adjust: 100%;
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    user-select: none;
  }
  input, textarea, select { user-select: text; }
</style>
<script>
(function () {
  var DESIGN_WIDTH = 1100;   // the width your layout is designed for
  function fit() {
    var z = Math.min(window.innerWidth / DESIGN_WIDTH, 1.6);
    document.documentElement.style.zoom = z;
    document.documentElement.style.setProperty('--z', z);
  }
  fit();
  window.addEventListener('resize', fit);
  window.addEventListener('orientationchange', fit);
  document.addEventListener('gesturestart', function (e) { e.preventDefault(); });
  document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
})();
</script>