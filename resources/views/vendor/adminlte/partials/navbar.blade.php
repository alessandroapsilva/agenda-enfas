@php
$unreadAlerts = 0;

try {
    if (\Illuminate\Support\Facades\Schema::hasTable('system_alerts')) {
        $unreadAlerts = \Illuminate\Support\Facades\DB::table('system_alerts')
            ->where('is_read', false)
            ->count();
    }
} catch (\Throwable) {
}
@endphp

<nav class="app-header navbar navbar-expand bg-body">
<div class="container-fluid">

<ul class="navbar-nav align-items-center">
  <li class="nav-item">
    <a
      class="nav-link"
      data-lte-toggle="sidebar"
      href="#"
      aria-label="Expandir ou recolher menu"
      title="Expandir ou recolher menu">
      <i class="bi bi-list fs-5"></i>
    </a>
  </li>

  <li class="nav-item">
    <button
      id="ea-sidebar-pin"
      class="nav-link btn border-0"
      type="button"
      title="Fixar menu aberto">
      <i class="bi bi-pin-angle"></i>
    </button>
  </li>

  <li class="nav-item d-none d-md-block">
    <a href="{{ url('/dashboard') }}" class="nav-link">
      <span class="fw-semibold">ENFAS Agenda</span>
    </a>
  </li>
</ul>

<ul class="navbar-nav ms-auto align-items-center gap-1">

  <li class="nav-item d-none d-lg-block">
    <button
      type="button"
      data-adminlte-search
      class="btn btn-sm ea-nav-pill d-flex align-items-center gap-2 px-3 text-body-secondary">
      <i class="bi bi-search"></i>
      <span>Pesquisar</span>
      <kbd class="small ms-2 border rounded px-1">⌘K</kbd>
    </button>
  </li>

  <li class="nav-item">
    <a
      href="{{ url('/alertas') }}"
      class="nav-link position-relative"
      title="Alertas">
      <i class="bi bi-bell"></i>
      @if($unreadAlerts > 0)
        <span
          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
          style="font-size:.56rem">
          {{ min($unreadAlerts, 99) }}
        </span>
      @endif
    </a>
  </li>

  <li class="nav-item">
    <a
      class="nav-link"
      href="#"
      data-lte-toggle="fullscreen"
      title="Tela cheia">
      <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
      <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
    </a>
  </li>

  @if(config('adminlte.color_mode_toggle', true))
    @include('adminlte::partials.color-mode')
  @endif

  @include('adminlte::partials.usermenu')
</ul>

</div>
</nav>

@include('adminlte::partials.command-palette')
