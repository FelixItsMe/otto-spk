<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPK Efisiensi Mesin - PT. OTTO</title>
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

        #login-section {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #f0f2f5 100%);
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
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

        .view-section { display: none; }
        .view-section.active { display: block; animation: fadeIn 0.4s; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
                display: block !important;
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
    <div id="login-section">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="fa-solid fa-industry text-orange" style="font-size: 3rem; margin-bottom: 15px;"></i>
                <h4 class="fw-bold">SPK Efisiensi Mesin</h4>
                <p class="text-muted small">Log in menggunakan akun Supervisor</p>
            </div>
            <form onsubmit="handleLogin(event)">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="supervisor" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" value="admin123" required>
                </div>
                <button type="submit" class="btn btn-orange w-100 py-2 fw-bold">Login</button>
            </form>
        </div>
    </div>

    <div id="app-section" style="display: none;">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-industry text-orange fs-2"></i>
                <h5 class="fw-bold mt-2 mb-0">PT. OTTO</h5>
                <small class="text-muted">Decision Support System</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="nav-link active" onclick="switchView('dashboard', this)"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="#" class="nav-link" onclick="switchView('upload', this)"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Data</a></li>
                <li><a href="#" class="nav-link" onclick="switchView('analisis', this)"><i class="fa-solid fa-diagram-project"></i> Analisis K-Means</a></li>
                <li><a href="#" class="nav-link" onclick="switchView('laporan', this)"><i class="fa-solid fa-file-lines"></i> Laporan OEE</a></li>
                <li><a href="#" class="nav-link" onclick="switchView('users', this)"><i class="fa-solid fa-users-gear"></i> Manajemen User</a></li>
                <li class="mt-4"><a href="#" class="nav-link text-danger" onclick="handleLogout()"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-nav">
                <div>
                    <h4 class="mb-0 fw-bold" id="page-title">Dashboard</h4>
                    <small class="text-muted">Tinjauan efisiensi operasional pabrik</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3 text-end">
                        <span class="d-block fw-bold" style="font-size: 0.9rem;">Bpk. Supervisor</span>
                        <span class="badge bg-orange">Admin Produksi</span>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=Supervisor&background=FF7A00&color=fff" alt="User" class="rounded-circle" width="40">
                </div>
            </div>

            <div id="view-dashboard" class="view-section active">
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon icon-orange me-3"><i class="fa-solid fa-gauge-high"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Rata-rata OEE</h6>
                                <h3 class="fw-bold mb-0">68.4%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon icon-green me-3"><i class="fa-solid fa-circle-check"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Mesin Optimal</h6>
                                <h3 class="fw-bold mb-0">12</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Mesin Waspada</h6>
                                <h3 class="fw-bold mb-0">5</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon icon-red me-3"><i class="fa-solid fa-skull-crossbones"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Mesin Kritis</h6>
                                <h3 class="fw-bold mb-0">3</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <h5 class="fw-bold mb-4">Tren OEE Pabrik (YTD)</h5>
                                <canvas id="lineChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-4">Peringatan Sistem <i class="fa-solid fa-bell text-danger"></i></h5>

                                <div class="alert alert-danger border-0 d-flex align-items-start">
                                    <i class="fa-solid fa-circle-exclamation mt-1 me-3 fs-4"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Anomali Terdeteksi: STR104</h6>
                                        <p class="small mb-0">Jarak Euclidean melampaui threshold. Kualitas produksi jatuh ke angka 40%. Segera periksa log manual.</p>
                                    </div>
                                </div>
                                <div class="alert alert-warning border-0 d-flex align-items-start">
                                    <i class="fa-solid fa-wrench mt-1 me-3 fs-4"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Jadwal Maintenance: KAP101</h6>
                                        <p class="small mb-0">Mesin berada di zona kuning. Performa menurun perlahan. Direkomendasikan pembersihan minggu depan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="view-upload" class="view-section">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-5 text-center">
                        <i class="fa-solid fa-file-csv text-muted mb-3" style="font-size: 4rem;"></i>
                        <h4 class="fw-bold">Integrasi Data Log Mesin</h4>
                        <p class="text-muted mb-4">Unggah file laporan harian produksi (.csv atau .xlsx) untuk memulai analisis algoritma K-Means.</p>

                        <div class="mx-auto" style="max-width: 500px;">
                            <input class="form-control form-control-lg mb-3" type="file" id="fileData">
                            <button class="btn btn-orange btn-lg w-100" onclick="simulateUpload(event)">
                                <i class="fa-solid fa-microchip me-2"></i> Eksekusi Kalkulasi OEE & K-Means
                            </button>

                            <div id="uploadProgress" class="progress mt-4 d-none" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-orange" role="progressbar" style="width: 0%"></div>
                            </div>
                            <p id="uploadStatus" class="mt-2 small fw-bold text-success d-none">Data berhasil diproses! Silakan cek tab Analisis.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="view-analisis" class="view-section">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body d-flex justify-content-between align-items-center bg-light">
                                <div>
                                    <h5 class="fw-bold mb-1">Pengaturan Model Analisis</h5>
                                    <small class="text-muted">Sesuaikan parameter algoritma sesuai kebutuhan kebijakan pabrik.</small>
                                </div>
                                <div class="d-flex gap-3">
                                    <select class="form-select w-auto">
                                        <option>Bobot Normal (A=1, P=1, Q=1)</option>
                                        <option>Prioritas Kualitas (Q=3)</option>
                                    </select>
                                    <select class="form-select w-auto">
                                        <option>Sensitivitas Ketat (1.0x StdDev)</option>
                                        <option selected>Sensitivitas Normal (1.5x StdDev)</option>
                                        <option>Sensitivitas Longgar (2.0x StdDev)</option>
                                    </select>
                                    <button class="btn btn-dark">Terapkan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="fw-bold">Visualisasi Sebaran Klaster (K=3)</h5>
                            <span class="badge bg-success p-2">Silhouette Score: 0.78 (Sangat Baik)</span>
                        </div>
                        <canvas id="scatterChart" height="120"></canvas>

                        <div class="mt-4 text-center d-flex justify-content-center gap-4">
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success"><i class="fa-solid fa-circle"></i> Zona Optimal</span>
                            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 border border-warning"><i class="fa-solid fa-circle"></i> Zona Waspada</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 border border-danger"><i class="fa-solid fa-circle"></i> Zona Kritis</span>
                            <span class="badge bg-dark px-3 py-2"><i class="fa-regular fa-circle text-danger fw-bold"></i> Deteksi Anomali</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="view-laporan" class="view-section">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold">Tabel Laporan Keputusan SPK</h5>
                            <button class="btn btn-outline-secondary"><i class="fa-solid fa-print"></i> Cetak Laporan PDF</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-custom">
                                <thead>
                                    <tr>
                                        <th>Kode Mesin</th>
                                        <th>Availability</th>
                                        <th>Performance</th>
                                        <th>Quality</th>
                                        <th>Skor OEE</th>
                                        <th>Status Klaster</th>
                                        <th>Anomali Algoritma</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">PAK103</td>
                                        <td>97.0%</td>
                                        <td>72.0%</td>
                                        <td>99.0%</td>
                                        <td>69.1%</td>
                                        <td><span class="badge bg-success">Optimal</span></td>
                                        <td><span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Normal</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">TAB101</td>
                                        <td>44.0%</td>
                                        <td>78.0%</td>
                                        <td>99.0%</td>
                                        <td>33.9%</td>
                                        <td><span class="badge bg-warning text-dark">Waspada</span></td>
                                        <td><span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Normal</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">MIX113</td>
                                        <td>4.0%</td>
                                        <td>4.0%</td>
                                        <td>100%</td>
                                        <td>0.16%</td>
                                        <td><span class="badge bg-danger">Kritis</span></td>
                                        <td><span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Normal</span></td>
                                    </tr>
                                    <tr style="background-color: rgba(220,53,69,0.05);">
                                        <td class="fw-bold text-danger">STR104</td>
                                        <td>59.0%</td>
                                        <td>81.0%</td>
                                        <td class="text-danger fw-bold">40.0%</td>
                                        <td>19.1%</td>
                                        <td><span class="badge bg-danger">Kritis</span></td>
                                        <td><span class="badge-anomali"><i class="fa-solid fa-triangle-exclamation"></i> Ya (Outlier)</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">KAP101</td>
                                        <td>60.0%</td>
                                        <td>83.3%</td>
                                        <td>98.0%</td>
                                        <td>48.9%</td>
                                        <td><span class="badge bg-warning text-dark">Waspada</span></td>
                                        <td><span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Normal</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">INJ106</td>
                                        <td>76.0%</td>
                                        <td>72.0%</td>
                                        <td>100%</td>
                                        <td>54.7%</td>
                                        <td><span class="badge bg-success">Optimal</span></td>
                                        <td><span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Normal</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="view-users" class="view-section">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold">Data Pengguna Sistem</h5>
                            <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fa-solid fa-plus me-1"></i> Tambah User
                            </button>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                    <input type="text" id="searchUser" class="form-control border-start-0" placeholder="Cari nama atau email..." onkeyup="filterUsers()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select id="filterRole" class="form-select" onchange="filterUsers()">
                                    <option value="all">Semua Role</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Teknisi">Teknisi</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-custom" id="userTable">
                                <thead>
                                    <tr>
                                        <th>Profil</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>No. Handphone</th>
                                        <th>Role</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="user-row" data-role="Supervisor">
                                        <td><img src="https://ui-avatars.com/api/?name=Budi+Santoso&background=FF7A00&color=fff" class="rounded-circle" width="40" alt="Avatar"></td>
                                        <td class="fw-bold">Budi Santoso</td>
                                        <td>budi@otto.co.id</td>
                                        <td>081234567890</td>
                                        <td><span class="badge bg-primary">Supervisor</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr class="user-row" data-role="Admin">
                                        <td><img src="https://ui-avatars.com/api/?name=Siti+Aminah&background=198754&color=fff" class="rounded-circle" width="40" alt="Avatar"></td>
                                        <td class="fw-bold">Siti Aminah</td>
                                        <td>siti.admin@otto.co.id</td>
                                        <td>085678901234</td>
                                        <td><span class="badge bg-success">Admin</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr class="user-row" data-role="Teknisi">
                                        <td><img src="https://ui-avatars.com/api/?name=Agus+Pratama&background=6c757d&color=fff" class="rounded-circle" width="40" alt="Avatar"></td>
                                        <td class="fw-bold">Agus Pratama</td>
                                        <td>agus.tek@otto.co.id</td>
                                        <td>082134567890</td>
                                        <td><span class="badge bg-secondary">Teknisi</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addUserModalLabel">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control" placeholder="Masukkan nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" placeholder="nama@otto.co.id" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">No. Handphone</label>
                                <input type="text" class="form-control" placeholder="08xxx" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tanggal Lahir</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Role Pengguna</label>
                            <select class="form-select" required>
                                <option value="" disabled selected>Pilih Role...</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Admin">Admin</option>
                                <option value="Teknisi">Teknisi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Gambar Profile</label>
                            <input type="file" class="form-control" accept="image/*">
                            <div class="form-text">Maksimal ukuran file 2MB (.jpg, .png)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-orange" onclick="saveUser()">Simpan Pengguna</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const pageTitles = {
            dashboard: 'Dashboard',
            upload: 'Integrasi Data Produksi',
            analisis: 'Analisis K-Means & Anomali',
            laporan: 'Laporan Keputusan Manajemen',
            users: 'Manajemen Pengguna'
        };

        function handleLogin(e) {
            e.preventDefault();
            document.getElementById('login-section').style.display = 'none';
            document.getElementById('app-section').style.display = 'flex';

            initDashboardChart();
            initScatterChart();
        }

        function handleLogout() {
            document.getElementById('app-section').style.display = 'none';
            document.getElementById('login-section').style.display = 'flex';
            document.querySelectorAll('.nav-link')[0].click();
        }

        function switchView(viewId, element) {
            document.getElementById('page-title').innerText = pageTitles[viewId];

            document.querySelectorAll('.view-section').forEach((sec) => {
                sec.classList.remove('active');
            });

            document.getElementById('view-' + viewId).classList.add('active');

            document.querySelectorAll('.sidebar-menu a').forEach((el) => {
                el.classList.remove('active');
            });

            element.classList.add('active');
        }

        function simulateUpload(evt) {
            const btn = evt.currentTarget;
            const file = document.getElementById('fileData').value;

            if (!file) {
                alert('Pilih file CSV atau Excel terlebih dahulu.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Memproses Data...';

            const progressContainer = document.getElementById('uploadProgress');
            const progressBar = progressContainer.querySelector('.progress-bar');
            const statusText = document.getElementById('uploadStatus');

            progressContainer.classList.remove('d-none');
            statusText.classList.add('d-none');

            let width = 0;
            const interval = setInterval(() => {
                width += 20;
                progressBar.style.width = width + '%';

                if (width >= 100) {
                    clearInterval(interval);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-microchip me-2"></i> Eksekusi Kalkulasi OEE & K-Means';
                    statusText.classList.remove('d-none');
                    progressBar.style.width = '0%';
                    progressContainer.classList.add('d-none');

                    setTimeout(() => {
                        document.querySelectorAll('.nav-link')[2].click();
                    }, 1000);
                }
            }, 500);
        }

        function initDashboardChart() {
            const canvas = document.getElementById('lineChart');
            if (!canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');
            if (window.lineChartInstance) {
                window.lineChartInstance.destroy();
            }

            window.lineChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                    datasets: [{
                        label: 'Nilai OEE (%)',
                        data: [45, 52, 48, 60, 65, 68.4],
                        borderColor: '#FF7A00',
                        backgroundColor: 'rgba(255, 122, 0, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#FF7A00',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        }

        function initScatterChart() {
            const canvas = document.getElementById('scatterChart');
            if (!canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');

            if (window.scatterChartInstance) {
                window.scatterChartInstance.destroy();
            }

            const optimalData = [
                { x: 84.5, y: 99.0, machine: 'PAK103' },
                { x: 74.0, y: 100.0, machine: 'INJ106' }
            ];
            const waspadaData = [
                { x: 61.0, y: 99.0, machine: 'TAB101' },
                { x: 71.6, y: 98.0, machine: 'KAP101' }
            ];
            const kritisData = [
                { x: 4.0, y: 100.0, machine: 'MIX113' }
            ];
            const anomaliData = [{ x: 70.0, y: 40.0, machine: 'STR104' }];

            window.scatterChartInstance = new Chart(ctx, {
                type: 'scatter',
                data: {
                    datasets: [
                        {
                            label: 'Optimal',
                            data: optimalData,
                            backgroundColor: '#198754',
                            pointRadius: 8,
                            pointHoverRadius: 10
                        },
                        {
                            label: 'Waspada',
                            data: waspadaData,
                            backgroundColor: '#ffc107',
                            pointRadius: 8,
                            pointHoverRadius: 10
                        },
                        {
                            label: 'Kritis',
                            data: kritisData,
                            backgroundColor: '#dc3545',
                            pointRadius: 8,
                            pointHoverRadius: 10
                        },
                        {
                            label: 'Anomali Terdeteksi',
                            data: anomaliData,
                            backgroundColor: 'rgba(220, 53, 69, 0.5)',
                            borderColor: '#000',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointRadius: 12,
                            pointHoverRadius: 15
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    const raw = context.raw;
                                    if (context.datasetIndex === 3) {
                                        return `${raw.machine} (ANOMALI) - Quality: ${raw.y}%`;
                                    }
                                    return `${raw.machine} - Avg A&P: ${raw.x}%, Quality: ${raw.y}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Ketersediaan & Performa (Rata-rata %)' },
                            min: 0,
                            max: 100
                        },
                        y: {
                            title: { display: true, text: 'Mutu Kualitas (Quality %)' },
                            min: 0,
                            max: 110
                        }
                    }
                }
            });
        }

        function filterUsers() {
            const searchValue = document.getElementById('searchUser').value.toLowerCase();
            const roleValue = document.getElementById('filterRole').value;
            const rows = document.querySelectorAll('.user-row');

            rows.forEach((row) => {
                const name = row.cells[1].innerText.toLowerCase();
                const email = row.cells[2].innerText.toLowerCase();
                const role = row.getAttribute('data-role');

                const matchSearch = name.includes(searchValue) || email.includes(searchValue);
                const matchRole = roleValue === 'all' || role === roleValue;

                row.style.display = matchSearch && matchRole ? '' : 'none';
            });
        }

        function saveUser() {
            alert('Pengguna baru berhasil ditambahkan ke dalam sistem!');

            const modalElement = document.getElementById('addUserModal');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }

            document.getElementById('addUserForm').reset();
        }
    </script>
</body>
</html>
