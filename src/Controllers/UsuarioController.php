<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Repositories\CatalogoRepository;
use App\Repositories\UsuarioRepository;
use App\Validation\UsuarioValidator;

/**
 * HU-01: gestión de usuarios (solo perfil administrador).
 */
final class UsuarioController
{
    private UsuarioRepository $usuarios;
    private CatalogoRepository $catalogo;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->catalogo = new CatalogoRepository();
    }

    public function index(): void
    {
        View::render('usuarios/index', [
            'title'    => 'Usuarios',
            'usuarios' => $this->usuarios->all(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm([], []);
    }

    public function store(): void
    {
        $perfiles = array_column($this->catalogo->perfiles(), 'nombre', 'id');
        $puntos   = array_map('intval', array_column($this->catalogo->puntosAtencion(), 'id'));

        ['datos' => $datos, 'errores' => $errores] = UsuarioValidator::validate($_POST, $perfiles, $puntos);

        if (!isset($errores['documento']) && $this->usuarios->existeDocumento($datos['documento'])) {
            $errores['documento'] = 'Ya existe un usuario con este documento.';
        }
        if (!isset($errores['correo']) && $this->usuarios->existeCorreo($datos['correo'])) {
            $errores['correo'] = 'Ya existe un usuario con este correo.';
        }

        if ($errores !== []) {
            http_response_code(422);
            $this->renderForm($datos, $errores);

            return;
        }

        $this->usuarios->create($datos);
        Session::flash('success', "Usuario {$datos['nombres']} {$datos['apellidos']} creado con perfil {$perfiles[$datos['perfil_id']]}.");
        redirect('/usuarios');
    }

    /**
     * @param array<string, mixed>  $old
     * @param array<string, string> $errores
     */
    private function renderForm(array $old, array $errores): void
    {
        $perfiles = $this->catalogo->perfiles();

        View::render('usuarios/create', [
            'title'          => 'Nuevo usuario',
            'old'            => $old,
            'errores'        => $errores,
            'perfiles'       => $perfiles,
            'puntos'         => $this->catalogo->puntosAtencion(),
            'perfilVentanilla' => (int) (array_column($perfiles, 'id', 'nombre')['ventanilla'] ?? 0),
        ]);
    }
}
