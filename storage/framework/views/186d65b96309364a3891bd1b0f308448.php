<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pertashop App — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --ink: #10140f;
    --red: #C1272D;
    --red-deep: #8f1c21;
    --green: #256b3f;
    --green-deep: #184b2b;
    --cream: #F7F4EC;
    --cream-dim: #EDE9DD;
    --gold: #B8872F;
    --text-dark: #1c1f1a;
    --text-muted: #6f7368;
    --ring: rgba(37,107,63,0.28);
    --danger: #C1272D;
  }

  *{ box-sizing:border-box; }
  html,body{ height:100%; margin:0; }

  body{
    font-family:'Inter', sans-serif;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    overflow:hidden;
    background:#0b0d0a;
  }

  .bg{
    position:absolute; inset:0;
    /* Kita gunakan PERTASHOP PAGE.png yang sudah ditambahkan user */
    background-image: url('<?php echo e(asset('images/PERTASHOP PAGE.png')); ?>?v=<?php echo e(time()); ?>');
    background-size:cover;
    background-position:center;
    filter: saturate(0.85) brightness(0.85);
    transform: scale(1.03);
  }

  .bg::after{
    content:"";
    position:absolute; inset:0;
    background:
      linear-gradient(180deg, rgba(10,12,9,0.55) 0%, rgba(10,12,9,0.42) 30%, rgba(9,11,8,0.72) 78%, rgba(6,8,6,0.92) 100%);
  }

  .eyebrow{
    position:absolute;
    top:38px;
    left:0; right:0;
    text-align:center;
    z-index:2;
    color: rgba(247,244,236,0.82);
    letter-spacing:0.22em;
    font-size:11.5px;
    font-weight:600;
    text-transform:uppercase;
  }
  .eyebrow span.gold{ color: var(--gold); }
  .eyebrow small{
    display:block;
    margin-top:6px;
    font-size:10px;
    letter-spacing:0.16em;
    color: rgba(247,244,236,0.5);
    font-weight:500;
  }

  .stripe{
    display:inline-flex;
    gap:3px;
    vertical-align:middle;
    margin:0 8px;
  }
  .stripe i{ width:10px; height:3px; border-radius:2px; display:inline-block; }
  .stripe i:nth-child(1){ background:var(--red); }
  .stripe i:nth-child(2){ background:var(--green); }

  .card{
    position:relative;
    z-index:2;
    width:380px;
    background: rgba(247,244,236,0.97);
    border-radius:22px;
    box-shadow:
      0 30px 60px -20px rgba(0,0,0,0.55),
      0 2px 0 rgba(255,255,255,0.4) inset;
    overflow:hidden;
    animation: riseIn 0.75s cubic-bezier(.19,1,.22,1) both;
  }

  .accent-bar{ display:flex; height:5px; }
  .accent-bar span{ flex:1; }
  .accent-bar span:nth-child(1){ background: linear-gradient(90deg,var(--red-deep),var(--red)); }
  .accent-bar span:nth-child(2){ background: linear-gradient(90deg,var(--green),var(--green-deep)); }

  .card-inner{ padding:36px 34px 32px; }

  .badge-wrap{ display:flex; flex-direction:column; align-items:center; margin-bottom:22px; }

  .badge{
    width:74px; height:74px;
    border-radius:50%;
    background: radial-gradient(circle at 32% 28%, #ffffff, var(--cream-dim) 60%);
    border:2px solid var(--gold);
    display:flex; align-items:center; justify-content:center;
    margin-bottom:14px;
    box-shadow: 0 6px 16px rgba(184,135,47,0.25);
  }
  .badge svg{ width:38px; height:38px; }

  h1{
    font-family:'Fraunces', serif;
    font-size:24px;
    font-weight:600;
    color: var(--text-dark);
    margin:0 0 4px;
    text-align:center;
  }
  .subtitle{ font-size:12px; color: var(--text-muted); text-align:center; margin:0; }

  .divider{
    height:1px;
    background: linear-gradient(90deg, transparent, rgba(0,0,0,0.09), transparent);
    margin:24px 0 22px;
  }

  label{
    display:block; font-size:12px; font-weight:600;
    color: var(--text-dark); margin-bottom:7px; letter-spacing:0.02em;
  }

  .field{ margin-bottom:6px; }
  .field + .field{ margin-top:14px; }

  .input-wrap{ position:relative; display:flex; align-items:center; }

  .input-wrap svg{
    position:absolute; left:14px; width:16px; height:16px;
    color: var(--text-muted); pointer-events:none; transition: color 0.2s ease;
  }

  input[type="email"], input[type="password"], input[type="text"]{
    width:100%;
    padding:12px 14px 12px 40px;
    border-radius:11px;
    border:1.5px solid #E3DFD3;
    background:#FCFBF7;
    font-family:'Inter', sans-serif;
    font-size:13.5px;
    color: var(--text-dark);
    outline:none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  }
  input::placeholder{ color:#B3AFA2; }
  input:focus{ border-color: var(--green); background:#fff; box-shadow: 0 0 0 4px var(--ring); }
  input.is-invalid{ border-color: var(--danger); }

  .error-text{
    font-size:11.5px;
    color: var(--danger);
    margin:6px 2px 0;
  }

  .row-between{
    display:flex; align-items:center; justify-content:space-between;
    margin:20px 0 22px;
  }

  .remember{
    display:flex; align-items:center; gap:8px;
    font-size:12.5px; color: var(--text-muted); cursor:pointer; user-select:none;
  }
  .remember input{
    -webkit-appearance:none; appearance:none;
    width:16px; height:16px; border-radius:5px;
    border:1.5px solid #CFC9B8; background:#fff; cursor:pointer;
    position:relative; transition: all 0.15s ease;
  }
  .remember input:checked{ background: var(--green); border-color: var(--green); }
  .remember input:checked::after{
    content:""; position:absolute; left:4px; top:1px;
    width:4px; height:8px; border:solid white; border-width:0 2px 2px 0;
    transform:rotate(45deg);
  }

  .forgot{ font-size:12.5px; color: var(--green-deep); text-decoration:none; font-weight:600; }
  .forgot:hover{ text-decoration:underline; }

  button.login{
    width:100%; padding:13px; border:none; border-radius:11px;
    background: linear-gradient(180deg, var(--green) 0%, var(--green-deep) 100%);
    color:#fff; font-family:'Inter', sans-serif; font-size:14px; font-weight:600;
    letter-spacing:0.02em; cursor:pointer;
    box-shadow: 0 10px 20px -8px rgba(24,75,43,0.55);
    transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    position:relative;
  }
  button.login::before{
    content:""; position:absolute; left:0; top:0; bottom:0; width:4px;
    background: var(--red); border-radius:11px 0 0 11px;
  }
  button.login:hover{ transform: translateY(-1px); box-shadow: 0 14px 24px -8px rgba(24,75,43,0.65); filter: brightness(1.04); }
  button.login:active{ transform: translateY(0); }

  .alert-success{
    background:#eef7f0; border:1px solid #bfe0c9; color:var(--green-deep);
    font-size:12.5px; padding:10px 12px; border-radius:10px; margin-bottom:16px;
  }

  .foot{
    position:absolute; bottom:22px; left:0; right:0; text-align:center; z-index:2;
    font-size:10.5px; letter-spacing:0.05em; color: rgba(247,244,236,0.45);
  }

  @keyframes riseIn{
    from{ opacity:0; transform: translateY(18px) scale(0.98); }
    to{ opacity:1; transform: translateY(0) scale(1); }
  }

  @media (prefers-reduced-motion: reduce){ .card{ animation:none; } }

  @media (max-width:420px){
    .card{ width:90vw; }
    .eyebrow{ font-size:10px; padding:0 16px; }
  }
</style>
</head>
<body>

  <div class="bg"></div>

  <!-- Note: Eyebrow di-hide jika menggunakan foto bg yg sudah ada tulisan -->
  <!--
  <div class="eyebrow">
    PT SERAYU AGUNG MANDIRI
    <span class="stripe"><i></i><i></i></span>
    <span class="gold">AGEN LPG 3&nbsp;KG PERTAMINA</span>
    <small>Semangat Terbarukan, Melayani Dengan Hati</small>
  </div>
  -->

  <div class="card">
    <div class="accent-bar"><span></span><span></span></div>
    <div class="card-inner">

      <div class="badge-wrap">
        <div class="badge" style="background: transparent; border: none; box-shadow: none;">
          <img src="<?php echo e(asset('images/logo-pertashop.png')); ?>" alt="Pertashop Logo" style="width: 80px; height: auto; object-fit: contain;">
        </div>
        <h1>SIPERI</h1>
        <p class="subtitle">Sistem Informasi PERtashop Indonesia</p>
      </div>

      <div class="divider"></div>

      
      <?php if(session('status')): ?>
        <div class="alert-success"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <form method="POST" action="<?php echo e(route('authenticate')); ?>">
        <?php echo csrf_field(); ?>

        <div class="field">
          <label for="email">Email / Nama Akun</label>
          <div class="input-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/>
              <path d="M3 6l9 7 9-7"/>
            </svg>
            <input
              id="email"
              type="text"
              name="email"
              value="<?php echo e(old('email')); ?>"
              placeholder="Email atau Nama (misal: Andre / Wawan / super-admin)"
              class="<?php echo e($errors->has('email') ? 'is-invalid' : ''); ?>"
              required
              autofocus
            >
          </div>
          <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="error-text"><?php echo e($message); ?></div>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <rect x="5" y="10" width="14" height="10" rx="2"/>
              <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
            </svg>
            <input
              id="password"
              type="password"
              name="password"
              placeholder="••••••••"
              class="<?php echo e($errors->has('password') ? 'is-invalid' : ''); ?>"
              required
            >
          </div>
          <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="error-text"><?php echo e($message); ?></div>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="row-between">
          <label class="remember">
            <input type="checkbox" name="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
            Ingat saya
          </label>

          <?php if(Route::has('password.request')): ?>
            <a class="forgot" href="<?php echo e(route('password.request')); ?>">Lupa password?</a>
          <?php endif; ?>
        </div>

        <button class="login" type="submit">Masuk ke Akun</button>
      </form>

    </div>
  </div>

  <div class="foot"><span style="font-size: 1.3em; vertical-align: -0.1em; margin-right: 2px;">&copy;</span> PertashopApp 2026</div>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/auth/login.blade.php ENDPATH**/ ?>