<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Voting System - Secure Digital Democracy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/functions.php'; ?>
    <link rel="stylesheet" href="<?= getAssetUrl('css/style.css') ?>">
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= getAssetUrl('index.php') ?>">
                <span class="logo-badge">OV</span>
                <span style="font-weight:700">Online Voting</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="<?= getAssetUrl('guest/view_results.php') ?>">Results</a></li>
                    <li class="nav-item nav-cta me-2"><a class="btn btn-primary btn-sm" href="<?= getAssetUrl('user/login.php') ?>">Vote Now</a></li>
                    <li class="nav-item nav-cta"><a class="btn btn-outline-brand btn-sm" href="<?= getAssetUrl('admin/login.php') ?>">Admin Panel</a></li>
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
                        <h1>🗳️ Secure Online Voting System</h1>
                        <p class="lead">Experience democracy in the digital age. Cast your vote securely, view real-time results, and participate in transparent elections from anywhere.</p>
                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <a class="btn btn-brand btn-lg px-4" href="<?= getAssetUrl('user/login.php') ?>">
                                <i class="fas fa-vote-yea me-2"></i>Vote Now
                            </a>
                            <a class="btn btn-outline-secondary btn-lg px-4" href="<?= getAssetUrl('admin/login.php') ?>">
                                <i class="fas fa-cog me-2"></i>Admin Panel
                            </a>
                            <a class="btn btn-outline-brand btn-lg px-4" href="<?= getAssetUrl('guest/view_results.php') ?>">
                                <i class="fas fa-chart-bar me-2"></i>View Results
                            </a>

                        </div>

                        <div class="mt-4 pt-3">
                            <div class="row g-3">
                                <div class="col-sm-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                            <i class="fas fa-shield-alt text-primary"></i>
                                        </div>
                                        <div>
                                            <small class="fw-bold d-block">Secure</small>
                                            <small class="text-muted">OTP Authentication</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                            <i class="fas fa-clock text-success"></i>
                                        </div>
                                        <div>
                                            <small class="fw-bold d-block">Real-time</small>
                                            <small class="text-muted">Live Results</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                            <i class="fas fa-users text-info"></i>
                                        </div>
                                        <div>
                                            <small class="fw-bold d-block">Accessible</small>
                                            <small class="text-muted">Easy to Use</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 text-center">
                        <div style="max-width:320px;height:240px;background:linear-gradient(135deg,#667eea22,#764ba222);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                            <div style="text-align:center;color:#667eea;">
                                <i class="fas fa-vote-yea" style="font-size:4rem;margin-bottom:1rem;"></i>
                                <div style="font-size:1.1rem;font-weight:600;">Secure Digital Voting</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="features py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Why Choose Our Voting System?</h2>
                    <p class="text-muted">Built with modern technology for secure and transparent elections</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="feature-card text-center p-4 h-100">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-rocket text-primary fa-2x"></i>
                            </div>
                            <h5 class="fw-bold">Quick Setup</h5>
                            <p class="text-muted">Create elections and add candidates in minutes. No complex configuration required.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card text-center p-4 h-100">
                            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-chart-line text-success fa-2x"></i>
                            </div>
                            <h5 class="fw-bold">Real-time Results</h5>
                            <p class="text-muted">Track votes as they come in with clear charts and instant data exports.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card text-center p-4 h-100">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-lock text-warning fa-2x"></i>
                            </div>
                            <h5 class="fw-bold">Secure & Reliable</h5>
                            <p class="text-muted">OTP authentication, encrypted data, and controlled admin access ensure integrity.</p>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-mobile-alt text-primary fa-2x me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Mobile Friendly</h6>
                                    <small class="text-muted">Vote from any device, anywhere</small>
                                </div>
                            </div>
                            <p class="text-muted small mb-0">Responsive design ensures perfect voting experience on desktop, tablet, and mobile devices.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope text-info fa-2x me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Email Integration</h6>
                                    <small class="text-muted">Secure OTP delivery system</small>
                                </div>
                            </div>
                            <p class="text-muted small mb-0">Automated email notifications with professionally designed templates for voter authentication.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>