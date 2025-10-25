<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> Apartments | Welcome</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
  --accent: #0dcaf0;
  --light: #f9f9f9;
  --dark: #0a0a0d;
  --border: rgba(255,255,255,0.08);
  --transition: 500ms cubic-bezier(.2,.9,.3,1);
}

/* Reset */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Manrope', sans-serif;
  color: var(--light);
  min-height: 100vh;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: #05080f;
}

/* Animated aurora gradient */
body::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, #0a0a0d, #13243a, #0a0a0d, #092b37);
  background-size: 300% 300%;
  animation: aurora 18s ease-in-out infinite;
  opacity: 0.6;
  z-index: 0;
}
@keyframes aurora {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Particle canvas */
#particles {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  opacity: 0.45;
}

/* Card container */
.card {
  position: relative;
  z-index: 2;
  max-width: 1000px;
  width: 92%;
  padding: 70px 80px;
  border-radius: 20px;
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--border);
  backdrop-filter: blur(24px) saturate(150%);
  box-shadow: 0 40px 120px rgba(0,0,0,0.6);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 60px;
  transition: transform var(--transition);
}
.card:hover { transform: scale(1.01); }

/* Left side */
.left { flex: 1; }
.brand-title {
  font-size: 48px;
  font-weight: 800;
  letter-spacing: -1px;
}
.brand-title span { color: var(--accent); }
.tagline {
  font-size: 18px;
  opacity: 0.75;
  margin-top: 14px;
  line-height: 1.6;
  max-width: 420px;
}

/* Buttons */
.buttons {
  margin-top: 40px;
  display: flex;
  gap: 18px;
}
.btn {
  text-decoration: none;
  font-weight: 600;
  font-size: 15px;
  padding: 14px 28px;
  border-radius: 10px;
  border: 1px solid var(--border);
  color: var(--light);
  background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
  transition: all var(--transition);
}
.btn:hover {
  border-color: var(--accent);
  background: rgba(13,202,240,0.15);
  transform: translateY(-4px);
}
.btn.primary {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
  box-shadow: 0 10px 30px rgba(13,202,240,0.25);
}
.btn.primary:hover {
  box-shadow: 0 18px 40px rgba(13,202,240,0.35);
}

/* Right image */
.right {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
}
.hero-img {
  width: 360px;
  height: 360px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, #1b2538, #000);
  box-shadow: 0 0 60px rgba(13,202,240,0.15);
  overflow: hidden;
  position: relative;
}
.hero-img::before {
  content: "";
  position: absolute;
  inset: 0;
  background: url('data:image/jpeg;base64,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') center/cover no-repeat;
  opacity: 0.8;
  filter: contrast(1.1) brightness(0.9);
}
.hero-img::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 80%, transparent 50%, rgba(0,0,0,0.9) 100%);
}

/* Footer */
.footer {
  position: absolute;
  bottom: 24px;
  left: 0; right: 0;
  text-align: center;
  font-size: 13px;
  color: rgba(255,255,255,0.45);
  letter-spacing: 0.5px;
}

/* Responsive */
@media (max-width: 900px) {
  .card { flex-direction: column; padding: 50px 30px; text-align: center; }
  .hero-img { width: 260px; height: 260px; margin-top: 40px; }
  .brand-title { font-size: 36px; }
  .tagline { margin: 0 auto; }
  .buttons { justify-content: center; }
}
</style>
</head>

<body>
<canvas id="particles"></canvas>

<div class="card">
  <div class="left">
    <h1 class="brand-title">Manage <span>Apartments</span></h1>
    <p class="tagline">Where modern technology meets real estate management. Experience refined control, smart automation, and executive design.</p>
    <div class="buttons">
      <a href="{{ route('admin.login') }}" class="btn primary">Admin Login</a>
      <a href="{{ route('secretary.login') }}" class="btn">Secretary Login</a>
      <a href="{{ route('tenant.login') }}" class="btn">Tenant Login</a>
    </div>
  </div>

  <div class="right">
    <div class="hero-img"></div>
  </div>
</div>

<div class="footer">© {{ date('Y') }}  Apartments — Executive Management Suite</div>

<script>
/* Particle animation */
const canvas = document.getElementById('particles');
const ctx = canvas.getContext('2d');
let particles = [];

function resizeCanvas() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

for (let i = 0; i < 60; i++) {
  particles.push({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: Math.random() * 2 + 0.5,
    dx: (Math.random() - 0.5) * 0.3,
    dy: (Math.random() - 0.5) * 0.3,
    opacity: Math.random() * 0.6 + 0.2
  });
}

function animate() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  for (let p of particles) {
    ctx.beginPath();
    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
    ctx.fillStyle = `rgba(13,202,240,${p.opacity})`;
    ctx.fill();
    p.x += p.dx;
    p.y += p.dy;

    if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
    if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
  }
  requestAnimationFrame(animate);
}
animate();
</script>

</body>
</html>
