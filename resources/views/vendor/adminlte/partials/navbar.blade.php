@php
$unreadAlerts = 0;

try {
    if (IlluminateSupportFacadesSchema::hasTable('system_alerts')) {
        $unreadAlerts = IlluminateSupportFacadesDB::table('system_alerts')
            ->where('is_read', false)
            ->count();
    }
} catch (Throwable) {
}
@endphp

<nav class="app-header navbar navbar-expand bg-body">
<div class="container-fluid ea-topbar-shell">

<ul class="navbar-nav align-items-center ea-topbar-left">
  <li class="nav-item">
    <a
      class="nav-link ea-topbar-menu"
      data-lte-toggle="sidebar"
      href="#"
      aria-label="Expandir ou recolher menu"
      title="Menu">
      <i class="bi bi-list"></i>
    </a>
  </li>

  <li class="nav-item d-none d-md-flex ea-topbar-product">
    <span class="ea-topbar-product-mark"></span>

    <div>
      <strong>ENFAS Agenda</strong>
      <small>Workspace operacional</small>
    </div>
  </li>
</ul>

<ul class="navbar-nav ms-auto align-items-center ea-topbar-actions">

  <li class="nav-item d-none d-lg-block">
    <button
      type="button"
      data-adminlte-search
      class="ea-global-search">
      <i class="bi bi-search"></i>
      <span>Pesquisar no sistema</span>
      <kbd>⌘K</kbd>
    </button>
  </li>

  <li class="nav-item">
    <a
      href="{{ url('/alertas') }}"
      class="ea-topbar-icon position-relative"
      title="Alertas">
      <i class="bi bi-bell"></i>

      @if($unreadAlerts > 0)
        <span class="ea-alert-badge">
          {{ min($unreadAlerts, 99) }}
        </span>
      @endif
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
