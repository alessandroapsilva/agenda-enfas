@php
$items = app('adminlte')->menu('sidebar');
@endphp

<aside
  class="app-sidebar shadow"
  data-bs-theme="dark"
  data-enable-persistence="true">

  <div class="sidebar-brand">
    <a href="{{ url('/dashboard') }}" class="brand-link">
      <img
        src="{{ asset(config('adminlte.logo_img','assets/brand/enfas-agenda.svg')) }}"
        alt="{{ config('adminlte.logo_img_alt','ENFAS Agenda') }}"
        class="brand-image shadow-sm">

      <span class="brand-text fw-semibold">
        ENFAS Agenda
      </span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-1" aria-label="Navegação principal">
      <ul
        class="nav sidebar-menu flex-column"
        data-lte-toggle="treeview"
        data-accordion="false"
        role="menu">
        @foreach($items as $item)
          @include('adminlte::partials.menu-item', ['item' => $item])
        @endforeach
      </ul>
    </nav>
  </div>
</aside>
