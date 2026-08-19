<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Bootstrap 5 --}}

	<link
		href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
		rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
		crossorigin="anonymous"
	>
	<style>
		:root {
			--brand-orange: #f57c00;
			--brand-orange-dark: #dd6f00;
			--bg-orange-soft: #fff2e4;
		}

		body {
			min-height: 100vh;
			background:
				radial-gradient(circle at top right, #ffd3a1 0%, transparent 40%),
				radial-gradient(circle at bottom left, #ffe7cc 0%, transparent 45%),
				linear-gradient(160deg, #fffaf4 0%, #fff3e6 100%);
			font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
		}

		.login-wrapper {
			min-height: 100vh;
		}

		.login-card {
			border: none;
			border-radius: 1.25rem;
			box-shadow: 0 1rem 2rem rgba(245, 124, 0, 0.15);
			overflow: hidden;
			max-width: 460px;
			width: 100%;
		}

		.card-head {
			background: linear-gradient(135deg, var(--brand-orange) 0%, #ff9a2e 100%);
			color: #fff;
			padding: 2rem;
		}

		.brand-badge {
			display: inline-block;
			padding: 0.35rem 0.8rem;
			border-radius: 999px;
			font-size: 0.8rem;
			font-weight: 700;
			letter-spacing: 0.04em;
			background-color: rgba(255, 255, 255, 0.22);
			margin-bottom: 0.9rem;
		}

		.form-section {
			padding: 2rem;
			background-color: #fff;
		}

		.form-label {
			font-weight: 600;
			color: #6b3800;
		}

		.form-control {
			border-radius: 0.75rem;
			border-color: #ffd0a1;
			padding: 0.7rem 0.9rem;
		}

		.form-control:focus {
			box-shadow: 0 0 0 0.2rem rgba(245, 124, 0, 0.2);
			border-color: var(--brand-orange);
		}

		.btn-brand {
			--bs-btn-bg: var(--brand-orange);
			--bs-btn-border-color: var(--brand-orange);
			--bs-btn-hover-bg: var(--brand-orange-dark);
			--bs-btn-hover-border-color: var(--brand-orange-dark);
			--bs-btn-active-bg: var(--brand-orange-dark);
			--bs-btn-active-border-color: var(--brand-orange-dark);
			--bs-btn-disabled-bg: #ffb061;
			--bs-btn-disabled-border-color: #ffb061;
			border-radius: 0.75rem;
			font-weight: 600;
			padding: 0.7rem 1rem;
		}

		.btn-outline-brand {
			--bs-btn-color: var(--brand-orange);
			--bs-btn-border-color: #ffc28a;
			--bs-btn-hover-color: #fff;
			--bs-btn-hover-bg: var(--brand-orange);
			--bs-btn-hover-border-color: var(--brand-orange);
			--bs-btn-active-bg: var(--brand-orange-dark);
			--bs-btn-active-border-color: var(--brand-orange-dark);
		}

		.input-group .btn {
			border-top-right-radius: 0.75rem;
			border-bottom-right-radius: 0.75rem;
		}
	</style>
</head>
<body>
<main class="container d-flex align-items-center justify-content-center login-wrapper py-5">
	<section class="login-card card">
		<div class="card-head">
			<span class="brand-badge">WELCOME BACK</span>
			<h1 class="h3 mb-2 fw-bold">Login menggunakan akun OTTO anda</h1>
		</div>

		<div class="form-section">
			<form id="loginForm" action="{{ route('login.authenticate') }}" method="POST" novalidate>
				@csrf
				<div class="mb-3">
					<label for="email" class="form-label">Email</label>
					<input
						type="email"
						class="form-control"
						id="email"
						name="email"
						placeholder="you@example.com"
						required
					>
                    @error('email')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
				</div>

				<div class="mb-3">
					<label for="password" class="form-label">Password</label>
					<div class="input-group">
						<input
							type="password"
							class="form-control"
							id="password"
							name="password"
							placeholder="Masukkan password anda"
							required
						>
						<button
							class="btn btn-outline-brand"
							type="button"
							id="togglePassword"
							aria-label="Show password"
							aria-pressed="false"
						>
							<i class="fa-regular fa-eye"></i>
						</button>
					</div>
                    @error('password')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
				</div>

				<div class="d-grid mt-4">
					<button type="submit" class="btn btn-brand text-white" id="loginButton">
						<span class="btn-text">Login</span>
						<span
							class="spinner-border spinner-border-sm ms-2 d-none"
							role="status"
							aria-hidden="true"
							id="loginSpinner"
						></span>
					</button>
				</div>
			</form>
		</div>
	</section>
</main>

<script>
	(function () {
		const togglePasswordBtn = document.getElementById('togglePassword');
		const passwordInput = document.getElementById('password');
		const loginForm = document.getElementById('loginForm');
		const loginButton = document.getElementById('loginButton');
		const loginSpinner = document.getElementById('loginSpinner');
		const loginButtonText = loginButton.querySelector('.btn-text');

		togglePasswordBtn.addEventListener('click', function () {
			const isHidden = passwordInput.type === 'password';
			passwordInput.type = isHidden ? 'text' : 'password';
			togglePasswordBtn.innerHTML = isHidden ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
			togglePasswordBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
			togglePasswordBtn.setAttribute('aria-pressed', String(isHidden));
		});

		loginForm.addEventListener('submit', function () {
			loginButton.disabled = true;
			loginSpinner.classList.remove('d-none');
			loginButtonText.textContent = 'Logging in...';
		});
	})();
</script>
</body>
</html>
