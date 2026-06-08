<?php

function currentUserName(): string
{
    return $_SESSION['nombre'] ?? 'Invitado';
}

function currentUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function hasRole(string $role): bool
{
    return currentUserRole() === $role;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['role']);
}

function isGuest(): bool
{
    return hasRole('guest');
}

function isAdmin(): bool
{
    return hasRole('admin') && isset($_SESSION['user_id']);
}

function requireAuthenticatedUser(): void
{
    if (!isLoggedIn() || isGuest() || !isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
