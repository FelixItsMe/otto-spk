<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'SPK Efisiensi Mesin' }} - PT. OTTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-orange: #FF7A00;
            --primary-hover: #E06A00;
            --bg-light: #F8F9FA;
            --text-dark: #2B2B2B;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .text-orange { color: var(--primary-orange) !important; }
        .bg-orange { background-color: var(--primary-orange) !important; }
        .btn-orange {
            background-color: var(--primary-orange);
            color: white;
            border: none;
        }
        .btn-orange:hover {
            background-color: var(--primary-hover);
            color: white;
        }

        #app-section {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid #eaeaea;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 24px;
            text-align: center;
            border-bottom: 1px solid #eaeaea;
        }
        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
            margin: 0;
        }
        .sidebar-menu li {
            padding: 5px 20px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #6c757d;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .sidebar-menu a i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 122, 0, 0.1);
            color: var(--primary-orange);
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .icon-orange { background: rgba(255, 122, 0, 0.1); color: var(--primary-orange); }
        .icon-green { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .icon-red { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

        .table-custom th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #eaeaea;
        }
        .table-custom td {
            vertical-align: middle;
        }
        .badge-anomali {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            #app-section {
                display: block;
            }

            .top-nav {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
<div id="app-section">
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-industry text-orange fs-2"></i>
            <h5 class="fw-bold mt-2 mb-0">PT. OTTO</h5>
            <small class="text-muted">Decision Support System</small>
        </div>
        <ul class="sidebar-menu">
            <li><a href="{{ route('dashboard.index') }}" class="{{ request()->routeIs('dashboard.*') ? 'active' : '' }}"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="{{ route('upload.index') }}" class="{{ request()->routeIs('upload.*') ? 'active' : '' }}"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Data</a></li>
            <li><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="fa-solid fa-users-gear"></i> Manajemen User</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="top-nav">
            <div>
                <h4 class="mb-0 fw-bold">{{ $pageTitle ?? 'Dashboard' }}</h4>
                <small class="text-muted">{{ $pageSubtitle ?? 'Tinjauan efisiensi operasional pabrik' }}</small>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="me-3 text-end">
                            <span class="d-block fw-bold" style="font-size: 0.9rem;">Bpk. Supervisor</span>
                            <span class="badge bg-orange">Admin Produksi</span>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=Supervisor&background=FF7A00&color=fff" alt="User" class="rounded-circle" width="40">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="fa-solid fa-sign-out-alt me-2"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
