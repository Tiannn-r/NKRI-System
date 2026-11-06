<!DOCTYPE html>

<html lang="id">

<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Portal CMS & Diagnostik Server - Sistem Final Assembly P9</title>

  

  <!-- Favicon -->

  <link rel="icon" type="image/png" href="/emblem.png">



  <!-- Inter Font -->

  <link rel="preconnect" href="https://fonts.googleapis.com">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">



  <!-- Tailwind CSS CDN -->

  <script src="https://cdn.tailwindcss.com"></script>

  <script>

    tailwind.config = {

      theme: {

        extend: {

          fontFamily: {

            sans: ['Inter', 'sans-serif'],

            mono: ['JetBrains Mono', 'monospace'],

          }

        }

      }

    }

  </script>



  <!-- FontAwesome 6 -->

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">



  <!-- Vue 3 CDN -->

  <script src="https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.global.prod.js"></script>



  <style>

    body {

      font-family: 'Inter', sans-serif;

    }

    [v-cloak] { display: none !important; }

  </style>

</head>

<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col antialiased">

  <div id="adminApp" v-cloak class="min-h-screen flex flex-col">

    @verbatim



    <!-- ========================================================================= -->

    <!-- STATE 1: GATE LOGIN KHUSUS ADMINISTRATOR (RE-AUTHENTICATION REQUIRED) -->

    <!-- ========================================================================= -->

    <div v-if="!isCmsAuthenticated" class="flex-grow flex items-center justify-center p-4 sm:p-6 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

      <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-700/30 overflow-hidden transform transition-all duration-300">

        

        <!-- Header Card -->

        <div class="bg-gradient-to-r from-red-600 via-red-700 to-rose-700 p-6 sm:p-8 text-white text-center relative overflow-hidden">

          <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>

          <div class="relative z-10">

            <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 mx-auto flex items-center justify-center text-3xl shadow-lg mb-3">

              <i class="fa-solid fa-server"></i>

            </div>

            <span class="inline-block bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full backdrop-blur-sm mb-2">

              <i class="fa-solid fa-shield-halved mr-1"></i> Area Administrator

            </span>

            <h1 class="text-xl sm:text-2xl font-black tracking-tight leading-tight">Portal CMS & Diagnostik</h1>

          </div>

        </div>



        <!-- Form Login Gate -->

        <div class="p-6 sm:p-8 space-y-5">

          <!-- Alert Pesan Error jika login gagal -->

          <div v-if="loginError" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl p-3.5 flex items-start space-x-2.5">

            <i class="fa-solid fa-triangle-exclamation text-base mt-0.5 flex-shrink-0"></i>

            <div>

              <strong class="font-bold block">Autentikasi Gagal</strong>

              <span>{{ loginError }}</span>

            </div>

          </div>



          <form @submit.prevent="submitCmsLogin" class="space-y-4">

            <!-- Username -->

            <div class="space-y-1.5">

              <label class="block text-xs font-bold uppercase text-slate-700 tracking-wider">

                Username Admin <span class="text-red-600">*</span>

              </label>

              <div class="relative">

                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">

                  <i class="fa-solid fa-user-shield text-sm"></i>

                </span>

                <input type="text" v-model="loginForm.username" required autofocus placeholder="Masukkan username admin"

                  class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-xl pl-10 pr-3.5 py-3 text-sm font-semibold text-slate-900 focus:outline-none transition">

              </div>

            </div>



            <!-- Password -->

            <div class="space-y-1.5">

              <label class="block text-xs font-bold uppercase text-slate-700 tracking-wider">

                Password Admin <span class="text-red-600">*</span>

              </label>

              <div class="relative">

                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">

                  <i class="fa-solid fa-key text-sm"></i>

                </span>

                <input :type="showPassword ? 'text' : 'password'" v-model="loginForm.password" required placeholder="Masukkan password admin"

                  class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-xl pl-10 pr-10 py-3 text-sm font-semibold text-slate-900 focus:outline-none transition">

                <button type="button" @click="showPassword = !showPassword"

                  class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">

                  <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>

                </button>

              </div>

            </div>



            <!-- Tombol Masuk -->

            <button type="submit" :disabled="loginLoading"

              class="w-full bg-red-600 hover:bg-red-700 active:scale-[0.98] text-white font-bold py-3.5 px-4 rounded-xl text-sm transition duration-150 shadow-lg shadow-red-600/30 flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50">

              <i v-if="loginLoading" class="fa-solid fa-circle-notch animate-spin"></i>

              <i v-else class="fa-solid fa-lock-open"></i>

              <span>{{ loginLoading ? 'Memverifikasi...' : 'Masuk Portal CMS' }}</span>

            </button>

          </form>



          <!-- Link Navigasi Kembali -->

          <div class="pt-2 text-center border-t border-slate-100">

            <a href="/" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition inline-flex items-center space-x-1.5 py-1">

              <i class="fa-solid fa-arrow-left text-[11px]"></i>

              <span>Kembali ke Aplikasi Operasional</span>

            </a>

          </div>

        </div>



      </div>

    </div>





    <!-- ========================================================================= -->

    <!-- STATE 2: DASHBOARD PORTAL CMS & DIAGNOSTIK SERVER (TERAUTENTIKASI) -->

    <!-- ========================================================================= -->

    <div v-else class="flex-grow flex h-screen overflow-hidden bg-slate-100/70 font-sans relative">

      

      <!-- ===================================================================== -->

      <!-- UNIVERSAL SIDEBAR DRAWER (CLEAN WHITE THEME, SMOOTH ANIMATION) -->

      <!-- ===================================================================== -->



      <!-- Backdrop Overlay -->

      <transition enter-active-class="transition-opacity ease-out duration-300" enter-from-class="opacity-0"

        enter-to-class="opacity-100" leave-active-class="transition-opacity ease-in duration-200"

        leave-from-class="opacity-100" leave-to-class="opacity-0">

        <div v-if="isCmsSidebarOpen" class="fixed inset-0 bg-slate-950/50 backdrop-blur-xs z-40"

          @click="isCmsSidebarOpen = false"></div>

      </transition>



      <!-- Sidebar Drawer (White Aesthetic) -->

      <transition enter-active-class="transition-transform ease-out duration-300" enter-from-class="-translate-x-full"

        enter-to-class="translate-x-0" leave-active-class="transition-transform ease-in duration-200"

        leave-from-class="translate-x-0" leave-to-class="-translate-x-full">

        <aside v-if="isCmsSidebarOpen"

          class="fixed inset-y-0 left-0 z-50 w-72 sm:w-80 max-w-[85vw] bg-white text-slate-800 shadow-2xl border-r border-slate-200 flex flex-col justify-between overflow-y-auto select-none">

          

          <div>

            <!-- Top Header Logo & Close Button -->

            <div class="p-4 sm:p-5 flex items-center justify-between border-b border-slate-100 bg-slate-50/70">

              <div class="flex items-center space-x-2.5">

                <img src="/emblem.png" alt="Logo" class="h-8 w-auto object-contain bg-white p-1 rounded-lg border border-slate-200 shadow-xs">

                <div class="leading-tight">

                  <div class="flex items-center gap-1.5">

                    <span class="font-black text-sm text-slate-900 tracking-wide">PORTAL CMS</span>

                  </div>

                  <p class="text-[10px] text-slate-500 font-medium">Final Assembly</p>

                </div>

              </div>

              <button type="button" @click="isCmsSidebarOpen = false"

                class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-slate-100 transition active:scale-95 cursor-pointer"

                title="Tutup Menu">

                <i class="fa-solid fa-xmark text-lg"></i>

              </button>

            </div>



            <!-- Navigation Menu Items (Clean & Simple, No Extra Text) -->

            <nav class="p-3 sm:p-4 space-y-4">

              

              <!-- GROUP 1: PENGGUNA & HAK AKSES -->

              <div class="space-y-1">

                <div class="px-3 py-1 text-[10px] font-black text-slate-400 uppercase tracking-wider">

                  Pengguna

                </div>

                <button type="button" @click="activeTab = 'users'; fetchUsers(); isCmsSidebarOpen = false"

                  :class="activeTab === 'users' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"

                  class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">

                  <div class="flex items-center space-x-2.5">

                    <i class="fa-solid fa-users-gear text-sm" :class="activeTab === 'users' ? 'text-white' : 'text-slate-400 group-hover:text-red-600'"></i>

                    <span class="font-bold">Kelola Pengguna</span>

                  </div>

                  <span v-if="usersList.length > 0"

                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"

                    :class="activeTab === 'users' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200'">

                    {{ usersList.length }}

                  </span>

                </button>

              </div>



              <!-- GROUP 2: PEMELIHARAAN DATABASE -->

              <div class="space-y-1">

                <div class="px-3 py-1 text-[10px] font-black text-slate-400 uppercase tracking-wider">

                  Pemeliharaan Data

                </div>

                <button type="button" @click="activeTab = 'cleanup'; isCmsSidebarOpen = false"

                  :class="activeTab === 'cleanup' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"

                  class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">

                  <div class="flex items-center space-x-2.5">

                    <i class="fa-solid fa-broom text-sm" :class="activeTab === 'cleanup' ? 'text-white' : 'text-slate-400 group-hover:text-red-600'"></i>

                    <span class="font-bold">Pembersihan Database</span>

                  </div>

                </button>

                <button type="button" @click="activeTab = 'anomalies'; scanAnomalies(); isCmsSidebarOpen = false"

                  :class="activeTab === 'anomalies' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"

                  class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">

                  <div class="flex items-center space-x-2.5">

                    <i class="fa-solid fa-wand-magic-sparkles text-sm" :class="activeTab === 'anomalies' ? 'text-white' : 'text-slate-400 group-hover:text-red-600'"></i>

                    <span class="font-bold">Pemindai Anomali</span>

                  </div>

                  <span v-if="anomalySummary.total_anomalies > 0"

                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"

                    :class="activeTab === 'anomalies' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-700 border border-red-200'">

                    {{ anomalySummary.total_anomalies }}

                  </span>

                </button>

              </div>



              <!-- GROUP 3: DIAGNOSTIK & LOG -->

              <div class="space-y-1">

                <div class="px-3 py-1 text-[10px] font-black text-slate-400 uppercase tracking-wider">

                  Diagnostik & Log

                </div>

                <button type="button" @click="activeTab = 'errors'; fetchErrorLogs(); isCmsSidebarOpen = false"

                  :class="activeTab === 'errors' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"

                  class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">

                  <div class="flex items-center space-x-2.5">

                    <i class="fa-solid fa-triangle-exclamation text-sm" :class="activeTab === 'errors' ? 'text-white' : 'text-slate-400 group-hover:text-red-600'"></i>

                    <span class="font-bold">Error Logger</span>

                  </div>

                  <span v-if="errorLogs.length > 0"

                    class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-500 text-white">

                    {{ errorLogs.length }}

                  </span>

                </button>

                <button type="button" @click="activeTab = 'slow_queries'; fetchSlowQueries(); isCmsSidebarOpen = false"

                  :class="activeTab === 'slow_queries' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"

                  class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">

                  <div class="flex items-center space-x-2.5">

                    <i class="fa-solid fa-stopwatch text-sm" :class="activeTab === 'slow_queries' ? 'text-white' : 'text-slate-400 group-hover:text-amber-500'"></i>

                    <span class="font-bold">Slow Query Monitor</span>

                  </div>

                  <span v-if="slowQueries.length > 0"

                    class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-slate-900">

                    {{ slowQueries.length }}

                  </span>

                </button>

              </div>



              <!-- GROUP 4: INTEGRASI DOKUMEN -->

              <div class="space-y-1">

                <div class="px-3 py-1 text-[10px] font-black text-slate-400 uppercase tracking-wider">

                  Integrasi Dokumen

                </div>

                <button type="button" @click="activeTab = 'do_settings'; fetchDoSettings(); isCmsSidebarOpen = false"

                  :class="activeTab === 'do_settings' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"

                  class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">

                  <div class="flex items-center space-x-2.5">

                    <i class="fa-solid fa-file-invoice text-sm" :class="activeTab === 'do_settings' ? 'text-white' : 'text-slate-400 group-hover:text-red-600'"></i>

                    <span class="font-bold">DO & Cloudinary</span>

                  </div>

                </button>

              </div>



            </nav>

          </div>



          <!-- Sidebar Footer: Admin Profile & Quick Links -->

          <div class="p-3.5 sm:p-4 border-t border-slate-200 bg-slate-50/80 space-y-3">

            <div class="flex items-center space-x-2.5 px-3 py-2 bg-white rounded-xl border border-slate-200 shadow-2xs">

              <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 border border-red-200 flex items-center justify-center text-xs font-bold shrink-0">

                <i class="fa-solid fa-user-shield"></i>

              </div>

              <div class="leading-tight truncate flex-1 min-w-0">

                <div class="text-xs font-bold text-slate-900 truncate">{{ adminUser.full_name || adminUser.username }}</div>

                <div class="text-[10px] text-slate-400 font-mono truncate">@{{ adminUser.username }}</div>

              </div>

            </div>



            <!-- Bottom Actions: App & Logout -->

            <div class="grid grid-cols-2 gap-2">

              <a href="/" target="_blank"

                class="bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-center py-2 px-2 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1.5 shadow-2xs"

                title="Buka Aplikasi Operasional Pabrik">

                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>

                <span class="truncate">Halaman</span>

              </a>

              <button type="button" @click="logoutCms"

                class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-center py-2 px-2 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"

                title="Kunci Portal">

                <i class="fa-solid fa-lock text-[10px]"></i>

                <span>Kunci</span>

              </button>

            </div>

          </div>



        </aside>

      </transition>



      <!-- ===================================================================== -->

      <!-- MAIN WORKSPACE CONTENT WRAPPER (FULL CANVAS) -->

      <!-- ===================================================================== -->

      <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">

        

        <!-- STICKY TOPBAR -->

        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 shadow-xs px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-3">

          

          <!-- Left: Toggle Sidebar Button & Page Title -->

          <div class="flex items-center space-x-3">

            <button type="button" @click="isCmsSidebarOpen = true"

              class="flex items-center space-x-2 px-3 py-2 text-slate-700 hover:text-red-600 hover:bg-slate-100 rounded-xl transition active:scale-95 cursor-pointer border border-slate-200 shadow-2xs"

              title="Buka Menu Navigasi Sidebar">

              <i class="fa-solid fa-bars text-base"></i>

            </button>



            <div>

              <h1 class="text-base sm:text-lg font-black text-slate-900 leading-tight">

                <span v-if="activeTab === 'users'"><i class="fa-solid fa-users-gear text-red-600 mr-1.5"></i>Kelola Pengguna</span>

                <span v-else-if="activeTab === 'cleanup'"><i class="fa-solid fa-broom text-red-600 mr-1.5"></i>Pembersihan Database</span>

                <span v-else-if="activeTab === 'anomalies'"><i class="fa-solid fa-wand-magic-sparkles text-red-600 mr-1.5"></i>Pemindai Anomali</span>

                <span v-else-if="activeTab === 'errors'"><i class="fa-solid fa-triangle-exclamation text-red-600 mr-1.5"></i>Backend Error Logger</span>

                <span v-else-if="activeTab === 'slow_queries'"><i class="fa-solid fa-stopwatch text-amber-500 mr-1.5"></i>Slow Query Monitor</span>

                <span v-else-if="activeTab === 'do_settings'"><i class="fa-solid fa-file-invoice text-red-600 mr-1.5"></i>Pengaturan DO & Dokumen</span>

              </h1>

            </div>

          </div>

        </header>



        <!-- FULL FUNGSI MAIN CONTENT AREA -->

        <main class="flex-grow p-4 sm:p-6 lg:p-8 space-y-6">



        <!-- ========================================================= -->

        <!-- TAB 1: ALAT PEMBERSIHAN DATABASE (CLEAN & SIMPLE) -->

        <!-- ========================================================= -->

        <div v-if="activeTab === 'cleanup'" class="space-y-4">

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            

            <!-- Card 1: Reset Tray & Pulihkan Stok -->

            <div class="bg-white border-2 border-red-200 rounded-2xl p-5 shadow-sm space-y-4 flex flex-col justify-between">

              <div>

                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-lg mb-3">

                  <i class="fa-solid fa-trash-can"></i>

                </div>

                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">Pembersihan Tray & Pulihkan Stok</h3>

                <p class="text-xs text-slate-500 mt-1">Mengosongkan seluruh data tray dan mengembalikan stok part ke status <span class="text-emerald-600 font-bold">'Tersedia'</span>.</p>

              </div>

              <button type="button" @click="confirmClearTrays" :disabled="loading"

                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow-md shadow-red-600/20 flex items-center justify-center space-x-2 cursor-pointer active:scale-95 disabled:opacity-50">

                <i class="fa-solid fa-arrows-rotate" :class="loading ? 'animate-spin' : ''"></i>

                <span>Reset Tray & Pulihkan Stok</span>

              </button>

            </div>



            <!-- Card 2: Hapus Riwayat Downtime Operator -->

            <div class="bg-white border border-amber-200 rounded-2xl p-5 shadow-sm space-y-4 flex flex-col justify-between">

              <div>

                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-lg mb-3">

                  <i class="fa-solid fa-clock-rotate-left"></i>

                </div>

                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">Hapus Riwayat Jeda / Downtime</h3>

                <p class="text-xs text-slate-500 mt-1">Mengosongkan seluruh riwayat catatan jeda workstation perakitan.</p>

              </div>

              <button type="button" @click="confirmClearDowntimes" :disabled="loading"

                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow-md shadow-amber-500/20 flex items-center justify-center space-x-2 cursor-pointer active:scale-95 disabled:opacity-50">

                <i class="fa-solid fa-trash"></i>

                <span>Bersihkan Riwayat Jeda</span>

              </button>

            </div>



            <!-- Card 3: Hapus Riwayat Audit Trail Sistem -->

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 flex flex-col justify-between">

              <div>

                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg mb-3">

                  <i class="fa-solid fa-list-check"></i>

                </div>

                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">Hapus Riwayat Audit Trail</h3>

                <p class="text-xs text-slate-500 mt-1">Mengosongkan seluruh catatan log audit pergantian part dan aktivitas.</p>

              </div>

              <button type="button" @click="confirmClearAuditLogs" :disabled="loading"

                class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow-md shadow-slate-800/20 flex items-center justify-center space-x-2 cursor-pointer active:scale-95 disabled:opacity-50">

                <i class="fa-solid fa-eraser"></i>

                <span>Bersihkan Seluruh Audit Log</span>

              </button>

            </div>



          </div>

        </div>



        <!-- ========================================================= -->

        <!-- TAB 2: BACKEND ERROR LOGGER (RINGKAS & PAGINATION 20) -->

        <!-- ========================================================= -->

        <div v-if="activeTab === 'errors'" class="space-y-4">

          <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5">

            

            <!-- Header Banner Ringkas dengan Total Error -->

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">

              <div class="flex items-center gap-3">

                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-lg shrink-0">

                  <i class="fa-solid fa-triangle-exclamation"></i>

                </div>

                <div>

                  <div class="flex items-center gap-2">

                    <h3 class="text-base font-black text-slate-900">Backend Error Logs</h3>

                    <span class="bg-red-500 text-white font-mono font-bold text-xs px-2.5 py-0.5 rounded-full">

                      {{ errorLogs.length }} Data

                    </span>

                  </div>

                  <p class="text-xs text-slate-500">Rekaman exception server backend.</p>

                </div>

              </div>



              <!-- Action Buttons -->

              <div class="flex items-center space-x-2 shrink-0 self-end sm:self-auto">

                <button type="button" @click="fetchErrorLogs"

                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">

                  <i class="fa-solid fa-rotate-right"></i>

                  <span>Refresh</span>

                </button>

                <button type="button" @click="clearErrorLogs" :disabled="errorLogs.length === 0"

                  class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">

                  <i class="fa-solid fa-trash"></i>

                  <span>Bersihkan</span>

                </button>

              </div>

            </div>



            <!-- List Error Logs -->

            <div v-if="errorLogs.length === 0" class="text-center py-12 text-slate-400">

              <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-1.5 block"></i>

              <span class="text-xs font-semibold text-slate-600">Tidak ada error backend tercatat.</span>

            </div>

            

            <div v-else class="space-y-4">

              <!-- Item List (Paged 20 Data) -->

              <div class="space-y-2">

                <div v-for="(err, idx) in pagedErrorLogs" :key="err.id || idx"

                  class="p-3.5 bg-slate-50/70 hover:bg-slate-50 border border-slate-200 rounded-xl space-y-1.5 transition">

                  <div class="flex items-center justify-between gap-2">

                    <div class="flex items-center gap-2">

                      <span class="text-xs font-bold font-mono text-slate-400">

                        #{{ (errorPage - 1) * errorPerPage + idx + 1 }}

                      </span>

                      <span class="bg-red-100 text-red-700 font-mono font-bold text-[10px] px-2 py-0.5 rounded border border-red-200">

                        HTTP {{ err.error_code || '500' }}

                      </span>

                    </div>

                    <span class="text-[11px] font-mono text-slate-400">{{ err.created_at }}</span>

                  </div>

                  <div class="text-xs font-bold text-slate-900 font-mono break-all bg-white p-2.5 rounded-lg border border-slate-200/80">

                    {{ err.message }}

                  </div>

                  <div v-if="err.file" class="text-[11px] text-slate-500 font-mono truncate">

                    {{ err.file }}:{{ err.line }}

                  </div>

                </div>

              </div>



              <!-- Pagination Controls (Batas 20 Data) -->

              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">

                <div class="text-xs text-slate-500 font-medium">

                  Menampilkan <strong>{{ (errorPage - 1) * errorPerPage + 1 }}</strong> - <strong>{{ Math.min(errorPage * errorPerPage, errorLogs.length) }}</strong> dari <strong>{{ errorLogs.length }}</strong> data

                </div>

                

                <div v-if="totalErrorPages > 1" class="flex items-center space-x-1.5 self-end sm:self-auto">

                  <button type="button" @click="errorPage = Math.max(1, errorPage - 1)" :disabled="errorPage === 1"

                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                    <i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya

                  </button>

                  

                  <span class="px-3 py-1.5 text-xs font-bold text-slate-700 font-mono">

                    Hal {{ errorPage }} / {{ totalErrorPages }}

                  </span>



                  <button type="button" @click="errorPage = Math.min(totalErrorPages, errorPage + 1)" :disabled="errorPage === totalErrorPages"

                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                    Berikutnya <i class="fa-solid fa-chevron-right ml-1"></i>

                  </button>

                </div>

              </div>

            </div>



          </div>

        </div>



        <!-- ========================================================= -->

        <!-- TAB 3: SLOW QUERY MONITOR (RINGKAS & PAGINATION 20) -->

        <!-- ========================================================= -->

        <div v-if="activeTab === 'slow_queries'" class="space-y-4">

          <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5">

            

            <!-- Header Banner Ringkas dengan Total Slow Query -->

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">

              <div class="flex items-center gap-3">

                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0">

                  <i class="fa-solid fa-stopwatch"></i>

                </div>

                <div>

                  <div class="flex items-center gap-2">

                    <h3 class="text-base font-black text-slate-900">Slow Query Monitor (>500ms)</h3>

                    <span class="bg-amber-500 text-slate-950 font-mono font-bold text-xs px-2.5 py-0.5 rounded-full">

                      {{ slowQueries.length }} Query

                    </span>

                  </div>

                  <p class="text-xs text-slate-500">Query SQL dengan durasi eksekusi >500ms.</p>

                </div>

              </div>



              <!-- Action Buttons -->

              <div class="flex items-center space-x-2 shrink-0 self-end sm:self-auto">

                <button type="button" @click="fetchSlowQueries"

                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">

                  <i class="fa-solid fa-rotate-right"></i>

                  <span>Refresh</span>

                </button>

                <button type="button" @click="clearSlowQueries" :disabled="slowQueries.length === 0"

                  class="bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">

                  <i class="fa-solid fa-trash"></i>

                  <span>Bersihkan</span>

                </button>

              </div>

            </div>



            <!-- List Slow Queries -->

            <div v-if="slowQueries.length === 0" class="text-center py-12 text-slate-400">

              <i class="fa-solid fa-gauge-high text-3xl text-emerald-400 mb-1.5 block"></i>

              <span class="text-xs font-semibold text-slate-600">Semua query dieksekusi di bawah 500ms.</span>

            </div>



            <div v-else class="space-y-4">

              <!-- Item List (Paged 20 Data) -->

              <div class="space-y-2">

                <div v-for="(q, idx) in pagedSlowQueries" :key="q.id || idx"

                  class="p-3.5 bg-slate-50/70 hover:bg-slate-50 border border-slate-200 rounded-xl space-y-1.5 transition">

                  <div class="flex items-center justify-between gap-2">

                    <div class="flex items-center gap-2">

                      <span class="text-xs font-bold font-mono text-slate-400">

                        #{{ (slowPage - 1) * slowPerPage + idx + 1 }}

                      </span>

                      <span class="bg-amber-100 text-amber-900 font-mono font-bold text-[10px] px-2 py-0.5 rounded border border-amber-300">

                        {{ Math.round(q.duration_ms || q.execution_time_ms) }} ms

                      </span>

                      <span v-if="q.endpoint" class="text-[10px] font-mono text-slate-500 bg-slate-200/60 px-1.5 py-0.5 rounded">

                        {{ q.method || 'GET' }} /{{ q.endpoint }}

                      </span>

                    </div>

                    <span class="text-[11px] font-mono text-slate-400">{{ q.created_at }}</span>

                  </div>

                  <div class="text-xs font-mono text-slate-800 bg-white p-2.5 rounded-lg border border-slate-200 overflow-x-auto shadow-2xs break-all">

                    {{ q.sql_query || q.query_sql }}

                  </div>

                </div>

              </div>



              <!-- Pagination Controls (Batas 20 Data) -->

              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">

                <div class="text-xs text-slate-500 font-medium">

                  Menampilkan <strong>{{ (slowPage - 1) * slowPerPage + 1 }}</strong> - <strong>{{ Math.min(slowPage * slowPerPage, slowQueries.length) }}</strong> dari <strong>{{ slowQueries.length }}</strong> data

                </div>



                <div v-if="totalSlowPages > 1" class="flex items-center space-x-1.5 self-end sm:self-auto">

                  <button type="button" @click="slowPage = Math.max(1, slowPage - 1)" :disabled="slowPage === 1"

                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                    <i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya

                  </button>



                  <span class="px-3 py-1.5 text-xs font-bold text-slate-700 font-mono">

                    Hal {{ slowPage }} / {{ totalSlowPages }}

                  </span>



                  <button type="button" @click="slowPage = Math.min(totalSlowPages, slowPage + 1)" :disabled="slowPage === totalSlowPages"

                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                    Berikutnya <i class="fa-solid fa-chevron-right ml-1"></i>

                  </button>

                </div>

              </div>

            </div>



          </div>

        </div>



        <!-- ========================================================= -->

        <!-- TAB 4: PEMINDAI ANOMALI (RINGKAS & PAGINATION 20) -->

        <!-- ========================================================= -->

        <div v-if="activeTab === 'anomalies'" class="space-y-5">

          

          <!-- Summary Cards & Controls -->

          <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">

              <div>

                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">

                  <i class="fa-solid fa-wand-magic-sparkles text-red-600"></i>

                  <span>Pemindai Anomali</span>

                </h3>

                <p class="text-xs text-slate-500">Pemeriksaan integritas nomor seri gantung dan desync data.</p>

              </div>

              <div class="flex items-center space-x-2">

                <button type="button" @click="scanAnomalies" :disabled="loading"

                  class="bg-red-600 hover:bg-red-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-md shadow-red-600/20 cursor-pointer disabled:opacity-50">

                  <i class="fa-solid fa-magnifying-glass" :class="loading ? 'animate-spin' : ''"></i>

                  <span>Pindai</span>

                </button>

                <button type="button" @click="repairAnomalies" :disabled="loading || anomalySummary.total_anomalies === 0"

                  class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-md shadow-emerald-600/20 cursor-pointer disabled:opacity-50">

                  <i class="fa-solid fa-wrench"></i>

                  <span>1-Click Auto Repair</span>

                </button>

              </div>

            </div>



            <!-- 5 Summary Metric Cards -->

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3">

              <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">

                <span class="text-[11px] text-slate-500 font-bold block">Orphan Parts</span>

                <strong class="text-xl font-mono text-slate-800">{{ anomalySummary.orphan_parts?.count || 0 }}</strong>

              </div>

              <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">

                <span class="text-[11px] text-slate-500 font-bold block">Stuck Sessions</span>

                <strong class="text-xl font-mono text-slate-800">{{ anomalySummary.stuck_sessions?.count || 0 }}</strong>

              </div>

              <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">

                <span class="text-[11px] text-slate-500 font-bold block">Desynced Stocks</span>

                <strong class="text-xl font-mono text-slate-800">{{ anomalySummary.desynced_stocks?.count || 0 }}</strong>

              </div>

              <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">

                <span class="text-[11px] text-slate-500 font-bold block">Duplicate Parts</span>

                <strong class="text-xl font-mono text-slate-800">{{ anomalySummary.duplicate_parts?.count || 0 }}</strong>

              </div>

              <div class="col-span-2 sm:col-span-4 lg:col-span-1 p-3 rounded-xl border flex flex-col justify-between"

                :class="anomalySummary.total_anomalies > 0 ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200'">

                <span class="text-[11px] font-bold block" :class="anomalySummary.total_anomalies > 0 ? 'text-red-700' : 'text-emerald-700'">

                  Total Anomali

                </span>

                <strong class="text-2xl font-mono" :class="anomalySummary.total_anomalies > 0 ? 'text-red-600' : 'text-emerald-600'">

                  {{ anomalySummary.total_anomalies || 0 }}

                </strong>

              </div>

            </div>

          </div>



          <!-- DETAILED ANOMALIES TABLE -->

          <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">

              <div>

                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">

                  <i class="fa-solid fa-list-check text-slate-700"></i>

                  <span>Rincian Data Anomali</span>

                  <span class="bg-slate-100 text-slate-700 text-xs font-bold px-2 py-0.5 rounded-full">

                    {{ filteredAnomalyItems.length }}

                  </span>

                </h3>

              </div>



              <!-- Filter Kategori Anomali -->

              <div class="flex items-center gap-2">

                <select v-model="anomalyCategoryFilter"

                  class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-red-600 min-h-[34px] cursor-pointer">

                  <option value="all">Semua Kategori ({{ allAnomalyItems.length }})</option>

                  <option value="orphan">Orphan Parts ({{ anomalySummary.orphan_parts?.count || 0 }})</option>

                  <option value="stuck">Stuck Assembly ({{ anomalySummary.stuck_sessions?.count || 0 }})</option>

                  <option value="desync">Desynced Stocks ({{ anomalySummary.desynced_stocks?.count || 0 }})</option>

                  <option value="duplicate">Duplicate Parts ({{ anomalySummary.duplicate_parts?.count || 0 }})</option>

                </select>

              </div>

            </div>



            <!-- Tabel Data Anomali -->

            <div class="overflow-x-auto rounded-xl border border-slate-200">

              <table class="w-full text-left text-xs">

                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">

                  <tr>

                    <th class="p-3 w-12 text-center">No</th>

                    <th class="p-3">Kategori</th>

                    <th class="p-3">Part / Rakitan</th>

                    <th class="p-3">Serial Number</th>

                    <th class="p-3">Konteks / Baki</th>

                    <th class="p-3">Keterangan</th>

                  </tr>

                </thead>

                <tbody class="divide-y divide-slate-100 text-slate-700">

                  <tr v-for="(item, idx) in pagedAnomalyItems" :key="item.id || idx" class="hover:bg-slate-50 transition">

                    <td class="p-3 text-center font-mono font-bold text-slate-400">

                      {{ (anomalyPage - 1) * anomalyPerPage + idx + 1 }}

                    </td>

                    <td class="p-3 whitespace-nowrap">

                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-flex items-center gap-1.5"

                        :class="item.badgeClass">

                        <i :class="item.icon"></i>

                        <span>{{ item.category }}</span>

                      </span>

                    </td>

                    <td class="p-3 font-semibold text-slate-900">{{ item.partType }}</td>

                    <td class="p-3 font-mono font-bold text-red-600">{{ item.sn }}</td>

                    <td class="p-3">

                      <div class="font-bold text-xs" :class="item.type === 'duplicate' ? 'text-rose-600 font-mono' : 'font-mono text-slate-700'">

                        {{ item.trayInfo }}

                      </div>

                      <div v-if="item.trayCodes" class="text-[11px] font-mono text-slate-600 mt-1.5 flex flex-wrap gap-1 max-w-lg">

                        <span v-for="tc in item.trayCodes.split(', ')" :key="tc"

                              class="bg-slate-100 hover:bg-slate-200 border border-slate-200 px-1.5 py-0.5 rounded text-[10px] text-slate-700 font-semibold inline-flex items-center gap-1 select-all"

                              :title="'Baki: ' + tc">

                          <i class="fa-solid fa-box text-slate-400 text-[9px]"></i> @{{ tc }}

                        </span>

                      </div>

                    </td>

                    <td class="p-3 text-slate-600 text-xs">{{ item.description }}</td>

                  </tr>



                  <tr v-if="filteredAnomalyItems.length === 0">

                    <td colspan="6" class="p-8 text-center text-slate-400">

                      <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-1.5 block"></i>

                      <span class="text-xs font-semibold text-slate-600">Tidak ada anomali. Database konsisten.</span>

                    </td>

                  </tr>

                </tbody>

              </table>

            </div>



            <!-- Pagination Bar (Batas 20 Data) -->

            <div v-if="filteredAnomalyItems.length > 0" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">

              <div class="text-xs text-slate-500 font-medium">

                Menampilkan <strong>{{ (anomalyPage - 1) * anomalyPerPage + 1 }}</strong> - <strong>{{ Math.min(anomalyPage * anomalyPerPage, filteredAnomalyItems.length) }}</strong> dari <strong>{{ filteredAnomalyItems.length }}</strong> data

              </div>



              <div v-if="totalAnomalyPages > 1" class="flex items-center space-x-1.5 self-end sm:self-auto">

                <button type="button" @click="anomalyPage = Math.max(1, anomalyPage - 1)" :disabled="anomalyPage === 1"

                  class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                  <i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya

                </button>



                <span class="px-3 py-1.5 text-xs font-bold text-slate-700 font-mono">

                  Hal {{ anomalyPage }} / {{ totalAnomalyPages }}

                </span>



                <button type="button" @click="anomalyPage = Math.min(totalAnomalyPages, anomalyPage + 1)" :disabled="anomalyPage === totalAnomalyPages"

                  class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                  Berikutnya <i class="fa-solid fa-chevron-right ml-1"></i>

                </button>

              </div>

            </div>



          </div>

        </div>



        <!-- ========================================================= -->

        <!-- TAB 5: PENGATURAN DELIVERY ORDER & DOKUMEN -->

        <!-- ========================================================= -->

        <div v-if="activeTab === 'do_settings'" class="space-y-4">

          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">

            <div class="flex items-center justify-between border-b border-slate-100 pb-3">

              <div>

                <h3 class="text-base font-black text-slate-900">Pengaturan Delivery Order & Dokumen</h3>

                <p class="text-xs text-slate-500">Konfigurasi prefix nomor surat jalan, packing list, dan kapasitas.</p>

              </div>

              <button type="button" @click="saveDoSettings" :disabled="loading"

                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition shadow-md shadow-red-600/20 flex items-center space-x-2 cursor-pointer disabled:opacity-50">

                <i class="fa-solid fa-floppy-disk"></i>

                <span>Simpan Pengaturan</span>

              </button>

            </div>



            <form @submit.prevent="saveDoSettings" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">

              <div>

                <label class="block font-bold text-slate-700 mb-1">Prefix Surat Jalan (DO)</label>

                <input type="text" v-model="doSettings.do_prefix" placeholder="/SJ/PPIC/" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-mono">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Prefix Packing List (PL)</label>

                <input type="text" v-model="doSettings.pl_prefix" placeholder="/PL/PPIC/" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-mono">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Prefix Crate ID</label>

                <input type="text" v-model="doSettings.crate_prefix" placeholder="2609-" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-mono">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Kapasitas Maksimal Crate (Peti)</label>

                <input type="number" v-model="doSettings.crate_capacity" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Kapasitas Maksimal Box</label>

                <input type="number" v-model="doSettings.box_capacity" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Nama Produk Default</label>

                <input type="text" v-model="doSettings.default_product_name" placeholder="Nanggala P9" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Nama Customer Default</label>

                <input type="text" v-model="doSettings.default_customer_name" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Alamat Customer Default</label>

                <input type="text" v-model="doSettings.default_customer_address" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">Nama Penandatangan (PPIC)</label>

                <input type="text" v-model="doSettings.default_signer_name" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5">

              </div>

            </form>

          </div>



          <!-- Card Kredensial Cloudinary (Admin CMS Terpusat) -->

          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">

              <div>

                <div class="flex items-center space-x-2">

                  <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">

                    <i class="fa-solid fa-cloud-arrow-up"></i>

                  </div>

                  <h3 class="text-base font-black text-slate-900">Integrasi Cloud Storage</h3>

                  <span v-if="cloudSettings.configured" class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center space-x-1">

                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span> Aktif

                  </span>

                  <span v-else class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center space-x-1">

                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1"></span> Belum Diatur

                  </span>

                </div>

                <p class="text-xs text-slate-500 mt-1">Konfigurasi penyimpanan PDF Packing List & QR Code.</p>

              </div>

              <div class="flex items-center space-x-2">

                <button type="button" @click="testCloudinaryConnection" :disabled="isTestingCloud"

                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2 px-3.5 rounded-xl text-xs transition flex items-center space-x-1.5 cursor-pointer">

                  <i class="fa-solid" :class="isTestingCloud ? 'fa-spinner fa-spin' : 'fa-plug'"></i>

                  <span>{{ isTestingCloud ? 'Menguji...' : 'Uji Koneksi' }}</span>

                </button>

                <button type="button" @click="saveCloudinarySettings" :disabled="isSavingCloud"

                  class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition shadow-md shadow-indigo-600/20 flex items-center space-x-1.5 cursor-pointer">

                  <i class="fa-solid" :class="isSavingCloud ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>

                  <span>{{ isSavingCloud ? 'Menyimpan...' : 'Simpan Kredensial' }}</span>

                </button>

              </div>

            </div>



            <!-- Test Result Banner -->

            <div v-if="cloudTestResult" class="p-3.5 rounded-xl text-xs flex items-start space-x-2.5"

              :class="cloudTestResult.ok ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800'">

              <i :class="cloudTestResult.ok ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-xmark text-red-600'" class="text-base mt-0.5"></i>

              <div class="flex-1">

                <strong class="font-bold block">{{ cloudTestResult.ok ? 'Koneksi Berhasil' : 'Koneksi Gagal' }}</strong>

                <span>{{ cloudTestResult.message }}</span>

              </div>

              <button type="button" @click="cloudTestResult = null" class="text-slate-400 hover:text-slate-700 cursor-pointer">&times;</button>

            </div>



            <!-- Form Inputs -->

            <form @submit.prevent="saveCloudinarySettings" class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">

              <div>

                <label class="block font-bold text-slate-700 mb-1">Cloud Name <span class="text-red-500">*</span></label>

                <input type="text" v-model="cloudForm.cloud_name" :placeholder="cloudSettings.cloud_name || 'Contoh: dxyz987'"

                  class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-mono text-xs">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">API Key <span class="text-red-500">*</span></label>

                <input type="text" v-model="cloudForm.api_key" :placeholder="cloudSettings.api_key_masked || 'Contoh: 123456789012345'"

                  class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-mono text-xs">

              </div>

              <div>

                <label class="block font-bold text-slate-700 mb-1">API Secret <span class="text-red-500">*</span></label>

                <input type="password" v-model="cloudForm.api_secret" :placeholder="cloudSettings.has_secret ? '•••••••••••••••• (tersimpan)' : 'Masukkan API Secret'"

                  class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-mono text-xs">

              </div>

            </form>

          </div>

        </div>



        <!-- ========================================================= -->

        <!-- TAB 6: KELOLA PENGGUNA, HAK AKSES & AKSES CMS -->

        <!-- Accordion per group (Gudang, Assembly, QC, Admin) default TUTUP -->

        <!-- ========================================================= -->

        <div v-if="activeTab === 'users'" class="space-y-6">



          <!-- Ringkasan Statistik Pengguna & Akses CMS -->

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">

              <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Pengguna</div>

              <div class="text-2xl font-black text-slate-900 mt-1">{{ usersList.length }}</div>

            </div>

            <div class="bg-white border border-purple-200 rounded-2xl p-4 shadow-sm bg-gradient-to-br from-white to-purple-50/30">

              <div class="text-[11px] font-bold text-purple-700 uppercase tracking-wider flex items-center gap-1.5">

                <i class="fa-solid fa-shield-halved text-purple-600"></i>

                <span>Akses CMS</span>

              </div>

              <div class="text-2xl font-black text-purple-700 mt-1">{{ countCmsUsers() }}</div>

            </div>

            <div class="bg-white border border-blue-200 rounded-2xl p-4 shadow-sm">

              <div class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Assembly</div>

              <div class="text-2xl font-black text-blue-700 mt-1">{{ countRoleUsers('assembly') }}</div>

            </div>

            <div class="bg-white border border-amber-200 rounded-2xl p-4 shadow-sm">

              <div class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Gudang & QC</div>

              <div class="text-2xl font-black text-amber-700 mt-1">{{ countRoleUsers('warehouse') + countRoleUsers('qc') }}</div>

            </div>

          </div>



          <!-- FORM TAMBAH / EDIT PENGGUNA -->

          <div id="user-form-card" class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">

              <div>

                <h3 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">

                  <i class="fa-solid" :class="editingUserId ? 'fa-user-pen text-amber-600' : 'fa-user-plus text-red-600'"></i>

                  <span>{{ editingUserId ? 'Edit Akun: ' + userForm.username : 'Tambah Pengguna Baru' }}</span>

                  <span v-if="editingUserId" class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">Mode Edit</span>

                </h3>

              </div>

              <button v-if="editingUserId" type="button" @click="resetUserForm"

                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 self-start sm:self-auto cursor-pointer">

                <i class="fa-solid fa-xmark"></i>

                <span>Batal Edit</span>

              </button>

            </div>



            <form @submit.prevent="saveUser" class="space-y-5">

              <!-- Profil Dasar User -->

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">

                <!-- NIK -->

                <div>

                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">NIK Pegawai</label>

                  <input type="text" v-model="userForm.nik" placeholder="Contoh: 262048"

                    class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 rounded-xl px-3 py-2 text-xs text-slate-900 font-semibold focus:outline-none min-h-[38px]">

                </div>



                <!-- Nama Lengkap -->

                <div class="xl:col-span-2">

                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Lengkap <span class="text-red-600">*</span></label>

                  <input type="text" v-model="userForm.full_name" required placeholder="Nama Lengkap Karyawan"

                    class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 rounded-xl px-3 py-2 text-xs text-slate-900 font-semibold focus:outline-none min-h-[38px]">

                </div>



                <!-- Departemen -->

                <div>

                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Departemen <span class="text-red-600">*</span></label>

                  <select v-model="userForm.department" required

                    class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 rounded-xl px-3 py-2 text-xs text-slate-900 font-semibold focus:outline-none min-h-[38px] cursor-pointer">

                    <option value="Warehouse">Warehouse (Gudang)</option>

                    <option value="Assembly">Assembly (Perakitan)</option>

                    <option value="Quality Control">Quality Control (QC)</option>

                    <option value="Administrator">Administrator</option>

                  </select>

                </div>



                <!-- Username -->

                <div>

                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Username <span class="text-red-600">*</span></label>

                  <input type="text" v-model="userForm.username" required placeholder="username_login"

                    class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 rounded-xl px-3 py-2 text-xs text-slate-900 font-mono font-bold focus:outline-none min-h-[38px]">

                </div>



                <!-- Password -->

                <div>

                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">

                    Password {{ editingUserId ? '(Opsional)' : '*' }}

                  </label>

                  <div class="relative">

                    <input :type="showUserPw ? 'text' : 'password'" v-model="userForm.password"

                      :required="!editingUserId" placeholder="Min 4 karakter"

                      class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 rounded-xl px-3 py-2 pr-9 text-xs text-slate-900 font-mono font-semibold focus:outline-none min-h-[38px]">

                    <button type="button" @click="showUserPw = !showUserPw" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1 cursor-pointer">

                      <i :class="showUserPw ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>

                    </button>

                  </div>

                </div>

              </div>



              <!-- KARTU KHUSUS: IZIN AKSES PORTAL CMS ADMINISTRATOR (/admin) (SIMPLE & SESUAI GAMBAR) -->

              <div class="bg-gradient-to-r from-purple-50/70 via-indigo-50/30 to-white border border-purple-200/90 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">

                <div class="flex items-center gap-3.5">

                  <div class="w-10 h-10 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-lg shrink-0 shadow-sm shadow-purple-600/30">

                    <i class="fa-solid fa-shield-halved"></i>

                  </div>

                  <div>

                    <div class="flex items-center gap-2">

                      <h4 class="text-sm font-black text-slate-900">

                        Akses Portal CMS (/admin)

                      </h4>

                      <span v-if="userForm.can_access_cms" class="bg-purple-100 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1">

                        <i class="fa-solid fa-circle-check"></i> Diizinkan

                      </span>

                      <span v-else class="bg-slate-200 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1">

                        <i class="fa-solid fa-ban"></i> Terkunci

                      </span>

                    </div>

                    

                  </div>

                </div>



                <!-- Custom Switch Sesuai Gambar Pengguna -->

                <div class="flex items-center gap-3 self-end sm:self-auto shrink-0 select-none">

                  <button type="button" @click="userForm.can_access_cms = !userForm.can_access_cms"

                    class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"

                    :class="userForm.can_access_cms ? 'bg-purple-600' : 'bg-slate-300'">

                    <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"

                      :class="userForm.can_access_cms ? 'translate-x-7' : 'translate-x-0'"></span>

                  </button>

                  <span class="text-xs font-black tracking-wider uppercase min-w-[70px]"

                    :class="userForm.can_access_cms ? 'text-purple-700 font-black' : 'text-slate-500 font-bold'">

                    {{ userForm.can_access_cms ? 'AKTIF' : 'NONAKTIF' }}

                  </span>

                </div>

              </div>



              <!-- HAK AKSES MODUL SISTEM (MENU UTAMA SELALU TERBUKA, KATEGORI GUDANG/ASSEMBLY/QC/ADMIN BISA DITUTUP BUKA) -->

              <div class="border border-slate-200 rounded-2xl p-4 sm:p-5 bg-slate-50/50 space-y-4">

                

                <!-- Header Hak Akses & Preset Chips -->

                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">

                  <div>

                    <h4 class="text-xs sm:text-sm font-black uppercase text-slate-900 tracking-wider flex items-center gap-2">

                      <i class="fa-solid fa-sliders text-red-600"></i>

                      <span>Hak Akses Modul Sistem</span>

                    </h4>

                    

                  </div>



                  <!-- Preset Chips & Expand All Controls -->

                  <div class="flex items-center gap-1.5 flex-wrap">

                    <span class="text-[11px] font-bold text-slate-400 mr-1 hidden sm:inline">Set Otoritas Cepat:</span>

                    <button type="button" @click="applyUserPreset('assembly')"

                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition cursor-pointer">

                      <i class="fa-solid fa-wand-magic-sparkles text-[10px] mr-1"></i>Preset Assembly

                    </button>

                    <button type="button" @click="applyUserPreset('gudang')"

                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition cursor-pointer">

                      <i class="fa-solid fa-wand-magic-sparkles text-[10px] mr-1"></i>Preset Gudang

                    </button>

                    <button type="button" @click="applyUserPreset('qc')"

                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition cursor-pointer">

                      <i class="fa-solid fa-wand-magic-sparkles text-[10px] mr-1"></i>Preset QC

                    </button>

                    <button type="button" @click="applyUserPreset('admin')"

                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition cursor-pointer">

                      <i class="fa-solid fa-crown text-[10px] mr-1"></i>Full Admin

                    </button>

                    <button type="button" @click="applyUserPreset('none')"

                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">

                      <i class="fa-solid fa-lock text-[10px] mr-1"></i>Kunci Semua

                    </button>



                    <div class="h-4 w-px bg-slate-200 mx-1 hidden sm:block"></div>



                    <button type="button" @click="setAllMenuGroups(true)"

                      class="px-2 py-1 rounded-lg text-[10px] font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer" title="Buka Semua Kategori">

                      Buka Semua

                    </button>

                    <button type="button" @click="setAllMenuGroups(false)"

                      class="px-2 py-1 rounded-lg text-[10px] font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer" title="Tutup Semua Kategori">

                      Tutup Semua

                    </button>

                  </div>

                </div>



                <!-- 4 Kategori Departemen (Gudang, Assembly, QC, Admin) Masing-Masing Bisa Ditutup Buka (Default Tertutup) -->

                <div class="space-y-3">

                  <div v-for="grp in cmsManagedMenuGroups" :key="grp.key"

                    class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">

                    

                    <!-- Accordion Header per Kategori -->

                    <button type="button" @click="toggleMenuGroup(grp.key)"

                      class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-slate-50/80 transition cursor-pointer select-none">

                      <div class="flex items-center gap-2.5">

                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs">

                          <i :class="grp.icon"></i>

                        </div>

                        <div class="flex items-center gap-2">

                          <span class="text-xs font-black uppercase text-slate-800 tracking-wide">{{ grp.title }}</span>

                          <span class="text-[10px] font-mono text-slate-400">({{ grp.menus.length }} Menu)</span>

                        </div>

                      </div>

                      <div class="flex items-center gap-2">

                        <span class="text-[11px] font-bold text-slate-400">{{ openMenuGroups[grp.key] ? 'Tutup' : 'Buka' }}</span>

                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform duration-200"

                          :class="{ 'rotate-180': openMenuGroups[grp.key] }"></i>

                      </div>

                    </button>



                    <!-- Body Daftar Menu (Hanya Muncul Jika Diklik Buka) -->

                    <div v-show="openMenuGroups[grp.key]" class="p-3 border-t border-slate-100 bg-slate-50/50 space-y-2">

                      <div v-for="menu in grp.menus" :key="menu.key"

                        class="bg-white p-2.5 rounded-xl border border-slate-200 flex items-center justify-between gap-3 shadow-2xs">

                        <div class="font-bold text-xs text-slate-800 truncate">

                          {{ menu.title }}

                        </div>



                        <!-- 3-Way Access Switcher (0 = None, 1 = Read, 2 = Write) -->

                        <div class="flex items-center bg-slate-100 p-0.5 rounded-lg shrink-0 border border-slate-200">

                          <button type="button" @click="setUserPermission(menu.key, 0)"

                            :class="(userForm.permissions && userForm.permissions[menu.key] === 0) ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"

                            class="px-2.5 py-1 rounded-md text-[10px] transition cursor-pointer" title="Tanpa Akses">

                            None (0)

                          </button>

                          <button type="button" @click="setUserPermission(menu.key, 1)"

                            :class="(userForm.permissions && userForm.permissions[menu.key] === 1) ? 'bg-amber-500 text-white font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"

                            class="px-2.5 py-1 rounded-md text-[10px] transition cursor-pointer" title="Hanya Baca">

                            Read (1)

                          </button>

                          <button type="button" @click="setUserPermission(menu.key, 2)"

                            :class="(userForm.permissions && userForm.permissions[menu.key] === 2) ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"

                            class="px-2.5 py-1 rounded-md text-[10px] transition cursor-pointer" title="Akses Tulis">

                            Write (2)

                          </button>

                        </div>

                      </div>

                    </div>



                  </div>

                </div>



              </div>



              <!-- Tombol Aksi Simpan Form -->

              <div class="flex items-center justify-end gap-2.5 pt-2">

                <button v-if="editingUserId" type="button" @click="resetUserForm"

                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer">

                  Batal

                </button>

                <button type="submit" :disabled="isSavingUser"

                  class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-md shadow-red-600/20 active:scale-95">

                  <i class="fa-solid" :class="isSavingUser ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>

                  <span>{{ isSavingUser ? 'Menyimpan...' : (editingUserId ? 'Perbarui Pengguna' : 'Simpan Pengguna') }}</span>

                </button>

              </div>

            </form>

          </div>



          <!-- TABEL DAFTAR PENGGUNA SISTEM (TANPA ID + CMS READ-ONLY + PAGINATION 10) -->

          <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">

              <div>

                <h3 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">

                  <i class="fa-solid fa-users text-slate-700"></i>

                  <span>Daftar Pengguna</span>

                  <span class="bg-slate-100 text-slate-700 text-xs font-bold px-2.5 py-0.5 rounded-full">{{ filteredUsersList.length }} Akun</span>

                </h3>

              </div>



              <!-- Filter & Search Bar -->

              <div class="flex flex-wrap items-center gap-2">

                <input type="text" v-model="userSearchQuery" placeholder="Cari nama / username / NIK..."

                  class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white w-48 sm:w-60 min-h-[34px]">

                <select v-model="userDeptFilter"

                  class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-red-600 min-h-[34px] cursor-pointer">

                  <option value="all">Semua Departemen</option>

                  <option value="Administrator">Administrator</option>

                  <option value="Warehouse">Warehouse (Gudang)</option>

                  <option value="Assembly">Assembly (Perakitan)</option>

                  <option value="Quality Control">Quality Control (QC)</option>

                </select>

                <button type="button" @click="fetchUsers" class="bg-slate-100 hover:bg-slate-200 text-slate-700 p-2 rounded-xl text-xs transition cursor-pointer" title="Refresh">

                  <i class="fa-solid fa-rotate-right" :class="loadingUsers ? 'fa-spin' : ''"></i>

                </button>

              </div>

            </div>



            <!-- Tabel Data: ID Dihilangkan, CMS Access Read-Only -->

            <div class="overflow-x-auto rounded-xl border border-slate-200">

              <table class="w-full text-left text-xs">

                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">

                  <tr>

                    <th class="p-3 w-12 text-center">No</th>

                    <th class="p-3">Nama & Username</th>

                    <th class="p-3">NIK</th>

                    <th class="p-3">Departemen</th>

                    <th class="p-3 text-center">Akses CMS</th>

                    <th class="p-3 text-center">Otoritas</th>

                    <th class="p-3 text-center w-32">Tindakan</th>

                  </tr>

                </thead>

                <tbody class="divide-y divide-slate-100 text-slate-700">

                  <tr v-for="(u, idx) in pagedUsersList" :key="u.id" class="hover:bg-slate-50/80 transition" :class="editingUserId === u.id ? 'bg-amber-50/40' : ''">

                    <!-- Kolom No (Pengganti ID) -->

                    <td class="p-3 text-center font-mono font-bold text-slate-400">

                      {{ (userPage - 1) * userPerPage + idx + 1 }}

                    </td>

                    <td class="p-3">

                      <div class="font-bold text-slate-900 flex items-center gap-1.5">

                        <span>{{ u.full_name }}</span>

                        <span v-if="u.id === 3" class="bg-red-100 text-red-700 text-[9px] font-black px-1.5 py-0.2 rounded">UTAMA</span>

                      </div>

                      <div class="text-[11px] text-slate-500 font-mono">@{{ u.username }}</div>

                    </td>

                    <td class="p-3 font-mono font-semibold">{{ u.nik || '-' }}</td>

                    <td class="p-3">

                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border"

                        :class="getDeptBadgeClass(u.department || u.role)">

                        {{ u.department || u.role }}

                      </span>

                    </td>



                    <!-- Status Akses Masuk Portal CMS (READ-ONLY) -->

                    <td class="p-3 text-center whitespace-nowrap">

                      <span v-if="u.id === 3 || Boolean(u.can_access_cms)"

                        class="bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[10px] px-2.5 py-1 rounded-full inline-flex items-center gap-1">

                        <i class="fa-solid fa-circle-check text-purple-600"></i> Aktif

                      </span>

                      <span v-else class="bg-slate-100 text-slate-500 border border-slate-200 font-medium text-[10px] px-2.5 py-1 rounded-full inline-flex items-center gap-1">

                        <i class="fa-solid fa-lock text-slate-400"></i> Terkunci

                      </span>

                    </td>



                    <!-- Ringkasan Otoritas Menu Tulis / Baca -->

                    <td class="p-3 text-center whitespace-nowrap">

                      <div class="inline-flex items-center gap-1 text-[11px] font-mono font-bold">

                        <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-1.5 py-0.5 rounded" title="Menu Write">

                          {{ countUserWritePerms(u) }}W

                        </span>

                        <span class="bg-amber-50 text-amber-800 border border-amber-200 px-1.5 py-0.5 rounded" title="Menu Read">

                          {{ countUserReadPerms(u) }}R

                        </span>

                      </div>

                    </td>



                    <!-- Tombol Aksi -->

                    <td class="p-3 text-center whitespace-nowrap">

                      <div class="inline-flex items-center gap-1">

                        <button type="button" @click="editUser(u)"

                          class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 p-1.5 rounded-lg text-xs font-bold transition cursor-pointer"

                          title="Edit Pengguna">

                          <i class="fa-solid fa-pen-to-square"></i>

                        </button>

                        <button type="button" @click="resetUserSession(u)"

                          class="bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 p-1.5 rounded-lg text-xs font-bold transition cursor-pointer"

                          title="Reset Sesi Login">

                          <i class="fa-solid fa-key"></i>

                        </button>

                        <button v-if="u.id !== 3" type="button" @click="deleteUser(u)"

                          class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 p-1.5 rounded-lg text-xs font-bold transition cursor-pointer"

                          title="Hapus Akun">

                          <i class="fa-solid fa-trash-can"></i>

                        </button>

                      </div>

                    </td>

                  </tr>



                  <tr v-if="filteredUsersList.length === 0">

                    <td colspan="7" class="p-8 text-center text-slate-400">

                      <i class="fa-solid fa-user-slash text-2xl mb-2 block"></i>

                      <span>Tidak ada pengguna yang cocok.</span>

                    </td>

                  </tr>

                </tbody>

              </table>

            </div>



            <!-- Pagination Bar Daftar Akun (Batas 10 Akun) -->

            <div v-if="filteredUsersList.length > 0" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">

              <div class="text-xs text-slate-500 font-medium">

                Menampilkan <strong>{{ (userPage - 1) * userPerPage + 1 }}</strong> - <strong>{{ Math.min(userPage * userPerPage, filteredUsersList.length) }}</strong> dari <strong>{{ filteredUsersList.length }}</strong> akun

              </div>



              <div v-if="totalUserPages > 1" class="flex items-center space-x-1.5 self-end sm:self-auto">

                <button type="button" @click="userPage = Math.max(1, userPage - 1)" :disabled="userPage === 1"

                  class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                  <i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya

                </button>



                <span class="px-3 py-1.5 text-xs font-bold text-slate-700 font-mono">

                  Hal {{ userPage }} / {{ totalUserPages }}

                </span>



                <button type="button" @click="userPage = Math.min(totalUserPages, userPage + 1)" :disabled="userPage === totalUserPages"

                  class="px-2.5 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-40 transition cursor-pointer">

                  Berikutnya <i class="fa-solid fa-chevron-right ml-1"></i>

                </button>

              </div>

            </div>



          </div>

        </div>



        </main>



        <!-- Footer CMS -->

        <footer class="bg-white border-t border-slate-200 py-3 px-6 text-center text-[11px] text-slate-400 flex items-center justify-between select-none">

          <span>Portal Administrator CMS &copy; 2026</span>

          <span class="font-mono text-[10px] text-slate-500">Database: <strong class="text-slate-700">db_final_assembly (Produksi)</strong></span>

        </footer>



      </div>

    </div>



    <!-- ========================================================================= -->

    <!-- TOAST NOTIFICATION (STANDARISASI UI/UX IN-APP)                            -->

    <!-- ========================================================================= -->

    <transition enter-active-class="transition-all duration-300 ease-out"

                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"

                enter-to-class="opacity-100 translate-y-0 sm:translate-x-0"

                leave-active-class="transition-all duration-200 ease-in"

                leave-from-class="opacity-100 translate-y-0 sm:translate-x-0"

                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4">

      <div v-if="toast.show"

        class="fixed bottom-5 right-5 z-[999999] max-w-sm w-full transition-all pointer-events-auto">

        <div

          :class="toast.type === 'error' ? 'bg-red-600 text-white shadow-2xl shadow-red-600/50' : (toast.type === 'warn' ? 'bg-amber-500 text-white shadow-2xl shadow-amber-500/50' : (toast.type === 'info' ? 'bg-blue-600 text-white shadow-2xl shadow-blue-600/50' : 'bg-emerald-600 text-white shadow-2xl shadow-emerald-600/50'))"

          class="p-4 rounded-2xl flex items-center space-x-3 border border-white/20 ring-2 ring-black/10">

          <i :class="toast.type === 'error' ? 'fa-solid fa-circle-exclamation' : (toast.type === 'warn' ? 'fa-solid fa-triangle-exclamation' : (toast.type === 'info' ? 'fa-solid fa-circle-info' : 'fa-solid fa-circle-check'))"

            class="text-2xl flex-shrink-0"></i>

          <div class="flex-grow">

            <div class="font-black text-xs tracking-wide">{{ toast.title }}</div>

            <div class="text-[11px] opacity-95 leading-snug mt-0.5">{{ toast.message }}</div>

          </div>

          <button @click="toast.show = false"

            class="text-white/70 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition cursor-pointer" title="Tutup Notifikasi">

            <i class="fa-solid fa-xmark text-sm"></i>

          </button>

        </div>

      </div>

    </transition>



    <!-- ========================================================================= -->

    <!-- GLOBAL IN-APP CONFIRMATION & PROMPT CARD MODAL (STANDARISASI UI/UX)       -->

    <!-- ========================================================================= -->

    <div v-if="confirmModalState.isOpen"

      class="fixed inset-0 z-[999998] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-fadeIn">

      <div

        class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-5 sm:p-6 space-y-4">

        <div class="flex items-start gap-3.5">

          <div

            :class="['w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs', confirmModalState.iconBg]">

            <i :class="[confirmModalState.icon, 'text-lg']"></i>

          </div>

          <div class="flex-1 min-w-0">

            <h3 class="text-base font-black text-slate-900 leading-snug">{{ confirmModalState.title }}</h3>

            <p class="text-xs text-slate-600 mt-1 leading-relaxed whitespace-pre-line font-medium">{{

              confirmModalState.message }}</p>

          </div>

        </div>



        <!-- Optional Input Field (Prompt mode) -->

        <div v-if="confirmModalState.hasInput" class="space-y-1.5 pt-1">

          <label v-if="confirmModalState.inputLabel"

            class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">

            {{ confirmModalState.inputLabel }}

          </label>

          <input type="text" v-model="confirmModalState.inputValue" :placeholder="confirmModalState.inputPlaceholder"

            @keyup.enter="handleConfirmCard(true)" autofocus

            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:outline-none focus:border-red-600 focus:bg-white transition shadow-inner">

        </div>



        <!-- Action Buttons -->

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">

          <button type="button" @click="handleConfirmCard(false)"

            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition active:scale-95 cursor-pointer min-h-[40px]">

            {{ confirmModalState.cancelText }}

          </button>

          <button type="button" @click="handleConfirmCard(true)"

            :class="['px-5 py-2.5 rounded-xl text-xs font-bold shadow-md transition active:scale-95 cursor-pointer flex items-center gap-1.5 min-h-[40px]', confirmModalState.confirmBtnClass]">

            <span>{{ confirmModalState.confirmText }}</span>

          </button>

        </div>

      </div>

    </div>



    @endverbatim

  </div>



  <script>

    const { createApp, ref, computed, watch, onMounted } = Vue;



    createApp({

      setup() {

        const isCmsAuthenticated = ref(false);

        const adminUser = ref({});

        const cmsToken = ref('');



        const loginForm = ref({ username: '', password: '' });

        const showPassword = ref(false);

        const loginLoading = ref(false);

        const loginError = ref('');



        const activeTab = ref('users');

        const loading = ref(false);



        // Sidebar drawer state

        const isCmsSidebarOpen = ref(false);



        // In-App Toast Notification State

        const toast = ref({ show: false, title: '', message: '', type: 'success' });

        let toastTimeout = null;

        const triggerToast = (title, message, type = 'success') => {

          if (toastTimeout) clearTimeout(toastTimeout);

          toast.value = { show: true, title, message, type };

          toastTimeout = setTimeout(() => { toast.value.show = false; }, 3500);

        };



        // In-App Confirmation & Prompt Card Modal State

        const confirmModalState = Vue.reactive({

          isOpen: false,

          title: 'Konfirmasi',

          message: '',

          icon: 'fa-solid fa-circle-question',

          iconBg: 'bg-blue-100 text-blue-600',

          confirmText: 'Ya, Lanjutkan',

          cancelText: 'Batal',

          confirmBtnClass: 'bg-blue-600 hover:bg-blue-700 text-white',

          hasInput: false,

          inputLabel: '',

          inputPlaceholder: '',

          inputValue: '',

          resolve: null

        });



        const showConfirmCard = (opts = {}) => {

          return new Promise((resolve) => {

            confirmModalState.title = opts.title || 'Konfirmasi';

            confirmModalState.message = opts.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';

            confirmModalState.icon = opts.icon || 'fa-solid fa-circle-question';

            confirmModalState.iconBg = opts.iconBg || (opts.confirmBtnClass && opts.confirmBtnClass.includes('red') ? 'bg-red-100 text-red-600' : (opts.confirmBtnClass && opts.confirmBtnClass.includes('emerald') ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600'));

            confirmModalState.confirmText = opts.confirmText || 'Ya, Lanjutkan';

            confirmModalState.cancelText = opts.cancelText || 'Batal';

            confirmModalState.confirmBtnClass = opts.confirmBtnClass || 'bg-blue-600 hover:bg-blue-700 text-white';

            confirmModalState.hasInput = false;

            confirmModalState.inputLabel = '';

            confirmModalState.inputPlaceholder = '';

            confirmModalState.inputValue = '';

            confirmModalState.resolve = resolve;

            confirmModalState.isOpen = true;

          });

        };



        const showPromptCard = (opts = {}) => {

          return new Promise((resolve) => {

            confirmModalState.title = opts.title || 'Masukkan Keterangan';

            confirmModalState.message = opts.message || '';

            confirmModalState.icon = opts.icon || 'fa-solid fa-pen-to-square';

            confirmModalState.iconBg = opts.iconBg || 'bg-amber-100 text-amber-600';

            confirmModalState.confirmText = opts.confirmText || 'Kirim / Simpan';

            confirmModalState.cancelText = opts.cancelText || 'Batal';

            confirmModalState.confirmBtnClass = opts.confirmBtnClass || 'bg-blue-600 hover:bg-blue-700 text-white';

            confirmModalState.hasInput = true;

            confirmModalState.inputLabel = opts.inputLabel || 'Keterangan:';

            confirmModalState.inputPlaceholder = opts.inputPlaceholder || 'Ketik di sini...';

            confirmModalState.inputValue = opts.defaultValue || '';

            confirmModalState.resolve = (confirmed) => {

              if (!confirmed) resolve(null);

              else resolve(confirmModalState.inputValue);

            };

            confirmModalState.isOpen = true;

          });

        };



        const handleConfirmCard = (confirmed) => {

          confirmModalState.isOpen = false;

          if (confirmModalState.resolve) {

            const r = confirmModalState.resolve;

            confirmModalState.resolve = null;

            r(confirmed);

          }

        };



        // Accordion per departemen (Gudang, Assembly, QC, Admin) default TUTUP

        const openMenuGroups = ref({

          gudang: false,

          perakitan: false,

          qc: false,

          admin: false

        });



        const toggleMenuGroup = (key) => {

          openMenuGroups.value[key] = !openMenuGroups.value[key];

        };



        const setAllMenuGroups = (open) => {

          Object.keys(openMenuGroups.value).forEach(k => {

            openMenuGroups.value[k] = open;

          });

        };



        // Diagnostic data

        const errorLogs = ref([]);

        const errorPage = ref(1);

        const errorPerPage = 20;



        const slowQueries = ref([]);

        const slowPage = ref(1);

        const slowPerPage = 20;



        const anomalySummary = ref({ total_anomalies: 0, orphan_parts: { count: 0, items: [] }, stuck_sessions: { count: 0, items: [] }, desynced_stocks: { count: 0, items: [] }, duplicate_parts: { count: 0, items: [] } });

        const anomalyCategoryFilter = ref('all');

        const anomalyPage = ref(1);

        const anomalyPerPage = 20;



        const doSettings = ref({});



        // Cloudinary Admin State

        const cloudSettings = ref({ cloud_name: '', api_key: '', api_key_masked: '', has_secret: false, configured: false });

        const cloudForm = ref({ cloud_name: '', api_key: '', api_secret: '' });

        const isTestingCloud = ref(false);

        const isSavingCloud = ref(false);

        const cloudTestResult = ref(null);



        // User & Permission Management (CMS Centralized)

        const usersList = ref([]);

        const loadingUsers = ref(false);

        const editingUserId = ref(null);

        const showUserPw = ref(false);

        const isSavingUser = ref(false);

        const userSearchQuery = ref('');

        const userDeptFilter = ref('all');

        const userPage = ref(1);

        const userPerPage = 10;



        const userForm = ref({

          id: null,

          nik: '',

          full_name: '',

          department: 'Assembly',

          role: 'assembly',

          username: '',

          password: '',

          can_access_cms: false,

          permissions: {}

        });



        const cmsManagedMenuGroups = [

          {

            key: 'gudang',

            title: 'Gudang (Warehouse)',

            icon: 'fa-solid fa-warehouse',

            menus: [

              { key: 'warehouse', title: 'Work Order (Input Part)' },

              { key: 'production_hold', title: 'Hasil Perakitan' },

              { key: 'returns', title: 'Pengembalian Barang' },

              { key: 'warehouse_receipt', title: 'Penerimaan Gudang' },

              { key: 'reassembly', title: 'Perakitan Ulang (RU)' },

              { key: 'traveler', title: 'Cetak Dokumen & Label' },

              { key: 'master', title: 'Master Stok Part' },

              { key: 'report', title: 'History Transaksi' },

              { key: 'delivery_order', title: 'Delivery Order & PL' }

            ]

          },

          {

            key: 'perakitan',

            title: 'Perakitan (Assembly)',

            icon: 'fa-solid fa-screwdriver-wrench',

            menus: [

              { key: 'assembly', title: 'Area Perakitan (Assembly)' },

              { key: 'assy_testing', title: 'Test Functional 1 (Assy)' }

            ]

          },

          {

            key: 'qc',

            title: 'Quality Control (QC)',

            icon: 'fa-solid fa-list-check',

            menus: [

              { key: 'qc', title: 'Test Functional 2 (QC)' }

            ]

          },

          {

            key: 'admin',

            title: 'Administrator & Verifikasi',

            icon: 'fa-solid fa-shield-halved',

            menus: [

              { key: 'users', title: 'Kelola Pengguna' },

              { key: 'verify_set', title: 'Verifikasi /Set (Satu Set)' },

              { key: 'verify_part', title: 'Verifikasi /Part (1 Part)' },

              { key: 'verify_replacement', title: 'Verifikasi Pergantian Part' },

              { key: 'audit', title: 'Riwayat Audit Gudang' },

              { key: 'downtime', title: 'Audit Downtime Perakitan' },

              { key: 'testing_points', title: 'Master Testing Point' },

              { key: 'add_part', title: 'Tambah Part Number' }

            ]

          }

        ];



        // Users Filter & Pagination (10 per page)

        const filteredUsersList = computed(() => {

          let list = usersList.value || [];

          if (userDeptFilter.value !== 'all') {

            const d = userDeptFilter.value.toLowerCase();

            list = list.filter(u => (u.department || '').toLowerCase() === d || (u.role || '').toLowerCase() === d);

          }

          if (userSearchQuery.value && userSearchQuery.value.trim()) {

            const q = userSearchQuery.value.toLowerCase().trim();

            list = list.filter(u =>

              (u.full_name && u.full_name.toLowerCase().includes(q)) ||

              (u.username && u.username.toLowerCase().includes(q)) ||

              (u.nik && u.nik.toLowerCase().includes(q))

            );

          }

          return list;

        });



        const totalUserPages = computed(() => Math.ceil(filteredUsersList.value.length / userPerPage) || 1);



        const pagedUsersList = computed(() => {

          const start = (userPage.value - 1) * userPerPage;

          return filteredUsersList.value.slice(start, start + userPerPage);

        });



        watch([userSearchQuery, userDeptFilter], () => {

          userPage.value = 1;

        });



        // Error Logs Pagination (20 per page)

        const totalErrorPages = computed(() => Math.ceil(errorLogs.value.length / errorPerPage) || 1);



        const pagedErrorLogs = computed(() => {

          const start = (errorPage.value - 1) * errorPerPage;

          return errorLogs.value.slice(start, start + errorPerPage);

        });



        // Slow Queries Pagination (20 per page)

        const totalSlowPages = computed(() => Math.ceil(slowQueries.value.length / slowPerPage) || 1);



        const pagedSlowQueries = computed(() => {

          const start = (slowPage.value - 1) * slowPerPage;

          return slowQueries.value.slice(start, start + slowPerPage);

        });



        // Anomaly Items Aggregator & Pagination (20 per page)

        const allAnomalyItems = computed(() => {

          const items = [];

          const s = anomalySummary.value;

          if (!s) return items;



          // 1. Orphan parts

          if (s.orphan_parts && Array.isArray(s.orphan_parts.items)) {

            s.orphan_parts.items.forEach(it => {

              items.push({

                id: 'orphan_' + it.id,

                category: 'Orphan Part',

                type: 'orphan',

                badgeClass: 'bg-red-50 text-red-700 border-red-200',

                icon: 'fa-solid fa-puzzle-piece',

                partType: it.part_type || '-',

                sn: it.sn || '-',

                trayInfo: it.tray_id ? 'Baki #' + it.tray_id : 'Tanpa Baki',

                description: 'Part terhubung ke ID baki yang tidak ada.'

              });

            });

          }



          // 2. Stuck sessions

          if (s.stuck_sessions && Array.isArray(s.stuck_sessions.items)) {

            s.stuck_sessions.items.forEach(it => {

              items.push({

                id: 'stuck_' + it.id,

                category: 'Stuck Assembly',

                type: 'stuck',

                badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',

                icon: 'fa-solid fa-clock',

                partType: 'Unit #' + (it.set_number || it.id),

                sn: it.tray_code ? `${it.tray_code}` : `Baki #${it.tray_id || '-'}`,

                trayInfo: 'PIC: ' + (it.pic_assembly || '-'),

                trayCodes: it.tray_code || '',

                description: 'Sesi perakitan menggantung >8 jam.'

              });

            });

          }



          // 3. Desynced stocks

          if (s.desynced_stocks && Array.isArray(s.desynced_stocks.items)) {

            s.desynced_stocks.items.forEach(it => {

              items.push({

                id: 'desync_' + it.id,

                category: 'Desynced Stock',

                type: 'desync',

                badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',

                icon: 'fa-solid fa-arrows-rotate',

                partType: it.part_type || '-',

                sn: it.sn || '-',

                trayInfo: it.tray_code ? it.tray_code : (it.allocated_tray_id ? 'Baki #' + it.allocated_tray_id : '-'),

                trayCodes: it.tray_code || '',

                description: 'Status Supplied padahal baki selesai / tidak ada.'

              });

            });

          }



          // 4. Duplicate parts (Disertai daftar nomor baki / tray)

          if (s.duplicate_parts && Array.isArray(s.duplicate_parts.items)) {

            s.duplicate_parts.items.forEach((it, idx) => {

              items.push({

                id: 'dup_' + idx,

                category: 'Duplicate Part',

                type: 'duplicate',

                badgeClass: 'bg-rose-50 text-rose-700 border-rose-200',

                icon: 'fa-solid fa-clone',

                partType: it.part_type || '-',

                sn: it.sn || '-',

                trayInfo: (it.occurrence_count || 2) + 'x Duplikasi',

                trayCodes: it.tray_codes || '',

                description: it.tray_codes ? `Ditemukan pada ${it.occurrence_count} baki aktif.` : 'Nomor seri dialokasikan di >1 baki aktif.'

              });

            });

          }



          return items;

        });



        const filteredAnomalyItems = computed(() => {

          if (anomalyCategoryFilter.value === 'all') return allAnomalyItems.value;

          return allAnomalyItems.value.filter(item => item.type === anomalyCategoryFilter.value);

        });



        const totalAnomalyPages = computed(() => Math.ceil(filteredAnomalyItems.value.length / anomalyPerPage) || 1);



        const pagedAnomalyItems = computed(() => {

          const start = (anomalyPage.value - 1) * anomalyPerPage;

          return filteredAnomalyItems.value.slice(start, start + anomalyPerPage);

        });



        watch(anomalyCategoryFilter, () => {

          anomalyPage.value = 1;

        });



        // Lifecycle Hooks

        onMounted(() => {

          const storedToken = sessionStorage.getItem('cms_admin_token');

          const storedUser = sessionStorage.getItem('cms_admin_user');

          if (storedToken && storedUser) {

            try {

              cmsToken.value = storedToken;

              adminUser.value = JSON.parse(storedUser);

              isCmsAuthenticated.value = true;

              fetchUsers();

              fetchErrorLogs();

              fetchSlowQueries();

            } catch (e) {

              sessionStorage.removeItem('cms_admin_token');

              sessionStorage.removeItem('cms_admin_user');

            }

          }

        });



        // Auth CMS

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

            if (data.ok) {

              cmsToken.value = data.data.cms_token;

              adminUser.value = data.data.user;

              isCmsAuthenticated.value = true;



              sessionStorage.setItem('cms_admin_token', cmsToken.value);

              sessionStorage.setItem('cms_admin_user', JSON.stringify(adminUser.value));



              loginForm.value = { username: '', password: '' };

              fetchUsers();

              fetchErrorLogs();

              fetchSlowQueries();

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

            title: 'Kunci Portal CMS',

            message: 'Apakah Anda yakin ingin mengunci dan keluar dari Portal Administrator CMS?',

            icon: 'fa-solid fa-lock',

            iconBg: 'bg-red-100 text-red-600',

            confirmText: 'Ya, Kunci',

            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'

          });

          if (!confirmed) return;



          try {

            await fetch('/api/admin/cms-logout', { method: 'POST' });

          } catch (e) {}



          sessionStorage.removeItem('cms_admin_token');

          sessionStorage.removeItem('cms_admin_user');

          cmsToken.value = '';

          adminUser.value = {};

          isCmsAuthenticated.value = false;

          triggerToast('Terkunci', 'Sesi Administrator telah diakhiri dengan aman.', 'info');

        };



        // Pembersihan Database

        const confirmClearTrays = async () => {

          const answer = await showPromptCard({

            title: 'Reset Tray & Pulihkan Stok',

            message: 'PERINGATAN: Mengosongkan seluruh data tray dan mengembalikan stok part ke status Tersedia.\nKetik RESET untuk konfirmasi:',

            icon: 'fa-solid fa-trash-can',

            iconBg: 'bg-red-100 text-red-600',

            inputLabel: 'Ketik RESET:',

            inputPlaceholder: 'RESET',

            confirmText: 'Proses Reset',

            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'

          });



          if (answer !== 'RESET') {

            if (answer !== null) triggerToast('Dibatalkan', 'Konfirmasi kata RESET tidak sesuai.', 'warn');

            return;

          }



          loading.value = true;

          try {

            const res = await fetch('/api/admin/cms/clear-trays', { method: 'POST', headers: { 'Accept': 'application/json' } });

            const data = await res.json();

            triggerToast(data.ok ? 'Berhasil' : 'Gagal', data.message || "Pembersihan tray berhasil!", data.ok ? 'success' : 'error');

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          } finally {

            loading.value = false;

          }

        };



        const confirmClearDowntimes = async () => {

          const confirmed = await showConfirmCard({

            title: 'Hapus Riwayat Jeda',

            message: 'Apakah Anda yakin ingin menghapus seluruh catatan riwayat waktu jeda workstation perakitan?',

            icon: 'fa-solid fa-clock-rotate-left',

            iconBg: 'bg-amber-100 text-amber-600',

            confirmText: 'Ya, Bersihkan',

            confirmBtnClass: 'bg-amber-500 hover:bg-amber-600 text-white'

          });

          if (!confirmed) return;



          loading.value = true;

          try {

            const res = await fetch('/api/admin/cms/clear-downtimes', { method: 'POST', headers: { 'Accept': 'application/json' } });

            const data = await res.json();

            triggerToast('Berhasil', data.message || "Riwayat jeda berhasil dibersihkan!", 'success');

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          } finally {

            loading.value = false;

          }

        };



        const confirmClearAuditLogs = async () => {

          const confirmed = await showConfirmCard({

            title: 'Hapus Riwayat Audit Log',

            message: 'Apakah Anda yakin ingin menghapus seluruh catatan riwayat log audit pergantian part dan aktivitas?',

            icon: 'fa-solid fa-eraser',

            iconBg: 'bg-slate-100 text-slate-700',

            confirmText: 'Ya, Bersihkan',

            confirmBtnClass: 'bg-slate-800 hover:bg-slate-900 text-white'

          });

          if (!confirmed) return;



          loading.value = true;

          try {

            const res = await fetch('/api/admin/cms/clear-audit-logs', { method: 'POST', headers: { 'Accept': 'application/json' } });

            const data = await res.json();

            triggerToast('Berhasil', data.message || "Audit log berhasil dibersihkan!", 'success');

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          } finally {

            loading.value = false;

          }

        };



        // Diagnostik (Error Logs & Slow Queries)

        const fetchErrorLogs = async () => {

          try {

            const res = await fetch('/api/admin/error-logs?limit=500');

            const data = await res.json();

            errorLogs.value = data.data || [];

            errorPage.value = 1;

          } catch (e) {

            console.error(e);

          }

        };



        const clearErrorLogs = async () => {

          const confirmed = await showConfirmCard({

            title: 'Bersihkan Log Error',

            message: 'Apakah Anda yakin ingin mengosongkan seluruh riwayat error log backend?',

            icon: 'fa-solid fa-trash',

            iconBg: 'bg-red-100 text-red-600',

            confirmText: 'Ya, Bersihkan',

            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'

          });

          if (!confirmed) return;



          try {

            await fetch('/api/admin/error-logs/clear', { method: 'POST', headers: { 'Accept': 'application/json' } });

            errorLogs.value = [];

            errorPage.value = 1;

            triggerToast('Berhasil', 'Log error backend berhasil dibersihkan.', 'success');

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          }

        };



        const fetchSlowQueries = async () => {

          try {

            const res = await fetch('/api/admin/slow-queries?limit=500');

            const data = await res.json();

            slowQueries.value = data.data || [];

            slowPage.value = 1;

          } catch (e) {

            console.error(e);

          }

        };



        const clearSlowQueries = async () => {

          const confirmed = await showConfirmCard({

            title: 'Bersihkan Slow Queries',

            message: 'Apakah Anda yakin ingin mengosongkan seluruh riwayat slow query database?',

            icon: 'fa-solid fa-trash',

            iconBg: 'bg-amber-100 text-amber-700',

            confirmText: 'Ya, Bersihkan',

            confirmBtnClass: 'bg-amber-500 hover:bg-amber-600 text-white'

          });

          if (!confirmed) return;



          try {

            await fetch('/api/admin/slow-queries/clear', { method: 'POST', headers: { 'Accept': 'application/json' } });

            slowQueries.value = [];

            slowPage.value = 1;

            triggerToast('Berhasil', 'Log slow query berhasil dibersihkan.', 'success');

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          }

        };



        // Pemindai Anomali

        const scanAnomalies = async () => {

          loading.value = true;

          try {

            const res = await fetch('/api/admin/anomalies/scan');

            const data = await res.json();

            anomalySummary.value = data.data || { total_anomalies: 0 };

            anomalyPage.value = 1;

            triggerToast('Pemindaian Selesai', `Ditemukan ${anomalySummary.value.total_anomalies || 0} anomali data.`, anomalySummary.value.total_anomalies > 0 ? 'warn' : 'success');

          } catch (e) {

            triggerToast('Gagal Memindai', e.message, 'error');

          } finally {

            loading.value = false;

          }

        };



        const repairAnomalies = async () => {

          const confirmed = await showConfirmCard({

            title: '1-Click Auto Repair',

            message: 'Jalankan 1-Click Auto Repair untuk memperbaiki seluruh anomali integritas data secara otomatis?',

            icon: 'fa-solid fa-wrench',

            iconBg: 'bg-emerald-100 text-emerald-600',

            confirmText: 'Mulai Auto Repair',

            confirmBtnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white'

          });

          if (!confirmed) return;



          loading.value = true;

          try {

            const res = await fetch('/api/admin/anomalies/repair', { method: 'POST', headers: { 'Accept': 'application/json' } });

            const data = await res.json();

            triggerToast('Berhasil', data.message || "Auto repair selesai!", 'success');

            scanAnomalies();

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          } finally {

            loading.value = false;

          }

        };



        // DO Settings

        const fetchDoSettings = async () => {

          try {

            const res = await fetch('/api/admin/cms/do-settings');

            const data = await res.json();

            doSettings.value = data.data || {};

          } catch (e) {

            console.error(e);

          }

          await fetchCloudinarySettings();

        };



        const saveDoSettings = async () => {

          loading.value = true;

          try {

            const res = await fetch('/api/admin/cms/do-settings', {

              method: 'POST',

              headers: { 'Content-Type': 'application/json' },

              body: JSON.stringify(doSettings.value)

            });

            const data = await res.json();

            triggerToast('Berhasil', data.message || "Pengaturan berhasil disimpan!", 'success');

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          } finally {

            loading.value = false;

          }

        };



        // User & Permission Management

        const fetchUsers = async () => {

          loadingUsers.value = true;

          try {

            const res = await fetch('/api/admin/users');

            const data = await res.json();

            if (data.ok && Array.isArray(data.data)) {

              usersList.value = data.data;

            }

          } catch (e) {

            console.error('fetchUsers error:', e);

          } finally {

            loadingUsers.value = false;

          }

        };



        const countCmsUsers = () => {

          return (usersList.value || []).filter(u => u.id === 3 || Boolean(u.can_access_cms)).length;

        };



        const countRoleUsers = (roleKey) => {

          return (usersList.value || []).filter(u => {

            const r = (u.role || '').toLowerCase();

            const d = (u.department || '').toLowerCase();

            return r === roleKey || d === roleKey;

          }).length;

        };



        const getDeptBadgeClass = (deptOrRole) => {

          const s = String(deptOrRole || '').toLowerCase();

          if (s.includes('admin')) return 'bg-purple-50 text-purple-700 border-purple-200';

          if (s.includes('warehouse') || s.includes('gudang')) return 'bg-amber-50 text-amber-700 border-amber-200';

          if (s.includes('assembly') || s.includes('perakitan')) return 'bg-blue-50 text-blue-700 border-blue-200';

          if (s.includes('qc') || s.includes('quality')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';

          return 'bg-slate-100 text-slate-700 border-slate-200';

        };



        const countUserWritePerms = (u) => {

          if (String(u.role || '').toLowerCase() === 'admin' || String(u.role || '').toLowerCase() === 'super admin') return 20;

          let count = 0;

          if (u.permissions && typeof u.permissions === 'object') {

            Object.values(u.permissions).forEach(val => {

              if (val === 2 || val === '2' || val === 'full' || val === true) count++;

            });

          }

          return count;

        };



        const countUserReadPerms = (u) => {

          let count = 0;

          if (u.permissions && typeof u.permissions === 'object') {

            Object.values(u.permissions).forEach(val => {

              if (val === 1 || val === '1' || val === 'read' || val === 'read-only') count++;

            });

          }

          return count;

        };



        const setUserPermission = (menuKey, level) => {

          if (!userForm.value.permissions) userForm.value.permissions = {};

          userForm.value.permissions[menuKey] = level;

        };



        const applyUserPreset = (preset, silent = false) => {

          if (!userForm.value.permissions) userForm.value.permissions = {};

          const allMenus = cmsManagedMenuGroups.flatMap(g => g.menus).map(m => m.key);



          if (preset === 'assembly') {

            userForm.value.department = 'Assembly';

            userForm.value.role = 'assembly';

            allMenus.forEach(k => userForm.value.permissions[k] = 0);

            userForm.value.permissions['warehouse'] = 1;

            userForm.value.permissions['production_hold'] = 2;

            userForm.value.permissions['traveler'] = 2;

            userForm.value.permissions['assembly'] = 2;

            userForm.value.permissions['assy_testing'] = 2;

            userForm.value.permissions['master'] = 1;

            userForm.value.permissions['report'] = 1;

          } else if (preset === 'gudang') {

            userForm.value.department = 'Warehouse';

            userForm.value.role = 'gudang';

            allMenus.forEach(k => userForm.value.permissions[k] = 0);

            userForm.value.permissions['warehouse'] = 2;

            userForm.value.permissions['production_hold'] = 2;

            userForm.value.permissions['returns'] = 2;

            userForm.value.permissions['warehouse_receipt'] = 2;

            userForm.value.permissions['reassembly'] = 2;

            userForm.value.permissions['traveler'] = 2;

            userForm.value.permissions['master'] = 2;

            userForm.value.permissions['report'] = 2;

            userForm.value.permissions['delivery_order'] = 2;

          } else if (preset === 'qc') {

            userForm.value.department = 'Quality Control';

            userForm.value.role = 'qc';

            allMenus.forEach(k => userForm.value.permissions[k] = 0);

            userForm.value.permissions['qc'] = 2;

            userForm.value.permissions['production_hold'] = 1;

            userForm.value.permissions['master'] = 1;

            userForm.value.permissions['report'] = 1;

          } else if (preset === 'admin') {

            userForm.value.department = 'Administrator';

            userForm.value.role = 'Admin';

            userForm.value.can_access_cms = true;

            allMenus.forEach(k => userForm.value.permissions[k] = 2);

          } else if (preset === 'none') {

            allMenus.forEach(k => userForm.value.permissions[k] = 0);

            userForm.value.can_access_cms = false;

          }



          if (!silent) {

            triggerToast('Preset Diterapkan', `Template izin ${preset.toUpperCase()} telah disetel.`, 'info');

          }

        };



        const resetUserForm = () => {

          editingUserId.value = null;

          showUserPw.value = false;

          setAllMenuGroups(false); // default tertutup

          userForm.value = {

            id: null,

            nik: '',

            full_name: '',

            department: 'Assembly',

            role: 'assembly',

            username: '',

            password: '',

            can_access_cms: false,

            permissions: {}

          };

          applyUserPreset('assembly', true); // silent agar tidak menimpa notifikasi simpan

        };



        const editUser = (u) => {

          editingUserId.value = u.id;

          showUserPw.value = false;

          setAllMenuGroups(false); // default tertutup

          let perms = {};

          if (u.permissions) {

            perms = typeof u.permissions === 'string' ? JSON.parse(u.permissions) : { ...u.permissions };

          }

          userForm.value = {

            id: u.id,

            nik: u.nik || '',

            full_name: u.full_name || '',

            department: u.department || 'Assembly',

            role: u.role || 'assembly',

            username: u.username || '',

            password: '',

            can_access_cms: (u.id === 3) ? true : Boolean(u.can_access_cms),

            permissions: perms

          };

          const card = document.getElementById('user-form-card');

          if (card) card.scrollIntoView({ behavior: 'smooth' });

        };



        const saveUser = async () => {

          if (!userForm.value.username || !userForm.value.full_name) {

            triggerToast('Validasi Gagal', 'Username dan Nama Lengkap wajib diisi.', 'warn');

            return;

          }

          if (!editingUserId.value && !userForm.value.password) {

            triggerToast('Validasi Gagal', 'Password wajib diisi untuk pengguna baru.', 'warn');

            return;

          }



          isSavingUser.value = true;

          try {

            const isEdit = Boolean(editingUserId.value);

            const targetUsername = userForm.value.username;

            const targetFullName = userForm.value.full_name;



            const payload = {

              id: editingUserId.value || userForm.value.id || null,

              nik: userForm.value.nik,

              full_name: userForm.value.full_name,

              department: userForm.value.department,

              role: userForm.value.role,

              username: userForm.value.username,

              can_access_cms: (editingUserId.value === 3) ? 1 : (userForm.value.can_access_cms ? 1 : 0),

              permissions: userForm.value.permissions

            };

            if (userForm.value.password) {

              payload.password = userForm.value.password;

            }



            const res = await fetch('/api/admin/users/update', {

              method: 'POST',

              headers: { 'Content-Type': 'application/json' },

              body: JSON.stringify(payload)

            });

            const data = await res.json();

            if (data.ok) {

              const successTitle = isEdit ? 'Pengguna Berhasil Diperbarui' : 'Pengguna Baru Berhasil Disimpan';

              const successMsg = isEdit 

                ? `Perubahan akun @${targetUsername} (${targetFullName}) berhasil disimpan.` 

                : `Akun baru @${targetUsername} (${targetFullName}) berhasil dibuat.`;

              resetUserForm();

              await fetchUsers();

              triggerToast(successTitle, data.message || successMsg, 'success');

            } else {

              triggerToast('Gagal', data.error || 'Gagal menyimpan pengguna.', 'error');

            }

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          } finally {

            isSavingUser.value = false;

          }

        };



        const resetUserSession = async (u) => {

          const confirmed = await showConfirmCard({

            title: 'Reset Sesi Login',

            message: `Putuskan sesi login aktif untuk akun "${u.username}"? Pengguna harus login ulang.`,

            icon: 'fa-solid fa-key',

            iconBg: 'bg-amber-100 text-amber-700',

            confirmText: 'Ya, Reset Sesi',

            confirmBtnClass: 'bg-amber-500 hover:bg-amber-600 text-white'

          });

          if (!confirmed) return;



          try {

            const res = await fetch('/api/admin/users/reset-session', {

              method: 'POST',

              headers: { 'Content-Type': 'application/json' },

              body: JSON.stringify({ id: u.id })

            });

            const data = await res.json();

            if (data.ok) {

              triggerToast('Berhasil', `Sesi login untuk @${u.username} berhasil direset.`, 'success');

            }

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          }

        };



        const deleteUser = async (u) => {

          if (u.id === 3) {

            triggerToast('Aksi Ditolak', 'Akun admin utama sistem dilindungi dan tidak dapat dihapus.', 'warn');

            return;

          }



          const confirmed = await showConfirmCard({

            title: 'Hapus Pengguna',

            message: `Apakah Anda yakin ingin menghapus akun "${u.full_name}" (@${u.username})? Tindakan ini bersifat permanen.`,

            icon: 'fa-solid fa-trash-can',

            iconBg: 'bg-rose-100 text-rose-700',

            confirmText: 'Ya, Hapus Akun',

            confirmBtnClass: 'bg-rose-600 hover:bg-rose-700 text-white'

          });

          if (!confirmed) return;



          try {

            const res = await fetch('/api/admin/users/delete', {

              method: 'POST',

              headers: { 'Content-Type': 'application/json' },

              body: JSON.stringify({ id: u.id })

            });

            const data = await res.json();

            if (data.ok) {

              triggerToast('Berhasil', data.message || 'Pengguna berhasil dihapus.', 'success');

              if (editingUserId.value === u.id) resetUserForm();

              await fetchUsers();

            } else {

              triggerToast('Gagal', data.error || 'Gagal menghapus pengguna.', 'error');

            }

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          }

        };



        // Cloudinary

        const fetchCloudinarySettings = async () => {

          try {

            const res = await fetch('/api/admin/cloudinary/settings');

            const data = await res.json();

            if (data.ok && data.data) {

              cloudSettings.value = data.data;

              if (data.data.cloud_name) cloudForm.value.cloud_name = data.data.cloud_name;

              if (data.data.api_key) cloudForm.value.api_key = data.data.api_key;

            }

          } catch (e) {

            console.error('fetchCloudinarySettings error:', e);

          }

        };



        const saveCloudinarySettings = async () => {

          if (!cloudForm.value.cloud_name && !cloudSettings.value.cloud_name) {

            triggerToast('Validasi Gagal', 'Cloud Name wajib diisi.', 'warn');

            return;

          }

          isSavingCloud.value = true;

          try {

            const res = await fetch('/api/admin/cloudinary/settings', {

              method: 'POST',

              headers: { 'Content-Type': 'application/json' },

              body: JSON.stringify({

                cloud_name: cloudForm.value.cloud_name || cloudSettings.value.cloud_name,

                api_key: cloudForm.value.api_key || cloudSettings.value.api_key,

                api_secret: cloudForm.value.api_secret

              })

            });

            const data = await res.json();

            if (data.ok) {

              cloudSettings.value = data.data;

              cloudForm.value.api_secret = '';

              triggerToast('Berhasil', 'Kredensial Cloudinary berhasil disimpan.', 'success');

            } else {

              triggerToast('Gagal', data.error || 'Gagal menyimpan kredensial Cloudinary.', 'error');

            }

          } catch (e) {

            triggerToast('Gagal', e.message, 'error');

          } finally {

            isSavingCloud.value = false;

          }

        };



        const testCloudinaryConnection = async () => {

          isTestingCloud.value = true;

          cloudTestResult.value = null;

          try {

            const res = await fetch('/api/admin/cloudinary/test', {

              method: 'POST',

              headers: { 'Content-Type': 'application/json' }

            });

            const data = await res.json();

            const msg = data.data?.message || data.message || (data.ok ? 'Koneksi Cloudinary berhasil.' : 'Koneksi gagal.');

            cloudTestResult.value = {

              ok: data.ok,

              message: msg

            };

            triggerToast(data.ok ? 'Koneksi Berhasil' : 'Koneksi Gagal', msg, data.ok ? 'success' : 'error');

          } catch (e) {

            const err = 'Gagal: ' + e.message;

            cloudTestResult.value = { ok: false, message: err };

            triggerToast('Koneksi Gagal', err, 'error');

          } finally {

            isTestingCloud.value = false;

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

          loading,

          confirmClearTrays,

          confirmClearDowntimes,

          confirmClearAuditLogs,

          errorLogs,

          errorPage,

          errorPerPage,

          totalErrorPages,

          pagedErrorLogs,

          fetchErrorLogs,

          clearErrorLogs,

          slowQueries,

          slowPage,

          slowPerPage,

          totalSlowPages,

          pagedSlowQueries,

          fetchSlowQueries,

          clearSlowQueries,

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

          usersList,

          loadingUsers,

          editingUserId,

          showUserPw,

          isSavingUser,

          userSearchQuery,

          userDeptFilter,

          userPage,

          userPerPage,

          totalUserPages,

          pagedUsersList,

          userForm,

          openMenuGroups,

          toggleMenuGroup,

          setAllMenuGroups,

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

          isCmsSidebarOpen,

          toast,

          triggerToast,

          confirmModalState,

          showConfirmCard,

          showPromptCard,

          handleConfirmCard,

        };

      }

    }).mount('#adminApp');

  </script>

</body>

</html>
