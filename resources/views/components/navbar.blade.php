@push('styles')
<style>
/* Estilos del navbar */
.navbar {
    background-color: #e67e22 !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
    color: white !important;
    font-weight: bold;
}

.navbar-brand img {
    height: 60px;
    margin-right: 5px;
}

.navbar-brand:hover {
    color: #f8f9fa !important;
}

/* Estilos del menú de inicio */
.dropdown-item {
    color: #333;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background-color: #fff3e0;
    color: #e67e22;
}

/* Estilos del botón de usuario */
.btn-light {
    background-color: white;
    color: #e67e22;
    border: none;
    transition: all 0.3s ease;
}

.btn-light:hover {
    background-color: #f8f9fa;
    color: #d35400;
}

/* Estilos del menú desplegable */
.dropdown-menu {
    border: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Estilos de las notificaciones */
.notifications-btn {
    background: transparent;
    border: none;
    color: white;
    position: relative;
    padding: 8px;
    transition: all 0.3s ease;
}

.notifications-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.notifications-badge {
    position: absolute;
    top: 0;
    right: 0;
    background-color: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    min-width: 1.5rem;
    text-align: center;
}

.notifications-menu {
    width: 320px;
    padding: 0;
    max-height: 400px;
    overflow-y: auto;
}

.notifications-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notifications-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f1f1;
    transition: background-color 0.3s ease;
}

.notification-item:hover {
    background-color: #fff3e0;
}

.notification-item.unread {
    background-color: #fff3e0;
}

.notification-content {
    display: flex;
    align-items: flex-start;
}

.notification-icon {
    margin-right: 0.75rem;
    color: #e67e22;
    font-size: 1.25rem;
}

.notification-text {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.notification-time {
    font-size: 0.75rem;
    color: #6c757d;
}

.notifications-footer {
    padding: 0.75rem;
    text-align: center;
    border-top: 1px solid #dee2e6;
}

.notifications-footer a {
    color: #e67e22;
    text-decoration: none;
}

.notifications-footer a:hover {
    text-decoration: underline;
}

/* Scrollbar personalizado para el menú de notificaciones */
.notifications-menu::-webkit-scrollbar {
    width: 6px;
}

.notifications-menu::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.notifications-menu::-webkit-scrollbar-thumb {
    background: #e67e22;
    border-radius: 3px;
}

.notifications-menu::-webkit-scrollbar-thumb:hover {
    background: #d35400;
}
</style>
@endpush

<nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #e67e22;">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}" style="color: white; font-weight: bold;">
            <img src="{{ asset('images/logo.png') }}" alt="Nefrouros Logo" style="height: 60px; margin-right: 5px;">
            NefroTrack
        </a>
        
        <div class="d-flex align-items-center">
            @auth
            <!-- Notificaciones -->
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative p-0 mx-2" 
                        type="button" 
                        id="notificationsDropdown" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                        style="color: white; text-decoration: none; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bell fa-lg"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill" 
                          style="background-color: #e74c3c; font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                        3
                        <span class="visually-hidden">notificaciones no leídas</span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" 
                     style="width: 320px; max-height: 400px; overflow-y: auto; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    
                    <div class="d-flex justify-content-between align-items-center p-3" 
                         style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <h6 class="mb-0">Notificaciones</h6>
                        <a href="#" class="text-muted text-decoration-none" style="font-size: 0.875rem;">
                            Marcar todas como leídas
                        </a>
                    </div>
                    <!-- Lista de notificaciones -->
                    <div class="list-group list-group-flush">
                        <!-- Notificación 1 -->
                        <div class="list-group-item" style="background-color: #fff3e0;">
                            <div class="d-flex">
                                <div class="me-3" style="color: #e67e22;">
                                    <i class="fas fa-file-alt fa-lg"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600;">Nueva auditoría asignada</div>
                                    <p class="mb-1" style="font-size: 0.875rem;">Se te ha asignado la auditoría de MEDELLIN - ABRIL 2025</p>
                                    <small class="text-muted">Hace 5 minutos</small>
                                </div>
                            </div>
                        </div>
                        <!-- Notificación 2 -->
                        <div class="list-group-item" style="background-color: #fff3e0;">
                            <div class="d-flex">
                                <div class="me-3" style="color: #e67e22;">
                                    <i class="fas fa-exclamation-circle fa-lg"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600;">Hallazgo pendiente</div>
                                    <p class="mb-1" style="font-size: 0.875rem;">Tienes un hallazgo pendiente por revisar en la factura FVMD3624</p>
                                    <small class="text-muted">Hace 1 hora</small>
                                </div>
                            </div>
                        </div>
                        <!-- Notificación 3 -->
                        <div class="list-group-item">
                            <div class="d-flex">
                                <div class="me-3" style="color: #e67e22;">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600;">Auditoría completada</div>
                                    <p class="mb-1" style="font-size: 0.875rem;">La auditoría de BOGOTA - MARZO 2025 ha sido completada exitosamente</p>
                                    <small class="text-muted">Ayer</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="text-center p-2" style="border-top: 1px solid #dee2e6;">
                        <a href="#" class="text-decoration-none" style="color: #e67e22;">Ver todas las notificaciones</a>
                    </div>
                </div>
            </div>

            <!-- Usuario -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" 
                        style="background-color: white; color: #e67e22;">
                    <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="border: none; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);">
                    <li><a class="dropdown-item" href="{{ route('home') }}" 
                           style="color: #333; transition: all 0.3s;">
                        <i class="bi bi-house me-2"></i> Inicio
                    </a></li>
                    <li><a class="dropdown-item" href="#" 
                           style="color: #333; transition: all 0.3s;">
                        <i class="bi bi-person me-2"></i> Perfil
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" 
                                    style="color: #333; transition: all 0.3s; background: none; border: none; width: 100%; text-align: left;">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @else
            <a href="{{ route('login') }}" class="btn btn-outline-light" 
               style="color: white; border-color: white;">
                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
            </a>
            @endauth
        </div>
    </div>
</nav>

<script>
// Añadir hover effects con JavaScript ya que no tenemos CSS externo
document.addEventListener('DOMContentLoaded', function() {
    // Hover para dropdown items
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f39c12';
            this.style.color = 'white';
        });
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.color = '#333';
        });
    });

    // Hover para el botón de usuario
    const userBtn = document.querySelector('.btn-light');
    if (userBtn) {
        userBtn.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
            this.style.color = '#d35400';
        });
        userBtn.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'white';
            this.style.color = '#e67e22';
        });
    }

    // Hover para el botón de login
    const loginBtn = document.querySelector('.btn-outline-light');
    if (loginBtn) {
        loginBtn.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        });
        loginBtn.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    }

    // Hover para el botón de notificaciones
    const notifBtn = document.querySelector('#notificationsDropdown');
    if (notifBtn) {
        notifBtn.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            this.style.borderRadius = '50%';
        });
        notifBtn.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    }
});
</script>