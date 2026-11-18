<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Administrator & CMS - Sistem Final Assembly P9</title>
  <link rel="icon" type="image/png" href="/emblem.png">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
          }
        }
      }
    }
  </script>

  <!-- FontAwesome 6.5.1 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Vue 3 CDN -->
  <script src="https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.global.prod.js"></script>

  <!-- Chart.js (Local Bundle with Dynamic CDN Fallback) -->
  <script src="/chart.min.js"></script>
  <script>
    if (typeof Chart === 'undefined') {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
      document.head.appendChild(s);
    }
  </script>

  <style>
    * {
      box-sizing: border-box;
      font-variant-numeric: tabular-nums lining-nums;
    }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    [v-cloak] { display: none !important; }

    /* Custom subtle scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    /* Ambient sky atmosphere for login gate */
    .sky-bg {
      background: radial-gradient(circle at 50% 15%, #e0f2fe 0%, #f0f9ff 35%, #f8fafc 85%, #f1f5f9 100%);
    }
    .halo-ring {
      position: absolute;
      border-radius: 9999px;
      pointer-events: none;
      border: 1px solid rgba(255, 255, 255, 0.7);
      box-shadow: inset 0 0 40px rgba(255, 255, 255, 0.5);
    }
  </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex flex-col antialiased selection:bg-red-500 selection:text-white">

  <div id="adminApp" v-cloak class="min-h-screen flex flex-col">
    @verbatim

    <!-- ========================================================================= -->
    <!-- STATE 1: GATE LOGIN KHUSUS ADMINISTRATOR (MATCHING IMAGE 2 REFERENCE)    -->
    <!-- ========================================================================= -->
    <div v-if="!isCmsAuthenticated" class="sky-bg min-h-screen w-full flex items-center justify-center p-4 relative overflow-hidden select-none">
      
      <!-- Ambient Sky / Orbital Elements -->
      <div class="halo-ring w-[680px] h-[680px] -top-24 sm:-top-16 opacity-70"></div>
      <div class="halo-ring w-[920px] h-[920px] -top-52 opacity-40"></div>
      <div class="absolute -top-32 w-96 h-96 bg-white/70 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Main Floating Login Card -->
      <div class="w-full max-w-[420px] bg-white/90 backdrop-blur-2xl rounded-3xl p-8 sm:p-10 shadow-[0_25px_60px_-15px_rgba(15,23,42,0.08)] border border-white/80 relative z-10 transition-all duration-300">
        
        <!-- Top Icon / Logo Container (NKRI Emblem) -->
        <div class="w-14 h-14 bg-white rounded-2xl shadow-md shadow-slate-200/50 border border-slate-100 flex items-center justify-center mx-auto mb-6">
          <img src="/emblem.png" alt="Logo NKRI" class="w-8 h-8 object-contain" onerror="this.outerHTML='<i class=\'fa-solid fa-shield-halved text-red-600 text-2xl\'></i>'">
        </div>

        <!-- Title (Clean without extra text) -->
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 text-center tracking-tight leading-snug mb-7">
          Masuk kedalam Halaman Admin
        </h1>

        <!-- Form Login Gate -->
        <form @submit.prevent="submitCmsLogin" class="space-y-4">
          
          <!-- Username Field -->
          <div class="space-y-1">
            <div class="relative flex items-center">
              <i class="fa-solid fa-user text-slate-400 absolute left-4 text-sm pointer-events-none"></i>
              <input 
                type="text" 
                v-model="loginForm.username" 
                autocomplete="username" 
                placeholder="Nama Pengguna (Username)" 
                required 
                autofocus
                class="w-full bg-slate-100/80 hover:bg-slate-100 focus:bg-white border border-slate-200/70 focus:border-red-500 focus:ring-4 focus:ring-red-500/10 rounded-2xl py-3.5 pl-11 pr-4 text-sm font-semibold text-slate-800 placeholder-slate-400 outline-none transition-all duration-200"
              >
            </div>
          </div>

          <!-- Password Field -->
          <div class="space-y-1">
            <div class="relative flex items-center">
              <i class="fa-solid fa-lock text-slate-400 absolute left-4 text-sm pointer-events-none"></i>
              <input 
                :type="showPassword ? 'text' : 'password'" 
                v-model="loginForm.password" 
                autocomplete="current-password" 
                placeholder="Kata Sandi (Password)" 
                required 
                class="w-full bg-slate-100/80 hover:bg-slate-100 focus:bg-white border border-slate-200/70 focus:border-red-500 focus:ring-4 focus:ring-red-500/10 rounded-2xl py-3.5 pl-11 pr-11 text-sm font-semibold text-slate-800 placeholder-slate-400 outline-none transition-all duration-200"
              >
              <button 
                type="button" 
                @click="showPassword = !showPassword" 
                class="absolute right-4 text-slate-400 hover:text-slate-600 transition p-1 cursor-pointer"
                title="Lihat / Sembunyikan Kata Sandi"
              >
                <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'" class="text-sm"></i>
              </button>
            </div>
          </div>

          <!-- Alert Error jika login gagal -->
          <div v-if="loginError" class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-2xl p-3.5 flex items-start gap-2.5 leading-relaxed text-left">
            <i class="fa-solid fa-circle-exclamation text-base mt-0.5 shrink-0 text-red-600"></i>
            <span>{{ loginError }}</span>
          </div>

          <!-- Submit Button (Red Theme Palette) -->
          <button 
            type="submit" 
            :disabled="loginLoading"
            class="w-full bg-red-600 hover:bg-red-700 active:scale-[0.99] disabled:opacity-50 text-white font-bold py-3.5 px-6 rounded-2xl text-sm transition-all duration-200 shadow-lg shadow-red-600/25 hover:shadow-red-600/35 flex items-center justify-center gap-2 mt-2 cursor-pointer"
          >
            <i v-if="loginLoading" class="fa-solid fa-circle-notch fa-spin"></i>
            <span>{{ loginLoading ? 'Memverifikasi...' : 'Masuk' }}</span>
          </button>

        </form>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- STATE 2: CMS PORTAL & DASHBOARD (MATCHING IMAGE 1 SAAS REFERENCE)         -->
    <!-- ========================================================================= -->
    <div v-else class="flex-grow flex h-screen overflow-hidden bg-[#f8fafc] font-sans relative">
      
      <!-- DESKTOP PERSISTENT SIDEBAR -->
      <aside class="hidden md:flex flex-col w-64 bg-white border-r border-slate-200/80 shrink-0 h-screen sticky top-0 justify-between select-none z-20">
        
        <div class="flex flex-col flex-1 min-h-0 overflow-y-auto">
          
          <!-- Brand Header (Workspace Profile) -->
          <div class="p-5 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center space-x-3 min-w-0">
              <div class="w-9 h-9 bg-white border border-slate-200 rounded-xl flex items-center justify-center p-1 shadow-xs shrink-0">
                <img src="/emblem.png" alt="Logo" class="w-full h-full object-contain" onerror="this.outerHTML='<i class=\'fa-solid fa-shield-halved text-red-600 text-base\'></i>'">
              </div>
              <div class="leading-tight truncate">
                <h2 class="font-extrabold text-sm text-slate-900 tracking-tight truncate">Sistem FA P9</h2>
                <p class="text-[11px] text-slate-400 font-medium truncate">Portal Administrator</p>
              </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg transition" title="Menu">
              <i class="fa-solid fa-angles-left text-xs"></i>
            </button>
          </div>

          <!-- Search Bar (Q Search ⌘K) -->
          <div class="px-4 pt-4 pb-2">
            <div class="relative flex items-center">
              <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-3 text-xs pointer-events-none"></i>
              <input 
                type="text" 
                v-model="userSearchQuery" 
                placeholder="Search..." 
                class="w-full bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200/80 rounded-xl py-2 pl-8 pr-9 text-xs font-medium text-slate-700 placeholder-slate-400 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500/20"
              >
              <!-- <span class="absolute right-2.5 text-[10px] font-mono font-bold text-slate-400 bg-white px-1.5 py-0.5 rounded border border-slate-200">⌘</span> -->
            </div>
          </div>

          <!-- Navigation Menus -->
          <nav class="px-3 py-3 space-y-6 flex-1">
            
            <!-- MAIN MENU GROUP -->
            <div class="space-y-1">
              <div class="text-[10px] font-extrabold text-slate-400 tracking-wider uppercase px-3 py-1">MAIN MENU</div>
              
              <!-- 1. DASHBOARD -->
              <button 
                type="button" 
                @click="switchTab('dashboard')"
                :class="activeTab === 'dashboard' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-gauge-high text-sm w-4 text-center" :class="activeTab === 'dashboard' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Dashboard</span>
                </div>
                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-red-100 text-red-700" v-if="activeTab === 'dashboard'">Live</span>
              </button>

              <!-- 2. LAPORAN PRODUKSI (DEDICATED MENU) -->
              <button 
                type="button" 
                @click="switchTab('reports_kpi')"
                :class="activeTab === 'reports_kpi' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-chart-line text-sm w-4 text-center" :class="activeTab === 'reports_kpi' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Laporan Produksi</span>
                </div>
                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600"></span>
              </button>

              <!-- 3. KELOLA PENGGUNA -->
              <button 
                type="button" 
                @click="switchTab('users')"
                :class="activeTab === 'users' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-users-gear text-sm w-4 text-center" :class="activeTab === 'users' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Kelola Pengguna</span>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="activeTab === 'users' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'">
                  {{ usersList.length }}
                </span>
              </button>

              <!-- 3. PEMINDAI ANOMALI -->
              <button 
                type="button" 
                @click="switchTab('anomalies')"
                :class="activeTab === 'anomalies' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-wand-magic-sparkles text-sm w-4 text-center" :class="activeTab === 'anomalies' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Pemindai Anomali</span>
                </div>
                <span v-if="anomalySummary.total_anomalies > 0" class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-red-100 text-red-700">
                  {{ anomalySummary.total_anomalies }}
                </span>
              </button>

              <!-- 4. PEMBERSIHAN DATABASE -->
              <button 
                type="button" 
                @click="switchTab('cleanup')"
                :class="activeTab === 'cleanup' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-broom text-sm w-4 text-center" :class="activeTab === 'cleanup' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Pembersihan Database</span>
                </div>
              </button>

            </div>

            <!-- SISTEM & MONITORING GROUP -->
            <div class="space-y-1">
              <div class="text-[10px] font-extrabold text-slate-400 tracking-wider uppercase px-3 py-1">SISTEM & MONITORING</div>

              <!-- 5. BACKEND ERROR LOGGER -->
              <button 
                type="button" 
                @click="switchTab('errors')"
                :class="activeTab === 'errors' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-triangle-exclamation text-sm w-4 text-center" :class="activeTab === 'errors' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Backend Error Log</span>
                </div>
                <span v-if="errorLogs.length > 0" class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600">
                  {{ errorLogs.length }}
                </span>
              </button>

              <!-- 6. SLOW QUERY MONITOR -->
              <button 
                type="button" 
                @click="switchTab('slow_queries')"
                :class="activeTab === 'slow_queries' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-stopwatch text-sm w-4 text-center" :class="activeTab === 'slow_queries' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Slow Query Monitor</span>
                </div>
              </button>

              <!-- 7. PENGATURAN DO & CLOUD -->
              <button 
                type="button" 
                @click="switchTab('do_settings')"
                :class="activeTab === 'do_settings' ? 'bg-red-50/80 text-red-600 font-bold border-l-4 border-red-600' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium border-l-4 border-transparent'"
                class="w-full text-left px-3 py-2.5 rounded-r-xl text-xs transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center space-x-2.5">
                  <i class="fa-solid fa-file-invoice text-sm w-4 text-center" :class="activeTab === 'do_settings' ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-700'"></i>
                  <span>Pengaturan DO & Cloud</span>
                </div>
              </button>

            </div>

          </nav>

        </div>

        <!-- Sidebar Footer: Admin Profile & Operational Links -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/50 space-y-3">
          
          <div class="flex items-center space-x-3 px-2 py-1.5">
            <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold shrink-0">
              <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="leading-tight truncate flex-1 min-w-0">
              <div class="text-xs font-bold text-slate-900 truncate">{{ adminUser.full_name || adminUser.username || 'Administrator' }}</div>
              <div class="text-[10px] text-slate-400 truncate font-mono">{{ adminUser.username || 'admin' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-1">
            <a href="/" target="_blank" class="bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-center py-2 px-2 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1.5 shadow-2xs">
              <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
              <span class="truncate">Operasional</span>
            </a>
            <button type="button" @click="logoutCms" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-center py-2 px-2 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1.5 cursor-pointer">
              <i class="fa-solid fa-lock text-[10px]"></i>
              <span>Kunci</span>
            </button>
          </div>

        </div>

      </aside>

      <!-- MOBILE SIDEBAR DRAWER -->
      <transition enter-active-class="transition-opacity ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isCmsSidebarOpen" class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs z-40 md:hidden" @click="isCmsSidebarOpen = false"></div>
      </transition>
      <transition enter-active-class="transition-transform ease-out duration-300" enter-from-class="-translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform ease-in duration-200" leave-from-class="translate-x-0" leave-to-class="-translate-x-full">
        <aside v-if="isCmsSidebarOpen" class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-white text-slate-800 shadow-2xl border-r border-slate-200 flex flex-col justify-between overflow-y-auto md:hidden">
          <div class="p-4 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
              <div class="w-8 h-8 bg-white border border-slate-200 rounded-lg flex items-center justify-center p-1 shadow-xs">
                <img src="/emblem.png" alt="Logo" class="w-full h-full object-contain">
              </div>
              <span class="font-extrabold text-sm text-slate-900">Sistem FA P9</span>
            </div>
            <button type="button" @click="isCmsSidebarOpen = false" class="text-slate-400 hover:text-slate-700 p-2 rounded-lg">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>
          <nav class="p-4 space-y-4 flex-1">
            <button @click="switchTab('dashboard'); isCmsSidebarOpen = false" :class="activeTab === 'dashboard' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-gauge-high"></i> Dashboard
            </button>
            <button @click="switchTab('reports_kpi'); isCmsSidebarOpen = false" :class="activeTab === 'reports_kpi' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-chart-line"></i> Laporan Produksi
            </button>
            <button @click="switchTab('users'); isCmsSidebarOpen = false" :class="activeTab === 'users' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-users-gear"></i> Kelola Pengguna
            </button>
            <button @click="switchTab('anomalies'); isCmsSidebarOpen = false" :class="activeTab === 'anomalies' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-wand-magic-sparkles"></i> Pemindai Anomali
            </button>
            <button @click="switchTab('cleanup'); isCmsSidebarOpen = false" :class="activeTab === 'cleanup' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-broom"></i> Pembersihan Database
            </button>
            <button @click="switchTab('errors'); isCmsSidebarOpen = false" :class="activeTab === 'errors' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-triangle-exclamation"></i> Error Logger
            </button>
            <button @click="switchTab('slow_queries'); isCmsSidebarOpen = false" :class="activeTab === 'slow_queries' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-stopwatch"></i> Slow Queries
            </button>
            <button @click="switchTab('do_settings'); isCmsSidebarOpen = false" :class="activeTab === 'do_settings' ? 'bg-red-50 text-red-600 font-bold' : 'text-slate-600'" class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center gap-2.5">
              <i class="fa-solid fa-file-invoice"></i> Pengaturan DO
            </button>
          </nav>
        </aside>
      </transition>

      <!-- MAIN WORKSPACE CONTENT AREA -->
      <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
        
        <!-- STICKY TOPBAR HEADER (MATCHING IMAGE 1) -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 px-5 sm:px-8 py-3.5 flex items-center justify-between gap-4">
          
          <!-- Left: Title & Mobile Toggle -->
          <div class="flex items-center space-x-3">
            <button type="button" @click="isCmsSidebarOpen = true" class="md:hidden text-slate-700 hover:text-red-600 p-2 rounded-xl border border-slate-200">
              <i class="fa-solid fa-bars text-base"></i>
            </button>
            <div>
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">
                <span v-if="activeTab === 'dashboard'">Dashboard</span>
                <span v-else-if="activeTab === 'reports_kpi'">Laporan Produksi</span>
                <span v-else-if="activeTab === 'users'">Kelola Pengguna</span>
                <span v-else-if="activeTab === 'anomalies'">Pemindai Anomali</span>
                <span v-else-if="activeTab === 'cleanup'">Pembersihan Database</span>
                <span v-else-if="activeTab === 'errors'">Backend Error Logger</span>
                <span v-else-if="activeTab === 'slow_queries'">Slow Query Monitor</span>
                <span v-else-if="activeTab === 'do_settings'">Pengaturan DO & Cloud</span>
              </h1>
            </div>
          </div>

          <!-- Right: Controls & Primary Actions -->
          <div class="flex items-center space-x-3">
            
            <!-- Refresh Button -->
            <button 
              type="button" 
              @click="fetchActiveTabData" 
              class="w-9 h-9 rounded-xl border border-slate-200/80 hover:bg-slate-50 text-slate-600 hover:text-slate-900 flex items-center justify-center transition shadow-2xs cursor-pointer"
              title="Perbarui Data"
            >
              <i class="fa-solid fa-rotate text-xs" :class="{'fa-spin': loading}"></i>
            </button>

            <!-- Notifications / Anomaly Indicator -->
            <button 
              type="button" 
              @click="switchTab('anomalies')"
              class="w-9 h-9 rounded-xl border border-slate-200/80 hover:bg-slate-50 text-slate-600 hover:text-slate-900 flex items-center justify-center relative transition shadow-2xs cursor-pointer"
              title="Notifikasi Anomali"
            >
              <i class="fa-regular fa-bell text-xs"></i>
              <span v-if="anomalySummary.total_anomalies > 0" class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-600 ring-2 ring-white"></span>
            </button>

            <!-- Admin Avatar Pill -->
            <div class="hidden sm:flex items-center space-x-2 pl-2 border-l border-slate-200">
              <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
                <i class="fa-solid fa-user-shield"></i>
              </div>
            </div>

            <!-- Primary Action Button (Dynamic per tab, matching Image 1) -->
            <button 
              v-if="activeTab === 'users'" 
              type="button" 
              @click="resetUserForm(); openUserForm = true"
              class="bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
            >
              <i class="fa-solid fa-plus text-[11px]"></i>
              <span>+ Tambah Pengguna</span>
            </button>

            <button 
              v-else-if="activeTab === 'anomalies'" 
              type="button" 
              @click="scanAnomalies"
              class="bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
            >
              <i class="fa-solid fa-wand-magic-sparkles text-[11px]"></i>
              <span>Pindai Sekarang</span>
            </button>

            <button 
              v-else-if="activeTab === 'dashboard'" 
              type="button" 
              @click="loadDashboardStats(false)"
              class="bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
            >
              <i class="fa-solid fa-arrows-rotate text-[11px]" :class="{'fa-spin': dashboardStats.loading}"></i>
              <span>Perbarui Ringkasan</span>
            </button>

            <button 
              v-else-if="activeTab === 'reports_kpi'" 
              type="button" 
              @click="loadDashboardStats(false)"
              class="bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
            >
              <i class="fa-solid fa-arrows-rotate text-[11px]" :class="{'fa-spin': dashboardStats.loading}"></i>
              <span>Perbarui Laporan</span>
            </button>

            <button 
              v-else-if="activeTab === 'errors'" 
              type="button" 
              @click="clearErrorLogs"
              class="bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
            >
              <i class="fa-solid fa-trash-can text-[11px]"></i>
              <span>Bersihkan Log</span>
            </button>

          </div>

        </header>

        <!-- SUBHEADER / SECONDARY TOOLBAR -->
        <div class="px-5 sm:px-8 py-3 bg-white border-b border-slate-200/60 flex flex-wrap items-center justify-between gap-3">
          
          <div class="flex items-center space-x-2">
            <!-- View Selector Pill -->
            <div class="inline-flex items-center bg-slate-100 rounded-lg p-0.5 border border-slate-200/70 text-xs font-semibold text-slate-700">
              <span class="px-2.5 py-1 bg-white rounded-md shadow-2xs font-bold text-slate-900 flex items-center gap-1.5">
                <i class="fa-solid fa-table-cells-large text-[11px]"></i>
                <span v-if="activeTab === 'dashboard'">Ringkasan Harian & Menu</span>
                <span v-else-if="activeTab === 'reports_kpi'">Laporan Produksi</span>
                <span v-else>Table View</span>
              </span>
            </div>

            <!-- Dept / Category Filter -->
            <div v-if="activeTab === 'users'" class="relative inline-block text-xs">
              <select v-model="userDeptFilter" class="bg-slate-100 border border-slate-200 text-slate-700 rounded-lg px-2.5 py-1 font-semibold focus:outline-none focus:bg-white cursor-pointer">
                <option value="all">Semua Departemen</option>
                <option value="administrator">Administrator</option>
                <option value="assembly">Assembly</option>
                <option value="qc">Quality Control</option>
                <option value="warehouse">Warehouse</option>
              </select>
            </div>
          </div>

          <!-- Right Filters & Stat Toggle -->
          <div class="flex items-center space-x-3 text-xs">
            <label class="flex items-center space-x-2 cursor-pointer text-slate-600 font-semibold select-none">
              <span>Show Statistics</span>
              <input type="checkbox" v-model="statsVisible" class="sr-only peer">
              <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600 relative"></div>
            </label>
          </div>

        </div>

        <!-- MAIN SCROLLABLE CONTENT BODY -->
        <main class="flex-grow p-5 sm:p-8 space-y-6">

          <!-- PROGRESS BAR PENGAMBILAN DATA CMS (REALTIME PROGRESS MONITOR) -->
          <div v-if="loadingProgress.active" class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border border-slate-700/80 rounded-2xl p-4 shadow-xl text-white transition-all duration-300">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2.5">
              <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                </span>
                <div>
                  <div class="text-xs font-bold tracking-wide flex items-center gap-2">
                    <span>Sinkronisasi Data Dashboard CMS</span>
                    <span class="px-2 py-0.5 rounded-full bg-red-500/20 text-red-300 text-[10px] font-mono border border-red-500/30">
                      @{{ loadingProgress.completedSteps }}/@{{ loadingProgress.totalSteps }} Proses Selesai
                    </span>
                  </div>
                  <div class="text-[11px] text-slate-300 mt-0.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-cloud-arrow-down text-slate-400 text-[10px]"></i>
                    <span>@{{ loadingProgress.detail || 'Menghubungkan ke server hosting...' }}</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-4">
                <div class="text-right">
                  <span class="text-lg font-black font-mono text-red-400">@{{ loadingProgress.percent }}%</span>
                  <div class="text-[10px] text-slate-400">
                    <span v-if="loadingProgress.recordsLoaded > 0">@{{ loadingProgress.recordsLoaded.toLocaleString() }} baris data</span>
                    <span v-else>Memproses query...</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Animated Gradient Progress Bar -->
            <div class="w-full bg-slate-700/60 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-600/50">
              <div class="bg-gradient-to-r from-red-500 via-amber-400 to-emerald-400 h-full rounded-full transition-all duration-300 ease-out shadow-sm"
                   :style="{ width: loadingProgress.percent + '%' }"></div>
            </div>
          </div>

          <!-- ================================================================= -->
          <!-- TOP 4 KPI STATS SUMMARY CARDS ROW (LAPORAN HARIAN & PIPELINE)     -->
          <!-- ================================================================= -->
          <div v-if="statsVisible && activeTab === 'dashboard'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- CARD 1: TOTAL SET DIRAKIT (GOOD SAJA) -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
              <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                <span>Total Set Dirakit (Good)</span>
                <i class="fa-solid fa-screwdriver-wrench text-red-500"></i>
              </div>
              <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ dashboardStats.assembly.good }} <span class="text-xs font-normal text-slate-400">Set Good</span>
              </div>
              <div class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                <i class="fa-solid fa-calendar-day text-[10px]"></i>
                <span>{{ dailyKpi.assembledGoodToday }} Selesai ({{ dailyKpi.periodLabel }})</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500">{{ dashboardStats.assembly.activeTrays }} Tray</span>
              </div>
            </div>

            <!-- CARD 2: SIAP KIRIM (LOLOS GUDANG) -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
              <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                <span>Siap Kirim (Lolos Gudang)</span>
                <i class="fa-solid fa-boxes-packing text-amber-500"></i>
              </div>
              <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ dashboardStats.pipeline.readyToShip }} <span class="text-xs font-normal text-slate-400">Set Siap Kirim</span>
              </div>
              <div class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                <i class="fa-solid fa-circle-check text-[10px]"></i>
                <span>{{ dailyKpi.warehouseAccToday }} ACC ({{ dailyKpi.periodLabel }})</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500">Menunggu DO</span>
              </div>
            </div>

            <!-- CARD 3: SET SUDAH DIKIRIM (DO) -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
              <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                <span>Set Sudah Dikirim</span>
                <i class="fa-solid fa-truck-fast text-indigo-500"></i>
              </div>
              <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ dashboardStats.delivery.shippedSNCount }} <span class="text-xs font-normal text-slate-400">Unit SN Dikirim</span>
              </div>
              <div class="text-[11px] font-semibold text-slate-600 flex items-center gap-1">
                <i class="fa-solid fa-file-invoice text-[10px] text-indigo-600"></i>
                <span class="font-bold text-slate-900">{{ dashboardStats.delivery.totalDO }} Dokumen DO</span>
                <span class="text-slate-300">•</span>
                <span>{{ dashboardStats.delivery.totalCrates }} Peti</span>
              </div>
            </div>

            <!-- CARD 4: RATA-RATA WAKTU OPERASIONAL (HARI INI / SESI AKTIF) -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
              <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                <span>Rata-rata Waktu ({{ dailyKpi.periodLabel }})</span>
                <i class="fa-solid fa-stopwatch" :class="dailyKpi.countAssy > 0 ? 'text-emerald-500' : 'text-slate-400'"></i>
              </div>
              <div class="text-2xl font-black text-slate-900 tracking-tight flex items-baseline gap-2 flex-wrap">
                <div class="flex items-baseline gap-1">
                  <span class="font-mono">{{ dailyKpi.avgAssyDisplayNumber }}</span>
                  <span class="text-xs font-bold text-slate-600">{{ dailyKpi.avgAssyDisplayUnit }} <span class="text-[10.5px] font-medium text-slate-400">Rakit / set</span></span>
                </div>
              </div>
              <div class="text-[11px] font-semibold flex items-center gap-1" :class="dashboardStats.times.trendColor">
                <i :class="dashboardStats.times.trendIcon"></i>
                <span>{{ dashboardStats.times.trendText }}</span>
              </div>
            </div>

          </div>

          <!-- ================================================================= -->
          <!-- 1. TAB DASHBOARD: RINGKASAN HARIAN, GRAFIK & MODUL OPERASIONAL    -->
          <!-- ================================================================= -->
          <div v-if="activeTab === 'dashboard'" class="space-y-6">
            
            <!-- SECTION A: GRAFIK TREN RATA-RATA WAKTU HARIAN (REQUESTED BY USER) -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                    <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Tren Rata-rata Waktu Pengerjaan per Hari</h2>
                  </div>
                  <p class="text-xs text-slate-500 mt-0.5">Pemantauan efisiensi waktu operator perakitan per hari</p>
                </div>

                <div class="flex items-center">
                  <!-- Legend Indicators -->
                  <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                    <span class="flex items-center gap-1.5">
                      <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                      <span>Rakit (Menit)</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- CHART CANVAS CONTAINER -->
              <div class="relative w-full h-64 sm:h-72 pt-2">
                <canvas id="dailyTimeTrendsChart"></canvas>
              </div>

              <!-- CHART SUMMARY CARDS -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs">
                <div class="p-3 bg-red-50/50 border border-red-100 rounded-xl flex items-center justify-between">
                  <div>
                    <div class="text-[11px] font-bold text-red-600 uppercase">Rata-rata Rakit ({{ dailyKpi.periodLabel }})</div>
                    <div class="text-lg font-black text-slate-900">{{ dailyKpi.avgAssyDisplay }} <span class="text-xs font-semibold text-slate-500">/ set</span></div>
                  </div>
                  <i class="fa-solid fa-screwdriver-wrench text-xl text-red-400"></i>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between">
                  <div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Status Performa</div>
                    <div class="text-xs font-bold text-slate-800 leading-snug">{{ dashboardStats.times.evaluationNote }}</div>
                  </div>
                  <i class="fa-solid fa-gauge-simple-high text-xl text-slate-400"></i>
                </div>
              </div>
            </div>

            <!-- SECTION B: RINGKASAN MODUL & MENU SISTEM (7 KARTU OPERASIONAL) -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-base font-extrabold text-slate-900">Ringkasan Modul & Menu Sistem</h2>
                  <p class="text-xs text-slate-500">Pemantauan menyeluruh alur produksi, pengujian, gudang, pengiriman, dan pengguna</p>
                </div>
                <div class="flex items-center gap-3">
                  <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-bold text-emerald-700" title="Data diperbarui otomatis setiap 10 detik">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Auto-Update</span>
                    <span v-if="lastSyncTimeStr" class="text-emerald-500 font-mono text-[10px]">• {{ lastSyncTimeStr }}</span>
                  </div>
                  <button type="button" @click="loadDashboardStats(false)" class="text-xs text-slate-600 hover:text-red-600 font-bold flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-arrows-rotate text-[11px]" :class="{'fa-spin': dashboardStats.loading}"></i>
                    <span>Sinkronisasi Status</span>
                  </button>
                </div>
              </div>

              <!-- GRID 7 SUMMARY CARDS FOR OPERATIONAL MENUS -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                
                <!-- CARD 1: AREA PERAKITAN (ASSEMBLY) -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-sm">
                          <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <div>
                          <h3 class="font-bold text-sm text-slate-900 leading-tight">Area Perakitan</h3>
                          <p class="text-[11px] text-slate-400">Assembly & Barcode P9</p>
                        </div>
                      </div>
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Aktif
                      </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-center">
                      <div class="p-2 bg-slate-50 rounded-xl">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Good</div>
                        <div class="text-base font-black text-emerald-600">{{ dashboardStats.assembly.good * 3 }} <span class="text-[10px] font-bold text-emerald-700">Part</span></div>
                        <div class="text-[9.5px] font-medium text-slate-400">({{ dashboardStats.assembly.good }} Set)</div>
                      </div>
                      <div class="p-2 bg-slate-50 rounded-xl">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Hold</div>
                        <div class="text-base font-black text-amber-600">{{ dashboardStats.assembly.hold * 3 }} <span class="text-[10px] font-bold text-amber-700">Part</span></div>
                        <div class="text-[9.5px] font-medium text-slate-400">({{ dashboardStats.assembly.hold }} Set)</div>
                      </div>
                      <div class="p-2 bg-slate-50 rounded-xl">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Scrap</div>
                        <div class="text-base font-black text-red-600">{{ dashboardStats.assembly.scrap * 3 }} <span class="text-[10px] font-bold text-red-700">Part</span></div>
                        <div class="text-[9.5px] font-medium text-slate-400">({{ dashboardStats.assembly.scrap }} Set)</div>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">{{ dashboardStats.assembly.activeTrays }} Tray Terdaftar</span>
                    <a href="/perakitan" target="_blank" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                      <span>Buka Menu</span>
                      <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                  </div>
                </div>

                <!-- CARD 2: TEST FUNCTIONAL 1 (TF 1) - GOOD & NOT GOOD SAJA -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                          <i class="fa-solid fa-microchip"></i>
                        </div>
                        <div>
                          <h3 class="font-bold text-sm text-slate-900 leading-tight">Test Functional 1</h3>
                          <p class="text-[11px] text-slate-400">Pengujian Fungsional Awal</p>
                        </div>
                      </div>
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                        Operasional
                      </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-center">
                      <div class="p-2.5 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                        <div class="text-[10px] font-bold text-emerald-700 uppercase">Good</div>
                        <div class="text-xl font-black text-emerald-600">{{ dashboardStats.tf1.passed * 3 }} <span class="text-xs font-bold text-emerald-700">Part</span></div>
                        <div class="text-[10px] font-medium text-slate-400">({{ dashboardStats.tf1.passed }} Set)</div>
                      </div>
                      <div class="p-2.5 bg-rose-50/50 border border-rose-100 rounded-xl">
                        <div class="text-[10px] font-bold text-rose-700 uppercase">Not Good</div>
                        <div class="text-xl font-black text-rose-600">{{ (dashboardStats.tf1.notGood + dashboardStats.tf1.hold) * 3 }} <span class="text-xs font-bold text-rose-700">Part</span></div>
                        <div class="text-[10px] font-medium text-slate-400">({{ dashboardStats.tf1.notGood + dashboardStats.tf1.hold }} Set)</div>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">Total: {{ dashboardStats.tf1.total * 3 }} Part ({{ dashboardStats.tf1.total }} Set)</span>
                    <a href="/tf1" target="_blank" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                      <span>Buka Menu</span>
                      <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                  </div>
                </div>

                <!-- CARD 3: QUALITY CONTROL (QC) - GOOD & NOT GOOD SAJA -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm">
                          <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <div>
                          <h3 class="font-bold text-sm text-slate-900 leading-tight">Quality Control (QC)</h3>
                          <p class="text-[11px] text-slate-400">Inspeksi & Test Functional 2</p>
                        </div>
                      </div>
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                        Standar P9
                      </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-center">
                      <div class="p-2.5 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                        <div class="text-[10px] font-bold text-emerald-700 uppercase">Good</div>
                        <div class="text-xl font-black text-emerald-600">{{ dashboardStats.qc.acc * 3 }} <span class="text-xs font-bold text-emerald-700">Part</span></div>
                        <div class="text-[10px] font-medium text-slate-400">({{ dashboardStats.qc.acc }} Set)</div>
                      </div>
                      <div class="p-2.5 bg-rose-50/50 border border-rose-100 rounded-xl">
                        <div class="text-[10px] font-bold text-rose-700 uppercase">Not Good</div>
                        <div class="text-xl font-black text-rose-600">{{ dashboardStats.qc.hold * 3 }} <span class="text-xs font-bold text-rose-700">Part</span></div>
                        <div class="text-[10px] font-medium text-slate-400">({{ dashboardStats.qc.hold }} Set)</div>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">{{ dashboardStats.qc.total > 0 ? (dashboardStats.qc.total * 3 + ' Part (' + dashboardStats.qc.total + ' Set) Diuji') : (dashboardStats.qc.waiting * 3 + ' Part Menunggu Uji TF 2') }}</span>
                    <a href="/qc" target="_blank" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                      <span>Buka Menu</span>
                      <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                  </div>
                </div>

                <!-- CARD 4: GUDANG & PENERIMAAN (WH/GR) - DOKUMEN, ACC & DECLINED -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                          <i class="fa-solid fa-warehouse"></i>
                        </div>
                        <div>
                          <h3 class="font-bold text-sm text-slate-900 leading-tight">Gudang & Penerimaan</h3>
                          <p class="text-[11px] text-slate-400">Penerimaan Fisik WH/GR</p>
                        </div>
                      </div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl space-y-2 text-xs">
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Total Dokumen WH/GR:</span>
                        <span class="font-bold text-slate-900">{{ dashboardStats.warehouse.totalDocs }} Dokumen</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Unit Diterima (ACC):</span>
                        <span class="font-black text-emerald-600">{{ dashboardStats.warehouse.acceptedSets * 3 }} Part <span class="text-[10px] font-normal text-slate-400">({{ dashboardStats.warehouse.acceptedSets }} Set)</span></span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Unit Ditolak (Declined):</span>
                        <span class="font-bold" :class="dashboardStats.warehouse.declinedSets > 0 ? 'text-red-600' : 'text-slate-600'">
                          {{ dashboardStats.warehouse.declinedSets * 3 }} Part <span class="text-[10px] font-normal text-slate-400">({{ dashboardStats.warehouse.declinedSets }} Set)</span>
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">Verifikasi Masuk Gudang</span>
                    <a href="/penerimaan" target="_blank" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                      <span>Buka Menu</span>
                      <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                  </div>
                </div>

                <!-- CARD 5: PERAKITAN ULANG (RU) - BELUM vs SUDAH DIARAHKAN -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm">
                          <i class="fa-solid fa-arrows-rotate"></i>
                        </div>
                        <div>
                          <h3 class="font-bold text-sm text-slate-900 leading-tight">Perakitan Ulang (RU)</h3>
                          <p class="text-[11px] text-slate-400">Perakitan Ulang & Ganti Part</p>
                        </div>
                      </div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl space-y-2 text-xs">
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Belum Diarahkan ke Operator:</span>
                        <span class="font-black text-amber-600">{{ dashboardStats.reassembly.notDispatched * 3 }} Part <span class="text-[10px] font-normal text-slate-400">({{ dashboardStats.reassembly.notDispatched }} Set)</span></span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Sudah Diarahkan ke Operator:</span>
                        <span class="font-black text-emerald-600">{{ dashboardStats.reassembly.dispatched * 3 }} Part <span class="text-[10px] font-normal text-slate-400">({{ dashboardStats.reassembly.dispatched }} Set)</span></span>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">Antrean Rakit Ulang (PU)</span>
                    <a href="/rakit-ulang" target="_blank" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                      <span>Buka Menu</span>
                      <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                  </div>
                </div>

                <!-- CARD 6: DELIVERY ORDER (DO) - DOKUMEN, PETI, DUS, SN -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                          <i class="fa-solid fa-truck-fast"></i>
                        </div>
                        <div>
                          <h3 class="font-bold text-sm text-slate-900 leading-tight">Delivery Order (DO)</h3>
                          <p class="text-[11px] text-slate-400">Surat Jalan & Packing List</p>
                        </div>
                      </div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl space-y-1.5 text-xs">
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Total Dokumen DO:</span>
                        <span class="font-bold text-slate-900">{{ dashboardStats.delivery.totalDO }} Dokumen</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Peti Terdaftar:</span>
                        <span class="font-bold text-indigo-600">{{ dashboardStats.delivery.totalCrates }} Peti</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Dus Kemasan:</span>
                        <span class="font-bold text-slate-800">{{ dashboardStats.delivery.totalBoxes }} Dus</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">SN Dimasukkan:</span>
                        <span class="font-black text-emerald-600">{{ dashboardStats.delivery.shippedSNCount }} Part</span>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">{{ dashboardStats.delivery.cloudSynced }} Terunggah ke Cloud</span>
                    <a href="/delivery" target="_blank" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                      <span>Buka Menu</span>
                      <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                  </div>
                </div>

                <!-- CARD 7: KELOLA PENGGUNA (CMS) - OPERATOR & WAREHOUSE -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-sm">
                          <i class="fa-solid fa-users-gear"></i>
                        </div>
                        <div>
                          <h3 class="font-bold text-sm text-slate-900 leading-tight">Kelola Pengguna</h3>
                          <p class="text-[11px] text-slate-400">Hak Akses & Akun Sistem</p>
                        </div>
                      </div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl space-y-1.5 text-xs">
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Total Akun Terdaftar:</span>
                        <span class="font-black text-slate-900">{{ usersList.length }} Pengguna</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Administrator:</span>
                        <span class="font-bold text-red-600">{{ countCmsUsers() }} Akun</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Operator Assembly:</span>
                        <span class="font-bold text-blue-600">{{ countDeptUsers('assembly') }} Akun</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-slate-500">Warehouse (Gudang):</span>
                        <span class="font-bold text-amber-600">{{ countDeptUsers('warehouse') }} Akun</span>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">{{ usersList.length }} Akun Sistem</span>
                    <button type="button" @click="switchTab('users')" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1 cursor-pointer">
                      <span>Kelola Pengguna</span>
                      <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </button>
                  </div>
                </div>

              </div>
            </div>

          </div>

          <!-- ================================================================= -->
          <!-- 2. TAB LAPORAN PRODUKSI (DEDICATED MENU: HARIAN, MINGGUAN, BULANAN)-->
          <!-- ================================================================= -->
          <div v-else-if="activeTab === 'reports_kpi'" class="space-y-6">
            
            <!-- TOP BAR: HEADER & PERIOD SWITCHER -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4">
              <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                  <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-sm">
                      <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                      <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Pusat Laporan Produksi & Traceability</h2>
                      <p class="text-xs text-slate-500">Rekapitulasi terpadu kinerja perakitan, pengujian fungsional, suplai gudang, dan pengiriman</p>
                    </div>
                  </div>
                </div>

                <!-- PERIOD SWITCHER BUTTONS: [Harian] [Mingguan] [Bulanan] [Kustom] -->
                <div class="flex flex-wrap items-center gap-2">
                  <div class="inline-flex p-1 bg-slate-100 rounded-2xl border border-slate-200">
                    <button 
                      type="button" 
                      @click="setReportPeriod('daily')"
                      :class="reportPeriod === 'daily' ? 'bg-white text-red-600 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                      class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                      <i class="fa-solid fa-calendar-day text-[11px]"></i>
                      <span>Harian</span>
                    </button>
                    <button 
                      type="button" 
                      @click="setReportPeriod('weekly')"
                      :class="reportPeriod === 'weekly' ? 'bg-white text-red-600 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                      class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                      <i class="fa-solid fa-calendar-week text-[11px]"></i>
                      <span>Mingguan</span>
                    </button>
                    <button 
                      type="button" 
                      @click="setReportPeriod('monthly')"
                      :class="reportPeriod === 'monthly' ? 'bg-white text-red-600 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                      class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                      <i class="fa-solid fa-calendar-days text-[11px]"></i>
                      <span>Bulanan</span>
                    </button>
                    <button 
                      type="button" 
                      @click="setReportPeriod('custom')"
                      :class="reportPeriod === 'custom' ? 'bg-white text-red-600 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                      class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                      <i class="fa-solid fa-sliders text-[11px]"></i>
                      <span>Rentang Kustom</span>
                    </button>
                  </div>

                  <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-bold text-emerald-700" title="Data diperbarui otomatis setiap 10 detik">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Auto-Update</span>
                    <span v-if="lastSyncTimeStr" class="text-emerald-500 font-mono text-[10px]">• {{ lastSyncTimeStr }}</span>
                  </div>

                  <button 
                    type="button" 
                    @click="loadDashboardStats(false)" 
                    class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center gap-1.5 transition cursor-pointer"
                  >
                    <i class="fa-solid fa-arrows-rotate text-[11px]" :class="{'fa-spin': dashboardStats.loading}"></i>
                    <span>Refresh</span>
                  </button>
                </div>
              </div>

              <!-- INTERACTIVE DATE NAVIGATOR & BACKDATE SELECTOR -->
              <div class="p-3 bg-red-50/40 border border-red-100 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs">
                
                <!-- 1. IF HARIAN: DATE PICKER + PREV / NEXT / TODAY / JUMP TO LATEST -->
                <div v-if="reportPeriod === 'daily'" class="flex flex-wrap items-center gap-2">
                  <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-day text-red-600"></i>
                    <span>Pilih Tanggal:</span>
                  </span>
                  
                  <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 shadow-2xs">
                    <button type="button" @click="shiftReportDate(-1)" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-600 font-bold cursor-pointer" title="Hari Sebelumnya (Backdate)">
                      <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>
                    
                    <input 
                      type="date" 
                      v-model="selectedReportDate" 
                      @change="renderReportCharts()"
                      class="bg-transparent font-bold font-mono text-slate-900 text-xs px-2 py-1 outline-none cursor-pointer"
                    >
                    
                    <button type="button" @click="shiftReportDate(1)" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-600 font-bold cursor-pointer" title="Hari Berikutnya">
                      <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                  </div>

                  <button type="button" @click="setReportDateToday" class="px-2.5 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-bold text-[11px] text-slate-700 cursor-pointer shadow-2xs">
                    Hari Ini
                  </button>

                  <button v-if="latestProductionDate && selectedReportDate !== latestProductionDate" type="button" @click="jumpToLatestDate" class="px-2.5 py-1.5 rounded-xl bg-red-100 text-red-700 hover:bg-red-200 font-bold text-[11px] cursor-pointer flex items-center gap-1 shadow-2xs">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Lompat ke Data Terakhir ({{ latestProductionDate }})</span>
                  </button>
                </div>

                <!-- 2. IF MINGGUAN: PREV / NEXT WEEK -->
                <div v-else-if="reportPeriod === 'weekly'" class="flex flex-wrap items-center gap-2">
                  <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-week text-red-600"></i>
                    <span>Navigasi Minggu:</span>
                  </span>

                  <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 shadow-2xs">
                    <button type="button" @click="shiftReportWeek(-1)" class="px-2.5 py-1 rounded-lg hover:bg-slate-100 text-slate-700 font-bold flex items-center gap-1 cursor-pointer">
                      <i class="fa-solid fa-chevron-left text-[10px]"></i>
                      <span>Minggu Lalu</span>
                    </button>
                    <span class="px-2 text-slate-300 font-mono text-xs">|</span>
                    <button type="button" @click="setReportWeekCurrent" class="px-2.5 py-1 rounded-lg hover:bg-slate-100 font-bold text-[11px] text-slate-700 cursor-pointer" :class="selectedWeekOffset === 0 ? 'bg-red-50 text-red-600 font-black' : ''">
                      Minggu Ini
                    </button>
                    <span class="px-2 text-slate-300 font-mono text-xs">|</span>
                    <button type="button" @click="shiftReportWeek(1)" class="px-2.5 py-1 rounded-lg hover:bg-slate-100 text-slate-700 font-bold flex items-center gap-1 cursor-pointer">
                      <span>Minggu Depan</span>
                      <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                  </div>
                </div>

                <!-- 3. IF BULANAN: MONTH PICKER + PREV / NEXT -->
                <div v-else-if="reportPeriod === 'monthly'" class="flex flex-wrap items-center gap-2">
                  <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-days text-red-600"></i>
                    <span>Pilih Bulan:</span>
                  </span>

                  <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 shadow-2xs">
                    <button type="button" @click="shiftReportMonth(-1)" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-600 font-bold cursor-pointer" title="Bulan Sebelumnya">
                      <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>

                    <input 
                      type="month" 
                      v-model="selectedReportMonth" 
                      @change="renderReportCharts()"
                      class="bg-transparent font-bold font-mono text-slate-900 text-xs px-2 py-1 outline-none cursor-pointer"
                    >

                    <button type="button" @click="shiftReportMonth(1)" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-600 font-bold cursor-pointer" title="Bulan Berikutnya">
                      <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                  </div>

                  <button type="button" @click="setReportMonthCurrent" class="px-2.5 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-bold text-[11px] text-slate-700 cursor-pointer shadow-2xs">
                    Bulan Ini
                  </button>
                </div>

                <!-- 4. IF KUSTOM: FROM DATE - TO DATE -->
                <div v-else-if="reportPeriod === 'custom'" class="flex flex-wrap items-center gap-2">
                  <span class="font-bold text-slate-700 flex items-center gap-1">
                    <i class="fa-solid fa-sliders text-red-600"></i>
                    <span>Rentang Kustom:</span>
                  </span>

                  <div class="flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-xl border border-slate-200 shadow-2xs">
                    <label class="text-[10px] font-bold text-slate-400">DARI</label>
                    <input type="date" v-model="customStartDate" @change="renderReportCharts()" class="bg-transparent font-bold font-mono text-slate-900 text-xs outline-none cursor-pointer">
                    <span class="text-slate-300 font-bold">—</span>
                    <label class="text-[10px] font-bold text-slate-400">SAMPAI</label>
                    <input type="date" v-model="customEndDate" @change="renderReportCharts()" class="bg-transparent font-bold font-mono text-slate-900 text-xs outline-none cursor-pointer">
                  </div>

                  <div class="flex items-center gap-1">
                    <button type="button" @click="setQuickCustomRange('7_days')" class="px-2 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 font-bold text-[10px] text-slate-700 cursor-pointer">
                      7 Hari
                    </button>
                    <button type="button" @click="setQuickCustomRange('30_days')" class="px-2 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 font-bold text-[10px] text-slate-700 cursor-pointer">
                      30 Hari
                    </button>
                    <button type="button" @click="setQuickCustomRange('all')" class="px-2 py-1 rounded-lg bg-red-100 text-red-700 hover:bg-red-200 font-bold text-[10px] cursor-pointer">
                      Semua Data
                    </button>
                  </div>
                </div>

                <!-- ACTIVE RANGE BADGE -->
                <div class="text-[11px] font-mono font-bold bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-slate-800 flex items-center gap-1.5 shadow-2xs ml-auto">
                  <i class="fa-solid fa-clock text-red-600 text-[10px]"></i>
                  <span>{{ activePeriodDateRangeLabel }}</span>
                </div>

              </div>
            </div>

            <!-- 7 CORE METRIC SUMMARY CARDS FOR ACTIVE PERIOD -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              
              <!-- METRIC 1: SUPPLY GUDANG -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                  <span>Supply Gudang</span>
                  <i class="fa-solid fa-boxes-packing text-amber-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ activePeriodStats.supplySets }} <span class="text-xs font-normal text-slate-400">Set Disuplai</span>
                </div>
                <div class="text-[11px] font-semibold text-slate-600 flex items-center gap-1">
                  <span>{{ activePeriodStats.supplyTrays }} Tray Diterbitkan</span>
                  <span class="text-slate-300">•</span>
                  <span>{{ activePeriodStats.supplyParts }} Part</span>
                </div>
              </div>

              <!-- METRIC 2: ASSEMBLY MERAKIT -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                  <span>Assembly Merakit</span>
                  <i class="fa-solid fa-screwdriver-wrench text-red-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ activePeriodStats.assembledGood }} <span class="text-xs font-normal text-slate-400">Set Good</span>
                </div>
                <div class="text-[11px] font-semibold flex items-center gap-1" :class="activePeriodStats.assembledHold > 0 ? 'text-amber-600' : 'text-emerald-600'">
                  <span>{{ activePeriodStats.assembledHold }} Hold</span>
                  <span class="text-slate-300">•</span>
                  <span>{{ activePeriodStats.assembledScrap }} Scrap</span>
                </div>
              </div>

              <!-- METRIC 3: TEST FUNCTIONAL 1 (TF 1) -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                  <span>Test Functional 1</span>
                  <i class="fa-solid fa-microchip text-blue-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ activePeriodStats.tf1Good }} <span class="text-xs font-normal text-slate-400">Good</span>
                </div>
                <div class="text-[11px] font-semibold flex items-center gap-1" :class="activePeriodStats.tf1NotGood > 0 ? 'text-rose-600' : 'text-slate-500'">
                  <span>{{ activePeriodStats.tf1NotGood }} Not Good</span>
                  <span class="text-slate-300">•</span>
                  <span>Total {{ activePeriodStats.tf1Total }} Uji</span>
                </div>
              </div>

              <!-- METRIC 4: SET LOLOS SELURUH PROSES (WH/GR ACC) -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                  <span>Lolos Seluruh Proses</span>
                  <i class="fa-solid fa-circle-check text-emerald-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ activePeriodStats.whAcceptedSets }} <span class="text-xs font-normal text-slate-400">Set ACC WH</span>
                </div>
                <div class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                  <i class="fa-solid fa-warehouse text-[10px]"></i>
                  <span>Diterima Fisik Gudang (Siap Kirim)</span>
                </div>
              </div>

              <!-- METRIC 5: SET SUDAH DIKIRIM (DELIVERY ORDER) -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                  <span>Set Sudah Dikirim</span>
                  <i class="fa-solid fa-truck-fast text-indigo-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ activePeriodStats.doShippedSets }} <span class="text-xs font-normal text-slate-400">Unit SN DO</span>
                </div>
                <div class="text-[11px] font-semibold text-slate-600 flex items-center gap-1">
                  <span>{{ activePeriodStats.doDocsCount }} Dokumen DO</span>
                  <span class="text-slate-300">•</span>
                  <span>{{ activePeriodStats.doCratesCount }} Peti</span>
                </div>
              </div>

              <!-- METRIC 6: RATA-RATA WAKTU RAKIT PER SET -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                  <span>Rata-rata Waktu Rakit</span>
                  <i class="fa-solid fa-stopwatch text-red-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ activePeriodStats.avgAssyNumber }} <span class="text-xs font-normal text-slate-400">{{ activePeriodStats.avgAssyUnit }} / Set</span>
                </div>
                <div class="text-[11px] font-semibold text-slate-500 flex items-center gap-1">
                  <i class="fa-solid fa-user-gear text-[10px]"></i>
                  <span>Pengerjaan Perakitan Operator</span>
                </div>
              </div>

              <!-- METRIC 7: EFISIENSI PIPELINE PRODUKSI -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                  <span>Tingkat Kelulusan QC</span>
                  <i class="fa-solid fa-award text-purple-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ activePeriodStats.qcPassRate }}% <span class="text-xs font-normal text-slate-400">Pass Rate</span>
                </div>
                <div class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                  <i class="fa-solid fa-shield-check text-[10px]"></i>
                  <span>{{ activePeriodStats.qcAccCount }} ACC dari {{ activePeriodStats.qcTotalCount }} Diperiksa</span>
                </div>
              </div>

            </div>

            <!-- SECTION B: GRAFIK TREN RATA-RATA WAKTU HARIAN (IDENTIK DENGAN DASHBOARD) -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                    <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Tren Rata-rata Waktu Pengerjaan</h2>
                  </div>
                  <p class="text-xs text-slate-500 mt-0.5">Pemantauan efisiensi waktu operator perakitan sesuai periode laporan</p>
                </div>

                <div class="flex items-center">
                  <!-- Legend Indicators -->
                  <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                    <span class="flex items-center gap-1.5">
                      <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                      <span>Rakit (Menit)</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- CHART CANVAS CONTAINER -->
              <div class="relative w-full h-64 sm:h-72 pt-2">
                <canvas id="reportTimeTrendsChart"></canvas>
              </div>

              <!-- CHART SUMMARY CARDS -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs">
                <div class="p-3 bg-red-50/50 border border-red-100 rounded-xl flex items-center justify-between">
                  <div>
                    <div class="text-[11px] font-bold text-red-600 uppercase">Rata-rata Rakit Periode Ini</div>
                    <div class="text-lg font-black text-slate-900">{{ activePeriodStats.avgAssyDisplay }} <span class="text-xs font-semibold text-slate-500">/ set</span></div>
                  </div>
                  <i class="fa-solid fa-screwdriver-wrench text-xl text-red-400"></i>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between">
                  <div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Status Performa</div>
                    <div class="text-xs font-bold text-slate-800 leading-snug">{{ reportPeriodEvaluationNote }}</div>
                  </div>
                  <i class="fa-solid fa-gauge-simple-high text-xl text-slate-400"></i>
                </div>
              </div>
            </div>

            <!-- DETAILED PERIOD BREAKDOWN TABLE -->
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xs overflow-hidden">
              <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <h3 class="font-extrabold text-sm text-slate-900">Rincian Data Produksi Periode {{ reportPeriod === 'daily' ? 'Harian' : (reportPeriod === 'weekly' ? 'Mingguan' : 'Bulanan') }}</h3>
                  <p class="text-xs text-slate-500">Tabel log operasional tray dan perakitan pada periode yang dipilih</p>
                </div>
                <div class="text-xs font-bold text-slate-500">
                  Total Data: <span class="text-slate-900 font-extrabold">{{ activePeriodTrays.length }} Tray Aktif</span>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                  <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider select-none">
                      <th class="p-3.5 pl-5">Kode Tray / Batch</th>
                      <th class="p-3.5">PIC Gudang</th>
                      <th class="p-3.5">Operator</th>
                      <th class="p-3.5 text-center">Supply (Set)</th>
                      <th class="p-3.5 text-center">Good (Rakit)</th>
                      <th class="p-3.5 text-center">TF 1 Pass</th>
                      <th class="p-3.5 text-center">WH/GR ACC</th>
                      <th class="p-3.5 text-center">Waktu Dibuat</th>
                      <th class="p-3.5 pr-5 text-right">Status Tray</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-medium">
                    <tr v-if="activePeriodTrays.length === 0">
                      <td colspan="9" class="p-8 text-center text-slate-400">
                        <i class="fa-solid fa-inbox text-3xl mb-2 block text-slate-300"></i>
                        Tidak ada data tray produksi pada periode ini.
                      </td>
                    </tr>
                    <tr v-for="t in pagedActivePeriodTrays" :key="t.id" class="hover:bg-slate-50/70 transition">
                      <td class="p-3.5 pl-5 font-bold font-mono text-slate-900">
                        <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-800">{{ t.tray_code }}</span>
                      </td>
                      <td class="p-3.5 text-slate-700">{{ t.pic_warehouse || '-' }}</td>
                      <td class="p-3.5 text-slate-700">{{ t.assigned_operator || '-' }}</td>
                      <td class="p-3.5 text-center font-bold text-slate-800">{{ t.total_sets || 0 }} Set</td>
                      <td class="p-3.5 text-center font-black text-emerald-600">{{ t.good_count || 0 }} Good</td>
                      <td class="p-3.5 text-center font-bold text-blue-600">{{ t.good_assy_test_count || 0 }} Pass</td>
                      <td class="p-3.5 text-center font-bold text-purple-600">{{ t.good_gr_count || 0 }} ACC</td>
                      <td class="p-3.5 text-center text-slate-500 font-mono text-[11px]">
                        <div>{{ formatDateTime(t.created_at) }}</div>
                        <div v-if="t.latest_assembly_at" class="text-[10px] text-slate-400 font-sans">Rakit: {{ formatDateTime(t.latest_assembly_at) }}</div>
                      </td>
                      <td class="p-3.5 pr-5 text-right">
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase" :class="t.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200'">
                          {{ t.status || 'Active' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Paginasi Tabel Laporan Produksi -->
              <div v-if="activePeriodTrays.length > reportTraysPageSize" class="px-5 py-3 bg-slate-50/70 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="text-slate-500 font-medium">
                  Menampilkan <span class="font-bold text-slate-800">{{ ((reportTraysPage - 1) * reportTraysPageSize) + 1 }}</span> - <span class="font-bold text-slate-800">{{ Math.min(reportTraysPage * reportTraysPageSize, activePeriodTrays.length) }}</span> dari <span class="font-bold text-slate-800">{{ activePeriodTrays.length }}</span> tray aktif
                </div>
                <div class="flex items-center gap-1.5">
                  <button @click="reportTraysPage = Math.max(1, reportTraysPage - 1)" :disabled="reportTraysPage <= 1" class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium shadow-xs transition">
                    <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                  </button>
                  <span class="px-2 font-bold text-slate-700">Halaman {{ reportTraysPage }} dari {{ reportTraysTotalPages }}</span>
                  <button @click="reportTraysPage = Math.min(reportTraysTotalPages, reportTraysPage + 1)" :disabled="reportTraysPage >= reportTraysTotalPages" class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium shadow-xs transition">
                    Next <i class="fa-solid fa-chevron-right ml-1"></i>
                  </button>
                </div>
              </div>
            </div>

          </div>
          <!-- ================================================================= -->
          <div v-else-if="activeTab === 'users'" class="space-y-5">
            
            <!-- Table Container Card (Matching Image 1 Aesthetic) -->
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xs overflow-hidden">
              
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                  
                  <!-- Table Header -->
                  <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider select-none">
                      <th class="p-4 w-10 text-center">
                        <input type="checkbox" @change="toggleSelectAllUsers" :checked="isAllUsersSelected" class="rounded border-slate-300 text-red-600 focus:ring-red-500/20 cursor-pointer">
                      </th>
                      <th class="py-4 px-4 font-bold text-slate-700">PENGGUNA</th>
                      <th class="py-4 px-4 font-bold text-slate-700">ROLE & DEPT</th>
                      <th class="py-4 px-4 font-bold text-slate-700">HAK AKSES</th>
                      <th class="py-4 px-4 font-bold text-slate-700">PORTAL CMS</th>
                      <th class="py-4 px-4 font-bold text-slate-700">SESI LOGIN</th>
                      <th class="py-4 px-4 font-bold text-slate-700 text-right">AKSI</th>
                    </tr>
                  </thead>

                  <!-- Table Body -->
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="u in pagedUsersList" :key="u.id" :class="isUserSelected(u.id) ? 'bg-red-50/30' : 'hover:bg-slate-50/80'" class="transition">
                      
                      <!-- Checkbox -->
                      <td class="p-4 text-center">
                        <input type="checkbox" :value="u.id" v-model="selectedUserIds" class="rounded border-slate-300 text-red-600 focus:ring-red-500/20 cursor-pointer">
                      </td>

                      <!-- User Info -->
                      <td class="py-3 px-4">
                        <div class="flex items-center space-x-3">
                          <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs uppercase shrink-0 border border-slate-200">
                            {{ (u.username || 'U').substring(0, 2) }}
                          </div>
                          <div class="leading-tight">
                            <div class="font-bold text-slate-900 text-xs">{{ u.full_name || u.username }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ u.username }}</div>
                          </div>
                        </div>
                      </td>

                      <!-- Role & Dept -->
                      <td class="py-3 px-4">
                        <span :class="getDeptBadgeClass(u.department || u.role)" class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                          {{ u.department || u.role }}
                        </span>
                      </td>

                      <!-- Permissions -->
                      <td class="py-3 px-4 font-mono">
                        <span class="text-emerald-700 font-bold">{{ countUserWritePerms(u) }} Write</span>
                        <span class="text-slate-300 mx-1">•</span>
                        <span class="text-slate-500">{{ countUserReadPerms(u) }} Read</span>
                      </td>

                      <!-- CMS Access -->
                      <td class="py-3 px-4">
                        <span v-if="u.id === 3 || u.can_access_cms" class="inline-flex items-center gap-1 text-[11px] font-bold text-red-600 bg-red-50 px-2.5 py-0.5 rounded-full border border-red-200">
                          <i class="fa-solid fa-shield-halved text-[9px]"></i> Aktif
                        </span>
                        <span v-else class="text-[11px] text-slate-400 font-medium">Tidak</span>
                      </td>

                      <!-- Session Status -->
                      <td class="py-3 px-4">
                        <span v-if="u.is_active || u.has_active_session" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                          Online
                        </span>
                        <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold text-slate-400 bg-slate-50 border border-slate-200">
                          Offline
                        </span>
                      </td>

                      <!-- Actions -->
                      <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end space-x-1.5">
                          <button type="button" @click="editUser(u)" class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition cursor-pointer" title="Edit Pengguna">
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                          </button>
                          <button type="button" @click="resetUserSession(u)" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition cursor-pointer" title="Reset Sesi Login">
                            <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
                          </button>
                          <button v-if="u.id !== 3" type="button" @click="deleteUser(u)" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" title="Hapus Pengguna">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                          </button>
                        </div>
                      </td>

                    </tr>
                  </tbody>

                </table>
              </div>

              <!-- Bottom Pagination Bar (Matching Image 1) -->
              <div class="p-4 border-t border-slate-200/80 bg-white flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600 select-none">
                
                <div class="flex items-center gap-2">
                  <span>Showing per page:</span>
                  <span class="font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">10</span>
                  <span class="text-slate-400">Total {{ filteredUsersList.length }} pengguna</span>
                </div>

                <div class="flex items-center space-x-1">
                  <button type="button" @click="userPage = Math.max(1, userPage - 1)" :disabled="userPage <= 1" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 hover:bg-slate-100 cursor-pointer">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                  </button>
                  
                  <span v-for="p in totalUserPages" :key="p" @click="userPage = p" :class="userPage === p ? 'bg-red-600 text-white font-bold' : 'hover:bg-slate-100 text-slate-700'" class="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer transition">
                    {{ p }}
                  </span>

                  <button type="button" @click="userPage = Math.min(totalUserPages, userPage + 1)" :disabled="userPage >= totalUserPages" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 hover:bg-slate-100 cursor-pointer">
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                  </button>
                </div>

              </div>

            </div>

            <!-- Floating Action Bar when items selected (Matching Image 1) -->
            <transition enter-active-class="transition ease-out duration-200" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
              <div v-if="selectedUserIds.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-white/95 backdrop-blur-md border border-slate-200 shadow-2xl rounded-2xl px-5 py-3 flex items-center space-x-4 z-40 text-xs font-bold text-slate-800">
                <span class="text-red-600">{{ selectedUserIds.length }} Pengguna Dipilih</span>
                <span class="text-slate-300">|</span>
                <button type="button" @click="bulkResetSessions" class="text-slate-700 hover:text-amber-600 flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-arrow-rotate-right text-[11px]"></i>
                  <span>Reset Sesi</span>
                </button>
                <button type="button" @click="selectedUserIds = []" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </transition>

            <!-- USER FORM MODAL / DRAWER (WITH PERMISSION MATRIX) -->
            <div v-if="openUserForm" class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs z-50 flex items-center justify-center p-4">
              <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 p-6 sm:p-8 space-y-6">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-lg">
                      <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div>
                      <h3 class="text-base font-extrabold text-slate-900">{{ editingUserId ? 'Edit Akun Pengguna' : 'Tambah Pengguna Baru' }}</h3>
                      <p class="text-xs text-slate-400">Atur kredensial login dan hak akses menu sistem</p>
                    </div>
                  </div>
                  <button type="button" @click="openUserForm = false; resetUserForm()" class="text-slate-400 hover:text-slate-700 p-2 rounded-xl">
                    <i class="fa-solid fa-xmark text-lg"></i>
                  </button>
                </div>

                <form @submit.prevent="saveUser" class="space-y-5">
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-xs font-bold text-slate-700 uppercase">Username *</label>
                      <input type="text" v-model="userForm.username" required placeholder="username_login" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:outline-none focus:border-red-500 focus:bg-white">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-xs font-bold text-slate-700 uppercase">Nama Lengkap *</label>
                      <input type="text" v-model="userForm.full_name" required placeholder="Nama Lengkap Operator" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:outline-none focus:border-red-500 focus:bg-white">
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-xs font-bold text-slate-700 uppercase">Password {{ editingUserId ? '(Kosongkan jika tidak diubah)' : '*' }}</label>
                      <input type="password" v-model="userForm.password" :required="!editingUserId" placeholder="Kata sandi..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:outline-none focus:border-red-500 focus:bg-white">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-xs font-bold text-slate-700 uppercase">Departemen *</label>
                      <select v-model="userForm.department" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:outline-none focus:border-red-500 focus:bg-white cursor-pointer">
                        <option value="administrator">Administrator</option>
                        <option value="assembly">Assembly</option>
                        <option value="qc">Quality Control</option>
                        <option value="warehouse">Warehouse</option>
                      </select>
                    </div>
                  </div>

                  <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-800">Akses Portal CMS Administrator</span>
                      <input type="checkbox" v-model="userForm.can_access_cms" class="rounded text-red-600 focus:ring-red-500/20 cursor-pointer">
                    </div>
                  </div>

                  <!-- Permission Matrix Preset Buttons -->
                  <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                      <span>Hak Akses Menu:</span>
                      <div class="flex gap-2">
                        <button type="button" @click="applyUserPreset('admin')" class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-700 font-bold">Admin</button>
                        <button type="button" @click="applyUserPreset('operator')" class="text-[10px] px-2 py-0.5 rounded bg-slate-200 text-slate-700 font-bold">Operator</button>
                        <button type="button" @click="applyUserPreset('readonly')" class="text-[10px] px-2 py-0.5 rounded bg-slate-200 text-slate-700 font-bold">Read-Only</button>
                      </div>
                    </div>

                    <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-2xl divide-y divide-slate-100 bg-white">
                      <div v-for="g in cmsManagedMenuGroups" :key="g.key" class="p-3">
                        <div class="font-bold text-xs text-slate-800 mb-2">{{ g.name }}</div>
                        <div class="grid grid-cols-2 gap-2">
                          <div v-for="m in g.menus" :key="m.key" class="flex items-center justify-between text-xs p-1.5 rounded-lg bg-slate-50">
                            <span class="truncate mr-2">{{ m.name }}</span>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-200 text-[10px] font-bold">
                              <button type="button" @click="setUserPermission(m.key, 0)" :class="userForm.permissions[m.key] === 0 || userForm.permissions[m.key] === undefined ? 'bg-white text-slate-700 shadow-2xs' : 'text-slate-400'" class="px-1.5 py-0.5 rounded">Off</button>
                              <button type="button" @click="setUserPermission(m.key, 1)" :class="userForm.permissions[m.key] === 1 ? 'bg-blue-600 text-white' : 'text-slate-400'" class="px-1.5 py-0.5 rounded">R</button>
                              <button type="button" @click="setUserPermission(m.key, 2)" :class="userForm.permissions[m.key] === 2 ? 'bg-emerald-600 text-white' : 'text-slate-400'" class="px-1.5 py-0.5 rounded">W</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" @click="openUserForm = false; resetUserForm()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-100">
                      Batal
                    </button>
                    <button type="submit" :disabled="isSavingUser" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-600/20 disabled:opacity-50 flex items-center gap-2">
                      <i v-if="isSavingUser" class="fa-solid fa-circle-notch fa-spin"></i>
                      <span>{{ isSavingUser ? 'Menyimpan...' : 'Simpan Pengguna' }}</span>
                    </button>
                  </div>

                </form>

              </div>
            </div>

          </div>

          <!-- ================================================================= -->
          <!-- 3. TAB PEMINDAI ANOMALI (SAAS STYLED)                             -->
          <!-- ================================================================= -->
          <div v-else-if="activeTab === 'anomalies'" class="space-y-6">
            
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-5">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h3 class="font-extrabold text-base text-slate-900">Pemindai Anomali Sistem</h3>
                  <p class="text-xs text-slate-400">Pemeriksaan integritas referensi part yatim, sesi nyangkut, dan inkonsistensi stok</p>
                </div>
                <div class="flex items-center space-x-3">
                  <button type="button" @click="scanAnomalies" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 font-bold text-slate-700 rounded-xl text-xs transition cursor-pointer">
                    <i class="fa-solid fa-arrows-rotate mr-1.5"></i> Pindai Ulang
                  </button>
                  <button v-if="anomalySummary.total_anomalies > 0" type="button" @click="repairAnomalies" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs shadow-md shadow-red-600/25 transition cursor-pointer">
                    <i class="fa-solid fa-wrench mr-1.5"></i> Perbaiki Otomatis
                  </button>
                </div>
              </div>

              <!-- Anomaly Categories Grid -->
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-1">
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Part Yatim (Orphan)</div>
                  <div class="text-xl font-black" :class="anomalySummary.orphan_parts.count > 0 ? 'text-red-600' : 'text-slate-800'">
                    {{ anomalySummary.orphan_parts.count }}
                  </div>
                </div>
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-1">
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Sesi Nyangkut</div>
                  <div class="text-xl font-black" :class="anomalySummary.stuck_sessions.count > 0 ? 'text-amber-600' : 'text-slate-800'">
                    {{ anomalySummary.stuck_sessions.count }}
                  </div>
                </div>
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-1">
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Stok Desinkron</div>
                  <div class="text-xl font-black" :class="anomalySummary.desynced_stocks.count > 0 ? 'text-amber-600' : 'text-slate-800'">
                    {{ anomalySummary.desynced_stocks.count }}
                  </div>
                </div>
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-1">
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Part Duplikat</div>
                  <div class="text-xl font-black" :class="anomalySummary.duplicate_parts.count > 0 ? 'text-red-600' : 'text-slate-800'">
                    {{ anomalySummary.duplicate_parts.count }}
                  </div>
                </div>
              </div>

              <!-- Anomaly Items List -->
              <div v-if="filteredAnomalyItems.length === 0" class="p-8 text-center text-slate-400 text-xs">
                <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2 block"></i>
                <span class="font-bold text-slate-700">Tidak ada anomali terdeteksi</span>
                <p class="text-[11px] text-slate-400 mt-1">Seluruh data database dan referensi part dalam kondisi sehat dan konsisten</p>
              </div>

              <div v-else class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                <div v-for="(it, idx) in pagedAnomalyItems" :key="idx" class="p-3 text-xs flex items-center justify-between hover:bg-slate-50">
                  <div class="flex items-center space-x-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-800">
                      {{ it.category }}
                    </span>
                    <span class="font-mono text-slate-700">{{ it.description || it.serial_number || it.key }}</span>
                  </div>
                  <span class="text-[11px] text-slate-400 font-mono">{{ it.timestamp || 'Tercatat' }}</span>
                </div>
              </div>

            </div>

          </div>

          <!-- ================================================================= -->
          <!-- 4. TAB PEMBERSIHAN DATABASE (CLEANUP)                             -->
          <!-- ================================================================= -->
          <div v-else-if="activeTab === 'cleanup'" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              
              <!-- CLEAR TRAYS -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4 flex flex-col justify-between">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-trash-can"></i>
                  </div>
                  <h3 class="font-extrabold text-sm text-slate-900">Reset Data Tray & Rakitan</h3>
                  <p class="text-xs text-slate-500 leading-relaxed">
                    Menghapus seluruh tray produksi, slot rakitan, dan riwayat uji coba pabrik.
                  </p>
                </div>
                <button type="button" @click="confirmClearTrays" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl text-xs shadow-sm transition cursor-pointer">
                  Bersihkan Tray & Rakitan
                </button>
              </div>

              <!-- CLEAR DOWNTIMES -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4 flex flex-col justify-between">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                  </div>
                  <h3 class="font-extrabold text-sm text-slate-900">Reset Catatan Downtime</h3>
                  <p class="text-xs text-slate-500 leading-relaxed">
                    Menghapus seluruh riwayat downtime, waktu henti lini perakitan, dan alasan kendala.
                  </p>
                </div>
                <button type="button" @click="confirmClearDowntimes" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl text-xs shadow-sm transition cursor-pointer">
                  Bersihkan Downtime
                </button>
              </div>

              <!-- CLEAR AUDIT LOGS -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4 flex flex-col justify-between">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-list-check"></i>
                  </div>
                  <h3 class="font-extrabold text-sm text-slate-900">Reset Audit Log Aktivitas</h3>
                  <p class="text-xs text-slate-500 leading-relaxed">
                    Membersihkan riwayat log audit dan jejak aktivitas operasional pengguna.
                  </p>
                </div>
                <button type="button" @click="confirmClearAuditLogs" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl text-xs shadow-sm transition cursor-pointer">
                  Bersihkan Audit Logs
                </button>
              </div>

            </div>

          </div>

          <!-- ================================================================= -->
          <!-- 5. TAB ERROR LOGGER (ERRORS)                                      -->
          <!-- ================================================================= -->
          <div v-else-if="activeTab === 'errors'" class="space-y-5">
            
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xs overflow-hidden">
              <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                  <h3 class="font-extrabold text-sm text-slate-900">Catatan Error Backend & API</h3>
                  <p class="text-xs text-slate-400">Total {{ errorLogs.length }} entri log kesalahan tercatat</p>
                </div>
                <button type="button" @click="clearErrorLogs" class="text-xs font-bold text-red-600 hover:text-red-700 cursor-pointer">
                  <i class="fa-solid fa-trash-can mr-1"></i> Bersihkan Log
                </button>
              </div>

              <div v-if="errorLogs.length === 0" class="p-8 text-center text-slate-400 text-xs font-semibold">
                Tidak ada log error yang tercatat. Sistem berjalan stabil.
              </div>

              <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                      <th class="p-3 w-40">Waktu</th>
                      <th class="p-3 w-32">Status</th>
                      <th class="p-3">Pesan Error</th>
                      <th class="p-3 w-48">File Sumber</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-mono">
                    <tr v-for="(err, idx) in pagedErrorLogs" :key="idx" class="hover:bg-slate-50/70">
                      <td class="p-3 text-slate-500 whitespace-nowrap">{{ err.created_at || err.timestamp || '-' }}</td>
                      <td class="p-3"><span class="bg-red-50 text-red-700 px-2 py-0.5 rounded font-bold">{{ err.status_code || 500 }}</span></td>
                      <td class="p-3 font-sans text-slate-800 font-medium">{{ err.message }}</td>
                      <td class="p-3 text-slate-400 truncate max-w-xs">{{ err.file || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>

          <!-- ================================================================= -->
          <!-- 6. TAB SLOW QUERY MONITOR                                         -->
          <!-- ================================================================= -->
          <div v-else-if="activeTab === 'slow_queries'" class="space-y-5">
            
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xs overflow-hidden">
              <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                  <h3 class="font-extrabold text-sm text-slate-900">Pemantauan Kueri Lambat (Slow Queries)</h3>
                  <p class="text-xs text-slate-400">Total {{ slowQueries.length }} kueri dengan durasi tinggi tercatat</p>
                </div>
                <button type="button" @click="clearSlowQueries" class="text-xs font-bold text-red-600 hover:text-red-700 cursor-pointer">
                  <i class="fa-solid fa-trash-can mr-1"></i> Bersihkan Log
                </button>
              </div>

              <div v-if="slowQueries.length === 0" class="p-8 text-center text-slate-400 text-xs font-semibold">
                Tidak ada slow queries. Seluruh respons kueri database berjalan di bawah ambang batas lambat.
              </div>

              <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                      <th class="p-3 w-28">Durasi</th>
                      <th class="p-3">Query SQL</th>
                      <th class="p-3 w-40">Waktu</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-mono">
                    <tr v-for="(q, idx) in pagedSlowQueries" :key="idx" class="hover:bg-slate-50/70">
                      <td class="p-3 whitespace-nowrap">
                        <span class="bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded">{{ q.duration_ms || q.duration }} ms</span>
                      </td>
                      <td class="p-3 text-slate-700 font-mono text-[11px] break-all">{{ q.sql }}</td>
                      <td class="p-3 text-slate-400 whitespace-nowrap">{{ q.created_at || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>

          <!-- ================================================================= -->
          <!-- 7. TAB PENGATURAN DO & CLOUDINARY                                 -->
          <!-- ================================================================= -->
          <div v-else-if="activeTab === 'do_settings'" class="space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              
              <!-- CLOUDINARY SETTINGS -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4">
                <div class="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                  </div>
                  <div>
                    <h3 class="font-extrabold text-sm text-slate-900">Integrasi Cloudinary PDF</h3>
                    <p class="text-xs text-slate-400">Penyimpanan awan otomatis untuk dokumen Packing List & DO</p>
                  </div>
                </div>

                <form @submit.prevent="saveCloudinarySettings" class="space-y-3 text-xs">
                  <div class="space-y-1">
                    <label class="font-bold text-slate-700">Cloud Name</label>
                    <input type="text" v-model="cloudForm.cloud_name" required placeholder="Contoh: final-assembly-p9" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:bg-white focus:outline-none">
                  </div>
                  <div class="space-y-1">
                    <label class="font-bold text-slate-700">API Key</label>
                    <input type="text" v-model="cloudForm.api_key" required placeholder="Contoh: 1234567890" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:bg-white focus:outline-none">
                  </div>
                  <div class="space-y-1">
                    <label class="font-bold text-slate-700">API Secret {{ cloudSettings.has_secret ? '(Tersimpan - Kosongkan jika tidak diubah)' : '' }}</label>
                    <input type="password" v-model="cloudForm.api_secret" :required="!cloudSettings.has_secret" placeholder="API Secret..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:bg-white focus:outline-none">
                  </div>

                  <div class="pt-2 flex items-center justify-between">
                    <button type="button" @click="testCloudinaryConnection" :disabled="isTestingCloud" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 font-bold text-slate-700 rounded-xl transition cursor-pointer">
                      <i class="fa-solid fa-plug text-[11px] mr-1"></i>
                      <span>{{ isTestingCloud ? 'Menguji...' : 'Uji Koneksi' }}</span>
                    </button>
                    <button type="submit" :disabled="isSavingCloud" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition cursor-pointer">
                      <span>{{ isSavingCloud ? 'Menyimpan...' : 'Simpan Kredensial' }}</span>
                    </button>
                  </div>
                </form>
              </div>

              <!-- DO SETTINGS -->
              <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-4">
                <div class="flex items-center space-x-3 border-b border-slate-100 pb-3">
                  <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-file-signature"></i>
                  </div>
                  <div>
                    <h3 class="font-extrabold text-sm text-slate-900">Format &amp; Batch Delivery Order</h3>
                    <p class="text-xs text-slate-400">Pengaturan batch terpusat dan penomoran otomatis dokumen pengiriman</p>
                  </div>
                </div>

                <div class="space-y-3 text-xs">
                  <div class="space-y-1">
                    <label class="font-bold text-slate-700 flex items-center justify-between">
                      <span>Nomor Batch Pengiriman Aktif</span>
                      <span class="text-[10px] text-red-600 font-bold">*Pengaturan Terpusat Batch</span>
                    </label>
                    <input type="number" min="1" v-model.number="doSettings.current_batch_number" placeholder="Contoh: 1" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:bg-white focus:outline-none font-bold text-slate-800">
                    <p class="text-[11px] text-slate-500">Nomor batch yang aktif untuk seluruh pembuatan Dokumen Packing List dan Surat Jalan. Jika nomor batch diubah, urutan nomor pengiriman otomatis direset kembali ke 1 (01).</p>
                  </div>
                  <div class="space-y-1">
                    <label class="font-bold text-slate-700">Format Kode Surat Jalan</label>
                    <input type="text" v-model="doSettings.do_number_format" placeholder="DO/P9/YYMM/XXXX" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:bg-white focus:outline-none">
                  </div>
                  <div class="space-y-1">
                    <label class="font-bold text-slate-700">Prefix Peti Default</label>
                    <input type="text" v-model="doSettings.default_crate_prefix" placeholder="CRATE-" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:bg-white focus:outline-none">
                  </div>
                  <div class="pt-4 flex justify-end">
                    <button type="button" @click="saveDoSettings" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-sm transition cursor-pointer">
                      Simpan Pengaturan DO
                    </button>
                  </div>
                </div>
              </div>

            </div>

          </div>

        </main>

      </div>

    </div>

    <!-- TOAST NOTIFICATION -->
    <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4" enter-to-class="opacity-100 translate-y-0 sm:translate-x-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0 sm:translate-x-0" leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4">
      <div v-if="toast.show" class="fixed bottom-5 right-5 z-50 max-w-sm w-full bg-white rounded-2xl shadow-2xl border border-slate-200 p-4 flex items-start space-x-3 select-none">
        <div :class="toast.type === 'error' ? 'bg-red-100 text-red-600' : (toast.type === 'warn' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600')" class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-sm">
          <i :class="toast.type === 'error' ? 'fa-solid fa-circle-xmark' : (toast.type === 'warn' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-check')"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-xs font-bold text-slate-900">{{ toast.title }}</div>
          <div class="text-[11px] text-slate-600 mt-0.5 leading-snug break-words">{{ toast.message }}</div>
        </div>
        <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-slate-600 p-1">
          <i class="fa-solid fa-xmark text-xs"></i>
        </button>
      </div>
    </transition>

    <!-- IN-APP CONFIRMATION MODAL -->
    <div v-if="confirmModalState.isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-xs select-none">
      <div class="w-full max-w-md bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5 text-center">
        <div :class="confirmModalState.iconBg" class="w-12 h-12 rounded-2xl mx-auto flex items-center justify-center text-xl shadow-sm">
          <i :class="confirmModalState.icon"></i>
        </div>
        <div>
          <h4 class="text-base font-extrabold text-slate-900">{{ confirmModalState.title }}</h4>
          <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ confirmModalState.message }}</p>
        </div>
        <div class="flex items-center space-x-3 pt-2">
          <button type="button" @click="handleConfirmCard(false)" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-100 transition cursor-pointer">
            {{ confirmModalState.cancelText }}
          </button>
          <button type="button" @click="handleConfirmCard(true)" :class="confirmModalState.confirmBtnClass" class="flex-1 px-4 py-2.5 rounded-xl font-bold text-xs shadow-md transition cursor-pointer">
            {{ confirmModalState.confirmText }}
          </button>
        </div>
      </div>
    </div>

    @endverbatim
  </div>

  <!-- VUE 3 APPLICATION LOGIC SCRIPT -->
  <script>
    const { createApp, ref, reactive, computed, onMounted, nextTick, watch } = Vue;

    createApp({
      setup() {
        const isCmsAuthenticated = ref(false);
        const adminUser = ref({});
        const cmsToken = ref('');

        const loginForm = ref({ username: '', password: '' });
        const showPassword = ref(false);
        const loginLoading = ref(false);
        const loginError = ref('');

        // Tab state: default to 'dashboard' as requested by user
        const activeTab = ref('dashboard');
        const loading = ref(false);
        const isCmsSidebarOpen = ref(false);
        const statsVisible = ref(true);

        // Toast Notification State
        const toast = ref({ show: false, title: '', message: '', type: 'success' });
        let toastTimeout = null;
        const triggerToast = (title, message, type = 'success') => {
          if (toastTimeout) clearTimeout(toastTimeout);
          toast.value = { show: true, title, message, type };
          toastTimeout = setTimeout(() => { toast.value.show = false; }, 3500);
        };

        // Confirmation Modal State
        const confirmModalState = reactive({
          isOpen: false,
          title: 'Konfirmasi',
          message: '',
          icon: 'fa-solid fa-circle-question',
          iconBg: 'bg-blue-100 text-blue-600',
          confirmText: 'Ya, Lanjutkan',
          cancelText: 'Batal',
          confirmBtnClass: 'bg-blue-600 hover:bg-blue-700 text-white',
          resolve: null
        });

        const showConfirmCard = (opts = {}) => {
          return new Promise((resolve) => {
            confirmModalState.title = opts.title || 'Konfirmasi';
            confirmModalState.message = opts.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            confirmModalState.icon = opts.icon || 'fa-solid fa-circle-question';
            confirmModalState.iconBg = opts.iconBg || (opts.confirmBtnClass && opts.confirmBtnClass.includes('red') ? 'bg-red-100 text-red-600' : 'bg-slate-900 text-white');
            confirmModalState.confirmText = opts.confirmText || 'Ya, Lanjutkan';
            confirmModalState.cancelText = opts.cancelText || 'Batal';
            confirmModalState.confirmBtnClass = opts.confirmBtnClass || 'bg-red-600 hover:bg-red-700 text-white';
            confirmModalState.resolve = resolve;
            confirmModalState.isOpen = true;
          });
        };

        const handleConfirmCard = (confirmed) => {
          confirmModalState.isOpen = false;
          if (typeof confirmModalState.resolve === 'function') {
            confirmModalState.resolve(confirmed);
            confirmModalState.resolve = null;
          }
        };

        // =====================================================================
        // DASHBOARD COMPREHENSIVE STATS & REPORTING DATA STATE (WITH BACKDATE)
        // =====================================================================
        const reportPeriod = ref('daily'); // 'daily', 'weekly', 'monthly', 'custom'
        const setReportPeriod = (period) => {
          reportPeriod.value = period;
          if (typeof nextTick === 'function') {
            nextTick(() => renderReportCharts());
          } else {
            setTimeout(renderReportCharts, 50);
          }
        };

        const todayIsoStr = () => {
          const d = new Date();
          const y = d.getFullYear();
          const m = String(d.getMonth() + 1).padStart(2, '0');
          const day = String(d.getDate()).padStart(2, '0');
          return `${y}-${m}-${day}`;
        };
        const currentMonthIsoStr = () => {
          const d = new Date();
          const y = d.getFullYear();
          const m = String(d.getMonth() + 1).padStart(2, '0');
          return `${y}-${m}`;
        };

        const parseDateSafe = (dateStr) => {
          if (!dateStr) return null;
          try {
            if (dateStr instanceof Date) return isNaN(dateStr.getTime()) ? null : dateStr;
            const s = String(dateStr).trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
              const [y, m, d] = s.split('-').map(Number);
              return new Date(y, m - 1, d, 0, 0, 0, 0);
            }
            const clean = s.includes('T') ? s : s.replace(' ', 'T');
            const d = new Date(clean);
            return isNaN(d.getTime()) ? null : d;
          } catch (_) {
            return null;
          }
        };

        const getWeekDateRange = (offset = selectedWeekOffset.value) => {
          const now = new Date();
          const day = now.getDay();
          const diffToMonday = day === 0 ? -6 : 1 - day;
          const monday = new Date(now.getFullYear(), now.getMonth(), now.getDate() + diffToMonday + (offset * 7), 0, 0, 0, 0);
          const sunday = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + 6, 23, 59, 59, 999);
          return { monday, sunday };
        };

        const isDateInDay = (dateStr, targetDateStr = todayIsoStr()) => {
          if (!dateStr) return false;
          try {
            const d = parseDateSafe(dateStr);
            if (!d) return false;
            const target = parseDateSafe(targetDateStr);
            if (!target) return false;
            return d.getFullYear() === target.getFullYear() &&
                   d.getMonth() === target.getMonth() &&
                   d.getDate() === target.getDate();
          } catch(e) {
            return false;
          }
        };

        const formatShortDate = (dateStr) => {
          if (!dateStr) return '-';
          try {
            const d = parseDateSafe(dateStr);
            if (!d) return dateStr;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
          } catch(e) {
            return dateStr;
          }
        };

        // Date selection state for backdate tracking
        const selectedReportDate = ref(todayIsoStr());
        const selectedWeekOffset = ref(0); // 0 = current week, -1 = last week, etc.
        const selectedReportMonth = ref(currentMonthIsoStr());
        const customStartDate = ref(todayIsoStr());
        const customEndDate = ref(todayIsoStr());
        const chartWindowMode = ref('active_dates'); // '7_days', '30_days', 'active_dates'
        const latestProductionDate = ref('');

        const shiftReportDate = (deltaDays) => {
          const d = parseDateSafe(selectedReportDate.value) || new Date();
          d.setDate(d.getDate() + deltaDays);
          const y = d.getFullYear();
          const m = String(d.getMonth() + 1).padStart(2, '0');
          const day = String(d.getDate()).padStart(2, '0');
          selectedReportDate.value = `${y}-${m}-${day}`;
          updateChartDataFromRaw();
          if (typeof nextTick === 'function') nextTick(() => renderReportCharts());
          else renderReportCharts();
        };

        const setReportDateToday = () => {
          selectedReportDate.value = todayIsoStr();
          updateChartDataFromRaw();
          if (typeof nextTick === 'function') nextTick(() => renderReportCharts());
          else renderReportCharts();
        };

        const jumpToLatestDate = () => {
          if (latestProductionDate.value) {
            selectedReportDate.value = latestProductionDate.value;
            updateChartDataFromRaw();
            if (typeof nextTick === 'function') nextTick(() => renderReportCharts());
            else renderReportCharts();
          }
        };

        const shiftReportWeek = (deltaWeeks) => {
          selectedWeekOffset.value += deltaWeeks;
          if (typeof nextTick === 'function') nextTick(() => renderReportCharts());
          else renderReportCharts();
        };

        const setReportWeekCurrent = () => {
          selectedWeekOffset.value = 0;
          if (typeof nextTick === 'function') nextTick(() => renderReportCharts());
          else renderReportCharts();
        };

        const shiftReportMonth = (deltaMonths) => {
          const parts = selectedReportMonth.value.split('-').map(Number);
          const y = parts[0] || new Date().getFullYear();
          const m = parts[1] || (new Date().getMonth() + 1);
          const d = new Date(y, m - 1 + deltaMonths, 1);
          const yr = d.getFullYear();
          const mo = String(d.getMonth() + 1).padStart(2, '0');
          selectedReportMonth.value = `${yr}-${mo}`;
          renderReportCharts();
        };

        const setReportMonthCurrent = () => {
          selectedReportMonth.value = currentMonthIsoStr();
          renderReportCharts();
        };

        const setQuickCustomRange = (mode) => {
          const now = new Date();
          customEndDate.value = todayIsoStr();
          if (mode === '7_days') {
            const d = new Date(now);
            d.setDate(now.getDate() - 6);
            customStartDate.value = d.toISOString().split('T')[0];
          } else if (mode === '30_days') {
            const d = new Date(now);
            d.setDate(now.getDate() - 29);
            customStartDate.value = d.toISOString().split('T')[0];
          } else if (mode === 'all') {
            customStartDate.value = '2026-01-01';
          }
          renderReportCharts();
        };

        const setChartWindow = (mode) => {
          chartWindowMode.value = mode;
          updateChartDataFromRaw();
        };

        const rawTraysList = ref([]);
        const rawAssyTestsList = ref([]);
        const rawQcTestsList = ref([]);
        const rawWarehouseReceiptsList = ref([]);
        const rawDeliveryOrdersList = ref([]);
        const rawPendingReassemblyList = ref([]);
        const lastSyncTimeStr = ref('');
        let autoUpdateTimer = null;
        let lastDailyTrendsData = [];
        let timeTrendsChartInstance = null;

        const dailyKpi = computed(() => {
          const assyItems = rawAssyTestsList.value;
          const whItems = rawWarehouseReceiptsList.value;
          const today = todayIsoStr();
          const latest = latestProductionDate.value;

          const calcMetricsFor = (targetDate) => {
            if (!targetDate) return null;
            let goodCount = 0;
            let countAssy = 0;
            let totalAssyMin = 0;
            let countTf1 = 0;
            let totalTf1Min = 0;
            let whAccCount = 0;

            assyItems.forEach(i => {
              if (isDateInDay(i.created_at || i.finished_at || i.started_at, targetDate)) {
                const st = (i.assy_test_status || i.status || '').toUpperCase();
                if (st === 'GOOD' || st === 'PASS') {
                  goodCount++;
                }
                if (i.started_at && i.finished_at) {
                  const diff = (new Date(i.finished_at) - new Date(i.started_at)) / 1000 / 60;
                  if (diff > 0 && diff < 1440) {
                    totalAssyMin += diff;
                    countAssy++;
                  }
                }
                if (i.finished_at && i.assy_tested_at) {
                  const diffTf1 = (new Date(i.assy_tested_at) - new Date(i.finished_at)) / 1000 / 60;
                  if (diffTf1 > 0 && diffTf1 < 1440) {
                    totalTf1Min += diffTf1;
                    countTf1++;
                  }
                }
              }
            });

            whItems.forEach(w => {
              if (isDateInDay(w.created_at || w.effective_warehouse_receipt_at, targetDate)) {
                if ((w.warehouse_receipt_status || w.status || '').toUpperCase() === 'ACCEPTED') {
                  whAccCount++;
                }
              }
            });

            return {
              hasActivity: (goodCount > 0 || whAccCount > 0 || countAssy > 0),
              goodCount,
              whAccCount,
              countAssy,
              totalAssyMin,
              countTf1,
              totalTf1Min
            };
          };

          const todayM = calcMetricsFor(today);
          let chosenTarget = today;
          let periodLabel = 'Hari Ini';
          let m = todayM;

          // Jika hari ini belum ada aktivitas namun terdapat sesi produksi sebelumnya, otomatis tampilkan sesi terakhir
          if ((!todayM || !todayM.hasActivity) && latest && latest !== today) {
            const latestM = calcMetricsFor(latest);
            if (latestM && latestM.hasActivity) {
              chosenTarget = latest;
              periodLabel = 'Sesi ' + formatShortDate(latest);
              m = latestM;
            }
          }

          const rawAvgAssy = m && m.countAssy > 0 ? (m.totalAssyMin / m.countAssy) : 0;
          const rawAvgTf1 = m && m.countTf1 > 0 ? (m.totalTf1Min / m.countTf1) : 0;

          let avgAssyDisplayNumber = '0';
          let avgAssyDisplayUnit = 'menit';
          let avgAssyDisplay = '0 menit';

          if (rawAvgAssy > 0 && rawAvgAssy < 1) {
            const secs = rawAvgAssy * 60;
            avgAssyDisplayNumber = secs < 10 ? secs.toFixed(1) : Math.round(secs).toString();
            avgAssyDisplayUnit = 'detik';
            avgAssyDisplay = `${avgAssyDisplayNumber} detik`;
          } else if (rawAvgAssy >= 1) {
            avgAssyDisplayNumber = rawAvgAssy.toFixed(1);
            avgAssyDisplayUnit = 'menit';
            avgAssyDisplay = `${avgAssyDisplayNumber} menit`;
          }

          let avgTf1Display = '0 menit';
          if (rawAvgTf1 > 0 && rawAvgTf1 < 1) {
            const secs = rawAvgTf1 * 60;
            avgTf1Display = `${secs < 10 ? secs.toFixed(1) : Math.round(secs)} detik`;
          } else if (rawAvgTf1 >= 1) {
            avgTf1Display = `${rawAvgTf1.toFixed(1)} menit`;
          }

          return {
            assembledGoodToday: m ? m.goodCount : 0,
            warehouseAccToday: m ? m.whAccCount : 0,
            countAssy: m ? m.countAssy : 0,
            avgAssyMinutes: rawAvgAssy >= 1 ? Number(rawAvgAssy.toFixed(1)) : Number(rawAvgAssy.toFixed(2)),
            avgTf1Minutes: rawAvgTf1 >= 1 ? Number(rawAvgTf1.toFixed(1)) : Number(rawAvgTf1.toFixed(2)),
            avgAssyDisplayNumber,
            avgAssyDisplayUnit,
            avgAssyDisplay,
            avgTf1Display,
            periodLabel,
            targetDate: chosenTarget,
            targetDateLabel: formatShortDate(chosenTarget)
          };
        });

        const dashboardStats = ref({
          loading: false,
          assembly: { total: 0, good: 0, hold: 0, scrap: 0, activeTrays: 0 },
          tf1: { total: 0, passed: 0, hold: 0, notGood: 0 },
          qc: { total: 0, acc: 0, hold: 0, waiting: 0 },
          warehouse: { totalDocs: 0, acceptedSets: 0, declinedSets: 0, totalReceipts: 0 },
          reassembly: { notDispatched: 0, dispatched: 0, pendingRU: 0 },
          delivery: { totalDO: 0, totalCrates: 0, totalBoxes: 0, shippedSNCount: 0, cloudSynced: 0 },
          pipeline: { readyToShip: 0 },
          times: {
            avgAssyMinutesToday: 0,
            avgTf1MinutesToday: 0,
            trendText: 'Belum Ada Perakitan Hari Ini',
            trendColor: 'text-slate-400',
            trendIcon: 'fa-regular fa-clock text-slate-400',
            trendBadgeClass: 'bg-slate-50 text-slate-600 border-slate-200',
            evaluationNote: 'Belum ada data perakitan hari ini. Menunggu operator memulai rakitan.'
          }
        });

        // Real-time Progress Monitor Pengambilan Data CMS
        const loadingProgress = ref({
          active: false,
          percent: 0,
          completedSteps: 0,
          totalSteps: 6,
          detail: '',
          recordsLoaded: 0
        });

        // Date Period Comparison Helpers (Supporting Backdate & Custom Ranges)
        const checkDateByPeriod = (dateStr, period = reportPeriod.value) => {
          const d = parseDateSafe(dateStr);
          if (!d) return false;

          if (period === 'daily') {
            const target = parseDateSafe(selectedReportDate.value);
            if (!target) return false;
            return d.getFullYear() === target.getFullYear() &&
                   d.getMonth() === target.getMonth() &&
                   d.getDate() === target.getDate();
          }

          if (period === 'weekly') {
            const { monday, sunday } = getWeekDateRange(selectedWeekOffset.value);
            return d >= monday && d <= sunday;
          }

          if (period === 'monthly') {
            const parts = selectedReportMonth.value.split('-').map(Number);
            const y = parts[0] || new Date().getFullYear();
            const m = parts[1] || (new Date().getMonth() + 1);
            return d.getFullYear() === y && (d.getMonth() + 1) === m;
          }

          if (period === 'custom') {
            const start = parseDateSafe(customStartDate.value);
            const end = parseDateSafe(customEndDate.value);
            if (start) start.setHours(0, 0, 0, 0);
            if (end) end.setHours(23, 59, 59, 999);
            if (start && end) return d >= start && d <= end;
            if (start) return d >= start;
            if (end) return d <= end;
            return true;
          }

          return true;
        };

        const activePeriodDateRangeLabel = computed(() => {
          const pad = (n) => String(n).padStart(2, '0');
          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
          const fmt = (d) => `${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;

          if (reportPeriod.value === 'daily') {
            const d = parseDateSafe(selectedReportDate.value) || new Date();
            return `${fmt(d)} (00:00:00 - 23:59:59)`;
          }

          if (reportPeriod.value === 'weekly') {
            const { monday, sunday } = getWeekDateRange(selectedWeekOffset.value);
            return `${fmt(monday)} s/d ${fmt(sunday)}`;
          }

          if (reportPeriod.value === 'monthly') {
            const parts = selectedReportMonth.value.split('-').map(Number);
            const y = parts[0] || new Date().getFullYear();
            const m = parts[1] || (new Date().getMonth() + 1);
            const start = new Date(y, m - 1, 1);
            const end = new Date(y, m, 0);
            return `Bulan ${months[m - 1]} ${y} (${fmt(start)} s/d ${fmt(end)})`;
          }

          if (reportPeriod.value === 'custom') {
            const start = parseDateSafe(customStartDate.value) || new Date();
            const end = parseDateSafe(customEndDate.value) || new Date();
            return `${fmt(start)} s/d ${fmt(end)}`;
          }

          return fmt(new Date());
        });

        // Helper to check if a tray was active in the selected period (created, assembled, or updated)
        // Optimasi O(1): Pre-index tray IDs & tray codes yang aktif di periode terpilih
        const activeTrayKeysInPeriod = computed(() => {
          const p = reportPeriod.value;
          const keys = new Set();
          (rawAssyTestsList.value || []).forEach(i => {
            if (checkDateByPeriod(i.finished_at || i.started_at || i.created_at, p)) {
              if (i.tray_id) keys.add(i.tray_id);
              if (i.tray_code) keys.add(i.tray_code);
            }
          });
          return keys;
        });

        const activePeriodTrays = computed(() => {
          const p = reportPeriod.value;
          const keys = activeTrayKeysInPeriod.value;
          return (rawTraysList.value || []).filter(t => {
            if (!t) return false;
            if (checkDateByPeriod(t.created_at, p)) return true;
            if (t.latest_assembly_at && checkDateByPeriod(t.latest_assembly_at, p)) return true;
            if (t.earliest_assembly_at && checkDateByPeriod(t.earliest_assembly_at, p)) return true;
            if (t.updated_at && checkDateByPeriod(t.updated_at, p)) return true;
            if (keys.has(t.id) || keys.has(t.tray_code)) return true;
            return false;
          });
        });

        // Client-side pagination untuk tabel Laporan Produksi (mencegah freeze/lag DOM)
        const reportTraysPage = ref(1);
        const reportTraysPageSize = ref(15);
        const reportTraysTotalPages = computed(() => Math.max(1, Math.ceil(activePeriodTrays.value.length / reportTraysPageSize.value)));
        const pagedActivePeriodTrays = computed(() => {
          const start = (reportTraysPage.value - 1) * reportTraysPageSize.value;
          return activePeriodTrays.value.slice(start, start + reportTraysPageSize.value);
        });
        watch(reportPeriod, () => { reportTraysPage.value = 1; });
        watch(reportDailyDate, () => { reportTraysPage.value = 1; });
        watch(reportWeeklyDate, () => { reportTraysPage.value = 1; });
        watch(reportMonthlyMonth, () => { reportTraysPage.value = 1; });
        watch(reportMonthlyYear, () => { reportTraysPage.value = 1; });
        watch(reportCustomStart, () => { reportTraysPage.value = 1; });
        watch(reportCustomEnd, () => { reportTraysPage.value = 1; });

        const activePeriodStats = computed(() => {
          const p = reportPeriod.value;

          // 1. Supply Gudang from Trays (Diterbitkan Gudang pada periode ini)
          const traysSuppliedInPeriod = rawTraysList.value.filter(t => checkDateByPeriod(t.created_at, p));
          let supplyTrays = traysSuppliedInPeriod.length;
          let supplySets = 0;
          let supplyParts = 0;
          traysSuppliedInPeriod.forEach(t => {
            supplySets += parseInt(t.total_sets || 0, 10);
            supplyParts += parseInt(t.parts_count || 0, 10);
          });

          // 2. Assembly Merakit (Dari seluruh baki yang memiliki aktivitas perakitan pada periode ini)
          let assembledGood = 0;
          let assembledHold = 0;
          let assembledScrap = 0;
          activePeriodTrays.value.forEach(t => {
            assembledGood += parseInt(t.good_count || 0, 10);
            assembledHold += parseInt(t.hold_count || 0, 10);
            assembledScrap += parseInt(t.scrap_count || 0, 10);
          });
          // Cross-check dengan item rakitan pada periode tersebut jika ada
          const assyGoodCountFromList = (rawAssyTestsList.value || [])
            .filter(i => checkDateByPeriod(i.finished_at || i.started_at || i.created_at, p))
            .filter(i => {
              const st = (i.assembly_status || i.original_assembly_status || i.status || '').toUpperCase();
              return st === 'GOOD' || st === 'PASS';
            }).length;
          if (assyGoodCountFromList > assembledGood) {
            assembledGood = assyGoodCountFromList;
          }

          // 2. TF 1 in period
          const tf1InPeriod = rawAssyTestsList.value.filter(i => checkDateByPeriod(i.created_at || i.finished_at || i.started_at, p));
          const tf1Total = tf1InPeriod.length;
          const tf1Good = tf1InPeriod.filter(i => {
            const s = (i.assy_test_status || i.status || '').toUpperCase();
            return s === 'GOOD' || s === 'PASS';
          }).length;
          const tf1NotGood = tf1Total - tf1Good;

          // 3. Average Durations in Period
          let totalAssyMinutes = 0, countAssyItems = 0;
          let totalTf1Minutes = 0, countTf1Items = 0;
          tf1InPeriod.forEach(i => {
            if (i.started_at && i.finished_at) {
              const diffSec = (new Date(i.finished_at) - new Date(i.started_at)) / 1000;
              if (diffSec > 0 && diffSec < 86400) {
                totalAssyMinutes += (diffSec / 60);
                countAssyItems++;
              }
            }
            if (i.finished_at && i.assy_tested_at) {
              const diffTf1Sec = (new Date(i.assy_tested_at) - new Date(i.finished_at)) / 1000;
              if (diffTf1Sec > 0 && diffTf1Sec < 86400) {
                totalTf1Minutes += (diffTf1Sec / 60);
                countTf1Items++;
              }
            }
          });
          const rawAssyAvg = countAssyItems > 0 ? (totalAssyMinutes / countAssyItems) : 0;
          const rawTf1Avg = countTf1Items > 0 ? (totalTf1Minutes / countTf1Items) : 0;
          const avgAssyDuration = rawAssyAvg >= 1 ? rawAssyAvg.toFixed(1) : (rawAssyAvg > 0 ? Number(rawAssyAvg.toFixed(2)) : '0.0');
          const avgTf1Duration = rawTf1Avg >= 1 ? rawTf1Avg.toFixed(1) : (rawTf1Avg > 0 ? Number(rawTf1Avg.toFixed(2)) : '0.0');
          const avgAssyNumber = rawAssyAvg >= 1 ? rawAssyAvg.toFixed(1) : (rawAssyAvg > 0 ? (rawAssyAvg * 60).toFixed(1) : '0');
          const avgAssyUnit = rawAssyAvg >= 1 ? 'Menit' : (rawAssyAvg > 0 ? 'Detik' : 'Menit');
          const avgAssyDisplay = rawAssyAvg >= 1 ? `${rawAssyAvg.toFixed(1)} menit` : (rawAssyAvg > 0 ? `${(rawAssyAvg * 60).toFixed(1)} detik` : '0 menit');

          // 4. Warehouse Receipts in Period
          const whInPeriod = rawWarehouseReceiptsList.value.filter(w => checkDateByPeriod(w.created_at || w.effective_warehouse_receipt_at, p));
          const whAcceptedSets = whInPeriod.filter(w => (w.warehouse_receipt_status || '').toUpperCase() === 'ACCEPTED').length;

          // 5. Delivery Orders in Period
          const doInPeriod = rawDeliveryOrdersList.value.filter(d => checkDateByPeriod(d.do_date || d.created_at, p));
          const doDocsCount = doInPeriod.length;
          let doCratesCount = 0;
          let doShippedSets = 0;
          doInPeriod.forEach(d => {
            const pls = d.packing_lists || d.packingLists || [];
            doCratesCount += pls.length;
            pls.forEach(pl => {
              const bxs = pl.boxes || [];
              bxs.forEach(box => {
                const itms = box.items || [];
                doShippedSets += itms.length;
              });
            });
          });

          // 6. QC acceptance in period
          const qcInPeriod = rawQcTestsList.value.filter(q => checkDateByPeriod(q.created_at || q.qc_tested_at, p));
          const qcTotalCount = qcInPeriod.length;
          const qcAccCount = qcInPeriod.filter(q => ['GOOD', 'GOOD_QC', 'ACC', 'ACCEPTED'].includes((q.qc_status || q.status || '').toUpperCase())).length;
          const qcPassRate = qcTotalCount > 0 ? Math.round((qcAccCount / qcTotalCount) * 100) : 100;

          return {
            supplyTrays,
            supplySets,
            supplyParts,
            assembledGood,
            assembledHold,
            assembledScrap,
            tf1Total,
            tf1Good,
            tf1NotGood,
            avgAssyDuration,
            avgTf1Duration,
            avgAssyNumber,
            avgAssyUnit,
            avgAssyDisplay,
            whAcceptedSets,
            doDocsCount,
            doCratesCount,
            doShippedSets,
            qcTotalCount,
            qcAccCount,
            qcPassRate
          };
        });

        const countDeptUsers = (deptKey) => {
          const k = String(deptKey || '').toLowerCase();
          return usersList.value.filter(u => {
            const d = String(u.department || '').toLowerCase();
            return d.includes(k);
          }).length;
        };

        const formatDateTime = (val) => {
          if (!val) return '-';
          try {
            const d = new Date(val);
            if (isNaN(d.getTime())) return String(val);
            const pad = (n) => String(n).padStart(2, '0');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
          } catch(e) {
            return String(val);
          }
        };

        const updateChartDataFromRaw = () => {
          const assyItems = rawAssyTestsList.value || [];
          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
          const daysMap = {};

          const distinctDates = new Set();
          // Selalu sertakan 7 hari terakhir agar grafik tren di Dashboard selalu kontinyu dan tidak kosong
          const now = new Date();
          for (let i = 6; i >= 0; i--) {
            const d = new Date(now);
            d.setDate(now.getDate() - i);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            distinctDates.add(`${y}-${m}-${day}`);
          }

          assyItems.forEach(item => {
            const dateStr = item.finished_at || item.started_at || item.created_at;
            if (dateStr) distinctDates.add(dateStr.split(' ')[0] || dateStr.split('T')[0]);
          });
          distinctDates.add(todayIsoStr());
          const sortedDates = Array.from(distinctDates).sort();

          sortedDates.forEach(k => {
            const d = new Date(k + 'T00:00:00');
            const lbl = !isNaN(d.getTime()) ? `${d.getDate()} ${months[d.getMonth()]}` : k;
            daysMap[k] = { label: lbl, assyTotalMin: 0, assyCount: 0, tf1TotalMin: 0, tf1Count: 0 };
          });

          assyItems.forEach(item => {
            const dateStr = item.finished_at || item.started_at || item.created_at;
            if (dateStr) {
              const key = dateStr.split(' ')[0] || dateStr.split('T')[0];
              if (daysMap[key]) {
                if (item.started_at && item.finished_at) {
                  const diff = (new Date(item.finished_at) - new Date(item.started_at)) / 1000 / 60;
                  if (diff > 0 && diff < 1440) {
                    daysMap[key].assyTotalMin += diff;
                    daysMap[key].assyCount++;
                  }
                }
                if (item.finished_at && item.assy_tested_at) {
                  const diffTf1 = (new Date(item.assy_tested_at) - new Date(item.finished_at)) / 1000 / 60;
                  if (diffTf1 > 0 && diffTf1 < 1440) {
                    daysMap[key].tf1TotalMin += diffTf1;
                    daysMap[key].tf1Count++;
                  }
                }
              }
            }
          });

          const dailyTrendsArray = Object.keys(daysMap).sort().map(k => {
            const d = daysMap[k];
            const rawAvgAssy = d.assyCount > 0 ? (d.assyTotalMin / d.assyCount) : 0;
            const rawAvgTf1 = d.tf1Count > 0 ? (d.tf1TotalMin / d.tf1Count) : 0;
            // Presisi desimal hingga 2 angka jika durasi sub-menit (detik), sehingga tidak dibulatkan jadi 0
            const avgAssy = rawAvgAssy >= 1 ? Number(rawAvgAssy.toFixed(1)) : Number(rawAvgAssy.toFixed(2));
            const avgTf1 = rawAvgTf1 >= 1 ? Number(rawAvgTf1.toFixed(1)) : Number(rawAvgTf1.toFixed(2));
            return { label: d.label, avgAssy, avgTf1, rawAvgAssy, rawAvgTf1 };
          });

          lastDailyTrendsData = dailyTrendsArray;
          renderTimeTrendsChart(dailyTrendsArray);
        };

        let renderChartRetryCount = 0;
        const renderTimeTrendsChart = (dailyData) => {
          const run = () => {
            const canvas = document.getElementById('dailyTimeTrendsChart');
            if (!canvas) {
              if (renderChartRetryCount < 15) {
                renderChartRetryCount++;
                setTimeout(run, 100);
              }
              return;
            }
            renderChartRetryCount = 0;

            if (typeof Chart === 'undefined') {
              setTimeout(() => renderTimeTrendsChart(dailyData), 200);
              return;
            }

            try {
              if (typeof Chart.getChart === 'function') {
                const existing = Chart.getChart(canvas) || Chart.getChart('dailyTimeTrendsChart');
                if (existing) existing.destroy();
              }
            } catch (_) {}

            if (timeTrendsChartInstance) {
              try { timeTrendsChartInstance.destroy(); } catch (_) {}
              timeTrendsChartInstance = null;
            }

            const dataList = Array.isArray(dailyData) && dailyData.length > 0 ? dailyData : [{ label: 'Hari Ini', avgAssy: 0, avgTf1: 0 }];
            const labels = dataList.map(d => d.label);
            const assyTimes = dataList.map(d => d.avgAssy);
            const tf1Times = dataList.map(d => d.avgTf1);

            // Deteksi jika semua durasi berkecepatan tinggi / sub-menit (< 1 menit / dalam hitungan detik)
            const maxAssy = Math.max(...assyTimes, 0);
            const isSubMinute = maxAssy > 0 && maxAssy < 1;

            let ySuggestedMax = 6;
            let yStepSize = 1;
            if (isSubMinute) {
              ySuggestedMax = Math.max(0.08, Number((maxAssy * 1.5).toFixed(2)));
              yStepSize = Number((ySuggestedMax / 4).toFixed(2));
            } else if (maxAssy >= 6) {
              ySuggestedMax = Math.ceil(maxAssy * 1.25);
              yStepSize = Math.max(1, Math.ceil(ySuggestedMax / 6));
            }

            try {
              timeTrendsChartInstance = new Chart(canvas, {
                type: 'line',
                data: {
                  labels: labels,
                  datasets: [
                    {
                      label: 'Rata-rata Perakitan',
                      data: assyTimes,
                      borderColor: '#ef4444',
                      backgroundColor: 'rgba(239, 68, 68, 0.08)',
                      fill: true,
                      tension: 0.35,
                      borderWidth: 2.5,
                      pointBackgroundColor: '#ef4444',
                      pointBorderColor: '#ffffff',
                      pointBorderWidth: 2,
                      pointRadius: 4.5,
                      pointHoverRadius: 6.5
                    }
                  ]
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: {
                    mode: 'index',
                    intersect: false
                  },
                  plugins: {
                    legend: { display: false },
                    tooltip: {
                      backgroundColor: '#0f172a',
                      titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                      bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                      padding: 10,
                      cornerRadius: 10,
                      callbacks: {
                        label: function(ctx) {
                          const val = ctx.parsed.y;
                          if (val > 0 && val < 1) {
                            const sec = (val * 60).toFixed(1);
                            return `${ctx.dataset.label}: ${sec} Detik (${val} m)`;
                          }
                          return `${ctx.dataset.label}: ${val} Menit`;
                        }
                      }
                    }
                  },
                  scales: {
                    x: {
                      grid: { color: 'rgba(226, 232, 240, 0.6)' },
                      ticks: { font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }, color: '#64748b' }
                    },
                    y: {
                      beginAtZero: true,
                      suggestedMin: 0,
                      suggestedMax: ySuggestedMax,
                      grid: { color: 'rgba(226, 232, 240, 0.6)' },
                      ticks: {
                        stepSize: yStepSize,
                        font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                        color: '#64748b',
                        callback: function(v) {
                          if (isSubMinute) {
                            return Math.round(v * 60) + 's';
                          }
                          return v + ' m';
                        }
                      }
                    }
                  }
                }
              });
            } catch (err) {
              console.error('Gagal membuat grafik Chart.js:', err);
            }
          };

          if (typeof nextTick === 'function') {
            nextTick(run);
          } else if (typeof Vue !== 'undefined' && typeof Vue.nextTick === 'function') {
            Vue.nextTick(run);
          } else {
            setTimeout(run, 50);
          }
        };

        // =====================================================================
        // LAPORAN PRODUKSI TIME TRENDS CHART (IDENTIK DENGAN DASHBOARD)
        // =====================================================================
        let reportTimeTrendsChartInstance = null;
        let renderReportChartsRetryCount = 0;

        const reportPeriodEvaluationNote = computed(() => {
          const assy = parseFloat(activePeriodStats.value.avgAssyDuration || 0);
          if (assy === 0) {
            return 'Belum ada data perakitan pada periode ini.';
          } else if (assy < 1.0) {
            const sec = Math.round(assy * 60);
            return `Sangat cepat (${sec} detik/set). Efisiensi kerja dan perakitan super optimal.`;
          } else if (assy <= 5.0) {
            return `Sangat cepat (${assy} menit/set). Efisiensi kerja dan perakitan sangat optimal.`;
          } else if (assy <= 10.0) {
            return `Normal (${assy} menit/set). Kecepatan perakitan stabil dan sesuai standar.`;
          } else {
            return `Memerlukan perhatian (${assy} menit/set). Evaluasi kesiapan part & alat.`;
          }
        });

        const renderReportCharts = () => {
          renderReportChartsRetryCount = 0; // Selalu reset retry counter saat fungsi dipanggil

          const run = () => {
            // Jika tab laporan sedang tidak dibuka, jangan render agar tidak membuang resource
            if (activeTab.value !== 'reports_kpi') {
              return;
            }

            const canvas = document.getElementById('reportTimeTrendsChart');
            if (!canvas) {
              if (renderReportChartsRetryCount < 20) {
                renderReportChartsRetryCount++;
                setTimeout(run, 100);
              }
              return;
            }
            renderReportChartsRetryCount = 0;

            if (typeof Chart === 'undefined') {
              setTimeout(run, 200);
              return;
            }

            try {
              if (typeof Chart.getChart === 'function') {
                const ex = Chart.getChart(canvas) || Chart.getChart('reportTimeTrendsChart');
                if (ex) ex.destroy();
              }
            } catch (_) {}

            if (reportTimeTrendsChartInstance) {
              try { reportTimeTrendsChartInstance.destroy(); } catch (_) {}
              reportTimeTrendsChartInstance = null;
            }

            const p = reportPeriod.value;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const assyItems = rawAssyTestsList.value || [];
            let labels = [];
            let assyTimes = [];

            if (p === 'daily') {
              const trays = activePeriodTrays.value || [];
              if (trays.length > 0) {
                labels = trays.map(t => t.tray_code);
                assyTimes = trays.map(t => {
                  const trayItems = assyItems.filter(i => (i.tray_id === t.id || i.tray_code === t.tray_code) && i.started_at && i.finished_at);
                  if (trayItems.length === 0) return 0;
                  const total = trayItems.reduce((acc, it) => {
                    const diff = (new Date(it.finished_at) - new Date(it.started_at)) / 1000 / 60;
                    return acc + (diff > 0 && diff < 1440 ? diff : 0);
                  }, 0);
                  const raw = total / trayItems.length;
                  return raw >= 1 ? Number(raw.toFixed(1)) : Number(raw.toFixed(2));
                });
              } else {
                labels = [formatShortDate(selectedReportDate.value)];
                assyTimes = [0];
              }
            } else if (p === 'weekly') {
              // MODE MINGGUAN: Render seluruh 7 hari dalam minggu terpilih (Senin s/d Minggu)
              const { monday } = getWeekDateRange(selectedWeekOffset.value);
              const dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
              const dateMap = {};

              for (let i = 0; i < 7; i++) {
                const d = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + i);
                const isoKey = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                const lbl = `${dayNames[i]} (${d.getDate()} ${months[d.getMonth()]})`;
                dateMap[isoKey] = { label: lbl, assyTotalMin: 0, assyCount: 0 };
              }

              assyItems.forEach(item => {
                const dateStr = item.finished_at || item.started_at || item.created_at;
                if (dateStr) {
                  const parsed = parseDateSafe(dateStr);
                  if (parsed) {
                    const isoKey = `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}-${String(parsed.getDate()).padStart(2, '0')}`;
                    if (dateMap[isoKey]) {
                      if (item.started_at && item.finished_at) {
                        const diff = (new Date(item.finished_at) - new Date(item.started_at)) / 1000 / 60;
                        if (diff > 0 && diff < 1440) {
                          dateMap[isoKey].assyTotalMin += diff;
                          dateMap[isoKey].assyCount++;
                        }
                      }
                    }
                  }
                }
              });

              labels = Object.keys(dateMap).map(k => dateMap[k].label);
              assyTimes = Object.keys(dateMap).map(k => {
                if (dateMap[k].assyCount === 0) return 0;
                const raw = dateMap[k].assyTotalMin / dateMap[k].assyCount;
                return raw >= 1 ? Number(raw.toFixed(1)) : Number(raw.toFixed(2));
              });
            } else if (p === 'monthly') {
              // MODE BULANAN: Render seluruh hari dalam bulan terpilih
              const parts = selectedReportMonth.value.split('-').map(Number);
              const yr = parts[0] || new Date().getFullYear();
              const mo = parts[1] || (new Date().getMonth() + 1);
              const daysInMonth = new Date(yr, mo, 0).getDate();
              const dateMap = {};

              for (let d = 1; d <= daysInMonth; d++) {
                const isoKey = `${yr}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                dateMap[isoKey] = { label: `${d} ${months[mo - 1]}`, assyTotalMin: 0, assyCount: 0 };
              }

              assyItems.forEach(item => {
                const dateStr = item.finished_at || item.started_at || item.created_at;
                if (dateStr) {
                  const parsed = parseDateSafe(dateStr);
                  if (parsed) {
                    const isoKey = `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}-${String(parsed.getDate()).padStart(2, '0')}`;
                    if (dateMap[isoKey]) {
                      if (item.started_at && item.finished_at) {
                        const diff = (new Date(item.finished_at) - new Date(item.started_at)) / 1000 / 60;
                        if (diff > 0 && diff < 1440) {
                          dateMap[isoKey].assyTotalMin += diff;
                          dateMap[isoKey].assyCount++;
                        }
                      }
                    }
                  }
                }
              });

              labels = Object.keys(dateMap).map(k => dateMap[k].label);
              assyTimes = Object.keys(dateMap).map(k => {
                if (dateMap[k].assyCount === 0) return 0;
                const raw = dateMap[k].assyTotalMin / dateMap[k].assyCount;
                return raw >= 1 ? Number(raw.toFixed(1)) : Number(raw.toFixed(2));
              });
            } else {
              // MODE RENTANG KUSTOM
              const dateMap = {};
              assyItems.forEach(item => {
                const dateStr = item.finished_at || item.started_at || item.created_at;
                if (dateStr && checkDateByPeriod(dateStr, p)) {
                  const parsed = parseDateSafe(dateStr);
                  if (parsed) {
                    const isoKey = `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}-${String(parsed.getDate()).padStart(2, '0')}`;
                    if (!dateMap[isoKey]) {
                      dateMap[isoKey] = { label: `${parsed.getDate()} ${months[parsed.getMonth()]}`, assyTotalMin: 0, assyCount: 0 };
                    }
                    if (item.started_at && item.finished_at) {
                      const diff = (new Date(item.finished_at) - new Date(item.started_at)) / 1000 / 60;
                      if (diff > 0 && diff < 1440) {
                        dateMap[isoKey].assyTotalMin += diff;
                        dateMap[isoKey].assyCount++;
                      }
                    }
                  }
                }
              });

              const sortedKeys = Object.keys(dateMap).sort();
              if (sortedKeys.length > 0) {
                labels = sortedKeys.map(k => dateMap[k].label);
                assyTimes = sortedKeys.map(k => {
                  if (dateMap[k].assyCount === 0) return 0;
                  const raw = dateMap[k].assyTotalMin / dateMap[k].assyCount;
                  return raw >= 1 ? Number(raw.toFixed(1)) : Number(raw.toFixed(2));
                });
              } else {
                labels = ['Periode Ini'];
                assyTimes = [0];
              }
            }

            const maxReportAssy = Math.max(...assyTimes, 0);
            const isReportSubMinute = maxReportAssy > 0 && maxReportAssy < 1;

            let reportYSuggestedMax = 6;
            let reportYStepSize = 1;
            if (isReportSubMinute) {
              reportYSuggestedMax = Math.max(0.08, Number((maxReportAssy * 1.5).toFixed(2)));
              reportYStepSize = Number((reportYSuggestedMax / 4).toFixed(2));
            } else if (maxReportAssy >= 6) {
              reportYSuggestedMax = Math.ceil(maxReportAssy * 1.25);
              reportYStepSize = Math.max(1, Math.ceil(reportYSuggestedMax / 6));
            }

            try {
              reportTimeTrendsChartInstance = new Chart(canvas, {
                type: 'line',
                data: {
                  labels: labels,
                  datasets: [
                    {
                      label: 'Rata-rata Perakitan',
                      data: assyTimes,
                      borderColor: '#ef4444',
                      backgroundColor: 'rgba(239, 68, 68, 0.08)',
                      fill: true,
                      tension: 0.35,
                      borderWidth: 2.5,
                      pointBackgroundColor: '#ef4444',
                      pointBorderColor: '#ffffff',
                      pointBorderWidth: 2,
                      pointRadius: 4.5,
                      pointHoverRadius: 6.5
                    }
                  ]
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: {
                    mode: 'index',
                    intersect: false
                  },
                  plugins: {
                    legend: { display: false },
                    tooltip: {
                      backgroundColor: '#0f172a',
                      titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                      bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                      padding: 10,
                      cornerRadius: 10,
                      callbacks: {
                        label: function(ctx) {
                          const val = ctx.parsed.y;
                          if (val > 0 && val < 1) {
                            const sec = (val * 60).toFixed(1);
                            return `${ctx.dataset.label}: ${sec} Detik (${val} m)`;
                          }
                          return `${ctx.dataset.label}: ${val} Menit`;
                        }
                      }
                    }
                  },
                  scales: {
                    x: {
                      grid: { color: 'rgba(226, 232, 240, 0.6)' },
                      ticks: { font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }, color: '#64748b' }
                    },
                    y: {
                      beginAtZero: true,
                      suggestedMin: 0,
                      suggestedMax: reportYSuggestedMax,
                      grid: { color: 'rgba(226, 232, 240, 0.6)' },
                      ticks: {
                        stepSize: reportYStepSize,
                        font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                        color: '#64748b',
                        callback: function(v) {
                          if (isReportSubMinute) {
                            return Math.round(v * 60) + 's';
                          }
                          return v + ' m';
                        }
                      }
                    }
                  }
                }
              });
            } catch (err) {
              console.error('Gagal membuat reportTimeTrendsChartInstance:', err);
            }
          };

          if (typeof nextTick === 'function') {
            nextTick(() => { setTimeout(run, 60); });
          } else {
            setTimeout(run, 60);
          }
        };

        const loadDashboardStats = async (isSilent = false) => {
          if (!isSilent) {
            dashboardStats.value.loading = true;
            loadingProgress.value = {
              active: true,
              percent: 5,
              completedSteps: 0,
              totalSteps: 6,
              detail: 'Menghubungkan ke server database...',
              recordsLoaded: 0
            };
          }

          let completed = 0;
          let totalRecords = 0;
          const onStepDone = (name, count = 0) => {
            completed++;
            totalRecords += count;
            const pct = Math.min(95, Math.round((completed / 6) * 100));
            if (!isSilent) {
              loadingProgress.value.completedSteps = completed;
              loadingProgress.value.percent = pct;
              loadingProgress.value.recordsLoaded = totalRecords;
              loadingProgress.value.detail = `${name} (${count > 0 ? count.toLocaleString() + ' data dimuat' : 'selesai'})`;
            }
          };

          try {
            const [traysRes, assyRes, qcRes, whRes, ruRes, doRes] = await Promise.allSettled([
              fetch('/api/assembly/trays')
                .then(r => r.json())
                .then(d => { onStepDone('Baki Perakitan', Array.isArray(d?.data) ? d.data.length : 0); return d; })
                .catch(() => { onStepDone('Baki Perakitan (timeout)', 0); return {}; }),

              fetch('/api/reports/assy-tests')
                .then(r => r.json())
                .then(d => { onStepDone('Hasil Uji Test Functional 1 (TF 1)', Array.isArray(d?.data) ? d.data.length : 0); return d; })
                .catch(() => { onStepDone('Hasil Uji TF 1 (timeout)', 0); return {}; }),

              fetch('/api/reports/qc-tests')
                .then(r => r.json())
                .then(d => { onStepDone('Hasil Quality Control (TF 2)', Array.isArray(d?.data) ? d.data.length : 0); return d; })
                .catch(() => { onStepDone('Hasil QC (timeout)', 0); return {}; }),

              fetch('/api/reports/warehouse-receipts')
                .then(r => r.json())
                .then(d => { onStepDone('Penerimaan Fisik Gudang', Array.isArray(d?.data) ? d.data.length : 0); return d; })
                .catch(() => { onStepDone('Penerimaan Gudang (timeout)', 0); return {}; }),

              fetch('/api/assembly/pending-reassembly')
                .then(r => r.json())
                .then(d => { onStepDone('Antrean Perakitan Ulang (RU)', Array.isArray(d?.data) ? d.data.length : 0); return d; })
                .catch(() => { onStepDone('Perakitan Ulang (timeout)', 0); return {}; }),

              fetch('/api/delivery-orders?all=1')
                .then(r => r.json())
                .then(d => { onStepDone('Dokumen Delivery Order', Array.isArray(d?.data) ? d.data.length : 0); return d; })
                .catch(() => { onStepDone('Delivery Order (timeout)', 0); return {}; })
            ]);

            // Store raw lists
            if (traysRes.status === 'fulfilled' && traysRes.value.ok && Array.isArray(traysRes.value.data)) {
              rawTraysList.value = traysRes.value.data;
            }
            if (assyRes.status === 'fulfilled' && assyRes.value.ok && Array.isArray(assyRes.value.data)) {
              rawAssyTestsList.value = assyRes.value.data;
            }
            if (qcRes.status === 'fulfilled' && qcRes.value.ok && Array.isArray(qcRes.value.data)) {
              rawQcTestsList.value = qcRes.value.data;
            }
            if (whRes.status === 'fulfilled' && whRes.value.ok && Array.isArray(whRes.value.data)) {
              rawWarehouseReceiptsList.value = whRes.value.data;
            }
            if (ruRes.status === 'fulfilled' && ruRes.value.ok && Array.isArray(ruRes.value.data)) {
              rawPendingReassemblyList.value = ruRes.value.data;
            }
            if (doRes.status === 'fulfilled' && doRes.value.ok && Array.isArray(doRes.value.data)) {
              rawDeliveryOrdersList.value = doRes.value.data;
            }

            // 1. Perakitan & Tray Summary
            const trays = rawTraysList.value;
            dashboardStats.value.assembly.activeTrays = trays.length;
            let g = 0, h = 0, s = 0;
            let puDispatchedSets = 0;
            trays.forEach(t => {
              g += parseInt(t.good_count || 0, 10);
              h += parseInt(t.hold_count || 0, 10);
              s += parseInt(t.scrap_count || 0, 10);
              if (t.tray_code && t.tray_code.includes('-PU')) {
                puDispatchedSets += parseInt(t.assemblies_count || t.total_sets || 0, 10);
              }
            });
            dashboardStats.value.assembly.good = g;
            dashboardStats.value.assembly.hold = h;
            dashboardStats.value.assembly.scrap = s;
            dashboardStats.value.assembly.total = g + h + s;

            // 2. TF 1 Summary (Good vs Not Good)
            const assyItems = rawAssyTestsList.value;
            dashboardStats.value.tf1.total = assyItems.length;
            dashboardStats.value.tf1.passed = assyItems.filter(i => (i.assy_test_status || '').toUpperCase() === 'GOOD' || (i.status || '').toUpperCase() === 'GOOD').length;
            dashboardStats.value.tf1.hold = assyItems.filter(i => (i.assy_test_status || '').toUpperCase().includes('HOLD') || (i.status || '').toUpperCase().includes('HOLD')).length;
            dashboardStats.value.tf1.notGood = assyItems.filter(i => (i.assy_test_status || '').toUpperCase().includes('NOT_GOOD') || (i.assy_test_status || '').toUpperCase().includes('NG')).length;

            // 3. QC Summary (Good vs Not Good)
            const qcItems = rawQcTestsList.value;
            const qcTestedOnly = qcItems.filter(i => {
              const st = (i.status || i.qc_status || i.qc_test_status || '').toUpperCase();
              return st && !st.includes('MENUNGGU') && st !== 'PENDING';
            });
            dashboardStats.value.qc.total = qcTestedOnly.length;
            dashboardStats.value.qc.acc = qcTestedOnly.filter(i => {
              const st = (i.status || i.qc_status || i.qc_test_status || '').toUpperCase();
              return ['GOOD', 'GOOD_QC', 'ACC', 'ACCEPTED', 'PASS'].includes(st);
            }).length;
            dashboardStats.value.qc.hold = qcTestedOnly.filter(i => {
              const st = (i.status || i.qc_status || i.qc_test_status || '').toUpperCase();
              return st.includes('HOLD') || st.includes('NOT_GOOD') || st.includes('NG');
            }).length;
            dashboardStats.value.qc.waiting = qcItems.length - qcTestedOnly.length;

            // 4. Warehouse Receipts Summary (Total Dokumen, ACC, Declined)
            const whItems = rawWarehouseReceiptsList.value;
            dashboardStats.value.warehouse.totalReceipts = whItems.length;
            const uniqueWhDocs = new Set();
            let whAccepted = 0, whDeclined = 0;
            whItems.forEach(w => {
              if (w.warehouse_doc_number && w.warehouse_doc_number !== '-') uniqueWhDocs.add(w.warehouse_doc_number);
              const st = (w.warehouse_receipt_status || w.status || '').toUpperCase();
              if (st === 'ACCEPTED') whAccepted++;
              else if (st === 'DECLINED') whDeclined++;
            });
            dashboardStats.value.warehouse.totalDocs = uniqueWhDocs.size;
            dashboardStats.value.warehouse.acceptedSets = whAccepted;
            dashboardStats.value.warehouse.declinedSets = whDeclined;

            // 5. Perakitan Ulang (RU) (Belum Diarahkan vs Sudah Diarahkan)
            dashboardStats.value.reassembly.notDispatched = rawPendingReassemblyList.value.length;
            dashboardStats.value.reassembly.dispatched = puDispatchedSets;
            dashboardStats.value.reassembly.pendingRU = rawPendingReassemblyList.value.length;

            // 6. Delivery Orders (Dokumen, Peti, Dus, SN)
            const dos = rawDeliveryOrdersList.value;
            dashboardStats.value.delivery.totalDO = dos.length;
            let totalCrates = 0, totalBoxes = 0, totalShippedSNs = 0;
            const shippedSNSet = new Set();
            dos.forEach(d => {
              const pls = d.packing_lists || d.packingLists || [];
              totalCrates += pls.length;
              pls.forEach(pl => {
                const bxs = pl.boxes || [];
                totalBoxes += bxs.length;
                bxs.forEach(box => {
                  const itms = box.items || [];
                  totalShippedSNs += itms.length;
                  itms.forEach(it => {
                    if (it.serial_number) shippedSNSet.add(it.serial_number);
                  });
                });
              });
            });
            dashboardStats.value.delivery.totalCrates = totalCrates;
            dashboardStats.value.delivery.totalBoxes = totalBoxes;
            dashboardStats.value.delivery.shippedSNCount = totalShippedSNs;
            dashboardStats.value.delivery.cloudSynced = dos.filter(d => Boolean(d.cloud_pdf_url)).length;

            // 7. Ready to Ship (Lolos Gudang belum masuk DO)
            dashboardStats.value.pipeline.readyToShip = Math.max(0, whAccepted - totalShippedSNs);

            // 8. Determine latest production date across data
            let latestDate = '';
            assyItems.forEach(i => {
              const d = i.finished_at || i.started_at || i.created_at;
              if (d) {
                const key = d.split(' ')[0] || d.split('T')[0];
                if (!latestDate || key > latestDate) latestDate = key;
              }
            });
            trays.forEach(t => {
              const d = t.latest_assembly_at || t.updated_at || t.created_at;
              if (d) {
                const key = d.split(' ')[0] || d.split('T')[0];
                if (!latestDate || key > latestDate) latestDate = key;
              }
            });
            whItems.forEach(w => {
              const d = w.created_at || w.effective_warehouse_receipt_at;
              if (d) {
                const key = d.split(' ')[0] || d.split('T')[0];
                if (!latestDate || key > latestDate) latestDate = key;
              }
            });
            latestProductionDate.value = latestDate;

            // 10. Compute Today's times & trend, then render chart
            let todayAssy = 0;
            let yesterdayAssy = 0;
            const now = new Date();
            const todayKey = now.toISOString().split('T')[0];
            const yesterdayD = new Date(now);
            yesterdayD.setDate(now.getDate() - 1);
            const yesterdayKey = yesterdayD.toISOString().split('T')[0];

            let todayMinSum = 0, todayCount = 0;
            let todayTf1MinSum = 0, todayTf1Count = 0;
            let yestMinSum = 0, yestCount = 0;
            assyItems.forEach(it => {
              const dateStr = it.finished_at || it.started_at || it.created_at;
              if (dateStr) {
                const k = dateStr.split(' ')[0] || dateStr.split('T')[0];
                if (it.started_at && it.finished_at) {
                  const diff = (new Date(it.finished_at) - new Date(it.started_at)) / 1000 / 60;
                  if (diff > 0 && diff < 1440) {
                    if (k === todayKey) { todayMinSum += diff; todayCount++; }
                    if (k === yesterdayKey) { yestMinSum += diff; yestCount++; }
                  }
                }
                if (k === todayKey) {
                  if (it.assy_tested_at && it.finished_at) {
                    const diffTf1 = (new Date(it.assy_tested_at) - new Date(it.finished_at)) / 1000 / 60;
                    if (diffTf1 > 0 && diffTf1 < 1440) {
                      todayTf1MinSum += diffTf1;
                      todayTf1Count++;
                    }
                  }
                }
              }
            });

            todayAssy = todayCount > 0 ? (todayMinSum / todayCount) : 0;
            todayTf1 = todayTf1Count > 0 ? (todayTf1MinSum / todayTf1Count) : 0;
            yesterdayAssy = yestCount > 0 ? (yestMinSum / yestCount) : 0;

            dashboardStats.value.times.avgAssyMinutesToday = dailyKpi.value.avgAssyMinutes;
            dashboardStats.value.times.avgTf1MinutesToday = dailyKpi.value.avgTf1Minutes;

            const activeDisplay = dailyKpi.value.avgAssyDisplay;
            const activePeriod = dailyKpi.value.periodLabel;

            if (todayCount === 0) {
              if (dailyKpi.value.countAssy > 0) {
                dashboardStats.value.times.trendText = `${activePeriod}: ${activeDisplay} / set`;
                dashboardStats.value.times.trendColor = 'text-emerald-600';
                dashboardStats.value.times.trendIcon = 'fa-solid fa-clock-rotate-left text-emerald-600';
                dashboardStats.value.times.trendBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                dashboardStats.value.times.evaluationNote = `Data terakhir tercatat ${activeDisplay} / set (${activePeriod}). Menunggu rakitan baru hari ini.`;
              } else {
                dashboardStats.value.times.trendText = 'Belum Ada Perakitan';
                dashboardStats.value.times.trendColor = 'text-slate-400';
                dashboardStats.value.times.trendIcon = 'fa-regular fa-clock text-slate-400';
                dashboardStats.value.times.trendBadgeClass = 'bg-slate-50 text-slate-600 border-slate-200';
                dashboardStats.value.times.evaluationNote = 'Belum ada data perakitan. Menunggu operator memulai rakitan.';
              }
            } else if (yesterdayAssy > 0 && todayAssy < yesterdayAssy) {
              const diffMin = yesterdayAssy - todayAssy;
              const diffText = diffMin < 1 ? `${Math.round(diffMin * 60)} detik` : `${diffMin.toFixed(1)} menit`;
              dashboardStats.value.times.trendText = `Efisiensi Naik: 🔽 ${diffText} Lebih Cepat`;
              dashboardStats.value.times.trendColor = 'text-emerald-600';
              dashboardStats.value.times.trendIcon = 'fa-solid fa-arrow-trend-down text-emerald-600';
              dashboardStats.value.times.trendBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
              dashboardStats.value.times.evaluationNote = `Peningkatan efisiensi waktu ${diffText} lebih cepat dibanding hari kemarin.`;
            } else if (yesterdayAssy > 0 && todayAssy > yesterdayAssy) {
              const diffMin = todayAssy - yesterdayAssy;
              const diffText = diffMin < 1 ? `${Math.round(diffMin * 60)} detik` : `${diffMin.toFixed(1)} menit`;
              dashboardStats.value.times.trendText = `Performa Melambat: 🔼 ${diffText}`;
              dashboardStats.value.times.trendColor = 'text-amber-600';
              dashboardStats.value.times.trendIcon = 'fa-solid fa-arrow-trend-up text-amber-600';
              dashboardStats.value.times.trendBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
              dashboardStats.value.times.evaluationNote = `Terjadi penambahan durasi rata-rata ${diffText}. Pastikan ketersediaan part dan alat memadai.`;
            } else {
              dashboardStats.value.times.trendText = 'Optimal: Rata-rata Normal';
              dashboardStats.value.times.trendColor = 'text-emerald-600';
              dashboardStats.value.times.trendIcon = 'fa-solid fa-circle-check text-emerald-600';
              dashboardStats.value.times.trendBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
              dashboardStats.value.times.evaluationNote = `Kecepatan perakitan stabil di ${activeDisplay} / set.`;
            }

            updateChartDataFromRaw();
            if (activeTab.value === 'reports_kpi') {
              renderReportCharts();
            }

          } catch (e) {
            console.error('Error loadDashboardStats:', e);
          } finally {
            dashboardStats.value.loading = false;
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            lastSyncTimeStr.value = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            if (!isSilent) {
              loadingProgress.value.percent = 100;
              loadingProgress.value.detail = `Sinkronisasi selesai (${totalRecords.toLocaleString()} baris data berhasil dimuat)!`;
              setTimeout(() => {
                loadingProgress.value.active = false;
              }, 1200);
            }
          }
        };

        const startAutoUpdate = () => {
          if (autoUpdateTimer) clearInterval(autoUpdateTimer);
          autoUpdateTimer = setInterval(() => {
            if (isCmsAuthenticated.value && document.visibilityState === 'visible' && !dashboardStats.value.loading && (activeTab.value === 'dashboard' || activeTab.value === 'reports_kpi')) {
              loadDashboardStats(true);
            }
          }, 30000);
        };

        const stopAutoUpdate = () => {
          if (autoUpdateTimer) {
            clearInterval(autoUpdateTimer);
            autoUpdateTimer = null;
          }
        };

        // =====================================================================
        // DATA STATE FOR CMS MODULES
        // =====================================================================
        const errorLogs = ref([]);
        const errorPage = ref(1);
        const errorPerPage = 20;

        const slowQueries = ref([]);
        const slowPage = ref(1);
        const slowPerPage = 20;

        const anomalySummary = ref({ 
          total_anomalies: 0, 
          orphan_parts: { count: 0, items: [] }, 
          stuck_sessions: { count: 0, items: [] }, 
          desynced_stocks: { count: 0, items: [] }, 
          duplicate_parts: { count: 0, items: [] } 
        });
        const anomalyCategoryFilter = ref('all');
        const anomalyPage = ref(1);
        const anomalyPerPage = 20;

        const doSettings = ref({});
        const cloudSettings = ref({ cloud_name: '', api_key: '', has_secret: false, configured: false });
        const cloudForm = ref({ cloud_name: '', api_key: '', api_secret: '' });
        const isTestingCloud = ref(false);
        const isSavingCloud = ref(false);
        const cloudTestResult = ref(null);

        const usersList = ref([]);
        const loadingUsers = ref(false);
        const editingUserId = ref(null);
        const isSavingUser = ref(false);
        const userSearchQuery = ref('');
        const userDeptFilter = ref('all');
        const userPage = ref(1);
        const userPerPage = 10;
        const openUserForm = ref(false);
        const selectedUserIds = ref([]);

        const userForm = ref({
          username: '',
          full_name: '',
          password: '',
          department: 'assembly',
          role: 'assembly',
          can_access_cms: false,
          permissions: {}
        });

        const cmsManagedMenuGroups = [
          {
            key: 'gudang',
            name: 'Gudang & Material',
            menus: [
              { key: 'warehouse', name: 'Work Order & Supply' },
              { key: 'warehouse_receipt', name: 'Good Receipts (WH/GR)' },
              { key: 'returns', name: 'Pengembalian Part' },
              { key: 'master', name: 'Master Stok' }
            ]
          },
          {
            key: 'perakitan',
            name: 'Perakitan & Pengujian',
            menus: [
              { key: 'assembly', name: 'Area Perakitan' },
              { key: 'production_hold', name: 'Hasil Perakitan (Hold)' },
              { key: 'assy_testing', name: 'Test Functional 1' },
              { key: 'qc', name: 'Test Functional 2 (QC)' },
              { key: 'functional_hold', name: 'Hasil Test Hold' },
              { key: 'reassembly', name: 'Perakitan Ulang (RU)' }
            ]
          },
          {
            key: 'dokumen',
            name: 'Dokumen & Pengiriman',
            menus: [
              { key: 'traveler', name: 'Cetak Dokumen' },
              { key: 'delivery_order', name: 'Delivery Order & PL' },
              { key: 'report', name: 'History & Laporan' }
            ]
          },
          {
            key: 'admin',
            name: 'Administrator',
            menus: [
              { key: 'users', name: 'Kelola Pengguna' },
              { key: 'verify_set', name: 'Verifikasi Set' },
              { key: 'verify_part', name: 'Verifikasi Part' },
              { key: 'verify_replacement', name: 'Pergantian Part' },
              { key: 'audit', name: 'Audit Log' },
              { key: 'downtime', name: 'Downtime Log' },
              { key: 'testing_points', name: 'Panduan Uji' }
            ]
          }
        ];

        // Multi-select helpers
        const isUserSelected = (id) => selectedUserIds.value.includes(id);
        const isAllUsersSelected = computed(() => {
          return pagedUsersList.value.length > 0 && pagedUsersList.value.every(u => selectedUserIds.value.includes(u.id));
        });
        const toggleSelectAllUsers = (e) => {
          if (e.target.checked) {
            pagedUsersList.value.forEach(u => {
              if (!selectedUserIds.value.includes(u.id)) selectedUserIds.value.push(u.id);
            });
          } else {
            const pageIds = pagedUsersList.value.map(u => u.id);
            selectedUserIds.value = selectedUserIds.value.filter(id => !pageIds.includes(id));
          }
        };

        const bulkResetSessions = async () => {
          if (selectedUserIds.value.length === 0) return;
          const confirmed = await showConfirmCard({
            title: 'Reset Sesi Masal',
            message: `Putuskan sesi login aktif untuk ${selectedUserIds.value.length} pengguna terpilih?`,
            confirmText: 'Ya, Reset Sesi',
            confirmBtnClass: 'bg-amber-600 hover:bg-amber-700 text-white'
          });
          if (!confirmed) return;
          for (const id of selectedUserIds.value) {
            const u = usersList.value.find(x => x.id === id);
            if (u) {
              await fetch('/api/admin/users/reset-session', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: u.username })
              }).catch(() => {});
            }
          }
          selectedUserIds.value = [];
          triggerToast('Berhasil', 'Seluruh sesi pengguna terpilih telah direset.', 'success');
          fetchUsers();
        };

        // Computed Lists
        const filteredUsersList = computed(() => {
          let list = usersList.value || [];
          if (userDeptFilter.value && userDeptFilter.value !== 'all') {
            const d = userDeptFilter.value.toLowerCase();
            list = list.filter(u => (u.department || '').toLowerCase() === d || (u.role || '').toLowerCase() === d);
          }
          if (userSearchQuery.value && userSearchQuery.value.trim()) {
            const q = userSearchQuery.value.toLowerCase().trim();
            list = list.filter(u => 
              (u.username && u.username.toLowerCase().includes(q)) ||
              (u.full_name && u.full_name.toLowerCase().includes(q)) ||
              (u.department && u.department.toLowerCase().includes(q))
            );
          }
          return list;
        });

        const totalUserPages = computed(() => Math.ceil(filteredUsersList.value.length / userPerPage) || 1);
        const pagedUsersList = computed(() => {
          const start = (userPage.value - 1) * userPerPage;
          return filteredUsersList.value.slice(start, start + userPerPage);
        });

        const totalErrorPages = computed(() => Math.ceil(errorLogs.value.length / errorPerPage) || 1);
        const pagedErrorLogs = computed(() => {
          const start = (errorPage.value - 1) * errorPerPage;
          return errorLogs.value.slice(start, start + errorPerPage);
        });

        const totalSlowPages = computed(() => Math.ceil(slowQueries.value.length / slowPerPage) || 1);
        const pagedSlowQueries = computed(() => {
          const start = (slowPage.value - 1) * slowPerPage;
          return slowQueries.value.slice(start, start + slowPerPage);
        });

        const allAnomalyItems = computed(() => {
          const res = [];
          const a = anomalySummary.value;
          (a.orphan_parts?.items || []).forEach(it => res.push({ category: 'Part Yatim', ...it }));
          (a.stuck_sessions?.items || []).forEach(it => res.push({ category: 'Sesi Nyangkut', ...it }));
          (a.desynced_stocks?.items || []).forEach(it => res.push({ category: 'Stok Desinkron', ...it }));
          (a.duplicate_parts?.items || []).forEach(it => res.push({ category: 'Part Duplikat', ...it }));
          return res;
        });

        const filteredAnomalyItems = computed(() => {
          if (anomalyCategoryFilter.value === 'all') return allAnomalyItems.value;
          return allAnomalyItems.value.filter(it => it.category === anomalyCategoryFilter.value);
        });

        const totalAnomalyPages = computed(() => Math.ceil(filteredAnomalyItems.value.length / anomalyPerPage) || 1);
        const pagedAnomalyItems = computed(() => {
          const start = (anomalyPage.value - 1) * anomalyPerPage;
          return filteredAnomalyItems.value.slice(start, start + anomalyPerPage);
        });

        // Tab Navigation
        const switchTab = (t) => {
          activeTab.value = t;
          if (typeof nextTick === 'function') {
            nextTick(() => {
              fetchActiveTabData();
            });
          } else {
            setTimeout(fetchActiveTabData, 50);
          }
        };

        const fetchActiveTabData = () => {
          if (activeTab.value === 'dashboard') {
            loadDashboardStats();
            if (lastDailyTrendsData && lastDailyTrendsData.length > 0) {
              renderTimeTrendsChart(lastDailyTrendsData);
            }
          } else if (activeTab.value === 'reports_kpi') {
            loadDashboardStats();
            if (typeof nextTick === 'function') {
              nextTick(() => renderReportCharts());
            } else {
              setTimeout(renderReportCharts, 80);
            }
          } else if (activeTab.value === 'users') {
            fetchUsers();
          } else if (activeTab.value === 'anomalies') {
            scanAnomalies();
          } else if (activeTab.value === 'errors') {
            fetchErrorLogs();
          } else if (activeTab.value === 'slow_queries') {
            fetchSlowQueries();
          } else if (activeTab.value === 'do_settings') {
            fetchDoSettings();
            fetchCloudinarySettings();
          }
        };

        // Lifecycle check
        onMounted(() => {
          const storedToken = sessionStorage.getItem('cms_admin_token');
          const storedUser = sessionStorage.getItem('cms_admin_user');
          if (storedToken && storedUser) {
            try {
              cmsToken.value = storedToken;
              adminUser.value = JSON.parse(storedUser);
              isCmsAuthenticated.value = true;
              loadDashboardStats(false);
              startAutoUpdate();
              fetchUsers();
            } catch (e) {
              sessionStorage.removeItem('cms_admin_token');
              sessionStorage.removeItem('cms_admin_user');
            }
          }

          // Real-time tab listeners for background sync
          document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && isCmsAuthenticated.value && (activeTab.value === 'dashboard' || activeTab.value === 'reports_kpi')) {
              loadDashboardStats(true);
            }
          });
          window.addEventListener('focus', () => {
            if (isCmsAuthenticated.value && (activeTab.value === 'dashboard' || activeTab.value === 'reports_kpi')) {
              loadDashboardStats(true);
            }
          });
        });

        // Authentication Methods
        const submitCmsLogin = async () => {
          loginLoading.value = true;
          loginError.value = '';
          try {
            const res = await fetch('/api/admin/cms-login', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
              body: JSON.stringify(loginForm.value)
            });
            const data = await res.json();
            if (data.ok && data.data?.cms_token) {
              cmsToken.value = data.data.cms_token;
              adminUser.value = data.data.user || {};
              isCmsAuthenticated.value = true;

              sessionStorage.setItem('cms_admin_token', cmsToken.value);
              sessionStorage.setItem('cms_admin_user', JSON.stringify(adminUser.value));
              localStorage.setItem('token', cmsToken.value);
              localStorage.setItem('role', adminUser.value.role || 'admin');
              localStorage.setItem('username', adminUser.value.username || '');
              localStorage.setItem('full_name', adminUser.value.full_name || '');

              loginForm.value = { username: '', password: '' };
              loadDashboardStats(false);
              startAutoUpdate();
              fetchUsers();
              triggerToast('Login Berhasil', 'Selamat datang di Portal Administrator CMS.', 'success');
            } else {
              loginError.value = data.error || 'Autentikasi gagal. Periksa username dan password.';
            }
          } catch (e) {
            loginError.value = 'Gagal menghubungi server: ' + e.message;
          } finally {
            loginLoading.value = false;
          }
        };

        const logoutCms = async () => {
          const confirmed = await showConfirmCard({
            title: 'Kunci Portal Administrator',
            message: 'Sesi administrator akan dikunci. Anda harus memasukkan password untuk masuk kembali.',
            confirmText: 'Kunci Sekarang',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!confirmed) return;
          stopAutoUpdate();
          try {
            await fetch('/api/admin/cms-logout', { method: 'POST' });
          } catch (e) {}
          sessionStorage.removeItem('cms_admin_token');
          sessionStorage.removeItem('cms_admin_user');
          cmsToken.value = '';
          adminUser.value = {};
          isCmsAuthenticated.value = false;
          triggerToast('Sesi Dikunci', 'Portal Administrator telah dikunci dengan aman.', 'info');
        };

        // Module Methods: Cleanup
        const confirmClearTrays = async () => {
          const confirmed = await showConfirmCard({
            title: 'Reset Tray & Rakitan',
            message: 'Tindakan ini akan mengosongkan seluruh data tray operasional. Data tidak dapat dikembalikan!',
            confirmText: 'Hapus Semua Tray',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!confirmed) return;
          try {
            const res = await fetch('/api/admin/cms/clear-trays', { method: 'POST', headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.ok) triggerToast('Berhasil', 'Seluruh data tray berhasil dibersihkan.', 'success');
            else triggerToast('Gagal', data.error || 'Gagal membersihkan data tray.', 'error');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const confirmClearDowntimes = async () => {
          const confirmed = await showConfirmCard({
            title: 'Reset Catatan Downtime',
            message: 'Apakah Anda yakin ingin menghapus seluruh catatan downtime lini perakitan?',
            confirmText: 'Hapus Downtime',
            confirmBtnClass: 'bg-slate-900 hover:bg-slate-800 text-white'
          });
          if (!confirmed) return;
          try {
            const res = await fetch('/api/admin/cms/clear-downtimes', { method: 'POST', headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.ok) triggerToast('Berhasil', 'Log downtime berhasil dibersihkan.', 'success');
            else triggerToast('Gagal', data.error, 'error');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const confirmClearAuditLogs = async () => {
          const confirmed = await showConfirmCard({
            title: 'Reset Audit Logs',
            message: 'Hapus seluruh catatan log aktivitas pengguna?',
            confirmText: 'Hapus Audit Log',
            confirmBtnClass: 'bg-slate-900 hover:bg-slate-800 text-white'
          });
          if (!confirmed) return;
          try {
            const res = await fetch('/api/admin/cms/clear-audit-logs', { method: 'POST', headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.ok) triggerToast('Berhasil', 'Log audit berhasil dibersihkan.', 'success');
            else triggerToast('Gagal', data.error, 'error');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        // Module Methods: Errors & Slow Queries
        const fetchErrorLogs = async () => {
          try {
            const res = await fetch('/api/admin/error-logs?limit=500');
            const data = await res.json();
            if (data.ok && Array.isArray(data.data)) errorLogs.value = data.data;
          } catch (e) {}
        };

        const clearErrorLogs = async () => {
          const confirmed = await showConfirmCard({
            title: 'Bersihkan Log Error',
            message: 'Hapus seluruh catatan log error backend?',
            confirmText: 'Bersihkan',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!confirmed) return;
          try {
            await fetch('/api/admin/error-logs/clear', { method: 'POST', headers: { 'Accept': 'application/json' } });
            errorLogs.value = [];
            triggerToast('Berhasil', 'Log error telah dibersihkan.', 'success');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const fetchSlowQueries = async () => {
          try {
            const res = await fetch('/api/admin/slow-queries?limit=500');
            const data = await res.json();
            if (data.ok && Array.isArray(data.data)) slowQueries.value = data.data;
          } catch (e) {}
        };

        const clearSlowQueries = async () => {
          const confirmed = await showConfirmCard({
            title: 'Bersihkan Slow Queries',
            message: 'Hapus seluruh catatan pemantauan kueri lambat?',
            confirmText: 'Bersihkan',
            confirmBtnClass: 'bg-slate-900 hover:bg-slate-800 text-white'
          });
          if (!confirmed) return;
          try {
            await fetch('/api/admin/slow-queries/clear', { method: 'POST', headers: { 'Accept': 'application/json' } });
            slowQueries.value = [];
            triggerToast('Berhasil', 'Log slow queries telah dibersihkan.', 'success');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        // Module Methods: Anomalies
        const scanAnomalies = async (silent = false) => {
          try {
            const res = await fetch('/api/admin/anomalies/scan');
            const data = await res.json();
            if (data.ok && data.data) {
              anomalySummary.value = data.data;
              if (!silent) triggerToast('Pemindaian Selesai', `Ditemukan ${data.data.total_anomalies} anomali data.`, data.data.total_anomalies > 0 ? 'warn' : 'success');
            }
          } catch (e) {
            if (!silent) triggerToast('Error', 'Gagal memindai anomali: ' + e.message, 'error');
          }
        };

        const repairAnomalies = async () => {
          const confirmed = await showConfirmCard({
            title: 'Perbaiki Anomali Otomatis',
            message: 'Sistem akan membersihkan referensi part rusak dan memulihkan integritas data. Lanjutkan?',
            confirmText: 'Perbaiki Sekarang',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!confirmed) return;
          try {
            const res = await fetch('/api/admin/anomalies/repair', { method: 'POST', headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.ok) {
              triggerToast('Berhasil', 'Perbaikan anomali selesai.', 'success');
              scanAnomalies(true);
            } else {
              triggerToast('Gagal', data.error, 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        // Module Methods: DO & Cloudinary Settings
        const fetchDoSettings = async () => {
          try {
            const res = await fetch('/api/admin/cms/do-settings');
            const data = await res.json();
            if (data.ok && data.data) doSettings.value = data.data;
          } catch (e) {}
        };

        const saveDoSettings = async () => {
          try {
            const res = await fetch('/api/admin/cms/do-settings', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(doSettings.value)
            });
            const data = await res.json();
            if (data.ok) triggerToast('Berhasil', 'Pengaturan DO berhasil disimpan.', 'success');
            else triggerToast('Gagal', data.error, 'error');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const fetchCloudinarySettings = async () => {
          try {
            const res = await fetch('/api/admin/cloudinary/settings');
            const data = await res.json();
            if (data.ok && data.data) {
              cloudSettings.value = data.data;
              cloudForm.value.cloud_name = data.data.cloud_name || '';
              cloudForm.value.api_key = data.data.api_key || '';
              cloudForm.value.api_secret = '';
            }
          } catch (e) {}
        };

        const saveCloudinarySettings = async () => {
          isSavingCloud.value = true;
          try {
            const res = await fetch('/api/admin/cloudinary/settings', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(cloudForm.value)
            });
            const data = await res.json();
            if (data.ok) {
              triggerToast('Berhasil', 'Kredensial Cloudinary berhasil disimpan.', 'success');
              fetchCloudinarySettings();
            } else {
              triggerToast('Gagal', data.error, 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSavingCloud.value = false;
          }
        };

        const testCloudinaryConnection = async () => {
          isTestingCloud.value = true;
          try {
            const res = await fetch('/api/admin/cloudinary/test', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(cloudForm.value)
            });
            const data = await res.json();
            const msg = data.data?.message || data.message || (data.ok ? 'Koneksi Cloudinary berhasil.' : 'Koneksi gagal.');
            triggerToast(data.ok ? 'Koneksi Berhasil' : 'Koneksi Gagal', msg, data.ok ? 'success' : 'error');
          } catch (e) {
            triggerToast('Koneksi Gagal', 'Gagal: ' + e.message, 'error');
          } finally {
            isTestingCloud.value = false;
          }
        };

        // Module Methods: Users
        const fetchUsers = async () => {
          loadingUsers.value = true;
          try {
            const res = await fetch('/api/admin/users');
            const data = await res.json();
            if (data.ok && Array.isArray(data.data)) usersList.value = data.data;
          } catch (e) {}
          finally { loadingUsers.value = false; }
        };

        const countCmsUsers = () => {
          return usersList.value.filter(u => String(u.department || '').toLowerCase().includes('admin')).length;
        };

        const countRoleUsers = (roleKey) => {
          return usersList.value.filter(u => (u.department || u.role || '').toLowerCase() === roleKey.toLowerCase()).length;
        };

        const getDeptBadgeClass = (d) => {
          const s = String(d || '').toLowerCase();
          if (s.includes('admin')) return 'bg-red-50 text-red-700 border border-red-200';
          if (s.includes('qc')) return 'bg-purple-50 text-purple-700 border border-purple-200';
          if (s.includes('ware') || s.includes('gudang')) return 'bg-blue-50 text-blue-700 border border-blue-200';
          return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
        };

        const countUserWritePerms = (u) => {
          if (!u || !u.permissions) return 0;
          const p = typeof u.permissions === 'string' ? JSON.parse(u.permissions || '{}') : u.permissions;
          return Object.values(p).filter(v => v === 2 || v === '2' || v === 'full').length;
        };

        const countUserReadPerms = (u) => {
          if (!u || !u.permissions) return 0;
          const p = typeof u.permissions === 'string' ? JSON.parse(u.permissions || '{}') : u.permissions;
          return Object.values(p).filter(v => v === 1 || v === '1' || v === 'read').length;
        };

        const setUserPermission = (k, lvl) => {
          userForm.value.permissions[k] = lvl;
        };

        const applyUserPreset = (preset) => {
          const allMenus = cmsManagedMenuGroups.flatMap(g => g.menus).map(m => m.key);
          const perms = {};
          if (preset === 'admin') {
            allMenus.forEach(k => perms[k] = 2);
            userForm.value.can_access_cms = true;
            userForm.value.department = 'administrator';
            userForm.value.role = 'admin';
          } else if (preset === 'operator') {
            allMenus.forEach(k => perms[k] = 0);
            ['assembly', 'assy_testing', 'production_hold'].forEach(k => perms[k] = 2);
            userForm.value.can_access_cms = false;
            userForm.value.department = 'assembly';
            userForm.value.role = 'assembly';
          } else if (preset === 'readonly') {
            allMenus.forEach(k => perms[k] = 1);
            userForm.value.can_access_cms = false;
          }
          userForm.value.permissions = perms;
        };

        const resetUserForm = () => {
          editingUserId.value = null;
          userForm.value = {
            username: '',
            full_name: '',
            password: '',
            department: 'assembly',
            role: 'assembly',
            can_access_cms: false,
            permissions: {}
          };
        };

        const editUser = (u) => {
          editingUserId.value = u.id;
          let p = {};
          try {
            p = typeof u.permissions === 'string' ? JSON.parse(u.permissions || '{}') : (u.permissions || {});
          } catch (e) {}
          userForm.value = {
            username: u.username,
            full_name: u.full_name || u.username,
            password: '',
            department: u.department || u.role || 'assembly',
            role: u.role || 'assembly',
            can_access_cms: Boolean(u.id === 3 || u.can_access_cms),
            permissions: p
          };
          openUserForm.value = true;
        };

        const saveUser = async () => {
          isSavingUser.value = true;
          try {
            const isEdit = Boolean(editingUserId.value);
            const payload = {
              user_id: editingUserId.value,
              username: userForm.value.username,
              full_name: userForm.value.full_name,
              department: userForm.value.department,
              role: userForm.value.department === 'administrator' ? 'admin' : userForm.value.department,
              can_access_cms: userForm.value.can_access_cms,
              permissions: userForm.value.permissions
            };
            if (userForm.value.password) payload.password = userForm.value.password;

            const res = await fetch('/api/admin/users/update', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.ok) {
              triggerToast('Berhasil', isEdit ? 'Pengguna berhasil diperbarui.' : 'Pengguna baru berhasil dibuat.', 'success');
              openUserForm.value = false;
              resetUserForm();
              fetchUsers();
            } else {
              triggerToast('Gagal', data.error || 'Gagal menyimpan pengguna.', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSavingUser.value = false;
          }
        };

        const resetUserSession = async (u) => {
          const confirmed = await showConfirmCard({
            title: 'Reset Sesi Login',
            message: `Putuskan sesi aktif untuk akun @${u.username}? Pengguna harus login ulang.`,
            confirmText: 'Reset Sesi',
            confirmBtnClass: 'bg-amber-600 hover:bg-amber-700 text-white'
          });
          if (!confirmed) return;
          try {
            const res = await fetch('/api/admin/users/reset-session', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ username: u.username })
            });
            const data = await res.json();
            if (data.ok) {
              triggerToast('Berhasil', `Sesi @${u.username} berhasil direset.`, 'success');
              fetchUsers();
            } else {
              triggerToast('Gagal', data.error, 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const deleteUser = async (u) => {
          if (u.id === 3) {
            triggerToast('Dilarang', 'Akun Administrator utama sistem tidak dapat dihapus.', 'error');
            return;
          }
          const confirmed = await showConfirmCard({
            title: 'Hapus Pengguna',
            message: `Apakah Anda yakin ingin menghapus akun @${u.username}? Tindakan ini permanen.`,
            confirmText: 'Hapus Akun',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!confirmed) return;
          try {
            const res = await fetch('/api/admin/users/delete', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ user_id: u.id })
            });
            const data = await res.json();
            if (data.ok) {
              triggerToast('Berhasil', 'Pengguna berhasil dihapus.', 'success');
              fetchUsers();
            } else {
              triggerToast('Gagal', data.error, 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        return {
          isCmsAuthenticated,
          adminUser,
          loginForm,
          showPassword,
          loginLoading,
          loginError,
          submitCmsLogin,
          logoutCms,
          activeTab,
          switchTab,
          fetchActiveTabData,
          loading,
          statsVisible,

          // Dashboard stats & Reporting
          dashboardStats,
          loadingProgress,
          loadDashboardStats,
          lastSyncTimeStr,
          startAutoUpdate,
          stopAutoUpdate,
          reportPeriod,
          setReportPeriod,
          dailyKpi,
          activePeriodDateRangeLabel,
          activePeriodStats,
          activePeriodTrays,
          reportTraysPage,
          reportTraysPageSize,
          reportTraysTotalPages,
          pagedActivePeriodTrays,
          countDeptUsers,
          formatDateTime,
          renderTimeTrendsChart,
          renderReportCharts,
          reportPeriodEvaluationNote,
          selectedReportDate,
          selectedWeekOffset,
          selectedReportMonth,
          customStartDate,
          customEndDate,
          chartWindowMode,
          latestProductionDate,
          shiftReportDate,
          setReportDateToday,
          shiftReportWeek,
          setReportWeekCurrent,
          shiftReportMonth,
          setReportMonthCurrent,
          setQuickCustomRange,
          setChartWindow,
          updateChartDataFromRaw,
          jumpToLatestDate,
          formatShortDate,
          todayIsoStr,

          // Cleanup
          confirmClearTrays,
          confirmClearDowntimes,
          confirmClearAuditLogs,

          // Errors
          errorLogs,
          errorPage,
          errorPerPage,
          totalErrorPages,
          pagedErrorLogs,
          fetchErrorLogs,
          clearErrorLogs,

          // Slow Queries
          slowQueries,
          slowPage,
          slowPerPage,
          totalSlowPages,
          pagedSlowQueries,
          fetchSlowQueries,
          clearSlowQueries,

          // Anomalies
          anomalySummary,
          anomalyCategoryFilter,
          anomalyPage,
          anomalyPerPage,
          allAnomalyItems,
          filteredAnomalyItems,
          totalAnomalyPages,
          pagedAnomalyItems,
          scanAnomalies,
          repairAnomalies,

          // DO & Cloud Settings
          doSettings,
          fetchDoSettings,
          saveDoSettings,
          cloudSettings,
          cloudForm,
          isTestingCloud,
          isSavingCloud,
          cloudTestResult,
          fetchCloudinarySettings,
          saveCloudinarySettings,
          testCloudinaryConnection,

          // Users Management
          usersList,
          loadingUsers,
          editingUserId,
          isSavingUser,
          userSearchQuery,
          userDeptFilter,
          userPage,
          userPerPage,
          totalUserPages,
          pagedUsersList,
          userForm,
          openUserForm,
          selectedUserIds,
          isUserSelected,
          isAllUsersSelected,
          toggleSelectAllUsers,
          bulkResetSessions,
          cmsManagedMenuGroups,
          fetchUsers,
          filteredUsersList,
          countCmsUsers,
          countRoleUsers,
          getDeptBadgeClass,
          countUserWritePerms,
          countUserReadPerms,
          setUserPermission,
          applyUserPreset,
          resetUserForm,
          editUser,
          saveUser,
          resetUserSession,
          deleteUser,

          // Global UI
          isCmsSidebarOpen,
          toast,
          triggerToast,
          confirmModalState,
          showConfirmCard,
          handleConfirmCard
        };
      }
    }).mount('#adminApp');
  </script>
</body>
</html>
