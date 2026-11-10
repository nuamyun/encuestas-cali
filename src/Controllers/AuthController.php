<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UsuarioRepository;

/**
 * HU-02: inicio y cierre de sesión.
 */
final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/inicio');
        }

        View::render('auth/login', ['title' => 'Iniciar sesión', 'correo' => '', 'error' => null], 'layouts/guest');
    }

    public function login(): void
    {
        $correo   = trim((string) ($_POST['correo'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($correo !== '' && $password !== '' && Auth::attempt($correo, $password, new UsuarioRepository())) {
            redirect('/inicio');
        }

        http_response_code(422);
        View::render('auth/login', [
            'title'  => 'Iniciar sesión',
            'correo' => $correo,
            'error'  => 'Correo o contraseña incorrectos.',
        ], 'layouts/guest');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        Session::flash('success', 'Cerraste sesión correctamente.');
        redirect('/login');
    }
}
