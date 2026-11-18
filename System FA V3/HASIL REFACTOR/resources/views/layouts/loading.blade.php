<body
  class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased selection:bg-red-500 selection:text-white">

  <!-- FULLSCREEN PROFESSIONAL LOADING SCREEN OVERLAY (Anti-FOUC & Industrial Brand Experience) -->
  <div id="app-loading-screen"
    class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-slate-950 text-white transition-opacity duration-500 select-none">
    <div class="flex flex-col items-center text-center p-6 space-y-6 max-w-sm">
      <!-- Animated Emblem / Shield Icon with Outer Glowing Pulse Ring -->
      <div class="relative flex items-center justify-center">
        <div class="absolute w-24 h-24 rounded-full bg-red-600/20 animate-ping"></div>
        <div
          class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-red-700 via-red-600 to-rose-500 shadow-2xl shadow-red-600/40 flex items-center justify-center text-white text-3xl border border-red-400/30 relative z-10">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
      </div>

      <!-- Title & Traceability System Branding -->
      <div class="space-y-1.5">
        <h2 class="text-xl sm:text-2xl font-black tracking-widest text-white uppercase drop-shadow-sm font-sans">
          FINAL ASSEMBLY
        </h2>
        <div
          class="inline-block px-3 py-1 rounded-full bg-red-950/80 border border-red-500/30 text-red-400 text-[10.5px] font-extrabold uppercase tracking-widest font-mono">
          SISTEM TRACEABILITY
        </div>
      </div>

      <!-- Dynamic Modern Animated Spinner & Progress Bar -->
      <div class="space-y-3.5 w-full max-w-[220px] pt-1">
        <!-- Modern Mini Loading Bar -->
        <div class="w-full bg-slate-800/80 rounded-full h-1.5 overflow-hidden p-0.5 border border-slate-700/50">
          <div class="bg-gradient-to-r from-red-600 via-rose-500 to-red-400 h-full rounded-full animate-pulse"></div>
        </div>

        <div class="flex items-center justify-center space-x-2 text-slate-400 text-xs font-medium font-sans">
          <i class="fa-solid fa-circle-notch fa-spin text-red-500 text-sm"></i>
          <span>Memuat sistem &amp; data...</span>
        </div>
      </div>
    </div>
  </div>

  <script>
      // Failsafe: Pastikan loading screen tidak macet selamanya jika terjadi error jaringan atau skrip
      (function () {
        function dismissLoader() {
          var loader = document.getElementById('app-loading-screen');
          if (loader && loader.style.opacity !== '0') {
            loader.style.transition = 'opacity 0.35s ease';
            loader.style.opacity = '0';
            loader.style.pointerEvents = 'none';
            setTimeout(function () {
              if (loader && loader.parentNode) {
                loader.parentNode.removeChild(loader);
              }
            }, 380);
          }
        }
        window.__dismissAppLoader = dismissLoader;
        window.addEventListener('load', function () {
          setTimeout(dismissLoader, 300);
        });
        // Batas waktu maksimum fallback
        setTimeout(dismissLoader, 2500);
      })();
  </script>

