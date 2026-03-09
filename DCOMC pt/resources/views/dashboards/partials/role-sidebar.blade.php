{{--
    Role-based sidebar inclusion: derive sidebar from current route only.
    This ensures the sidebar never switches when navigating within a role's area.
    Order matters: check more specific prefixes first (e.g. unifast before admin fallback).
--}}
@php
$sidebar = match (true) {
    request()->routeIs('admin.*') => 'admin-sidebar',
    request()->routeIs('registrar.*') => 'registrar-sidebar',
    request()->routeIs('staff.*') => 'staff-sidebar',
    request()->routeIs('dean.*') => 'dean-sidebar',
    request()->routeIs('cor.archive.*') => 'dean-sidebar',
    request()->routeIs('unifast.*') => 'unifast-sidebar',
    default => 'admin-sidebar',
};
@endphp
@include('dashboards.partials.' . $sidebar)
