# Patrones de Diseño y Principios SOLID — BovWeight CR API

> **Proyecto:** BovWeight CR · Módulo 1 — Gestión de Usuarios  
> **Curso:** IF7100 · Universidad de Costa Rica  
> **Framework:** Laravel 13 · PHP 8.3

---

## Tabla de contenidos

1. [Patrón Factory](#1-patrón-factory)
2. [Patrón Repository](#2-patrón-repository)
3. [Patrón Observer](#3-patrón-observer)
4. [S — Single Responsibility Principle](#4-s--single-responsibility-principle)
5. [O — Open/Closed Principle](#5-o--openclosed-principle)
6. [L — Liskov Substitution Principle](#6-l--liskov-substitution-principle)
7. [I — Interface Segregation Principle](#7-i--interface-segregation-principle)
8. [D — Dependency Inversion Principle](#8-d--dependency-inversion-principle)

---

## 1. Patrón Factory

### Qué es

El patrón **Factory** (GoF — Creational) define una interfaz para crear objetos y deja que las subclases o implementaciones concretas decidan qué clase instanciar. El cliente que necesita el objeto nunca usa `new` directamente; delega esa responsabilidad al factory.

### Por qué se aplicó aquí

En BovWeight CR existen tres tipos de usuario: `Administrador`, `Ganadero` y `Veterinario`. Cada uno requiere que se consulte la tabla `tipo_usuarios` para obtener el `tipo_id` correspondiente y se combinen los atributos correctos antes de persistir el modelo. Sin un factory, esa lógica estaría duplicada en todo lugar donde se cree un usuario — el servicio de usuarios, el servicio de solicitudes, futuras importaciones masivas, etc.

### Dónde está implementado

| Rol GoF | Archivo |
|---|---|
| **Creator (interfaz)** | `app/Contracts/IUserFactory.php` |
| **ConcreteCreator** | `app/Factories/UserFactory.php` |
| **Product** | `app/Models/User.php` |
| **Consumidor 1** | `app/Services/UsuarioService.php` |
| **Consumidor 2** | `app/Services/SolicitudRegistroService.php` |
| **Registro DI** | `app/Providers/AppServiceProvider.php` → `register()` |

### El contrato (interfaz)

```php
// app/Contracts/IUserFactory.php
interface IUserFactory
{
    /**
     * @param  string  $tipoNombre  "Ganadero" | "Veterinario" | "Administrador"
     * @param  array{nombre: string, correo: string, contrasena: string}  $datos
     */
    public function make(string $tipoNombre, array $datos): User;
}
```

### La implementación concreta

```php
// app/Factories/UserFactory.php
class UserFactory implements IUserFactory
{
    private array $defaults = [
        'Administrador' => [],
        'Ganadero'      => [],
        'Veterinario'   => [],
    ];

    public function make(string $tipoNombre, array $datos): User
    {
        $tipo = TipoUsuario::where('nombre', $tipoNombre)->first();

        if (! $tipo) {
            throw new InvalidArgumentException("Tipo de usuario desconocido: {$tipoNombre}");
        }

        $atributos = array_merge(
            $this->defaults[$tipoNombre] ?? [],
            $datos,
            ['tipo_id' => $tipo->id]   // ← el cliente nunca necesita saber el ID
        );

        return User::create($atributos);
    }
}
```

### Uso en los servicios

**En `UsuarioService::crear()`** — cuando el administrador crea un usuario directamente (HU-01.4):

```php
// app/Services/UsuarioService.php
public function crear(string $tipoNombre, array $datos): User
{
    // ...validación de correo duplicado...

    $contrasenaPlana = $datos['contrasena'] ?? Str::random(12);
    $datos['contrasena'] = $contrasenaPlana;

    // FACTORY: el servicio no sabe cómo se construye el User internamente
    $usuario = $this->userFactory->make($tipoNombre, $datos);

    UsuarioCreado::dispatch($usuario, $contrasenaPlana);

    return $usuario->load('tipoUsuario');
}
```

**En `SolicitudRegistroService::aprobar()`** — cuando el admin aprueba una solicitud y crea al usuario (HU-01.8):

```php
// app/Services/SolicitudRegistroService.php
private function aprobar(SolicitudRegistro $solicitud, string $tipoUsuario): SolicitudRegistro
{
    // ...actualiza estado a 'Aprobado'...

    $contrasenaPlana = Str::random(12);

    // FACTORY: mismo factory, mismo contrato, diferente punto de llamada
    $usuario = $this->userFactory->make($tipoUsuario, [
        'nombre'    => $solicitud->nombre . ' ' . $solicitud->apellidos,
        'correo'    => $solicitud->correo,
        'contrasena'=> $contrasenaPlana,
    ]);

    SolicitudAprobada::dispatch($solicitud, $usuario, $contrasenaPlana);

    return $solicitud->fresh('estado');
}
```

### Registro como singleton en el contenedor

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Singleton: la misma instancia de UserFactory en toda la aplicación
    $this->app->singleton(IUserFactory::class, UserFactory::class);
}
```

### Flujo completo

```
POST /api/usuarios  { tipo_nombre: "Ganadero", nombre: ..., correo: ..., contrasena: ... }
        │
        ▼
UsuarioController::store()
        │  delega a
        ▼
UsuarioService::crear('Ganadero', $datos)
        │  llama a
        ▼
IUserFactory::make('Ganadero', $datos)      ← depende del CONTRATO
        │  resuelto por el contenedor a
        ▼
UserFactory::make()
        ├── TipoUsuario::where('nombre', 'Ganadero')->first()  → tipo_id = 2
        ├── array_merge(defaults, datos, ['tipo_id' => 2])
        └── User::create($atributos)  → User persistido en BD
```

### Justificación de la decisión

- **Centralización:** La lógica de `tipo_id` y defaults por tipo existe en un único lugar. Si se cambia cómo se construye un Ganadero, se cambia solo en `UserFactory`.
- **Extensibilidad:** Agregar el tipo `Transportista` = añadir la entrada en `$defaults` y en el seeder. Ningún servicio cambia.
- **Testabilidad:** En tests se puede hacer `$this->app->bind(IUserFactory::class, FakeUserFactory::class)` sin tocar ningún servicio.

---

## 2. Patrón Repository

### Qué es

El patrón **Repository** (GoF — Structural, variante de Domain-Driven Design) actúa como una capa de abstracción entre la lógica de negocio y el mecanismo de persistencia. Los servicios hablan con una interfaz; la implementación concreta (Eloquent, Doctrine, en-memoria) queda oculta.

### Por qué se aplicó aquí

Sin el patrón Repository, todos los servicios y controladores contendrían llamadas directas a `User::where(...)`, `SolicitudRegistro::with(...)->get()`, etc. Esto produce tres problemas graves:

1. **Acoplamiento duro:** Cambiar el ORM o el motor de BD implica modificar cada servicio.
2. **Tests costosos:** Probar un servicio requiere una BD real porque Eloquent está incrustado.
3. **Duplicación:** La misma query `User::with('tipoUsuario')->where('correo', ...)` aparecería en múltiples lugares.

### Dónde está implementado

| Rol GoF | Archivo |
|---|---|
| **Repository (interfaz)** | `app/Contracts/IUserRepository.php` |
| **Repository (interfaz)** | `app/Contracts/ISolicitudRegistroRepository.php` |
| **ConcreteRepository** | `app/Repositories/EloquentUserRepository.php` |
| **ConcreteRepository** | `app/Repositories/EloquentSolicitudRegistroRepository.php` |
| **Consumidores** | `AuthService`, `UsuarioService`, `SolicitudRegistroService` |
| **Registro DI** | `app/Providers/AppServiceProvider.php` → `register()` |

### Los contratos (interfaces)

```php
// app/Contracts/IUserRepository.php
interface IUserRepository
{
    public function findById(int $id): ?User;
    public function findByEmail(string $correo): ?User;
    public function findAll(?string $search = null): Collection;
    public function existsByEmail(string $correo, ?int $excludeId = null): bool;
    public function save(User $user): User;
    public function delete(int $id): void;
}

// app/Contracts/ISolicitudRegistroRepository.php
interface ISolicitudRegistroRepository
{
    public function findById(int $id): ?SolicitudRegistro;
    public function findAll(): Collection;
    public function findPendientes(): Collection;
    public function existsByEmail(string $correo): bool;
    public function save(SolicitudRegistro $solicitud): SolicitudRegistro;
}
```

### Las implementaciones concretas (Eloquent)

```php
// app/Repositories/EloquentUserRepository.php
class EloquentUserRepository implements IUserRepository
{
    public function findById(int $id): ?User
    {
        return User::with('tipoUsuario')->find($id);
    }

    public function findByEmail(string $correo): ?User
    {
        return User::with('tipoUsuario')->where('correo', $correo)->first();
    }

    public function findAll(?string $search = null): Collection
    {
        return User::with('tipoUsuario')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('correo', 'like', "%{$search}%");
            }))
            ->get();
    }

    public function existsByEmail(string $correo, ?int $excludeId = null): bool
    {
        return User::where('correo', $correo)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function save(User $user): User
    {
        $user->save();
        return $user->fresh('tipoUsuario');
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
    }
}
```

```php
// app/Repositories/EloquentSolicitudRegistroRepository.php
class EloquentSolicitudRegistroRepository implements ISolicitudRegistroRepository
{
    public function findPendientes(): Collection
    {
        return SolicitudRegistro::with('estado')
            ->whereHas('estado', fn ($q) => $q->where('nombre', 'Pendiente'))
            ->latest()
            ->get();
    }
    // ... findById, findAll, existsByEmail, save
}
```

### Uso en los servicios

Los servicios inyectan el **contrato**, nunca la clase concreta:

```php
// app/Services/AuthService.php
class AuthService
{
    public function __construct(
        private readonly IUserRepository $usuarios,   // ← interfaz, no Eloquent
    ) {}

    public function login(string $correo, string $contrasena): array
    {
        $usuario = $this->usuarios->findByEmail($correo);  // ← habla con el contrato

        if (! $usuario || ! Hash::check($contrasena, $usuario->contrasena)) {
            throw new AuthenticationException('Credenciales incorrectas.');
        }
        // ...
    }
}
```

### Registro de los bindings

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Laravel inyecta EloquentUserRepository donde alguien pide IUserRepository
    $this->app->bind(IUserRepository::class, EloquentUserRepository::class);
    $this->app->bind(ISolicitudRegistroRepository::class, EloquentSolicitudRegistroRepository::class);
    $this->app->bind(IFincaRepository::class, EloquentFincaRepository::class);
}
```

### Flujo completo (autenticación)

```
POST /api/auth/login { correo, contrasena }
        │
        ▼
AuthController::login()
        │  delega a
        ▼
AuthService::login()
        │  llama a  (solo conoce la interfaz)
        ▼
IUserRepository::findByEmail('admin@bovweight.com')
        │  contenedor resuelve a
        ▼
EloquentUserRepository::findByEmail()
        └── User::with('tipoUsuario')->where('correo', $correo)->first()
                │
                └── SQL: SELECT * FROM users WHERE correo = ? (con eager load de tipo)
```

### Justificación de la decisión

- **Desacoplamiento total:** `AuthService` no importa ninguna clase de Eloquent. Si se migra a MongoDB, se crea `MongoUserRepository` y se cambia el binding; ningún servicio cambia.
- **Tests aislados:** Los tests de feature usan SQLite en memoria gracias a `RefreshDatabase`. El sistema funciona igual porque el servicio habla con el contrato, no con MySQL directamente.
- **Consultas centralizadas:** La query de búsqueda por `nombre OR correo` existe solo en `EloquentUserRepository::findAll()`. Si cambia el criterio de búsqueda, se modifica un único método.

---

## 3. Patrón Observer

### Qué es

El patrón **Observer** (GoF — Behavioral) define una dependencia uno-a-muchos entre objetos: cuando el **Subject** (observable) cambia de estado, todos sus **Observers** suscritos son notificados automáticamente, sin que el Subject los conozca.

En Laravel este patrón se implementa mediante el sistema de **Events y Listeners**.

### Por qué se aplicó aquí

Cuando el administrador aprueba una solicitud, ocurren múltiples efectos secundarios: enviar un correo con las credenciales, registrar en logs, potencialmente notificar por SMS en el futuro. Sin el patrón Observer, `SolicitudRegistroService::aprobar()` tendría que llamar explícitamente a cada uno de esos efectos, acumulando responsabilidades ajenas y creciendo con cada nueva necesidad (violación de OCP y SRP).

Con Observer, el servicio solo **anuncia que algo ocurrió** (`dispatch`). Quién reacciona y cómo es completamente ajeno al servicio.

### Dónde está implementado

| Rol GoF | Concepto Laravel | Archivo |
|---|---|---|
| **Subject / Observable** | Event | `app/Events/SolicitudAprobada.php` |
| **Subject / Observable** | Event | `app/Events/SolicitudRechazada.php` |
| **Subject / Observable** | Event | `app/Events/UsuarioCreado.php` |
| **ConcreteObserver** | Listener | `app/Listeners/NotificarAprobacionSolicitud.php` |
| **ConcreteObserver** | Listener | `app/Listeners/NotificarRechazoSolicitud.php` |
| **ConcreteObserver** | Listener | `app/Listeners/NotificarBienvenidaUsuario.php` |
| **EventManager** | AppServiceProvider | `app/Providers/AppServiceProvider.php` → `boot()` |

### Los eventos (Subjects)

```php
// app/Events/SolicitudAprobada.php
// Transporta el estado relevante que los observers necesitan
class SolicitudAprobada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SolicitudRegistro $solicitud,
        public readonly User $usuario,
        public readonly string $contrasenaPlana,  // para enviarla por correo
    ) {}
}

// app/Events/SolicitudRechazada.php
class SolicitudRechazada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SolicitudRegistro $solicitud,
        public readonly string $motivoRechazo,
    ) {}
}

// app/Events/UsuarioCreado.php
class UsuarioCreado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $usuario,
        public readonly string $contrasenaPlana,
    ) {}
}
```

### Los listeners (ConcreteObservers)

```php
// app/Listeners/NotificarAprobacionSolicitud.php
class NotificarAprobacionSolicitud
{
    public function handle(SolicitudAprobada $event): void
    {
        Mail::to($event->solicitud->correo)
            ->send(new CredencialesAccesoMail(
                $event->solicitud,
                $event->usuario,
                $event->contrasenaPlana,
            ));
    }
}

// app/Listeners/NotificarRechazoSolicitud.php
class NotificarRechazoSolicitud
{
    public function handle(SolicitudRechazada $event): void
    {
        Mail::to($event->solicitud->correo)
            ->send(new RechazoSolicitudMail(
                $event->solicitud,
                $event->motivoRechazo,
            ));
    }
}

// app/Listeners/NotificarBienvenidaUsuario.php
class NotificarBienvenidaUsuario
{
    public function handle(UsuarioCreado $event): void
    {
        Mail::to($event->usuario->correo)
            ->send(new BienvenidaUsuarioMail(
                $event->usuario,
                $event->contrasenaPlana,
            ));
    }
}
```

### Registro de los observers en el EventManager

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    // Suscripción: cada Event → su Listener (Observer)
    Event::listen(SolicitudAprobada::class, NotificarAprobacionSolicitud::class);
    Event::listen(SolicitudRechazada::class, NotificarRechazoSolicitud::class);
    Event::listen(UsuarioCreado::class,      NotificarBienvenidaUsuario::class);
}
```

### Dispatch del evento en los servicios (el Subject notifica)

```php
// app/Services/SolicitudRegistroService.php
private function aprobar(SolicitudRegistro $solicitud, string $tipoUsuario): SolicitudRegistro
{
    // ...actualiza BD...
    $contrasenaPlana = Str::random(12);
    $usuario = $this->userFactory->make($tipoUsuario, [...]);

    // El servicio ANUNCIA lo que ocurrió — no llama ningún listener directamente
    SolicitudAprobada::dispatch($solicitud, $usuario, $contrasenaPlana);

    return $solicitud->fresh('estado');
}

private function rechazar(SolicitudRegistro $solicitud, string $motivo): SolicitudRegistro
{
    // ...actualiza BD...
    SolicitudRechazada::dispatch($solicitud, $motivo);
    return $solicitud->fresh('estado');
}

// app/Services/UsuarioService.php
public function crear(string $tipoNombre, array $datos): User
{
    // ...validación y factory...
    UsuarioCreado::dispatch($usuario, $contrasenaPlana);
    return $usuario->load('tipoUsuario');
}
```

### Flujo completo (aprobación de solicitud)

```
Admin → PUT /api/solicitudes/{id}/revisar
        { "decision": "aprobar", "tipo_usuario": "Ganadero" }
            │
            ▼
SolicitudRegistroController::revisar()
            │
            ▼
SolicitudRegistroService::aprobar()
    ├─ 1. Actualiza solicitud.estado_id → 'Aprobado' (via Repository)
    ├─ 2. Genera contraseña: Str::random(12)
    ├─ 3. Crea User con tipo 'Ganadero' (via Factory)
    └─ 4. SolicitudAprobada::dispatch(...)
                │   ← el servicio termina aquí
                │
                ▼
        Laravel Event Bus
        (consulta AppServiceProvider::boot)
                │
                ▼
        NotificarAprobacionSolicitud::handle($event)
                └── Mail::to(correo)->send(new CredencialesAccesoMail(...))
                        └── Gmail SMTP → correo al nuevo usuario con sus credenciales
```

### Tabla de eventos y sus efectos

| Evento | Disparado en | Observer | Efecto |
|---|---|---|---|
| `UsuarioCreado` | `UsuarioService::crear()` | `NotificarBienvenidaUsuario` | Correo de bienvenida con credenciales |
| `SolicitudAprobada` | `SolicitudRegistroService::aprobar()` | `NotificarAprobacionSolicitud` | Correo con credenciales de acceso |
| `SolicitudRechazada` | `SolicitudRegistroService::rechazar()` | `NotificarRechazoSolicitud` | Correo con motivo del rechazo |

### Justificación de la decisión

- **Desacoplamiento total:** `SolicitudRegistroService` no importa ninguna clase de correo ni de notificación. Puede evolucionar sin saber cuántos observers existen.
- **Extensibilidad sin modificar código existente:** Agregar notificación por SMS = crear `NotificarAprobacionSms::class` + una línea en `AppServiceProvider::boot()`. Ningún servicio cambia (OCP).
- **Testabilidad:** `Event::fake([SolicitudAprobada::class])` en los tests impide que los listeners se ejecuten, permitiendo verificar solo que el evento fue despachado sin necesidad de infraestructura de correo.

---

## 4. S — Single Responsibility Principle

> *"Una clase debe tener una única razón para cambiar."*

Cada clase del proyecto tiene exactamente **una responsabilidad**:

| Clase | Única responsabilidad | Si cambia... |
|---|---|---|
| `AuthController` | Traducir HTTP ↔ JSON para autenticación | ...solo si cambia el contrato HTTP |
| `AuthService` | Lógica de login, logout, reset de contraseña | ...solo si cambian las reglas de autenticación |
| `UsuarioService` | Reglas de negocio del CRUD de usuarios | ...solo si cambian esas reglas |
| `SolicitudRegistroService` | Flujo de solicitudes y revisión admin | ...solo si cambia ese flujo |
| `EloquentUserRepository` | Queries SQL sobre `users` | ...solo si cambian las consultas |
| `UserFactory` | Construir un `User` con el tipo correcto | ...solo si cambia cómo se crea un usuario |
| `NotificarAprobacionSolicitud` | Enviar el correo de credenciales al aprobar | ...solo si cambia ese correo |
| `EsAdministrador` (middleware) | Verificar si el usuario autenticado es admin | ...solo si cambia la lógica de rol |
| `StoreUsuarioRequest` | Validar el request de creación de usuario | ...solo si cambian las reglas de validación |
| `UserResource` | Dar forma al JSON de respuesta de usuario | ...solo si cambia el formato de respuesta |

**Evidencia en el código:**

`UsuarioService` solo contiene lógica de negocio — no tiene ni una línea de Eloquent, ni HTML, ni validación de request:

```php
// app/Services/UsuarioService.php
class UsuarioService
{
    public function eliminar(int $id): void
    {
        $usuario = $this->obtener($id);
        $usuario->tokens()->delete();       // revoca tokens Sanctum
        $this->usuarios->delete($id);       // delega la query al Repository
    }
}
```

`EloquentUserRepository` solo contiene queries — sin reglas de negocio ni lógica de correos:

```php
// app/Repositories/EloquentUserRepository.php
public function findAll(?string $search = null): Collection
{
    return User::with('tipoUsuario')
        ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
            $q->where('nombre', 'like', "%{$search}%")
              ->orWhere('correo', 'like', "%{$search}%");
        }))
        ->get();
}
```

---

## 5. O — Open/Closed Principle

> *"Las entidades de software deben estar abiertas para extensión, pero cerradas para modificación."*

### Extensión del Observer sin modificar código existente

Para agregar una notificación por SMS cuando se aprueba una solicitud, **ninguna clase existente cambia**:

```php
// app/Providers/AppServiceProvider.php — solo se AGREGA una línea
public function boot(): void
{
    Event::listen(SolicitudAprobada::class, NotificarAprobacionSolicitud::class); // existente
    Event::listen(SolicitudAprobada::class, NotificarAprobacionSmsSolicitud::class); // NUEVO
    Event::listen(SolicitudRechazada::class, NotificarRechazoSolicitud::class);   // existente
    Event::listen(UsuarioCreado::class,      NotificarBienvenidaUsuario::class);  // existente
}
```

`SolicitudRegistroService`, `SolicitudAprobada`, y `NotificarAprobacionSolicitud` permanecen **intactos**.

### Extensión del Factory sin modificar servicios

Para agregar el tipo de usuario `Transportista`:

```php
// app/Factories/UserFactory.php — solo se AGREGA la entrada
private array $defaults = [
    'Administrador' => [],
    'Ganadero'      => [],
    'Veterinario'   => [],
    'Transportista' => ['es_transportista' => true],  // NUEVO
];
```

`UsuarioService` y `SolicitudRegistroService` **no cambian** — siguen llamando a `$this->userFactory->make($tipoNombre, $datos)` exactamente igual.

### Extensión del Repository sin modificar servicios

Para agregar una consulta de usuarios por tipo:

```php
// app/Contracts/IUserRepository.php — se EXTIENDE la interfaz
public function findByTipo(string $tipoNombre): Collection;  // NUEVO

// app/Repositories/EloquentUserRepository.php — se IMPLEMENTA
public function findByTipo(string $tipoNombre): Collection
{
    return User::with('tipoUsuario')
        ->whereHas('tipoUsuario', fn($q) => $q->where('nombre', $tipoNombre))
        ->get();
}
```

Ningún servicio que ya use `IUserRepository` necesita modificarse.

---

## 6. L — Liskov Substitution Principle

> *"Los objetos de un programa deben poder reemplazarse por instancias de sus subtipos sin alterar el correcto funcionamiento del programa."*

### En el Repository

`AuthService`, `UsuarioService` y `SolicitudRegistroService` reciben `IUserRepository`. Cualquier clase que implemente ese contrato puede sustituirla:

```php
// app/Providers/AppServiceProvider.php
// Producción: MySQL con Eloquent
$this->app->bind(IUserRepository::class, EloquentUserRepository::class);

// Tests: misma interfaz, implementación en memoria
// $this->app->bind(IUserRepository::class, InMemoryUserRepository::class);

// Futura migración: misma interfaz, motor diferente
// $this->app->bind(IUserRepository::class, MongoUserRepository::class);
```

`AuthService::login()` funciona idénticamente con cualquiera de esas tres implementaciones porque solo llama a métodos declarados en `IUserRepository`.

**Evidencia práctica:** Los 39 tests de feature usan SQLite en memoria mediante `RefreshDatabase`, mientras producción usa MySQL. Los servicios no cambian una línea — la sustitución es transparente.

### En el Factory

`UsuarioService` y `SolicitudRegistroService` reciben `IUserFactory`. Si se quisiera un `TestUserFactory` que no persista a BD (útil en tests unitarios):

```php
class TestUserFactory implements IUserFactory
{
    public function make(string $tipoNombre, array $datos): User
    {
        $user = new User($datos);
        $user->id = rand(1, 1000);  // simula persistencia sin BD
        return $user;
    }
}
```

Los servicios continúan funcionando sin ninguna modificación porque dependen del contrato `IUserFactory`, no de `UserFactory` concretamente.

---

## 7. I — Interface Segregation Principle

> *"Los clientes no deben verse forzados a depender de interfaces que no usan."*

En lugar de una interfaz genérica `IRepository` con todos los métodos posibles, el proyecto define **tres contratos pequeños y cohesivos**, cada uno con exactamente los métodos que sus consumidores necesitan:

```php
// 6 métodos — todos usados por AuthService o UsuarioService
interface IUserRepository
{
    public function findById(int $id): ?User;
    public function findByEmail(string $correo): ?User;
    public function findAll(?string $search = null): Collection;
    public function existsByEmail(string $correo, ?int $excludeId = null): bool;
    public function save(User $user): User;
    public function delete(int $id): void;
}

// 5 métodos — todos usados por SolicitudRegistroService
interface ISolicitudRegistroRepository
{
    public function findById(int $id): ?SolicitudRegistro;
    public function findAll(): Collection;
    public function findPendientes(): Collection;
    public function existsByEmail(string $correo): bool;
    public function save(SolicitudRegistro $solicitud): SolicitudRegistro;
}

// 1 método — todo lo que los servicios necesitan del factory
interface IUserFactory
{
    public function make(string $tipoNombre, array $datos): User;
}
```

**Por qué importa:** Si se hubiera creado una interfaz `IRepository` con `findById`, `findAll`, `findPendientes`, `existsByEmail`, `save`, `delete` para todos los modelos, `EloquentSolicitudRegistroRepository` estaría forzado a implementar `delete()` aunque nunca se use para solicitudes — violando ISP.

Cada implementación solo está obligada a definir métodos que realmente usa:

- `EloquentSolicitudRegistroRepository` **no tiene** `delete()` — porque las solicitudes no se eliminan.
- `EloquentUserRepository` **no tiene** `findPendientes()` — porque no aplica a usuarios.

---

## 8. D — Dependency Inversion Principle

> *"Los módulos de alto nivel no deben depender de módulos de bajo nivel. Ambos deben depender de abstracciones. Las abstracciones no deben depender de los detalles; los detalles deben depender de las abstracciones."*

Este principio es el **eje central** de toda la arquitectura. Se aplica sistemáticamente en cada servicio:

### Todos los servicios dependen de abstracciones

```php
// app/Services/AuthService.php
class AuthService
{
    public function __construct(
        private readonly IUserRepository $usuarios,  // ← abstracción
    ) {}
}

// app/Services/UsuarioService.php
class UsuarioService
{
    public function __construct(
        private readonly IUserRepository $usuarios,  // ← abstracción
        private readonly IUserFactory $userFactory,  // ← abstracción
    ) {}
}

// app/Services/SolicitudRegistroService.php
class SolicitudRegistroService
{
    public function __construct(
        private readonly ISolicitudRegistroRepository $solicitudes, // ← abstracción
        private readonly IUserRepository $usuarios,                 // ← abstracción
        private readonly IUserFactory $userFactory,                 // ← abstracción
    ) {}
}
```

Ninguno de estos servicios tiene un `use App\Repositories\...` ni `use App\Factories\UserFactory` en sus imports. Solo conocen contratos.

### Las clases concretas solo se nombran en un lugar

```php
// app/Providers/AppServiceProvider.php — el único archivo que conoce las implementaciones
public function register(): void
{
    $this->app->bind(IUserRepository::class,              EloquentUserRepository::class);
    $this->app->bind(ISolicitudRegistroRepository::class, EloquentSolicitudRegistroRepository::class);
    $this->app->bind(IFincaRepository::class,             EloquentFincaRepository::class);

    $this->app->singleton(IUserFactory::class, UserFactory::class);
    $this->app->singleton(IFincaFactory::class, FincaFactory::class);
}
```

### El middleware también aplica DIP

```php
// app/Http/Middleware/EsAdministrador.php
public function handle(Request $request, Closure $next): Response
{
    if (! $request->user()?->esAdministrador()) {
        return response()->json(['message' => 'Acceso denegado.'], 403);
    }
    return $next($request);
}
```

El middleware llama a `esAdministrador()` — un método del modelo `User` que internamente consulta la relación `tipoUsuario`. El middleware no sabe cómo se determina si alguien es admin; depende de la abstracción que ofrece el modelo.

### Diagrama de dependencias

```
                        ┌─────────────────┐
                        │  AppServiceProvider  │  ← único que conoce implementaciones
                        └────────┬────────┘
                                 │ bind / singleton
               ┌─────────────────┼─────────────────┐
               │                 │                 │
       IUserRepository  ISolicitudRepo  IUserFactory
               │                 │                 │
    ┌──────────┴───┐    ┌────────┴────┐   ┌────────┴────┐
    │ AuthService  │    │ Solicitud   │   │UsuarioService│
    │UsuarioService│    │ Service     │   └─────────────┘
    └──────────────┘    └─────────────┘
               ↑                 ↑                 ↑
       (dependen de         (dependen de      (dependen de
        abstracciones)       abstracciones)    abstracciones)

    Implementaciones concretas (solo conocidas por AppServiceProvider):
    EloquentUserRepository, EloquentSolicitudRegistroRepository, UserFactory
```

**Resultado:** El código de negocio (`AuthService`, `UsuarioService`, `SolicitudRegistroService`) nunca cambia cuando se modifica el mecanismo de persistencia o creación. El flujo de dependencias va de lo concreto hacia lo abstracto, no al revés.

---

## Resumen ejecutivo

| Patrón / Principio | Archivos clave | Beneficio principal |
|---|---|---|
| **Factory** | `IUserFactory`, `UserFactory`, `UsuarioService`, `SolicitudRegistroService` | Centraliza la creación de usuarios por tipo; extensible sin tocar servicios |
| **Repository** | `IUserRepository`, `ISolicitudRegistroRepository`, `Eloquent*Repository` | Aísla BD del negocio; intercambiable sin tocar servicios |
| **Observer** | `SolicitudAprobada/Rechazada/UsuarioCreado` + Listeners | Desacopla efectos secundarios (correos) de la lógica de negocio |
| **SRP** | Cada clase en su capa | Una sola razón para cambiar por clase |
| **OCP** | `AppServiceProvider::boot()`, `UserFactory::$defaults` | Extensión sin modificación de código existente |
| **LSP** | Contratos `IUserRepository`, `IUserFactory` | Implementaciones intercambiables transparentemente (SQLite ↔ MySQL) |
| **ISP** | Tres interfaces separadas y cohesivas | Ninguna clase implementa métodos que no usa |
| **DIP** | Constructor injection en los tres servicios | El negocio depende de abstracciones, nunca de Eloquent directamente |

---

*BovWeight CR · IF7100 UCR · 2026*
