@php
$user = auth()->user();

$name = $user->name
    ?? ($user->username
    ?? ($user->email ?? 'Usuário'));

$parts = collect(preg_split('/\s+/', trim($name)))
    ->filter()
    ->take(2);

$initials = $parts
    ->map(fn ($part) =>
        mb_strtoupper(mb_substr($part, 0, 1))
    )
    ->implode('');

if (! $initials) {
    $initials = 'U';
}

$username = $user->username ?? null;
$role = $user->role ?? null;
@endphp

<li class="nav-item dropdown user-menu">
  <a
    href="#"
    class="nav-link dropdown-toggle d-flex align-items-center gap-2"
    data-bs-toggle="dropdown"
    aria-expanded="false">

    <span class="ea-avatar sm">{{ $initials }}</span>

    <span class="d-none d-md-inline">
      {{ $name }}
    </span>
  </a>

  <ul class="dropdown-menu dropdown-menu-end ea-user-menu">
    <li class="p-3">
      <div class="d-flex align-items-center gap-3">
        <span class="ea-avatar lg">{{ $initials }}</span>
        <div class="min-w-0">
          <strong class="d-block text-truncate">{{ $name }}</strong>
          @if($username)
            <small class="text-body-secondary d-block">
              {{ '@' . $username }}
            </small>
          @endif
          @if($role)
            <span class="badge text-bg-light mt-1">
              {{ ucfirst($role) }}
            </span>
          @endif
        </div>
      </div>
    </li>

    <li><hr class="dropdown-divider my-0"></li>

    <li class="p-2">
      <a
        href="{{ url('/configuracoes/aparencia') }}"
        class="dropdown-item rounded-2">
        <i class="bi bi-palette me-2"></i>
        Aparência
      </a>

      <a
        href="{{ url('/configuracoes/agenda') }}"
        class="dropdown-item rounded-2">
        <i class="bi bi-sliders me-2"></i>
        Preferências
      </a>

      <form action="{{ url('/logout') }}" method="POST">
        @csrf
        <button
          type="submit"
          class="dropdown-item rounded-2 text-danger">
          <i class="bi bi-box-arrow-right me-2"></i>
          Sair
        </button>
      </form>
    </li>
  </ul>
</li>
