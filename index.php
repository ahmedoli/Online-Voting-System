<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Online_Voting_System/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="/Online_Voting_System/index.php">
                <span class="logo-badge">OV</span>
                <span style="font-weight:700">Online Voting</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="/Online_Voting_System/guest/view_results.php">Results</a></li>
                    <li class="nav-item nav-cta"><a class="btn btn-outline-brand btn-sm" href="/Online_Voting_System/admin/login.php">Admin Panel</a></li>
                </ul>
            </div>
        </div>
        <div class="site-header-divider"></div>
    </nav>

    <main class="container-fluid py-5">
        <section class="hero">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h1>Secure, simple online voting</h1>
                        <p class="lead">Create and manage elections, track results, and let voters cast securely — all from one intuitive dashboard.</p>
                        <div class="d-flex gap-16">
                            <a class="btn btn-brand" href="/Online_Voting_System/admin/login.php">Admin Panel</a>
                            <a class="btn btn-outline-brand" href="/Online_Voting_System/guest/view_results.php">View Results</a>
                        </div>
                    </div>
                    <div class="col-md-5 text-center">
                        <img src="/Online_Voting_System/js/placeholder-illustration.svg" alt="voting illustration" style="max-width:320px;opacity:0.95;border-radius:8px">
                    </div>
                </div>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <h5>Quick Setup</h5>
                            <p class="muted">Create elections and add candidates in minutes.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <h5>Real-time Results</h5>
                            <p class="muted">Track votes as they come in with clear charts and exports.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <h5>Secure</h5>
                            <p class="muted">Built with simple security practices — password hashing and controlled admin access.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>

</html>