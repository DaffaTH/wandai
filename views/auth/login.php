<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Wandai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="public/assets/css/style.css">
  <link rel="stylesheet" href="public/assets/css/logo-resize.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 25%, #ffa726 50%, #ff7043 75%, #d84315 100%);
      background-size: 400% 400%;
      animation: gradientShift 15s ease infinite;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      position: relative;
      overflow: hidden;
    }

    /* Animated background elements */
    body::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: float 20s linear infinite;
    }

    body::after {
      content: '';
      position: absolute;
      top: 20%;
      right: 10%;
      width: 300px;
      height: 300px;
      background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
      border-radius: 50%;
      animation: pulse 8s ease-in-out infinite;
    }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    @keyframes float {
      0% { transform: translate(-50px, -50px) rotate(0deg); }
      100% { transform: translate(-50px, -50px) rotate(360deg); }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.3; }
      50% { transform: scale(1.1); opacity: 0.1; }
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .login-container {
      position: relative;
      z-index: 10;
      animation: slideUp 0.8s ease-out;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 24px;
      padding: 3rem 2.5rem;
      box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.1),
        0 0 0 1px rgba(255, 255, 255, 0.2) inset;
      width: 100%;
      max-width: 440px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #ff6b35, #ff8c42, #ffa726, #ff7043);
      background-size: 200% 100%;
      animation: shimmer 3s linear infinite;
    }

    @keyframes shimmer {
      0% { background-position: -200% 0; }
      100% { background-position: 200% 0; }
    }

    .login-card h4 {
      font-weight: 700;
      font-size: 1.5rem;
      color: #2d3748;
      margin-bottom: 0.5rem;
      background: linear-gradient(135deg, #ff6b35, #ff7043);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: fadeIn 1s ease-out 0.5s both;
    }

    .login-card .subtitle {
      color: #64748b;
      font-size: 0.95rem;
      margin-bottom: 2rem;
      font-weight: 400;
      animation: fadeIn 1s ease-out 0.7s both;
    }

    .form-group {
      position: relative;
      margin-bottom: 1.5rem;
      animation: fadeIn 1s ease-out 0.9s both;
    }

    .form-control {
      background: rgba(255, 255, 255, 0.8);
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 16px;
      padding: 1rem 1.25rem;
      font-size: 0.95rem;
      font-weight: 400;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      backdrop-filter: blur(10px);
    }

    .form-control:focus {
      outline: none;
      border-color: #ff7043;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 
        0 0 0 3px rgba(255, 112, 67, 0.1),
        0 8px 20px rgba(255, 112, 67, 0.15);
      transform: translateY(-2px);
    }

    .form-control::placeholder {
      color: #94a3b8;
      font-weight: 400;
    }

    .btn-primary {
      background: linear-gradient(135deg, #ff6b35, #ff7043);
      border: none;
      border-radius: 16px;
      padding: 1rem 2rem;
      font-weight: 600;
      font-size: 1rem;
      letter-spacing: 0.5px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 8px 20px rgba(255, 112, 67, 0.3);
      position: relative;
      overflow: hidden;
      animation: fadeIn 1s ease-out 1.1s both;
    }

    .btn-primary::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: left 0.5s ease;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #e55a2b, #e5633a);
      transform: translateY(-2px);
      box-shadow: 0 12px 25px rgba(255, 112, 67, 0.4);
    }

    .btn-primary:hover::before {
      left: 100%;
    }

    .btn-primary:active {
      transform: translateY(0);
      box-shadow: 0 6px 15px rgba(255, 112, 67, 0.3);
    }

    .alert-danger {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #dc2626;
      border-radius: 12px;
      padding: 0.875rem 1.25rem;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      backdrop-filter: blur(10px);
      animation: fadeIn 0.5s ease-out;
    }

    .copyright {
      font-size: 0.8rem;
      color: #94a3b8;
      margin-top: 2rem;
      font-weight: 400;
      animation: fadeIn 1s ease-out 1.3s both;
    }

    /* Responsive design */
    @media (max-width: 480px) {
      .login-card {
        margin: 1rem;
        padding: 2rem 1.5rem;
      }

      .login-card h4 {
        font-size: 1.25rem;
      }

      .form-control {
        padding: 0.875rem 1rem;
      }
    }

    /* Loading animation for button */
    .btn-loading {
      position: relative;
      color: transparent;
    }

    .btn-loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid transparent;
      border-top: 2px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* ===============================================
       SPACING KONSISTEN - FIXED!
       Badge → Logo = Logo → Title = 1rem
       =============================================== */

    /* Sensus Badge */
    .sensus-badge {
      background: linear-gradient(135deg, #ff8c42, #ffa726);
      color: white;
      padding: 0.6rem 1.25rem;
      border-radius: 25px;
      font-size: 0.8rem;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(255, 140, 66, 0.4);
      animation: pulse 2s ease-in-out infinite;
      display: inline-block;
      margin-bottom: 1rem !important;  /* Jarak badge ke logo: 1rem */
      letter-spacing: 0.5px;
    }

    /* Logo Container */
    .logo-container {
      margin-bottom: 1rem !important;  /* Jarak logo ke title: 1rem (SAMA!) */
      animation: fadeInScale 0.8s ease-out 0.3s both;
    }

    /* Logo Image - RESIZE + NO MARGIN */
    .login-card img,
    .logo-container img,
    img[alt*="LogoWandai"] {
      width: 220px !important;
      height: 220px !important;
      object-fit: contain !important;
      margin-bottom: 0 !important;  /* HAPUS margin - spacing dari container */
      filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.15)) !important;
      transition: transform 0.3s ease !important;
    }

    /* Hover effect */
    .login-card img:hover {
      transform: scale(1.05) !important;
      filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.2)) !important;
    }

    /* Title - perbesar sedikit */
    .login-card h4 {
      font-weight: 700 !important;
      font-size: 1.75rem !important;
      margin-top: 0 !important;
      margin-bottom: 0.5rem !important;  /* Jarak title ke subtitle */
    }

    /* Subtitle */
    .login-card .subtitle {
      font-size: 1rem !important;
      margin-bottom: 2.5rem;
    }

    /* Animation logo */
    @keyframes fadeInScale {
      from {
        opacity: 0;
        transform: scale(0.8);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* Responsive - Mobile */
    @media (max-width: 768px) {
      .login-card img {
        width: 180px !important;
        height: 180px !important;
      }
      .login-card h4 {
        font-size: 1.5rem !important;
      }
    }

    @media (max-width: 576px) {
      .login-card img {
        width: 150px !important;
        height: 150px !important;
      }
      .login-card h4 {
        font-size: 1.35rem !important;
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-card">
      <div class="sensus-badge">Sensus Ekonomi 2026</div>
      
      <div class="logo-container">
        <img src="public/assets/img/wandaicmprs.png" alt="LogoWandai">
      </div>
      
      <h4>BPS Kabupaten Paniai</h4>
      <p class="subtitle">Silakan login untuk masuk ke sistem Wandai</p>
      
      <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>
      
      <form method="POST" action="index.php?controller=auth&action=prosesLogin" id="loginForm">
        <div class="form-group">
          <!-- UPDATED: Placeholder dan name diubah ke email -->
          <input type="text" class="form-control" name="username" placeholder="Email" required>
        </div>
        
        <div class="form-group">
          <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        
        <div class="d-grid">
          <button type="submit" class="btn btn-primary" id="loginBtn">
            <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
          </button>
        </div>
      </form>
      
      <div class="copyright">
        © 2025 BPS Kabupaten Paniai<br>
        <small><em>Smart Monitoring for Better Performance</em></small>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Add loading animation on form submit
    document.getElementById('loginForm').addEventListener('submit', function() {
      const btn = document.getElementById('loginBtn');
      btn.classList.add('btn-loading');
      btn.disabled = true;
    });

    // Add focus effects
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
      });
    });

    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
        const form = e.target.closest('form');
        const inputs = form.querySelectorAll('input[required]');
        const currentIndex = Array.from(inputs).indexOf(e.target);
        
        if (currentIndex < inputs.length - 1) {
          inputs[currentIndex + 1].focus();
        } else {
          form.querySelector('button[type="submit"]').click();
        }
      }
    });
  </script>
</body>

</html>