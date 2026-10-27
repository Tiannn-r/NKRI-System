@include('layouts.head')

@include('layouts.loading')

  <div id="app" v-cloak class="flex-grow flex flex-col relative z-10 bg-slate-50 min-h-screen">

    @include('layouts.header')

    <!-- MAIN CONTAINER -->
    <main class="flex-grow max-w-7xl w-full mx-auto p-3 sm:p-6 lg:p-8 space-y-4 sm:space-y-6">

      @include('modules.assembly.index')
      @include('modules.tf1.index')
      @include('modules.warehouse.returns')
      @include('modules.warehouse.supply')
      @include('modules.warehouse.receipts')
      @include('modules.traveler.index')
      @include('modules.qc.index')
      @include('modules.holds.production')
      @include('modules.holds.functional')
      @include('modules.reassembly.index')
      @include('modules.reports.index')
      @include('modules.delivery-orders.index')
      @include('modules.master-stock.index')
      @include('modules.admin.index')

    </main>

    @include('modals.index')

    @include('prints.index')

  </div>

  <!-- VUE 3 APPLICATION SCRIPT -->
  <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>